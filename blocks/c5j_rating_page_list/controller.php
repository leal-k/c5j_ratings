<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace Concrete\Package\C5jRatings\Block\C5jRatingPageList;

use C5jRatings\Page\PageList;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Page\Page;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\User\User;
use Core;
use Database;

class Controller extends \Concrete\Block\PageList\Controller
{
    protected $btTable = 'btC5jRatingPageList';

    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;

    /** @var \Concrete\Core\Application\Application */
    protected $app;

    public function getBlockTypeName(): string
    {
        return t('C5j Rating Page List');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Shows page list with rating button');
    }

    public function getJavaScriptStrings()
    {
        return [
            'feed-name' => t('Please give your RSS Feed a name.'),
        ];
    }

    public function on_start()
    {
        $this->list = new PageList();
        $this->list->disableAutomaticSorting();
        $this->list->setNameSpace('b' . $this->bID);

        $cArray = [];

        switch ($this->orderBy) {
            case 'display_asc':
                $this->list->sortByDisplayOrder();
                break;
            case 'display_desc':
                $this->list->sortByDisplayOrderDescending();
                break;
            case 'chrono_asc':
                $this->list->sortByPublicDate();
                break;
            case 'modified_desc':
                $this->list->sortByDateModifiedDescending();
                break;
            case 'random':
                $this->list->sortBy('RAND()');
                break;
            case 'alpha_asc':
                $this->list->sortByName();
                break;
            case 'alpha_desc':
                $this->list->sortByNameDescending();
                break;
            case 'rated_desc':
                $this->list->sortByMostRated();
                break;
            default:
                $this->list->sortByPublicDateDescending();
                break;
        }

        $now = Core::make('helper/date')->toDB();
        $end = $start = null;

        switch ($this->filterDateOption) {
            case 'now':
                $start = date('Y-m-d') . ' 00:00:00';
                $end = $now;
                break;

            case 'past':
                $end = $now;

                if ($this->filterDateDays > 0) {
                    $past = date('Y-m-d', strtotime("-{$this->filterDateDays} days"));
                    $start = "$past 00:00:00";
                }
                break;

            case 'future':
                $start = $now;

                if ($this->filterDateDays > 0) {
                    $future = date('Y-m-d', strtotime("+{$this->filterDateDays} days"));
                    $end = "$future 23:59:59";
                }
                break;

            case 'between':
                $start = "{$this->filterDateStart} 00:00:00";
                $end = "{$this->filterDateEnd} 23:59:59";
                break;

            case 'all':
            default:
                break;
        }

        if ($start) {
            $this->list->filterByPublicDate($start, '>=');
        }
        if ($end) {
            $this->list->filterByPublicDate($end, '<=');
        }

        $c = Page::getCurrentPage();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        if ($this->displayFeaturedOnly == 1) {
            $cak = CollectionAttributeKey::getByHandle('is_featured');
            if (is_object($cak)) {
                $this->list->filterByIsFeatured(1);
            }
        }
        if ($this->displayAliases) {
            $this->list->includeAliases();
        }
        if (isset($this->ignorePermissions) && $this->ignorePermissions) {
            $this->list->ignorePermissions();
        }

        $this->list->filter('cvName', '', '!=');

        if ($this->ptID) {
            $this->list->filterByPageTypeID($this->ptID);
        }

        if ($this->filterByRelated) {
            $ak = CollectionKey::getByHandle($this->relatedTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $topics = $c->getAttribute($ak->getAttributeKeyHandle());
                if (is_array($topics) && count($topics) > 0) {
                    $topic = $topics[array_rand($topics)];
                    $this->list->filter('p.cID', $c->getCollectionID(), '<>');
                    $this->list->filterByTopic($topic);
                }
            }
        }

        if ($this->filterByCustomTopic) {
            $ak = CollectionKey::getByHandle($this->customTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $topic = Node::getByID($this->customTopicTreeNodeID);
                if ($topic) {
                    $ak->getController()->filterByAttribute($this->list, $this->customTopicTreeNodeID);
                }
            }
        }

        $this->list->filterByExcludePageList(false);

        if ((int) ($this->cParentID) != 0) {
            $cParentID = ($this->cThis) ? $this->cID : (($this->cThisParent) ? $this->cPID : $this->cParentID);
            if ($this->includeAllDescendents) {
                $this->list->filterByPath(Page::getByID($cParentID)->getCollectionPath());
            } else {
                $this->list->filterByParentID($cParentID);
            }
        }

        if ($this->filterByUserRated) {
            $u = Core::make(User::class);
            $uID = $u->getUserID();
            $this->list->filterByUserRated($uID);
        }

        if ($this->miniNumOfRatings && $this->numOfRatings) {
            $this->list->filterByMinimumNumberOfRatings($this->numOfRatings);
        }

        return $this->list;
    }

    public function getbtnType(int $bID = 0)
    {
        $this->db = Core::make('database/connection');
        $sql = 'SELECT btnType FROM btC5jRatings WHERE bID = ?';
        $params = [$bID];

        return $this->db->fetchColumn($sql, $params);

    }

