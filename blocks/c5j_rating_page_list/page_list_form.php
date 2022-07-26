<?php
defined('C5_EXECUTE') or die('Access Denied.');
use Concrete\Package\C5jRatings\Block\C5jRatingPageList\Controller;
use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Attribute\Category;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\PageSelector;
use Concrete\Core\Entity\Page\Feed;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Form\Service\Widget\DateTime;

/** @var Controller $controller */
/** @var int $num */
/** @var string $orderBy */
/** @var int $cParentID */
/** @var bool $cThis */
/** @var bool $cThisParent */
/** @var bool $useButtonForLink */
/** @var string $buttonLinkText */
/** @var string $pageListTitle */
/** @var bool $filterByRelated */
    $filterByRelated = $filterByRelated ?? 0;
/** @var bool $filterByCustomTopic */
    $filterByCustomTopic = $filterByCustomTopic ?? 0;
/** @var bool $filterByUserRated */
    $filterByUserRated = $filterByUserRated ?? 0;
/** @var bool $miniNumOfRatings */
    $miniNumOfRatings = $miniNumOfRatings ?? 0;
/** @var int $numOfRatings */
    $numOfRatings = $numOfRatings ?? 0;
/** @var string $filterDateOption */
/** @var int $filterDateDays */
/** @var string $filterDateStart */
/** @var string $filterDateEnd */
/** @var string $relatedTopicAttributeKeyHandle */
/** @var string $customTopicAttributeKeyHandle */
/** @var int $customTopicTreeNodeID */
/** @var bool $includeName */
/** @var bool $includeDate */
/** @var bool $includeDescription */
/** @var bool $includeAllDescendents */
/** @var bool $paginate */
/** @var bool $displayAliases */
/** @var bool $ignorePermissions */
/** @var bool $enableExternalFiltering */
/** @var int $ptID */
/** @var int $truncateSummaries */
/** @var bool $displayFeaturedOnly */
/** @var string $noResultsMessage */
/** @var bool $displayThumbnail */
/** @var int $truncateChars */
/** @var BlockType $bt */
/** @var CollectionKey $featuredAttribute */
/** @var Category[] $attributeKeys */
/** @var BlockType $thumbnailAttribute */
/** @var bool $isOtherPage */
/** @var Feed $rssFeed */
/** @var string $btnType */
$btnType = $btnType ?? 'Heart';

if (!isset($filterDateDays)) {
    $filterDateDays = false;
}
if (!isset($filterDateStart)) {
    $filterDateStart = false;
}
if (!isset($filterDateEnd)) {
    $filterDateEnd = false;
}
if (!isset($ignorePermissions)) {
    $ignorePermissions = false;
}
if (!isset($isOtherPage)) {
    $isOtherPage = false;
}
if (!isset($rssFeed)) {
    $rssFeed = false;
}

$c = Page::getCurrentPage();

$siteType = null;

if ($c) {
    $pageType = $c->getPageTypeObject();

    if ($pageType) {
        $siteType = $pageType->getSiteTypeObject(); // gotta have this for editing defaults pages.
    } else {
        $tree = $c->getSiteTreeObject();
        if (is_object($tree)) {
            $siteType = $tree->getSiteType();
        }
    }
}

$app = Application::getFacadeApplication();

/** @var PageSelector $pageSelector */
$pageSelector = $app->make(PageSelector::class);
/** @var UserInterface $userInterface */
$userInterface = $app->make(UserInterface::class);
/** @var Form $form */
$form = $app->make(Form::class);
/** @var DateTime $dateTime */
$dateTime = $app->make(DateTime::class);

echo $userInterface->tabs([
    ['page-list-settings', t('Settings'), true],
    ['page-list-preview', t('Preview')],
]);
?>

