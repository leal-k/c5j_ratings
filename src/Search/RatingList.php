<?php

namespace C5jRatings\Search;

use C5jRatings\Entity\C5jRating;
use Concrete\Core\Search\ItemList\EntityItemList;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class RatingList extends EntityItemList implements PaginationProviderInterface
{
    protected $entityManager;
    protected $itemsPerPage = 10;
    protected $autoSortColumns = ['r.cID','r.uID','r.ratedAt'];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /**
     * Filters by rated date.
     *
     * @param string $date
     * @param mixed $comparison
     */
    public function filterByRatedDate($date, $comparison = '>=')
    {
        $this->query->andWhere($this->query->expr()->GTE('r.ratedAt', ':date'));
        $this->query->setParameter('date', $date);
    }

    /**
     * @return \Doctrine\ORM\EntityManager|EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function createQuery()
    {
        $this->query->select('r')->from(C5jRating::class, 'r');
    }

    /**
     * @param $result
     *
     * @return C5jRating
     */
    public function getResult($result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalResults()
    {
        $count = 0;
        $query = $this->query->select('count(distinct r.id)')->setMaxResults(1)->resetDQLParts(['groupBy', 'orderBy']);
        try {
            $count = $query->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginationAdapter()
    {
        return new DoctrineORMAdapter($this->deliverQueryObject());
    }
}