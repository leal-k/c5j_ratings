<?php
defined('C5_EXECUTE') or die('Access Denied.');

// TODO::Display the rating button on view

$c = Page::getCurrentPage();

/** @var \Concrete\Core\Utility\Service\Text $th */
$th = Core::make('helper/text');
/** @var \Concrete\Core\Localization\Service\Date $dh */
$dh = Core::make('helper/date');

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
                <h5><?php echo h($pageListTitle) ?></h5>
            </div>
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

            if(isset($pages)){

            foreach ($pages as $page) {

                $c = \Concrete\Core\Page\Page::getByID($page['cID'], 'ACTIVE');
                if (is_object($c)) {
                    $buttonClasses = 'ccm-block-page-list-read-more';
                    $entryClasses = 'ccm-block-page-list-page-entry';
                    $title = $c->getCollectionName();
                    if ($c->getCollectionPointerExternalLink() != '') {
                        $url = $c->getCollectionPointerExternalLink();
                        if ($c->openCollectionPointerExternalLinkInNewWindow()) {
                            $target = '_blank';
                        }
                    } else {
                        $url = $c->getCollectionLink();
                        $target = $c->getAttribute('nav_target');
                    }
                    $target = empty($target) ? '_self' : $target;
                    $description = $c->getCollectionDescription();
                    $description = $controller->truncateSummaries ? $th->wordSafeShortText($description, $controller->truncateChars) : $description;
                    $thumbnail = false;
                    if ($displayThumbnail) {
                        $thumbnail = $c->getAttribute('thumbnail');
                    }
                    if (is_object($thumbnail) && $includeEntryText) {
                        $entryClasses = 'ccm-block-page-list-page-entry-horizontal';
                    }

                    $date = $dh->formatDateTime($c->getCollectionDatePublic(), true);
                }
                ?>
                <div class="<?php echo $entryClasses ?>">

                    <?php if (is_object($thumbnail)) {
                        ?>
                        <div class="ccm-block-page-list-page-entry-thumbnail">
                            <?php
                            $img = Core::make('html/image', [$thumbnail]);
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
                                    <?php
                                    $displayRatings = $displayRatings ?? "";
                                    $btnType = $btnType ?? "";
                                    if($displayRatings){
                                        if($btnType){
                                            $active = $controller->getRatedValue($page['cID']) === 1 ? $btnType . '-active' : '';
                                        ?>
                                        <div>
                                            <span class="<?= $btnType ?>-btn <?= $active ?>" id="btn-<?= $page['cID'] ?>" onclick="isPageRatedBy(<?= $page['cID'] ?>)"></span>
                                                <span class="ratings-<?= $page['cID'] ?>" id="<?=$btnType?>"><?= $page['ratings'] ?? 0 ?></span>
                                        </div>
                                        <?php
                                             }
                                        }
                                        ?>
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

                        </div>
                        <?php
                    } ?>
                </div>

                <?php
            }
            }?>
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
    function isPageRatedBy(cID) {
        let uID = getUserID();
        let btnType = $(".ratings-"+cID).attr("id");
        let ratedValue = 1;
        if($("#btn-"+cID).hasClass(btnType+"-active")) {
            ratedValue = 0;
        }

        pageRateIt(uID, cID, ratedValue, btnType);
    }

    function getUserID() {
        let uID = "<?= Core::make('user')->getUserID() ?>";
        if (!uID) {
            const client = new ClientJS();
            uID = client.getFingerprint();
        }

        return uID;
    }

    function pageRateIt(uID, cID, ratedValue, btnType) {
        $.ajax({
            url: "<?= $view->action('rate_page')?>",
            type: 'post',
            data: {
                token: "<?= Core::make('token')->generate('rate_page') ?>",
                uID: uID,
                cID: cID,
                ratedValue: ratedValue
            },
            success: function(data) {
                $(".ratings-"+cID).text(data['ratings']);
                if(ratedValue === 0){
                    $("#btn-"+cID).removeClass(btnType+"-active");
                }else{
                    $("#btn-"+cID).addClass(btnType+"-active");
                }
            }
        });


    }


</script>