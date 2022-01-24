<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */

namespace C5jRatings\Traits;

use C5jRatings\Entity\C5jRating;
use Symfony\Component\HttpFoundation\JsonResponse;

trait RatingTrait
{
    protected $cID;
    protected $uID;

    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rating', $this->post('token'))) {
            $uID = $this->getUserID();
            $cID = $this->getCollectionID();
            $ratedValue = $this->post('ratedValue');
            $this->addRating($uID, $cID, $bID, $ratedValue);

            return JsonResponse::create($this->getRatings($cID, $uID));
        }
    }

    protected function getUserID(): int
    {
        if (!$this->uID && $this->getRequest()->request->has('uID')) {
            $this->uID = $this->getRequest()->request->get('uID');
        }

        if (!$this->uID) {
            $user = $this->app->make('user');
            if ($user->isRegistered()) {
                $this->uID = $user->getUserID();
            }
        }

        return (int) $this->uID;
    }

    protected function getCollectionID(): int
    {
        if (!$this->cID && $this->getRequest()->request->has('cID')) {
            $this->cID = $this->getRequest()->request->get('cID');
        }

        if (!$this->cID) {
            $this->cID = $this->getRequest()->getCurrentPage()->getCollectionID();
        }

        return (int) $this->cID;
    }

    protected function addRating(int $uID, int $cID, int $bID, int $ratedValue): C5jRating
    {
        $rating = C5jRating::getByCIDAndUID($cID, $uID);
        if (!$rating) {
            $rating = new C5jRating();
        }
        $rating->setBID($bID);
        $rating->setCID($cID);
        $rating->setUID($uID);
        $rating->setRatedValue($ratedValue);
        $rating->save();

        return $rating;
    }

    protected function getRatings($cID = 0, $uID = 0): array
    {
        $cID = $cID ?? $this->getCollectionID();
        $uID = $uID ?? $this->getUserID();

        return [
            'cID' => $cID,
            'isRated' => $this->isRatedBy($cID, $uID),
            'ratings' => $this->getRatingsCount($cID),
        ];
    }

    protected function isRatedBy(int $cID, int $uID): bool
    {
        $db = $this->app->make('database/connection');
        $sql = 'SELECT ratedValue FROM C5jRatings WHERE cID = ? and uID = ? and ratedValue != 0';
        $params = [$cID, $uID];

        return (int)$db->fetchColumn($sql, $params);
    }

    protected function getRatingsCount(int $cID): int
    {
        $db = $this->app->make('database/connection');
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE cID = ? and ratedValue != 0';
        $params = [$cID];

        return (int) $db->fetchColumn($sql, $params);
    }

    public function action_get_ratings(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rating', $this->post('token'))) {
            $cID = $this->getCollectionID();
            $uID = $this->getUserID();

            return JsonResponse::create($this->getRatings($cID, $uID));
        }
    }
}