<?php use app\services\{Ui,EmployeePhoto};
$this->registerJsFile(Yii::$app->request->baseUrl.'/js/employee-photo.js?v=2');
$profile=json_decode($person->hemis_profile??'{}',true)?:[];$photo=EmployeePhoto::url($profile['photo_url']??null);
?>
<span class="employee-photo"><?php if($photo): ?><img data-employee-photo src="<?= Ui::h(\yii\helpers\Url::to(['kpi/employee-photo','id'=>$person->id])) ?>" data-photo-fallback="<?= Ui::h($photo) ?>" width="72" height="72" style="object-fit:cover;border-radius:8px" alt="<?= Ui::h($person->name) ?> rasmi" loading="lazy" referrerpolicy="no-referrer"><span hidden>Rasm yo‘q</span><?php else: ?><span>Rasm yo‘q</span><?php endif ?></span>
