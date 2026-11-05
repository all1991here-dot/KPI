<?php use yii\helpers\Html;use app\services\Ui;$this->title='Kirish · FarDU KPI';$hasPhoto=is_file(Yii::getAlias('@app/web/images/fardu-campus.jpg')); ?>
<section class="fardu-login-card">
<div class="fardu-photo-panel<?= $hasPhoto?' has-campus-photo':'' ?>">
<?php if($hasPhoto): ?><img class="fardu-campus-photo" src="<?= Ui::h(Yii::$app->request->baseUrl) ?>/images/fardu-campus.jpg?v=3" alt="Farg‘ona davlat universitetining kirish darvozasi"><?php endif ?>
<div class="fardu-photo-shade"></div><div class="fardu-photo-caption"><span class="academic-line"></span><h1>Ilm. Mehnat.<br>Natija.</h1><p>FarDU professor-o‘qituvchilari<br>faoliyatini baholash tizimi</p></div>
</div>
<div class="fardu-login-form"><div class="login-card"><div class="fardu-login-brand"><?= Ui::brandLogo() ?></div><span class="login-eyebrow">XODIM KABINETI</span><h2>Xush kelibsiz!</h2><p class="login-description">Davom etish uchun hisobingizga kiring.</p>
<?php if($error): ?><div role="alert" class="alert error"><?= Ui::h($error) ?></div><?php endif ?>
<?= Html::beginForm(['auth/login'],'post') ?>
<?= Ui::input('Login','username',Yii::$app->request->post('username',''),'text',['required'=>true,'autocomplete'=>'username','autofocus'=>true,'maxlength'=>100,'placeholder'=>'Loginingizni kiriting']) ?>
<?= Ui::input('Parol','password','','password',['required'=>true,'autocomplete'=>'current-password','maxlength'=>128,'placeholder'=>'Parolingizni kiriting']) ?>
<button class="button full" type="submit">Tizimga kirish <?= Ui::icon('arrow') ?></button><?= Html::endForm() ?>
<p class="login-help">Hisob yoki parol bo‘yicha KPI administratoriga murojaat qiling.</p></div></div></section>
<div class="login-footer">© <?= date('Y') ?> FarDU · KPI baholash tizimi</div>
