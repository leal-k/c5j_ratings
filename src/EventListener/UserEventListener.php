<?php

namespace C5jRatings\EventListener;

use C5jRatings\Entity\C5jRating;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\Event\DeleteUser;
use Doctrine\ORM\EntityManagerInterface;

class UserEventListener
{
    public static function onUserDelete(DeleteUser $event): void
    {
        $ui = $event->getUserInfoObject();
        if ($ui) {
            $uID = $ui->getUserID();
            $app = Application::getFacadeApplication();
            /** @var EntityManagerInterface $em */
            $em = $app->make(EntityManagerInterface::class);
            /** @var C5jRating $rating */
            $ratings = $em->getRepository(C5jRating::class)->findBy(['uID' => $uID]);
            foreach ($ratings as $rating) {
                $rating->delete();
            }
        }
    }
}
