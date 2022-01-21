<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 */
$btnType = $btnType ?? 'clap';
?>

<div>
    <span class="<?= $btnType ?>-btn"></span>
    <?php if ($displayRatings): ?>
        <span class="ratings <?= $btnType ?>" id="<?= $bID ?>"><?= $ratings ?? 0 ?></span>
    <?php endif; ?>
    <input type="hidden" name="<?= $bID ?>" value="<?= $btnType ?>">
</div>


<script>
    $(document).ready(function () {
        let uID = getUserID();
        isRatedByPage(uID);
        let $el = $('.<?= $btnType ?>-btn');
        let activeClass = '<?= $btnType ?>-active';

        $($el).on('click', function() {
            const value = $el.hasClass(activeClass) ? 0 : 1;
            rateIt(uID, value);
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

    function rateIt(uID, value) {
        $.ajax({
            url: "<?= URL::to($view->action('rate')) ?>",
            type: 'post',
            data: {
                token: "<?= Core::make('token')->generate('rate') ?>",
                uID: uID,
                ratedValue: value
            },
            success: function(data) {
                $('.ratings').each(function() {
                    $(this).text(data['ratings']);
                    let btnType = $(this).attr("class").split(' ')[1];
                    if(parseInt(value) === 1){
                        $(this).siblings("."+btnType+"-btn").addClass(btnType+"-active");
                    }else{
                        $(this).siblings("."+btnType+"-btn").removeClass(btnType+"-active");
                    }
                });
            }
        });
    }

    function isRatedByPage(uID) {
        let isRated = false;
        $.ajax({
            url: "<?= URL::to($view->action('is_rated')) ?>",
            type: 'post',
            async: false,
            data: {
                token: "<?= Core::make('token')->generate('is_rated') ?>",
                uID: uID,
            },
            success: function(data) {
                isRated = data['isRated'];
                $('.ratings').each(function() {
                    $(this).text(data['ratings']);
                    let btnType = $(this).attr("class").split(' ')[1];
                    if(isRated === true){
                        $(this).siblings("."+btnType+"-btn").addClass(btnType+"-active");
                    }else{
                        $(this).siblings("."+btnType+"-btn").removeClass(btnType+"-active");
                    }
                });
            }
        });
    }
</script>