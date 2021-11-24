<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */

namespace C5jRatings\Page;
use Doctrine\DBAL\Types\Type;
use Pagerfanta\Adapter\DoctrineDbalAdapter;

class PageList extends \Concrete\Core\Page\PageList
{
    public function __construct()
    {
        parent::__construct();
        $this->query->leftJoin('p', 'C5jRatings', 'r', 'p.cID = r.cID and r.ratedValue = 1');
        $this->query->addSelect('SUM(r.ratedValue) AS ratings');
        $this->query->addSelect('r.bID');
        $this->query->addGroupBy('r.cID');

    }

    public function filterByUserRated(int $uID): void
    {
        $this->query->andWhere('r.uID = :uID');
        $this->query->setParameter('uID', $uID, Type::INTEGER);
    }

    public function filterByMinimumNumberOfRatings(int $num = 0): void
    {
        $this->query->andHaving($this->query->expr()->gte('SUM(r.ratedValue)', $num));
    }

    public function sortByMostRated(): void
    {
        $this->query->orderBy('ratings', 'DESC');
    }

    public function getResult($queryRow)
    {
        return $queryRow;
    }

    public function getPaginationAdapter()
    {
        $adapter = new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            // We need to reset the potential custom order by here because otherwise, if we've added
            // items to the select parts, and we're ordering by them, we get a SQL error
            // when we get total results, because we're resetting the select
           $query->resetQueryParts(['orderBy'])->select('count(distinct p.cID)')->setMaxResults(1);
        });

        return $adapter;
    }
}