<?php
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */

namespace Concrete\Package\C5jRatings\Block\C5jRatingPageList;

use C5jRatings\Page\PageList;
use C5jRatings\Traits\RatingTrait;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Http\RequestBase;
use Concrete\Core\Page\Page;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\User\User;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Block\View\BlockView;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Attribute\Key\CollectionKey as CollectionAttributeKey;

class Controller extends \Concrete\Block\PageList\Controller
{
    use RatingTrait;
    protected $btTable = 'btC5jRatingPageList';

    /** @var \Concrete\Core\Database\Connection\Connection */
    protected $db;

    /** @var \Concrete\Core\Application\Application */
    protected $app;

    /** @var \Concrete\Core\Validation\CSRF\Token */
    protected $token;
	
	public $filterByRelated;
	public $filterByUserRated;
	public $miniNumOfRatings;
	public $numOfRatings;

    /**
     * A simple hack to fix the PHP8 compatibility issue with core.
     * @See https://github.com/concretecms/concretecms/pull/11017/files
     *
     * @var array
     */
    protected $requestArray;

    public function getBlockTypeName(): string
    {
        return t('Rating Page List');
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
        $this->requireAsset('javascript', 'jquery');
        $this->requireAsset('c5j_ratings');
    }
    
	
	public function action_preview_pane()
	{
		$bt = BlockType::getByHandle('page_list');
		$controller = $bt->getController();
		
		$request = $this->request;
		$num = ($request->query->get('num') > 0) ? $request->query->get('num') : 0;
		$cThis = ($request->query->get('cParentID') == $request->query->get('current_page')) ?  '1' : '0';
		$cParentID = ($request->query->get('cParentID') == 'OTHER') ?  $request->query->get('cParentIDValue') : $request->query->get('cParentID');
		
		if ($request->query->get('filterDateOption') != 'between') {
			$filterDateStart = null;
			$filterDateEnd = null;
		} else {
			$filterDateStart = $request->query->get('filterDateStart');
			$filterDateEnd = $request->query->get('filterDateEnd');
		}

		if ($request->query->get('filterDateOption') == 'past') {
			$filterDateDays = $request->query->get('filterDatePast');
		} elseif ($request->query->get('filterDateOption') == 'future') {
			$filterDateDays = $request->query->get('filterDateFuture');
		} else {
			$filterDateDays = null;
		}

		$controller->num = $num;
		$controller->cParentID = $cParentID;
		$controller->cThis = $cThis;
		$controller->orderBy = $request->query->get('orderBy');
		$controller->ptID = $request->query->get('ptID');
		$controller->rss = $request->query->get('rss');
		$controller->displayFeaturedOnly = $request->query->get('displayFeaturedOnly') ?? false;
		$controller->displayAliases = $request->query->get('displayAliases') ?? false;
		$controller->paginate = $request->query->get('paginate') ?? false;
		$controller->enableExternalFiltering = $request->query->get('enableExternalFiltering') ?? false;
		$controller->filterByRelated = $request->query->get('filterByRelated') ?? false;
		$controller->relatedTopicAttributeKeyHandle = $request->query->get('relatedTopicAttributeKeyHandle');
		$controller->filterByCustomTopic = ($request->query->get('topicFilter') == 'custom') ? '1' : '0';
		$controller->customTopicAttributeKeyHandle = $request->query->get('customTopicAttributeKeyHandle');
		$controller->customTopicTreeNodeID = $request->query->get('customTopicTreeNodeID');
		$controller->includeAllDescendents = $request->query->get('includeAllDescendents') ?? false;
		$controller->includeDate = $request->query->get('includeDate') ?? false;
		$controller->displayThumbnail = $request->query->get('displayThumbnail') ?? false;
		$controller->includeDescription = $request->query->get('includeDescription') ?? false;
		$controller->useButtonForLink = $request->query->get('useButtonForLink') ?? false;
		$controller->filterDateOption = $request->query->get('filterDateOption');
		$controller->filterDateStart = $filterDateStart;
		$controller->filterDateEnd = $filterDateEnd;
		$controller->filterDateDays = $filterDateDays;

		$controller->filterByCustomTopic = $request->query->get('filterByCustomTopic') ?? false;
		$controller->filterByUserRated = $request->query->get('filterByUserRated') ?? false;
		$controller->miniNumOfRatings = $request->query->get('miniNumOfRatings') ?? false;
		$controller->numOfRatings = $request->query->get('numOfRatings') ?? '0';

		$controller->set('includeEntryText', true);
		$controller->set('includeName', true);
		$controller->set('displayThumbnail', $controller->displayThumbnail);
		$bv = new BlockView($bt);
		ob_start();
		$bv->render('view');
		$content = ob_get_contents();
		ob_end_clean();

		return $this->app->make(ResponseFactoryInterface::class)->create($content);
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
			case 'recently_rated_desc':
				$this->list->sortByMostRecentlyRated();
				break;
            default:
                $this->list->sortByPublicDateDescending();
                break;
        }

        $now = $this->app->make('helper/date')->toDB();
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

        $c = $this->getRequest()->getCurrentPage();
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
            $uID = 0;
            $user = $this->app->make(User::class);
            if ($user->isRegistered()) {
                $uID = $user->getUserID();
            }
            $this->list->filterByUserRated($uID);
        }

        if ($this->miniNumOfRatings && $this->numOfRatings) {
            $this->list->filterByMinimumNumberOfRatings($this->numOfRatings);
        }

        return $this->list;
    }

    public function getPassThruActionAndParameters($parameters): array
    {
	    if ($parameters[0] == 'preview_pane') {
		    $method = 'action_' . $parameters[0];
		    $parameters = array_slice($parameters, 1);
		    return [$method, $parameters];
	    }
    	
        if ($parameters[0] == 'rate') {
            $method = 'action_rate';
            $parameters = array_slice($parameters, 1);
        } elseif ($parameters[0] == 'get_ratings') {
            $method = 'action_get_ratings';
            $parameters = array_slice($parameters, 1);
        } elseif ($parameters[0] == 'tag') {
            $method = 'action_filter_by_tag';
            $parameters = array_slice($parameters, 1);
        } elseif ($this->app->make('helper/validation/numbers')->integer($parameters[0])) {
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

    public function isValidControllerTask($method, $parameters = []): bool
    {
	    if ($method == 'action_preview_pane') {
		    return true;
	    }
    	
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

    /**
     * A simple hack to fix the PHP8 compatibility issue with core.
     * @See https://github.com/concretecms/concretecms/pull/11017/files
     */
    public function post($field = false, $defaultValue = null)
    {
        // the only post that matters is the one for this attribute's name space
        $req = ($this->requestArray == false) ? $_POST : $this->requestArray;
        if (isset($req['_bf']) && is_array($req['_bf'])) {
            $identifier = $this->identifier;
            $b = $this->getBlockObject();
            if (is_object($b)) {
                $xc = $b->getBlockCollectionObject();
                if (is_object($xc)) {
                    $identifier .= '_' . $xc->getCollectionID();
                }
            }

            $p = $req['_bf'][$identifier];
            if ($field) {
                return $p[$field];
            }

            return $p;
        }

        return RequestBase::post($field, $defaultValue);
    }
}
