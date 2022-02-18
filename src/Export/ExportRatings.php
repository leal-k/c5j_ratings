<?php

namespace C5jRatings\Export;

use C5jRatings\Entity\C5jRating;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\UserInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Iterator;
use League\Csv\Writer;

class ExportRatings
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

    public function insertRows(array $ratings): void
    {
        $this->writer->insertAll($this->getRows($ratings));
    }

    private function getHeaders(): Iterator
    {
        yield 'PageName';
        yield 'UserName';
        yield 'ratedAt';
    }

    private function getRows(array $ratings): Iterator
    {
        foreach ($ratings as $rating) {
            yield iterator_to_array($this->getRow($rating));
        }
    }

    private function getRow(C5jRating $rating): Iterator
    {
        $page = \Concrete\Core\Page\Page::getByID($rating->getCID());
        $ui = $this->app->make(UserInfoRepository::class)->getByID($rating->getUID());
        if($ui){
            $name = $ui->getUserName();
        }else{
            $name = 'unknown';
        }

        yield $page->getCollectionName();
        yield $name;
        yield $rating->getRatedAt()->format('Y/m/d H:i:s');

    }
}
