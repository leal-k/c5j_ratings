<?php
/**
 * Class EntityManagerTrait.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace C5jRatings\Entity;

use Concrete\Core\Support\Facade\Facade;
use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerTrait
{
    protected $entityManager;

    public function save()
    {
        $em = $this->getEntityManager();
        $em->persist($this);
        $em->flush();
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface|mixed
     */
    public function getEntityManager()
    {
        if (!$this->entityManager) {
            $app = Facade::getFacadeApplication();
            $this->entityManager = $app->make(EntityManagerInterface::class);
        }

        return $this->entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function refresh()
    {
        $em = $this->getEntityManager();
        $em->refresh($this);
    }

    public function delete()
    {
        $em = $this->getEntityManager();
        $em->remove($this);
        $em->flush();
    }
}
