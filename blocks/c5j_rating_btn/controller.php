<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
namespace Concrete\Package\C5jRatings\Block\C5jRatingBtn;

use C5jRatings\Entity\C5jRating;
use Carbon\Carbon;
use Concrete\Core\Block\BlockController;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends BlockController
{
    protected $btTable = 'btC5jRatings';
    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;
    /** @var \Concrete\Core\Validation\CSRF\Token */
    protected $token;

    public function getBlockTypeName(): string
    {
        return t('C5j Rating Button');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Shows a rating button & count');
    }

    public function on_start(): void
    {
        $this->db = $this->app->make('database/connection');
        $this->token = $this->app->make('helper/validation/token');
        $this->set('token', $this->token);
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('c5j_ratings');
    }

    public function view()
    {
        $this->set('ratings', $this->getRatingsCount());
    }

    private function getRatingsCount(): int
    {
        $c = $this->getRequest()->getCurrentPage();

        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE cID = ? and ratedValue != 0';
        $params = [$c->getCollectionID()];

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rate', $this->post('token'))) {
            $this->addRating($this->post('uID'), $this->post('ratedValue'));

            return JsonResponse::create(['ratings' => $this->getRatingsCount()]);
        }
    }

    private function addRating($uID, $ratedValue): C5jRating
    {
        $rating = C5jRating::getByCIDAndUID($this->getRequest()->getCurrentPage()->getCollectionID(), $uID);
        if (!$rating) {
            $rating = new C5jRating();
        }
        $rating->setBID($this->bID);
        $rating->setCID($this->getRequest()->getCurrentPage()->getCollectionID());
        $rating->setUID($uID);
        $rating->setRatedValue($ratedValue);
        $rating->save();

        return $rating;
    }

    public function action_is_rated(int $bID)
    {
        if ($this->token->validate('is_rated', $this->post('token'))) {
            return JsonResponse::create([
                'isRated' => $this->isRatedBy($this->post('uID')),
                'ratings' => $this->getRatingsCount()]);
        }
    }

    private function isRatedBy(int $uID): bool
    {
        $sql = 'SELECT ratedValue AS ratings FROM C5jRatings WHERE cID = ? AND uID = ? AND ratedValue != 0';
        $params = [$this->getRequest()->getCurrentPage()->getCollectionID(), $uID];

        return (bool) $this->db->fetchColumn($sql, $params);
    }
}
