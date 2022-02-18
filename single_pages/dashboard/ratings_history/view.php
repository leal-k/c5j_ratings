<?php
defined('C5_EXECUTE') or die('Access Denied.');
use Concrete\Core\User\UserInfoRepository;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
/* @var Concrete\Core\Form\Service\Form $form */
$form = $app->make('helper/form');
/* @var Concrete\Core\Validation\CSRF\Token $token */
$token = $app->make('helper/validation/token');
?>
<div class="ccm-dashboard-header-buttons">
    <a id="ccm-export-results" class="btn btn-success" href="<?= $view->action('csv_export', $token->generate('export')) ?>?<?=$query?>">
        <i class="fa fa-download"></i> <?php echo t('Export to CSV'); ?>
    </a>
</div>
<form action="<?php echo $view->action('search_ratings'); ?>" class="ccm-search-fields">
        <?php echo $token->output('search_ratings'); ?>
    <div class="form-group row">
        <div class="col-md-4 pull-right">
            <?=$form->label('rated_date', t('Rated Date')); ?>
            <div class="ccm-search-field-content">
                <?= $app->make('helper/form/date_time')->date('rated_date', $rated_date) ?>
            </div>
        </div>
    </div>
    <div class="ccm-search-fields-submit">
        <button type="submit" class="btn btn-primary pull-right"><?php echo t('Search'); ?></button>
    </div>
    </form>
<div class="ccm-dashboard-content-full">
<?php
if(isset($ratingsList, $ratings) && count($ratings) > 0){
?>

    <div class="table-responsive">
            <table class="ccm-search-results-table">
                <thead>
                    <tr>
                        <th class="<?=$ratingsList->getSortClassName('r.cID')?>"><a href="<?=$ratingsList->getSortURL('r.cID')?>">Page Name</a></th>
                        <th class="<?=$ratingsList->getSortClassName('r.uID')?>"><a href="<?=$ratingsList->getSortURL('r.uID')?>">User</a></th>
                        <th class="<?=$ratingsList->getSortClassName('r.ratedAt')?>"><a href="<?=$ratingsList->getSortURL('r.ratedAt')?>">ratedAt</a></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($ratings as $rating) {
                        $page = \Concrete\Core\Page\Page::getByID($rating->getCID());
                        $ui = $app->make(UserInfoRepository::class)->getByID($rating->getUID());
                        if($ui){
                            $name = $ui->getUserName();
                        }else{
                            $name = 'unknown';
                        }
                    ?>
                        <tr>
                            <td><?=h($page->getCollectionName())?></td>
                            <td><?=h($name)?></td>
                            <td><?=h($rating->getRatedAt()->format('Y/m/d'))?></td>
                        </tr>
                <?php
                    }
                ?>
                </tbody>
            </table>
    </div>
    <div class="ccm-search-results-pagination">
        <?php
        if (isset($pagination) && is_object($pagination)) {
            echo $pagination->renderDefaultView();
        }
        ?>
    </div>
<?php
}else{
    echo "<p class='text-center'>No search results were found</p>";
}
?>
</div>
