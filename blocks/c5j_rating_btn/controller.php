<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
namespace Concrete\Package\C5jRatings\Block\C5jRatingBtn;

use C5jRatings\Traits\RatingTrait;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btC5jRatings';
    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;
    /** @var \Concrete\Core\Validation\CSRF\Token */
    protected $token;

    protected $btCacheBlockOutput = true;

    use RatingTrait;

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
    }

    public function registerViewAssets($outputContent = ''): void
    {
        $this->requireAsset('c5j_ratings');
    }

    public function view(): void
    {
        $cID = (int) $this->getRequest()->getCurrentPage()->getCollectionID();
        $uID = (int) $this->app->make('user')->getUserID();
        $this->set('ratings', $this->getRatings($cID, $uID));
    }
}
