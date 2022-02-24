<?php

namespace Concrete\Package\C5jRatings\Controller\SinglePage\Dashboard;

use C5jRatings\Export\RatingExporter;
use C5jRatings\Search\RatingList;
use Carbon\Carbon;
use Concrete\Core\Csv\WriterFactory;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Ratings extends DashboardPageController
{
    protected $ratingsList;

    public function on_start()
    {
        parent::on_start();
        $this->ratingsList = new RatingList($this->entityManager);
    }

    public function view()
    {
        $r = Request::getInstance();
        $query = http_build_query([
            'rated_date' => $r->query->get('rated_date'),
            'ccm_order_by' => $r->query->get('ccm_order_by'),
            'ccm_order_by_direction' => $r->query->get('ccm_order_by_direction'),
        ]);
        $this->set('query', $query);

        $pagination = $this->ratingsList->getPagination();
        $results = $pagination->getCurrentPageResults();

        $this->set('ratingsList', $this->ratingsList);
        $this->set('ratings', $results);
        $this->set('pagination', $pagination);
    }

    public function search_ratings()
    {
        if ($this->token->validate('search_ratings')) {
            $this->getFilterByRatedDate();
            $this->view();
        }
    }

    public function csv_export($token = '')
    {
        if (!$this->token->validate('export', $token)) {
            $this->error->add($this->token->getErrorMessage());
        }

        if (!$this->error->has()) {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=ratings_' . Carbon::now() . '.csv',
            ];

            $app = $this->app;
            $config = $this->app->make('config');
            $bom = $config->get('concrete.export.csv.include_bom') ? $config->get('concrete.charset_bom') : '';

            $ratingList = $this->getFilterByRatedDate();

            return StreamedResponse::create(
                function () use ($app, $bom, $ratingList) {
                    $writer = $app->build(
                        RatingExporter::class,
                        [
                            'writer' => $this->app->make(WriterFactory::class)->createFromPath('php://output', 'w'),
                        ]
                    );
                    echo $bom;
                    $writer->insertHeaders();
                    $writer->insertRatingList($ratingList);
                }, 200, $headers
            );
        }

        return $this->buildRedirect($this->action('view'));
    }

    protected function getFilterByRatedDate()
    {
        $r = Request::getInstance();
        if ($r->query->has('rated_date') && $r->query->get('rated_date') !== '') {
            $this->ratingsList->filterByRatedDate($r->query->get('rated_date'));
            $this->set('rated_date', $r->query->get('rated_date'));

        }

        return $this->ratingsList;
    }
}
