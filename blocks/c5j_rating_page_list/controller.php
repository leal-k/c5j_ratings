<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace Concrete\Package\C5jRatings\Block\C5jRatingPageList;

use C5jRatings\Entity\C5jRating;
use C5jRatings\Page\PageList;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Page\Page;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\User\User;
use Core;
use Database;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends \Concrete\Block\PageList\Controller
{
    protected $btTable = 'btC5jRatingPageList';

    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;

    /** @var \Concrete\Core\Application\Application */
    protected $app;

    /** @var \Concrete\Core\Validation\CSRF\Token */
    protected $token;

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

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'client');
        $this->requireAsset('css', 'ratings_button');
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

    public function action_rate_page(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('rate_page', $this->post('token'))) {
            $this->addRating($this->post('uID'), $this->post('cID'),$bID, $this->post('ratedValue'));

            return JsonResponse::create(['ratings' => $this->getRatingsCount($this->post('uID'), $this->post('cID'))]);
        }
    }

    public function action_is_rated_page(int $bID)
    {
        $this->token = $this->app->make('helper/validation/token');
        if ($this->token->validate('is_rated_page', $this->post('token'))) {
            return JsonResponse::create(['isRatedPage' => $this->isRatedBy($this->post('cID'),$this->post('uID'))]);
        }
    }

    public function isRatedBy(int $cID,int $uID): bool
    {
        $db = $this->app->make('database/connection');
        $sql = 'SELECT ratedValue FROM C5jRatings WHERE cID = ? and uID = ? and ratedValue != 0';
        $params = [$cID,$uID];

        return (int) $db->fetchColumn($sql, $params);
    }

    public function getPassThruActionAndParameters($parameters)
    {
        if ($parameters[0] == 'rate_page') {
            $method = 'action_rate_page';
            $parameters = array_slice($parameters, 1);
        }else if ($parameters[0] == 'is_rated_page') {
            $method = 'action_is_rated_page';
            $parameters = array_slice($parameters, 1);
        }elseif ($parameters[0] == 'tag') {
            $method = 'action_filter_by_tag';
            $parameters = array_slice($parameters, 1);
        } elseif (Core::make('helper/validation/numbers')->integer($parameters[0])) {
            // then we're going to treat this as a year.
            $method = 'action_filter_by_date';
            $parameters[0] = (int) ($parameters[0]);
            if (isset($parameters[1])) {
                $parameters[1] = (int) ($parameters[1]);
            }
        } else {
            $parameters = $method = null;
        }

        return [$method, $parameters];
    }

    public function isValidControllerTask($method, $parameters = [])
    {
        if ($method === 'action_filter_by_date') {
            // Parameter 0 must be set
            if (!isset($parameters[0]) || $parameters[0] < 0 || $parameters[0] > 9999) {
                return false;
            }
            // Parameter 1 can be null
            if (isset($parameters[1])) {
                if ($parameters[1] < 1 || $parameters[1] > 12) {
                    return false;
                }
            }
        }

        return true;
    }

    private function addRating(int $uID, int $cID, int $bID, int $ratedValue): C5jRating
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

    private function getRatingsCount(int $uID, int $cID): int
    {
        $db = $this->app->make('database/connection');
        $sql = 'SELECT SUM(ratedValue) AS ratings FROM C5jRatings WHERE cID=? and ratedValue != 0';

        return (int) $db->fetchColumn($sql, [$cID]);

    }
}
