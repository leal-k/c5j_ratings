<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace C5jRatings\Page;

use Doctrine\DBAL\Types\Type;

class PageList extends \Concrete\Core\Page\PageList
{
    public function __construct()
    {
        parent::__construct();
        $this->query->leftJoin('p', 'C5jRatings', 'r', 'p.cID = r.cID');
        $this->query->addSelect('SUM(r.ratedValue) AS ratings');
        $this->query->addSelect('r.bID');
        $this->query->addGroupBy('p.cID');
        $this->query->addGroupBy('r.bID');
        $this->query->addGroupBy('p.cDisplayOrder');
    }

    public function filterByUserRated(int $uID): void
    {
        $this->query->andWhere($this->query->expr()->in('p.cID', 'Select cID from C5jRatings where uID = :uID  and ratedValue != 0'));
        $this->query->setParameter('uID', $uID, Type::INTEGER);
    }

    public function filterByMinimumNumberOfRatings(int $num = 0): void
    {
        if ($num > 0) {
            $this->query->andWhere($this->query->expr()->in('p.cID', 'SELECT cID FROM C5jRatings GROUP BY cID HAVING SUM(ratedValue) >= :num'));
            $this->query->setParameter('num', $num, Type::INTEGER);
        }
    }

    public function sortByMostRated(): void
    {
        $this->query->orderBy('ratings', 'DESC');
    }

	public function sortByMostRecentlyRated(): void
	{
		$this->query->orderBy('r.createdAt', 'DESC');
	}
}
