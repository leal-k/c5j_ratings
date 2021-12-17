<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace Concrete\Package\C5jRatings;

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;

class Controller extends Package
{
    /**
     * @var string package handle
     */
    protected $pkgHandle = 'c5j_ratings';

    /**
     * @var string required concrete5 version
     */
    protected $appVersionRequired = '8.1.0';

    /**
     * @var string package version
     */
    protected $pkgVersion = '0.1.0';

    protected $pkgAutoloaderRegistries = [
        'src' => '\C5jRatings',
    ];

    /**
     * @return string Package name
     */
    public function getPackageName(): string
    {
        return t('C5j Ratings');
    }

    /**
     * @return string Package description
     */
    public function getPackageDescription(): string
    {
        return t('Adds a rating button block');
    }

    /**
     * Package installation process.
     */
    public function install()
    {
        parent::install();
        $this->installXml();
    }

    /**
     * Package upgrade process.
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installXml();
    }

    public function on_start()
    {
        $al = AssetList::getInstance();
        $al->register(
            'javascript', 'client', 'js/client.min.js', ['position' => Asset::ASSET_POSITION_HEADER], 'c5j_ratings'
        );
        $al->register(
            'css', 'ratings_button', 'css/ratings_button.css', ['position' => Asset::ASSET_POSITION_HEADER], 'c5j_ratings'
        );
    }

    private function installXml(): void
    {
        $ci = new ContentImporter();
        $ci->importContentFile($this->getPackagePath() . '/config/install.xml');
    }
}
