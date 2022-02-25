<?php

namespace C5jRatings\Export;

use C5jRatings\Entity\C5jRating;
use C5jRatings\Search\RatingList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\UserInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Iterator;
use League\Csv\Writer;

class RatingExporter
{
    /**
     * @var \League\Csv\Writer
     */
    protected $writer;

    /** @var \Concrete\Core\Application\Application */
    protected $app;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface|null $entityManager
     */
    protected $entityManager;

    public function __construct(Writer $writer)
    {
        $this->writer = $writer;
        $this->app = Application::getFacadeApplication();
        $this->entityManager = $this->app->make(EntityManagerInterface::class);
    }

    public function insertHeaders(): void
    {
        $this->writer->insertOne(iterator_to_array($this->getHeaders()));
    }

    /**
     * Insert all data from the passed RatingList.
     *
     * @param \C5jRatings\Search\RatingList $ratingList
     */
    public function insertRatingList(RatingList $ratingList): void
    {
        $this->writer->insertAll($this->projectList($ratingList));
    }

    private function getHeaders(): Iterator
    {
        yield 'PageName';
        yield 'UserName';
        yield 'ratedAt';
    }

    /**
     * A generator that takes an ratingList and converts it to CSV rows.
     *
     * @param \C5jRatings\Search\RatingList $ratingList
     *
     * @return \Generator
     */
    private function projectList(RatingList $ratingList): Iterator
    {
        $ratings = $ratingList->executeGetResults();
        foreach ($ratings as $rating) {
            yield iterator_to_array($this->projectEntry($rating));
        }
    }

    /**
     * Turn an Entry into an array.
     *
     * @param \C5jRatings\Entity\C5jRating $entry
     * @param C5jRating $rating
     *
     * @return array
     */
    private function projectEntry(C5jRating $rating): Iterator
    {
        $page = \Concrete\Core\Page\Page::getByID($rating->getCID());
        $ui = $this->app->make(UserInfoRepository::class)->getByID($rating->getUID());
        if($ui){
            $name = h($ui->getUserName());
        }else{
            $name = t('Unknown');
        }

        yield $page->getCollectionName();
        yield $name;
        yield $rating->getRatedAt()->format('Y/m/d H:i:s');

    }
}
