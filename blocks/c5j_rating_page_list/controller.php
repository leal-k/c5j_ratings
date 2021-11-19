<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */

namespace Concrete\Package\C5jRatings\Block\C5jRatingPageList;

class Controller extends \Concrete\Block\PageList\Controller
{
    public function getBlockTypeName(): string
    {
        return t('C5j Rating Page List');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Shows page list with rating button');
    }

    public function on_start()
    {
        parent::on_start();
        // TODO::Implement filtering & sorting
    }
}