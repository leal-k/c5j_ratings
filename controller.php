<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace Concrete\Package\C5jRatings;

use C5jRatings\EventListener\PageEventListener;
use C5jRatings\EventListener\UserEventListener;
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
    protected $appVersionRequired = '8.5.0';

    /**
     * @var string package version
     */
    protected $pkgVersion = '1.0.2-rc2';

    protected $pkgAutoloaderRegistries = [
        'src' => '\C5jRatings',
    ];

    /**
     * @return string Package name
     */
    public function getPackageName(): string
    {
        return t('Macareux Ratings');
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

    public function on_start(): void
    {
        $this->registerAssets();

		$director = $this->app->make('director');
		$director->addListener('on_page_delete', function ($e) {
			$ratingPageEvent = $this->app->make(PageEventListener::class);
			$ratingPageEvent->onPageDelete($e);
		});
		$director->addListener('on_user_delete', function ($e) {
			$ratingUserEvent = $this->app->make(UserEventListener::class);
			$ratingUserEvent->onUserDelete($e);
		});
    }

    private function installXml(): void
    {
        $ci = new ContentImporter();
        $ci->importContentFile($this->getPackagePath() . '/config/install.xml');
    }

    private function registerAssets(): void
    {
        $al = AssetList::getInstance();
        $al->register(
            'css',
            'c5j_ratings',
            'css/c5j_ratings.css',
            ['position' => Asset::ASSET_POSITION_HEADER],
            'c5j_ratings'
        );
        $al->register(
            'javascript',
            'client',
            'js/client.base.min.js',
            ['position' => Asset::ASSET_POSITION_FOOTER],
            'c5j_ratings'
        );
        $al->register(
            'javascript',
            'c5j_ratings',
            'js/c5j_ratings.js',
            ['position' => Asset::ASSET_POSITION_FOOTER],
            'c5j_ratings'
        );
        $al->registerGroup('c5j_ratings', [
            ['css', 'c5j_ratings'],
            ['javascript', 'client'],
            ['javascript', 'c5j_ratings'],
        ]);
    }
}
