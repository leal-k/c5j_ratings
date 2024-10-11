<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace C5jRatings\Traits;

use C5jRatings\Entity\C5jRating;
use Concrete\Core\Http\Request;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\User;
use Concrete\Core\Validation\CSRF\Token;
use Symfony\Component\HttpFoundation\JsonResponse;

trait RatingTrait
{
    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        $uID = (int) $this->post('uID');
        if ($this->validateToken('rating', $this->post('token'), $uID)) {
            $cID = (int) $this->post('cID');
            $ratedValue = $this->post('ratedValue');
            $this->addRating($uID, $cID, $bID, $ratedValue);

            return JsonResponse::create($this->getRatings($cID, $uID, $bID));
        }

        return JsonResponse::create($this->token->getErrorMessage());
    }

    public function action_get_ratings(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        $uID = (int) $this->post('uID');
        if ($this->validateToken('rating', $this->post('token'), $uID)) {
            $ratings = [];
            $cID = $this->post('cID');
            if (is_array($cID)) {
                foreach ($cID as $id) {
                    $ratings[] = $this->getRatings((int)$id, $uID, $bID);
                }
            } else {
                $ratings = $this->getRatings((int)$cID, $uID, $bID);
            }

            return JsonResponse::create($ratings);
        }
        return JsonResponse::create($this->token->getErrorMessage());
    }

    public function generate($action = '', $time = null, $uID = 0): string
    {
        $app = Application::getFacadeApplication();
        $u = $app->make(User::class);
        $user = $u->getByUserID($uID);
        if ($user) {
            $uID = $user->getUserID();
        } else {
            $uID = 0;
        }

        if (!$time) {
            $time = time();
        }
        $config = $app->make('config/database');

        return $time . ':' . md5($time . ':' . $uID . ':' . $action . ':' . $config->get('concrete.security.token.validation'));
    }

    public function validateToken($action = '', $token = null, $uID = 0): bool
    {
        $app = Application::getFacadeApplication();
        if ($token === null) {
            $request = $app->make(Request::class);
            $token = $request->request->get(Token::DEFAULT_TOKEN_NAME);
            if ($token === null) {
                $token = $request->query->get(Token::DEFAULT_TOKEN_NAME);
            }
        }
        if (is_string($token)) {
            $parts = explode(':', $token);
            if ($parts[0] && isset($parts[1])) {
                $time = $parts[0];
                $hash = $parts[1];
                $compHash = $this->generate($action, $time, $uID);
                $now = time();

                if (substr($compHash, strpos($compHash, ':') + 1) == $hash) {
                    $diff = $now - $time;
                    //hash is only valid if $diff is less than VALID_HASH_TIME_RECORD
                    return $diff <= Token::VALID_HASH_TIME_THRESHOLD;
                }
                $logger = $app->make('log/factory')->createLogger(Channels::CHANNEL_SECURITY);
                $logger->debug(t('Validation token did not match'), [
                    'uID' => $uID,
                    'action' => $action,
                    'time' => $time,
                ]);
            }
        }

        return false;
    }

    protected function addRating(int $uID, int $cID, int $bID, int $ratedValue): C5jRating
    {
        $rating = C5jRating::getByCIDAndUIDAndBID($cID, $uID, $bID);
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

    protected function getRatings(int $cID, int $uID, int $bID): array
    {
        return [
            'cID' => $cID,
            'uID' => $uID,
            'bID' => $bID,
            'isRated' => $this->isRatedBy($cID, $uID, $bID),
            'ratings' => $this->getRatingsCount($cID, $bID),
        ];
    }

    protected function isRatedBy(int $cID, int $uID, int $bID = null): bool
    { //added bID to the method so the query requests the right uID for that button
        $db = $this->app->make('database/connection');
        $params = [];
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE ratedValue != 0 ';
        if ($bID) {
            $sql .= ' AND bID = ?';
            $params[] = $bID;
        }
        if ($cID) {
            $sql .= ' AND cID = ?';
            $params[] = $cID;
        }
        return (int) $db->fetchColumn($sql, $params);
    }

    protected function getRatingsCount(int $cID, int $bID = null): int
    { //used the base SQL for the query and if it has the parameters, it concatenates
        $db = $this->app->make('database/connection');
        $params = [];
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE ratedValue != 0 ';
        if ($bID) {
            $sql .= ' AND bID = ?';
            $params[] = $bID;
        }
        if ($cID) {
            $sql .= ' AND cID = ?';
            $params[] = $cID;
        }
        return (int) $db->fetchColumn($sql, $params);
    }
}
