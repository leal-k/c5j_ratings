<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
namespace Concrete\Package\C5jRatings\Block\C5jRatingBtn;

use C5jRatings\Entity\C5jRating;
use Carbon\Carbon;
use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Block\BlockController;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends BlockController
{
    protected $btTable = 'btC5jRatings';
    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;
    /** @var \Concrete\Core\Validation\CSRF\Token */
    protected $token;

    public function getBlockTypeName(): string
    {
        return t('C5j Rating Button');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Shows a rating button & count');
    }

    public function on_start(): void
    {
        $this->db = $this->app->make('database/connection');
        $this->token = $this->app->make('helper/validation/token');
        $this->set('token', $this->token);
        $al = AssetList::getInstance();
        $al->register('javascript', 'client', 'js/client.min.js', ['position' => Asset::ASSET_POSITION_HEADER], 'c5j_ratings');
        $al->register('css', 'ratings_button', 'css/ratings_button.css', ['position' => Asset::ASSET_POSITION_HEADER], 'c5j_ratings');
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'client');
        $this->requireAsset('css', 'ratings_button');
    }

    public function view()
    {
        $this->set('ratings', $this->getRatingsCount());
    }

    private function getRatingsCount(): int
    {
        // TODO::Use entity
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE bID=?';

        return (int) $this->db->fetchColumn($sql, [$this->bID]);
    }

    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rate', $this->post('token'))) {
            $this->addRating($this->post('uID'), $this->post('ratedValue'));

            return JsonResponse::create(['ratings' => $this->getRatingsCount()]);
        }
    }

    private function addRating($uID, $ratedValue): C5jRating
    {
        $rating = C5jRating::getByBIDAndUID($this->bID, $uID);
        if (!$rating) {
            $rating = new C5jRating();
        }
        $rating->setBID($this->bID);
        $rating->setCID($this->getRequest()->getCurrentPage()->getCollectionID());
        $rating->setUID($uID);
        $rating->setRatedValue($ratedValue);
        $rating->save();

        return $rating;
    }

    public function action_is_rated(int $bID)
    {
        if ($this->token->validate('is_rated', $this->post('token'))) {
            return JsonResponse::create(['isRated' => $this->isRatedBy($this->post('uID'))]);
        }
    }

    private function isRatedBy(int $uID): bool
    {
        // TODO::Use entity
        $sql = "SELECT ratedValue FROM C5jRatings WHERE bID = ? AND uID = ?";
        $params = [$this->bID, $uID];

        return (bool) $this->db->fetchColumn($sql, $params);
    }
}
