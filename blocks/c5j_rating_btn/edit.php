<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * @author: Biplob Hossain <biplob.ice@gmail.com>
 * @license MIT
 *
 * @var \Concrete\Core\Form\Service\Form $form
 */
/** @var string $btnType */
$btnType = $btnType ?? 'Heart';
/** @var bool $displayRatings */
$displayRatings = $displayRatings ?? 0;
?>
<fieldset>
    <div class="form-group">
        <?php echo $form->label('btnType', t('Button Type')); ?>
        <?php echo $form->select('btnType', ['clap' => t('Clap'), 'heart' => t('Heart'), 'like' => t('Like')], $btnType); ?>
    </div>

    <div class="form-group">
        <label class="control-label"><?= t('Display the total rating?') ?></label>
        <div class="radio">
            <label><?= $form->radio('displayRatings', 1, $displayRatings) ?> <?= t('Yes') ?></label>
        </div>
        <div class="radio">
            <label><?= $form->radio('displayRatings', 0, $displayRatings) ?><?= t('No') ?></label>
        </div>
    </div>
</fieldset>