    public function save($args)
    {
        // If we've gotten to the process() function for this class, we assume that we're in
        // the clear, as far as permissions are concerned (since we check permissions at several
        // points within the dispatcher)
        $db = Database::connection();

        $bID = $this->bID;
        $c = $this->getCollectionObject();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        $args += [
            'enableExternalFiltering' => 0,
            'includeAllDescendents' => 0,
            'includeDate' => 0,
            'truncateSummaries' => 0,
            'displayFeaturedOnly' => 0,
            'topicFilter' => '',
            'displayThumbnail' => 0,
            'displayAliases' => 0,
            'truncateChars' => 0,
            'paginate' => 0,
            'rss' => 0,
            'pfID' => 0,
            'filterDateOption' => '',
            'cParentID' => null,
        ];

        if (is_numeric($args['cParentID'])) {
            $args['cParentID'] = (int) ($args['cParentID']);
        }

        $args['num'] = ($args['num'] > 0) ? $args['num'] : 0;
        $args['cThis'] = ($args['cParentID'] === $this->cID) ? '1' : '0';
        $args['cThisParent'] = ($args['cParentID'] === $this->cPID) ? '1' : '0';
        $args['cParentID'] = ($args['cParentID'] === 'OTHER') ? (empty($args['cParentIDValue']) ? null : $args['cParentIDValue']) : $args['cParentID'];
        if (!$args['cParentID']) {
            $args['cParentID'] = 0;
        }
        $args['enableExternalFiltering'] = ($args['enableExternalFiltering']) ? '1' : '0';
        $args['includeAllDescendents'] = ($args['includeAllDescendents']) ? '1' : '0';
        $args['includeDate'] = ($args['includeDate']) ? '1' : '0';
        $args['truncateSummaries'] = ($args['truncateSummaries']) ? '1' : '0';
        $args['displayFeaturedOnly'] = ($args['displayFeaturedOnly']) ? '1' : '0';
        $args['filterByRelated'] = ($args['topicFilter'] == 'related') ? '1' : '0';
        $args['filterByCustomTopic'] = ($args['topicFilter'] == 'custom') ? '1' : '0';
        $args['displayThumbnail'] = ($args['displayThumbnail']) ? '1' : '0';
        $args['displayAliases'] = ($args['displayAliases']) ? '1' : '0';
        $args['truncateChars'] = (int) ($args['truncateChars']);
        $args['paginate'] = (int) ($args['paginate']);
        $args['rss'] = (int) ($args['rss']);
        $args['ptID'] = (int) ($args['ptID']);

        if (!$args['filterByRelated']) {
            $args['relatedTopicAttributeKeyHandle'] = '';
        }

        if (!$args['filterByCustomTopic'] || !$this->app->make('helper/number')->isInteger($args['customTopicTreeNodeID'])) {
            $args['customTopicAttributeKeyHandle'] = '';
            $args['customTopicTreeNodeID'] = 0;
        }

        if ($args['rss']) {
            if (isset($this->pfID) && $this->pfID) {
                $pf = Feed::getByID($this->pfID);
            }

            if (!is_object($pf)) {
                $pf = new \Concrete\Core\Entity\Page\Feed();
                $pf->setTitle($args['rssTitle']);
                $pf->setDescription($args['rssDescription']);
                $pf->setHandle($args['rssHandle']);
            }

            $pf->setParentID($args['cParentID']);
            $pf->setPageTypeID($args['ptID']);
            $pf->setIncludeAllDescendents($args['includeAllDescendents']);
            $pf->setDisplayAliases($args['displayAliases']);
            $pf->setDisplayFeaturedOnly($args['displayFeaturedOnly']);
            $pf->setDisplayAliases($args['displayAliases']);
            $pf->displayShortDescriptionContent();
            $pf->save();
            $args['pfID'] = $pf->getID();
        } elseif (isset($this->pfID) && $this->pfID && !$args['rss']) {
            // let's make sure this isn't in use elsewhere.
            $cnt = $db->fetchColumn('select count(pfID) from btPageList where pfID = ?', [$this->pfID]);
            if ($cnt == 1) { // this is the last one, so we delete
                $pf = Feed::getByID($this->pfID);
                if (is_object($pf)) {
                    $pf->delete();
                }
            }
            $args['pfID'] = 0;
        }

        if ($args['filterDateOption'] != 'between') {
            $args['filterDateStart'] = null;
            $args['filterDateEnd'] = null;
        }

        if ($args['filterDateOption'] == 'past') {
            $args['filterDateDays'] = $args['filterDatePast'];
        } elseif ($args['filterDateOption'] == 'future') {
            $args['filterDateDays'] = $args['filterDateFuture'];
        } else {
            $args['filterDateDays'] = null;
        }

        $args['filterByUserRated'] = isset($args['filterByUserRated']) ? $args['filterByUserRated'] : 0;
        $args['miniNumOfRatings'] = isset($args['miniNumOfRatings']) ? $args['miniNumOfRatings'] : 0;

        $args['pfID'] = (int) ($args['pfID']);
        parent::save($args);
    }
}
