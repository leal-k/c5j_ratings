<?php
/**
 * Class Controller.
 *
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
namespace Concrete\Package\C5jRatings;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;

class Controller extends Package
{
    /**
     * @var string Package handle.
     */
    protected $pkgHandle = 'c5j_ratings';

    /**
     * @var string Required concrete5 version.
     */
    protected $appVersionRequired = '8.1.0';

    /**
     * @var string Package version.
     */
    protected $pkgVersion = '0.0.1';

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

    private function installXml(): void
    {
        $ci = new ContentImporter();
        $ci->importContentFile($this->getPackagePath() . '/config/install.xml');
    }
}
