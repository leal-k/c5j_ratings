<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */
$btnType = $btnType ?? 'clap';
?>

<div>
    <span class="<?= $btnType ?>-btn <?= $bID ?>"></span>
    <?php if ($displayRatings): ?>
        <span class="ratings" id="<?= $bID ?>"></span>
    <?php endif; ?>
</div>


<script>
    $(document).ready(function () {
        let uID = getUserID();
        let $el = $('.<?= $btnType ?>-btn');
        let activeClass = '<?= $btnType ?>-active';
        let bID = $el.siblings(".ratings").attr("id");
        $el.toggleClass(activeClass, isRatedBy(uID,bID));

        $($el).on('click', function() {
            $el.toggleClass(activeClass);
            const value = $el.hasClass(activeClass) ? 1 : 0;
            rateIt(uID, value ,bID);
        });
    });

    function getUserID() {
        let uID = "<?= Core::make('user')->getUserID() ?>";
        if (!uID) {
            const client = new ClientJS();
            uID = client.getFingerprint();
        }

        return uID;
    }

    function rateIt(uID, value, bID) {
        $.ajax({
            url: "<?= URL::to($view->action('rate')) ?>",
            type: 'post',
            data: {
                token: "<?= Core::make('token')->generate('rate') ?>",
                uID: uID,
                ratedValue: value,
                bID: bID
            },
            success: function(data) {
                $("#"+bID).text(data['ratings']);
            }
        });
    }

    function isRatedBy(uID,bID) {
        let isRated = false;
        $.ajax({
            url: "<?= URL::to($view->action('is_rated')) ?>",
            type: 'post',
            async: false,
            data: {
                token: "<?= Core::make('token')->generate('is_rated') ?>",
                uID: uID,
                bID: bID,
            },
            success: function(data) {
                isRated = data['isRated'];
                $("#"+bID).text(data['ratings']);
            }
        });

        return isRated;
    }
</script>