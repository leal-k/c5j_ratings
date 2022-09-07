<?php
defined('C5_EXECUTE') or die('Access Denied.');
use Concrete\Core\Page\Page;

$c = Page::getCurrentPage();
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
/** @var \Concrete\Core\Utility\Service\Text $th */
$th = $app->make('helper/text');
/** @var \Concrete\Core\Localization\Service\Date $dh */
$dh = $app->make('helper/date');
$pages = $pages ?? [];
$showPagination = $showPagination ?? [];

if (is_object($c) && $c->isEditMode() && $controller->isBlockEmpty()) {
    ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty C5j Rating Page List Block.') ?></div>
    <?php
} else {
        ?>

    <div class="ccm-block-page-list-wrapper">

        <?php if (isset($pageListTitle) && $pageListTitle) {
            ?>
            <div class="ccm-block-page-list-header">
            <?php
            $config = $app->make('config');
            $codeVersion = $config->get('concrete.version');
            if (version_compare($codeVersion, '9.0.0', '>')) {
                ?>
                <<?php echo $titleFormat; ?>><?php echo h($pageListTitle) ?></<?php echo $titleFormat; ?>>
                <?php
            } else {
            ?>
                <h5><?php echo h($pageListTitle) ?></h5>
            <?php
            }
            ?>
            </div>
            <?php
        } ?>

        <?php if (isset($rssUrl) && $rssUrl) {
            ?>
            <a href="<?php echo $rssUrl ?>" target="_blank" class="ccm-block-page-list-rss-feed">
                <i class="fa fa-rss"></i>
            </a>
            <?php
        } ?>

        <div class="ccm-block-page-list-pages">

            <?php

            $includeEntryText = false;
        if (
                (isset($includeName) && $includeName)
                ||
                (isset($includeDescription) && $includeDescription)
                ||
                (isset($useButtonForLink) && $useButtonForLink)
            ) {
            $includeEntryText = true;
        }

        foreach ($pages as $page) {

                // Prepare data for each page being listed...
            $buttonClasses = 'ccm-block-page-list-read-more';
            $entryClasses = 'ccm-block-page-list-page-entry';
            $title = $page->getCollectionName();
            if ($page->getCollectionPointerExternalLink() != '') {
                $url = $page->getCollectionPointerExternalLink();
                if ($page->openCollectionPointerExternalLinkInNewWindow()) {
                    $target = '_blank';
                }
            } else {
                $url = $page->getCollectionLink();
                $target = $page->getAttribute('nav_target');
            }
            $target = empty($target) ? '_self' : $target;
            $description = $page->getCollectionDescription();
            $description = $controller->truncateSummaries ? $th->wordSafeShortText($description, $controller->truncateChars) : $description;
            $thumbnail = false;
            if ($displayThumbnail) {
                $thumbnail = $page->getAttribute('thumbnail');
            }
            if (is_object($thumbnail) && $includeEntryText) {
                $entryClasses = 'ccm-block-page-list-page-entry-horizontal';
            }

            $date = $dh->formatDateTime($page->getCollectionDatePublic(), true);

            //Other useful page data...

            //$last_edited_by = $page->getVersionObject()->getVersionAuthorUserName();

            /* DISPLAY PAGE OWNER NAME
             * $page_owner = UserInfo::getByID($page->getCollectionUserID());
             * if (is_object($page_owner)) {
             *     echo $page_owner->getUserDisplayName();
             * }
             */

            /* CUSTOM ATTRIBUTE EXAMPLES:
             * $example_value = $page->getAttribute('example_attribute_handle', 'display');
             *
             * When you need the raw attribute value or object:
             * $example_value = $page->getAttribute('example_attribute_handle');
             */

            /* End data preparation. */

            /* The HTML from here through "endforeach" is repeated for every item in the list... */ ?>

                <div class="<?php echo $entryClasses ?>">

                    <?php if (is_object($thumbnail)) {
                ?>
                        <div class="ccm-block-page-list-page-entry-thumbnail">
                            <?php
                            $img = $app->make('html/image', ['f' => $thumbnail]);
                $tag = $img->getTag();
                $tag->addClass('img-responsive');
                echo $tag; ?>
                        </div>
                        <?php
            } ?>

                    <?php if ($includeEntryText) {
                ?>
                        <div class="ccm-block-page-list-page-entry-text">

                            <?php if (isset($includeName) && $includeName) {
                    ?>
                                <div class="ccm-block-page-list-title">
                                    <?php if (isset($useButtonForLink) && $useButtonForLink) {
                        ?>
                                        <?php echo h($title); ?>
                                        <?php
                    } else {
                        ?>
                                        <a href="<?php echo h($url) ?>"
                                           target="<?php echo h($target) ?>"><?php echo h($title) ?></a>
                                        <?php
                    } ?>
                                </div>
                                <?php
                } ?>

                            <?php if (isset($includeDate) && $includeDate) {
                    ?>
                                <div class="ccm-block-page-list-date"><?php echo h($date) ?></div>
                                <?php
                } ?>

                            <?php if (isset($includeDescription) && $includeDescription) {
                    ?>
                                <div class="ccm-block-page-list-description"><?php echo h($description) ?></div>
                                <?php
                } ?>

                            <?php if (isset($useButtonForLink) && $useButtonForLink) {
                    ?>
                                <div class="ccm-block-page-list-page-entry-read-more">
                                    <a href="<?php echo h($url) ?>" target="<?php echo h($target) ?>"
                                       class="<?php echo h($buttonClasses) ?>"><?php echo h($buttonLinkText) ?></a>
                                </div>
                                <?php
                } ?>

                            <?php if ($btnType) {
                    $cID = $page->getCollectionID();
                    $ratingBtnID = sprintf('rating-%d-%d', $bID, $cID); ?>
                                <div class="ratings-wrapper">
                                    <span id="<?= $ratingBtnID ?>" class="rating-<?= $cID ?> <?= $btnType ?>-btn" data-btn-type="<?= $btnType ?>" onclick="addRating($(this), <?= $cID ?>)"></span>
                                    <?php if ($displayRatings) { ?>
                                        <span class="num-ratings"><?= $ratings['ratings'] ?? 0 ?></span>
                                    <?php } ?>
                                    <input type="hidden" name="pageIDs[]" value="<?= $cID ?>">
                                </div>
                                <?php
                } ?>
                        </div>
                        <?php
            } ?>
                </div>

                <?php
        } ?>
        </div><!-- end .ccm-block-page-list-pages -->

        <?php if (count($pages) == 0) { ?>
            <div class="ccm-block-page-list-no-pages"><?php echo h($noResultsMessage) ?></div>
        <?php } ?>

    </div><!-- end .ccm-block-page-list-wrapper -->


    <?php if ($showPagination) { ?>
        <?php echo $pagination; ?>
    <?php } ?>

    <?php
    } ?>
<script>
    $(document).ready(function () {
        let getUrl = "<?= URL::to($view->action('get_ratings')) ?>";
        let params = {
            token: "<?= $app->make('token')->generate('rating') ?>",
            uID: getUserID(),
        };

        $('input[name^="pageIDs"]').each(function() {
            params['cID'] = this.value;
            getRatings(getUrl, params);
        });
    });

    function getUserID() {
        let uID = "<?= $app->make('user')->getUserID() ?>";
        if (!uID) {
            const client = new ClientJS();
            uID = client.getFingerprint();
        }

        return uID;
    }

    function addRating(elem, cID) {
        let addUrl = "<?= URL::to($view->action('rate')) ?>";
        let btnType = elem.data('btn-type');
        let activeClass = btnType + '-active';
        let params = {
            token: "<?= $app->make('token')->generate('rating') ?>",
            uID: getUserID(),
            cID: cID,
            ratedValue: elem.hasClass(activeClass) ? 0 : 1
        }
        updateRatings(addUrl, params);
    }
</script>