<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 *
 * @license MIT
 */
$btnType = $btnType ?? 'clap';
$cID = Page::getCurrentPage()->getCollectionID();
$ratingBtnID = sprintf('rating-%d-%d', $bID, $cID);
?>

<div class="ratings-wrapper">
    <span id="<?= $ratingBtnID ?>" class="rating-<?= $cID ?> <?= $btnType ?>-btn" data-btn-type="<?= $btnType ?>" onclick="addRating($(this), <?= $cID ?>)"></span>
    <?php if ($displayRatings) { ?>
        <span class="num-ratings"><?= $ratings['ratings'] ?? 0 ?></span>
    <?php } ?>
</div>

<script>
    $(document).ready(function () {
        let getUrl = "<?= URL::to($view->action('get_ratings')) ?>";
        let params = {
            token: "<?= Core::make('token')->generate('rating') ?>",
            uID: getUserID(),
            cID: '<?= $cID ?>',
        };
        getRatings(getUrl, params);
    });

    function getUserID() {
        let uID = "<?= Core::make('user')->getUserID() ?>";
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
            token: "<?= Core::make('token')->generate('rating') ?>",
            uID: getUserID(),
            cID: cID,
            ratedValue: elem.hasClass(activeClass) ? 0 : 1
        }
        updateRatings(addUrl, params);
    }
</script>