<div class="ccm-tab-content tab-content" id="ccm-tab-content-page-list-settings">
    <div class="tab-pane active pagelist-form" id="page-list-settings" role="tabpanel">
        <input type="hidden" name="pageListPreviewPane" value="<?= h($controller->getActionURL('preview_pane')) ?>"/>
        <fieldset>
            <div class="form-group">
                <?php echo $form->label('num', t('Number of Pages to Display')); ?>
                <?php echo $form->number("num", $num); ?>
            </div>

            <div class="form-group">
                <?php
                    $pageTypes = ['0' => t('** All')];
                    /** @noinspection PhpUndefinedClassInspection */
                    foreach (PageType::getList(false, $siteType) as $pageType) {
                        /** @var Type $pageType */
                        $pageTypes[$pageType->getPageTypeID()] = $pageType->getPageTypeDisplayName();
                    }

                    echo $form->label('ptID', t('Page Type'));
                    echo $form->select("ptID", $pageTypes, $ptID);
                ?>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <?php echo $form->label('', t('Topics')); ?>
                <div class="form-check">
                    <?php echo $form->radio("topicFilter", "", $topicFilter, ["id" => "topicFilter", "name" => "topicFilter"]); ?>
                    <?php echo $form->label("topicFilter", t("No topic filtering"), ["class" => "form-check-label"]); ?>
                </div>
                <div class="form-check">
                    <?php echo $form->radio("topicFilter", "custom", $topicFilter, ["id" => "topicFilterCustom", "name" => "topicFilter"]); ?>
                    <?php echo $form->label("topicFilterCustom", t("Custom Topic"), ["class" => "form-check-label"]); ?>
                </div>
                <div class="form-check">
                    <?php echo $form->radio("topicFilter", "related", $topicFilter, ["id" => "topicFilterRelated", "name" => "topicFilter"]); ?>
                    <?php echo $form->label("topicFilterRelated", t("Related Topic"), ["class" => "form-check-label"]); ?>
                </div>
                
                
                <div data-row="custom-topic">
                    <select class="form-control" name="customTopicAttributeKeyHandle" id="customTopicAttributeKeyHandle">
                        <option value=""><?=t('Choose topics attribute.')?></option>
                        <?php foreach ($attributeKeys as $attributeKey) {
                    $attributeController = $attributeKey->getController(); ?>
                            <option data-topic-tree-id="<?=$attributeController->getTopicTreeID()?>" value="<?=$attributeKey->getAttributeKeyHandle()?>" <?php if ($attributeKey->getAttributeKeyHandle() == $customTopicAttributeKeyHandle) {
                        ?>selected<?php
                    } ?>><?=$attributeKey->getAttributeKeyDisplayName()?></option>
                            <?php
                } ?>
                    </select>
                    <div class="tree-view-container">
                        <div class="tree-view-template">
                        </div>
                    </div>
                    <input type="hidden" name="customTopicTreeNodeID" value="<?php echo $customTopicTreeNodeID ?>">

                </div>
                
                <div data-row="related-topic">
                    <span class="help-block">
                        <?=t('Allows other blocks like the topic list block to pass search criteria to this page list block.')?>
                    </span>
                    <?php
                        $relatedTopicAttributeKeyHandles = [
                                "" => t('Choose topics attribute.')
                        ];

                        foreach ($attributeKeys as $attributeKey) {
                            $relatedTopicAttributeKeyHandles[$attributeKey->getAttributeKeyHandle()] = $attributeKey->getAttributeKeyDisplayName();
                        }

                        echo $form->select("relatedTopicAttributeKeyHandle", $relatedTopicAttributeKeyHandles, $relatedTopicAttributeKeyHandle);
                    ?>
                    
                </div>
            </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?= t('Filter by Public Date') ?></label>
                <?php
                $filterDateOptions = [
                    'all' => t('Show All'),
                    'now' => t('Today'),
                    'past' => t('Before Today'),
                    'future' => t('After Today'),
                    'between' => t('Between'),
                ];
                $i = 0;

                foreach ($filterDateOptions as $filterDateOptionHandle => $filterDateOptionLabel) {
                    $isChecked = ($filterDateOption == $filterDateOptionHandle) ? 'checked' : ''; ?>
                    <div class="form-check">
                        <?php $id = "filterDateOption" . $i++; ?>
                        <?php echo $form->radio("filterDateOption", $filterDateOptionHandle, $filterDateOption, ["id" => $id, "name" => "filterDateOption", "class" => "form-check-input filterDateOption"]); ?>
                        <?php echo $form->label($id, $filterDateOptionLabel, ["class" => "form-check-label"]); ?>
                    </div>
                    <?php
                } ?>

                <div class="filterDateOptionDetail" data-filterDateOption="past">
                    <div class="form-group">
                        <label class="control-label"><?=t('Days in the Past')?> <i class="launch-tooltip fa fa-question-circle" title="<?=t('Leave 0 to show all past dated pages')?>"></i></label>
                        <?php echo $form->text("filterDatePast", $filterDateDays ? $filterDateDays : 0); ?>
                    </div>
                </div>

                <div class="filterDateOptionDetail" data-filterDateOption="future">
                    <div class="form-group">
                        <label class="control-label"><?=t('Days in the Future')?> <i class="launch-tooltip fa fa-question-circle" title="<?=t('Leave 0 to show all future dated pages')?>"></i></label>
                        <?php echo $form->text("filterDateFuture", $filterDateDays ? $filterDateDays : 0); ?>
                    </div>
                </div>

                <div class="filterDateOptionDetail" data-filterDateOption="between">
                    <?php
                    echo $dateTime->date('filterDateStart', $filterDateStart);
                    echo '<p>' . t('and') . '</p>';
                    echo $dateTime->date('filterDateEnd', $filterDateEnd);
                    ?>
                </div>

            </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <?php echo $form->label('', t('Rating Filters')); ?>

                <div class="form-check">
                    <?php echo $form->checkbox("filterByUserRated", "1", $filterByUserRated); ?>
                    <?php echo $form->label("filterByUserRated", t("Rated by me."), ["class" => "form-check-label"]); ?>
                </div>
                
                <?php
                    $config = $app->make('config');
                    $codeVersion = $config->get('concrete.version');
                    if (version_compare($codeVersion, '9.0.0', '<')) {
                        ?>
                        <div class="ccm-page-list-number-ratings">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <input id="ccm-pagelist-miniNumOfRatings" name="miniNumOfRatings" type="checkbox"
                                           value="1" <?= ($miniNumOfRatings ? 'checked="checked"' : '') ?> />
                                    <?= t('Minimum number of ratings.') ?>
                                </span>
                                <input class="form-control" id="ccm-pagelist-numOfRatings" <?= ($miniNumOfRatings ? '' : 'disabled="disabled"') ?>
                                       type="text" name="numOfRatings" size="3" value="<?=$numOfRatings ?>" />
                                <span class="input-group-addon">
                                    <?= t('ratings') ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="ccm-page-list-number-ratings">
                        <label class="control-label form-label">
                            <?php echo t('Minimum number of ratings.') ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <input id="ccm-pagelist-miniNumOfRatings" name="miniNumOfRatings" type="checkbox"
                                       value="1" <?= ($miniNumOfRatings ? 'checked="checked"' : '') ?> />
                            </span>
                                    
                                    <?php
                                        $miscFields = ["id" => "ccm-pagelist-numOfRatings", "name" => "numOfRatings", "size" => 3, "min" => 0];
                                        
                                        if (!$miniNumOfRatings) {
                                            $miscFields["disabled"] = "disabled";
                                        }
                                        
                                        echo $form->number("numOfRatings", $numOfRatings, $miscFields);
                                    ?>
    
                            <span class="input-group-text">
                                <?php echo t('ratings') ?>
                            </span>
                        </div>
                    </div>
                        <?php
                    }
                ?>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <?php echo $form->label('', t('Other Filters')); ?>

                <div class="form-check">

                    <?php
                        $miscFields = [
                                "style" => "vertical-align: middle",
                                "id" => "featuredPagesOnly",
                                "name" => "displayFeaturedOnly"
                        ];

                        if (!is_object($featuredAttribute)) {
                            $miscFields["disabled"] = "disabled";
                        }

                        echo $form->checkbox("displayFeaturedOnly", "1", $displayFeaturedOnly, $miscFields);
                        echo $form->label("featuredPagesOnly", t("Featured pages only."), ["class" => "form-check-label"]);
                    ?>

                    <?php if (!is_object($featuredAttribute)) { ?>
                        <div class="help-block">
                            <?php echo t(
                                    '(<strong>Note</strong>: You must create the "is_featured" page attribute first.)');
                            ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="form-check">
                    <?php echo $form->checkbox("displayAliases", "1", $displayAliases); ?>
                    <?php echo $form->label("displayAliases", t("Display page aliases."), ["class" => "form-check-label"]); ?>
                </div>
                <div class="form-check">
                    <?php echo $form->checkbox("ignorePermissions", "1", $ignorePermissions); ?>
                    <?php echo $form->label("ignorePermissions", t("Ignore page permissions."), ["class" => "form-check-label"]); ?>
                </div>
                <div class="form-check">
                    <?php echo $form->checkbox("enableExternalFiltering", "1", $enableExternalFiltering); ?>
                    <?php echo $form->label("enableExternalFiltering", t("Enable Other Blocks to Filter This Page List."), ["class" => "form-check-label"]); ?>
                </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <?php echo $form->label('', t('Pagination')); ?>

                <div class="form-check">
                    <?php echo $form->checkbox("paginate", "1", $paginate); ?>
                    <?php echo $form->label("paginate", t("Display pagination interface if more items are available than are displayed."), ["class" => "form-check-label"]); ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="form-group">
                <?php echo $form->label('', t('Location')); ?>

                <div class="form-check">
                    <?php echo $form->radio("cParentID", 0, $cParentID, ["id" => "cEverywhereField"]); ?>
                    <?php echo $form->label("cEverywhereField", t('Everywhere'), ["class" => "form-check-label"]); ?>
                </div>

                <div class="form-check">
                    <?php echo $form->radio("cParentID", $c->getCollectionID(), $cThis ? $c->getCollectionID() : null, ["id" => "cThisPageField"]); ?>
                    <?php echo $form->label("cThisPageField", t('Beneath this page'), ["class" => "form-check-label"]); ?>
                </div>

                <div class="form-check">
                    <?php echo $form->radio("cParentID", $c->getCollectionParentID(), $cThisParent ? $c->getCollectionParentID() : null, ["id" => "cThisParentField"]); ?>
                    <?php echo $form->label("cThisParentField", t('At the current level'), ["class" => "form-check-label"]); ?>
                </div>

                <div class="form-check">
                    <?php echo $form->radio("cParentID", 'OTHER', $isOtherPage ? 'OTHER' : false, ["id" => "cOtherField"]); ?>
                    <?php echo $form->label("cOtherField", t('Beneath another page'), ["class" => "form-check-label"]); ?>
                </div>

                <div class="ccm-page-list-page-other" <?php if (!$isOtherPage) {
                    ?> style="display: none" <?php
                } ?>>

                    <?php echo $pageSelector->selectPage('cParentIDValue', $isOtherPage ? $cParentID : false, ['askIncludeSystemPages' => true]); ?>
                </div>

                <div class="ccm-page-list-all-descendents"
                     style="<?php echo ($cParentID === 0) ? ' display: none;' : ''; ?>">

                    <div class="form-check">
                        <?php echo $form->checkbox("includeAllDescendents", '1', $includeAllDescendents, ["id" => "includeAllDescendents"]); ?>
                        <?php echo $form->label("includeAllDescendents", t('Include all child pages'), ["class" => "form-check-label"]); ?>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="form-group">
                <?php
                    echo $form->label("orderBy", t('Sort'));
                    echo $form->select("orderBy", [
                            "display_asc" => t('Sitemap order'),
                            "display_desc" => t('Reverse sitemap order'),
                            "chrono_desc" => t('Most recent first'),
                            "chrono_asc" => t('Earliest first'),
                            "alpha_asc" => t('Alphabetical order'),
                            "alpha_desc" => t('Reverse alphabetical order'),
                            "modified_desc" => t('Most recently modified first'),
                            "rated_desc" => t('Most rated first'),
                            "recently_rated_desc" => t('Most recently rated first'),
                            "random" => t('Random')
                    ], $orderBy);
                ?>
            </div>
        </fieldset>


    <fieldset>
        <legend>
            <?php echo t('Output') ?>
        </legend>

        <div class="form-group">
            <?php
                echo $form->label("btnType", t('Button Type'));
                echo $form->select("btnType", [
                        "clap" => t('Clap'),
                        "heart" => t('Heart'),
                        "like" => t('Like')
                ], $btnType);
            ?>
        </div>
        <div class="form-group">
            <label class="control-label"><?=t('Display the total rating?')?></label>
            <div class="radio">
                <label>
                    <input type="radio" id="displayRatings1" name="displayRatings" value="1" class="ccm-input-radio" checked="checked">
                    <?=t('Yes')?>
                </label>
            </div>
            <div class="radio">
                <label>
                    <input type="radio" id="displayRatings2" name="displayRatings" value="0" class="ccm-input-radio">
                    <?=t('No')?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label form-label">
                <?php echo t('Provide RSS Feed') ?>
            </label>

            <div class="form-check">
                <?php echo $form->radio("rss", "0", is_object($rssFeed) ? '1' : '0', ["id" => "disableRssFeed", "name" => "rss", "class" => "form-check-input rssSelector"]); ?>
                <?php echo $form->label("disableRssFeed", t("No"), ["class" => "form-check-label"]); ?>
            </div>

            <div class="form-check">
                <?php echo $form->radio("rss", "1", is_object($rssFeed) ? '1' : '0', ["id" => "enableRssFeed", "name" => "rss", "class" => "form-check-input rssSelector"]); ?>
                <?php echo $form->label("enableRssFeed", t("Yes"), ["class" => "form-check-label"]); ?>
            </div>

            <div id="ccm-pagelist-rssDetails" <?php echo(is_object($rssFeed) ? '' : 'style="display:none;"') ?>>
                <?php if (is_object($rssFeed)) { ?>
                    <?php echo t('RSS Feed can be found here: %s', '<a href="' . h($rssFeed->getFeedURL()) . '" target="_blank">' . $rssFeed->getFeedURL() . '</a>') ?>
                <?php } else { ?>
                    <div class="form-group">
                        <?php echo $form->label('num', t('RSS Feed Title')); ?>
                        <?php echo $form->text("rssTitle", $num, ["id" => "ccm-pagelist-rssTitle", "name" => "rssTitle"]); ?>
                    </div>

                    <div class="form-group">
                        <?php echo $form->label("rssDescription", ('RSS Feed Description')); ?>
                        <?php echo $form->textarea("rssDescription"); ?>
                    </div>

                    <div class="form-group">
                        <?php echo $form->label('', t('RSS Feed Location')); ?>

                        <div class="input-group">
                            <span class="input-group-text">
                                <?php echo (string)Url::to('/rss') ?>/
                            </span>

                            <?php echo $form->text("rssHandle"); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label form-label">
                <?php echo t('Include Page Name') ?>
            </label>

            <div class="form-check">
                <?php echo $form->radio("includeName", "0", $includeName ? '1' : '0', ["id" => "disableIncludeName", "name" => "includeName"]); ?>
                <?php echo $form->label("disableIncludeName", t("No"), ["class" => "form-check-label"]); ?>
            </div>

            <div class="form-check">
                <?php echo $form->radio("includeName", "1", $includeName ? '1' : '0', ["id" => "enableIncludeName", "name" => "includeName"]); ?>
                <?php echo $form->label("enableIncludeName", t("Yes"), ["class" => "form-check-label"]); ?>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label form-label">
                <?php echo t('Include Page Description') ?>
            </label>

            <div class="form-check">
                <?php echo $form->radio("includeDescription", "0", $includeDescription ? '1' : '0', ["id" => "disableIncludeDescription", "name" => "includeDescription"]); ?>
                <?php echo $form->label("disableIncludeDescription", t("No"), ["class" => "form-check-label"]); ?>
            </div>

            <div class="form-check">
                <?php echo $form->radio("includeDescription", "1", $includeDescription ? '1' : '0', ["id" => "enableIncludeDescription", "name" => "includeDescription"]); ?>
                <?php echo $form->label("enableIncludeDescription", t("Yes"), ["class" => "form-check-label"]); ?>
            </div>

            <div class="ccm-page-list-truncate-description" <?= ($includeDescription ? '' : 'style="display:none;"') ?>>
                <label class="control-label form-label"><?=t('Display Truncated Description')?></label>
                <div class="input-group">
	
				<?php
					$config = $app->make('config');
					$codeVersion = $config->get('concrete.version');
					if (version_compare($codeVersion, '9.0.0', '<')) {
						?>
                        <span class="input-group-addon">
                            <input id="ccm-pagelist-truncateSummariesOn" name="truncateSummaries" type="checkbox"
                                   value="1" <?= ($truncateSummaries ? 'checked="checked"' : '') ?> />
                        </span>
                        <input class="form-control" id="ccm-pagelist-truncateChars" <?= ($truncateSummaries ? '' : 'disabled="disabled"') ?>
                               type="text" name="truncateChars" size="3" value="<?= (int) $truncateChars ?>" />
                        <span class="input-group-addon">
                            <?= t('characters') ?>
                        </span>
						<?php
					} else {
						?>
                        <span class="input-group-text">
                            <input id="ccm-pagelist-truncateSummariesOn" name="truncateSummaries" type="checkbox"
                                   value="1" <?php echo($truncateSummaries ? 'checked="checked"' : '') ?> />
                        </span>
							
							<?php
								$miscFields = ["id" => "ccm-pagelist-truncateChars", "name" => "truncateChars", "step" => 1, "min" => 0];
								
								if (!$truncateSummaries) {
									$miscFields["disabled"] = "disabled";
								}
								
								echo $form->number("truncateChars", (int)$truncateChars, $miscFields);
							?>

                            <span class="input-group-text">
                            <?php echo t('characters') ?>
                        </span>
						<?php
					}
				?>
                </div>
            </div>
    
            <div class="form-group">
                <?php echo $form->label('', t('Include Public Page Date')); ?>
    
                <div class="form-check">
                    <?php echo $form->radio("includeDate", "0", $includeDate ? '1' : '0', ["id" => "disableIncludeDate", "name" => "includeDate"]); ?>
                    <?php echo $form->label("disableIncludeDate", t("No"), ["class" => "form-check-label"]); ?>
                </div>
    
                <div class="form-check">
                    <?php echo $form->radio("includeDate", "1", $includeDate ? '1' : '0', ["id" => "enableIncludeDate", "name" => "includeDate"]); ?>
                    <?php echo $form->label("enableIncludeDate", t("Yes"), ["class" => "form-check-label"]); ?>
                </div>
    
                <div class="help-block">
                    <?php echo t('This is usually the date the page is created. It can be changed from the page attributes panel.') ?>
                </div>
            </div>
    
            <div class="form-group">
                <label class="control-label form-label">
                    <?php echo t('Display Thumbnail Image') ?>
                </label>
    
                <div class="form-check">
                    <?php echo $form->radio("displayThumbnail", "0", $displayThumbnail ? '1' : '0', ["id" => "disableIncludeThumbnail", "name" => "displayThumbnail"]); ?>
                    <?php echo $form->label("disableIncludeThumbnail", t("No"), ["class" => "form-check-label"]); ?>
                </div>
    
                <div class="form-check">
                    <?php echo $form->radio("displayThumbnail", "1", $displayThumbnail ? '1' : '0', ["id" => "enableIncludeThumbnail", "name" => "displayThumbnail"]); ?>
                    <?php echo $form->label("enableIncludeThumbnail", t("Yes"), ["class" => "form-check-label"]); ?>
                </div>
    
                <?php if (!is_object($thumbnailAttribute)) { ?>
                    <div class="help-block">
                        <?php echo t('You must create an attribute with the \'thumbnail\' handle in order to use this option.') ?>
                    </div>
                <?php } ?>
            </div>
    
            <div class="form-group">
                <label class="control-label form-label">
                    <?php echo t('Use Different Link than Page Name') ?>
                </label>
    
                <div class="form-check">
                    <?php echo $form->radio("useButtonForLink", "0", $useButtonForLink ? '1' : '0', ["id" => "disableUseButtonForLink", "name" => "useButtonForLink"]); ?>
                    <?php echo $form->label("disableUseButtonForLink", t("No"), ["class" => "form-check-label"]); ?>
                </div>
    
                <div class="form-check">
                    <?php echo $form->radio("useButtonForLink", "1", $useButtonForLink ? '1' : '0', ["id" => "enableUseButtonForLink", "name" => "useButtonForLink"]); ?>
                    <?php echo $form->label("enableUseButtonForLink", t("Yes"), ["class" => "form-check-label"]); ?>
                </div>
    
                <div class="ccm-page-list-button-text" <?php echo($useButtonForLink ? '' : 'style="display:none;"') ?>>
                    <div class="form-group">
                        <?php echo $form->label('buttonLinkText', t('Link Text')); ?>
                        <?php echo $form->text("buttonLinkText", $buttonLinkText); ?>
                    </div>
                </div>
            </div>
    
            <div class="form-group">
                <?php echo $form->label('pageListTitle', t('Title of Page List')); ?>
                
                    <?php
                        $config = $app->make('config');
                        $codeVersion = $config->get('concrete.version');
                        if (version_compare($codeVersion, '9.0.0', '>')) {
                            ?>
                            <div class="input-group">
                                <?php echo $form->text("pageListTitle", $pageListTitle); ?>
                                <?php echo $form->select('titleFormat', \Concrete\Core\Block\BlockController::$btTitleFormats, $titleFormat, array('style' => 'width:105px;flex-grow:0;', 'class' => 'form-select')); ?>
                            </div>
                            <?php
                        } else {
							echo $form->text("pageListTitle", $pageListTitle);
                        }
                    ?>
                
            </div>
    
            <div class="form-group">
                <?php echo $form->label("noResultsMessage", ('Message to Display When No Pages Listed.')); ?>
                <?php echo $form->textarea("noResultsMessage", $noResultsMessage); ?>
            </div>
    
            <div class="loader">
                <i class="fas fa-cog fa-spin"></i>
            </div>
        </fieldset>
    </div>


    <div class="tab-pane" id="page-list-preview" role="tabpanel">
        <div class="render">

        </div>

        <div class="cover"></div>
    </div>
</div>

<style type="text/css">

    div.pagelist-form div.cover {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
    }

    div.pagelist-form div.render .ccm-page-list-title {
        font-size: 12px;
        font-weight: normal;
    }

    div.pagelist-form label.checkbox,
    div.pagelist-form label.radio {
        font-weight: 300;
    }
    
    #ccm-tab-content-page-list-settings {
        display: block !important;
    }
</style>
<script type="application/javascript">
    Concrete.event.publish('pagelist.edit.open');
    $(function() {
        $("ul.nav.nav-tabs > li > a").on("click", function() {
            if ($(this).attr("data-tab") == 'page-list-preview' || $(this).attr("id") == "page-list-preview-tab") {
                $("#page-list-preview").addClass("active");
                $("#page-list-settings").removeClass("active");
            } else {
                $("#page-list-preview").removeClass("active");
                $("#page-list-settings").addClass("active");
            }
        });
        
        $('input[name=topicFilter]').on('change', function() {
            if ($(this).val() == 'related') {
                $('div[data-row=related-topic]').show();
                $('div[data-row=custom-topic]').hide();
            } else if ($(this).val() == 'custom') {
                $('div[data-row=custom-topic]').show();
                $('div[data-row=related-topic]').hide();
            } else {
                $('div[data-row=related-topic]').hide();
                $('div[data-row=custom-topic]').hide();
            }
        });

        var treeViewTemplate = $('.tree-view-template');

        $('select[name=customTopicAttributeKeyHandle]').on('change', function() {
            var chosenTree = $(this).find('option:selected').attr('data-topic-tree-id');
            $('.tree-view-template').remove();
            if (!chosenTree) {
                return;
            }
            $('.tree-view-container').append(treeViewTemplate);
            $('.tree-view-template').concreteTree({
                'treeID': chosenTree,
                'chooseNodeInForm': true,
                'selectNodesByKey': [<?=(int) $customTopicTreeNodeID?>],
                'onSelect' : function(nodes) {
                    if (nodes.length) {
                        $('input[name=customTopicTreeNodeID]').val(nodes[0]);
                    } else {
                        $('input[name=customTopicTreeNodeID]').val('');
                    }
                    Concrete.event.publish('pagelist.topictree.select');
                }
            });
        });
        $('input[name=topicFilter]:checked').trigger('change');
        if ($('#topicFilterCustom').is(':checked')) {
            $('select[name=customTopicAttributeKeyHandle]').trigger('change');
        }
    });

</script>

