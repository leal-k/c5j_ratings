<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
namespace Concrete\Package\C5jRatings\Block\C5jRatings;

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
        return t('C5j Ratings');
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
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'client');
    }

    public function add()
    {
        //
    }

    public function edit()
    {
        //
    }

    public function view()
    {
        $this->set('ratings', $this->getRatingsCount());
    }

    public function action_rate(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rate', $this->post('token'))) {
            $this->addRating($this->post('uID'), $this->post('ratedValue'));

            return JsonResponse::create(['ratings' => $this->getRatingsCount()]);
        }
    }

    public function action_is_rated(int $bID)
    {
        if ($this->token->validate('is_rated', $this->post('token'))) {
            return JsonResponse::create(['isRated' => $this->isRatedBy($this->post('uID'))]);
        }
    }

    private function addRating($uID, $ratedValue): void
    {
        $sql = 'INSERT INTO C5jRatings (bID, uID, ratedValue, ratedAt)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE ratedValue =?, ratedAt = ?';
        $params = [$this->bID, $uID, $ratedValue, Carbon::now(), $ratedValue, Carbon::now()];

        $this->db->executeQuery($sql, $params);
    }

    private function getRatingsCount(): int
    {
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE bID=?';

        return (int) $this->db->fetchColumn($sql, [$this->bID]);
    }

    private function isRatedBy(int $uID): bool
    {
        $sql = "SELECT ratedValue FROM C5jRatings WHERE bID = ? AND uID = ?";
        $params = [$this->bID, $uID];

        return (bool) $this->db->fetchColumn($sql, $params);
    }
}
