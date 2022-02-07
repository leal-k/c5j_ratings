<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace C5jRatings\Traits;

use C5jRatings\Entity\C5jRating;
use Symfony\Component\HttpFoundation\JsonResponse;

trait RatingTrait
{
    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rating', $this->post('token'))) {
            $cID = (int) $this->post('cID');
            $uID = (int) $this->post('uID');
            $ratedValue = $this->post('ratedValue');
            $this->addRating($uID, $cID, $bID, $ratedValue);

            return JsonResponse::create($this->getRatings($cID, $uID));
        }
    }

    public function action_get_ratings(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rating', $this->post('token'))) {
            $cID = (int) $this->post('cID');
            $uID = (int) $this->post('uID');

            return JsonResponse::create($this->getRatings($cID, $uID));
        }
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

    protected function getRatings(int $cID, int $uID): array
    {
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

        return (int) $db->fetchColumn($sql, $params);
    }

    protected function getRatingsCount(int $cID): int
    {
        $db = $this->app->make('database/connection');
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE cID = ? and ratedValue != 0';
        $params = [$cID];

        return (int) $db->fetchColumn($sql, $params);
    }
}
