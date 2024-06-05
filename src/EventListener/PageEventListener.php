<?php

namespace C5jRatings\EventListener;

use C5jRatings\Entity\C5jRating;
use Concrete\Core\Page\DeletePageEvent;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;

class PageEventListener
{
    public static function onPageDelete(DeletePageEvent $event): void
    {
        $page = $event->getPageObject();
        if ($page) {
            $cID = $page->getCollectionID();
            $app = Application::getFacadeApplication();
            /** @var EntityManagerInterface $em */
            $em = $app->make(EntityManagerInterface::class);
            /** @var C5jRating $rating */
            $ratings = $em->getRepository(C5jRating::class)->findBy(['cID' => $cID]);
            foreach ($ratings as $rating) {
                $rating->delete();
            }
        }
    }
}
