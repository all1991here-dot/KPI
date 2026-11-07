<?php use app\services\{Ui,Catalog};use yii\helpers\Html;
$this->title=$edit?'Mezonni tahrirlash':'Yangi mezon';$this->registerCssFile(Yii::$app->request->baseUrl.'/css/organization.css?v=3');$v=fn($key,$default='')=>Yii::$app->request->post($key,$edit[$key]??$default);
$types=['QatiyBall'=>'Bir martalik ball','BirlikUchun'=>'Har bir natija uchun','ChegaraliYigindi'=>'Limitli yig‘indi','Ballgacha'=>'Ekspert bahosi','DarajaShkalasi'=>'Sifat shkalasi','PulFormulasi'=>'Grant summasi','XirshIndeksi'=>'Xirsh indeksi'];
?>
<div class="page-heading"><div><span class="eyebrow">SUPERADMINISTRATOR</span><h1><?= $edit?'Mezonni tahrirlash':'Yangi mezon' ?></h1><p><?= $edit?Ui::h($edit['name']):'Mezon ma’lumotlarini kiriting.' ?></p></div><?= Html::a('← Mezonlarga qaytish',['admin/indicators'],['class'=>'button secondary']) ?></div>
<div class="alert info">Katalog tahriri oldingi tasdiqlangan hujjat va ballarni o‘zgartirmaydi. Mavjud davr ballarini “Hisoblash qoidalari”da yangilang. O‘chirish oldingi hujjatlar tarixini saqlaydi.</div>
<section class="card organization-editor"><h2><?= $edit?'Mezonni tahrirlash':'Yangi mezon' ?></h2><?= Html::beginForm(['admin/indicator-edit','id'=>$edit['id']??null],'post',['id'=>'indicator-details-form']) ?><div class="two-columns">
<?= Ui::input('Mezon kodi','code',$v('code'),'text',['required'=>true,'maxlength'=>120]) ?><?= Ui::select('Xodim toifasi','category',$v('category','PHD'),Catalog::CATEGORIES) ?>
<?= Ui::input('Yo‘nalish kodi','section',$v('section'),'text',['required'=>true,'maxlength'=>40]) ?><?= Ui::input('Yo‘nalish nomi','section_name',$v('section_name'),'text',['required'=>true,'maxlength'=>250]) ?>
<?= Ui::input('Band kodi','criterion_code',$v('criterion_code'),'text',['required'=>true,'maxlength'=>120]) ?><?= Ui::input('Band nomi','criterion_name',$v('criterion_name'),'text',['required'=>true,'maxlength'=>1000]) ?>
<?= Ui::input('Mezon nomi','name',$v('name'),'text',['required'=>true,'maxlength'=>1000]) ?><?= Ui::select('Hisoblash turi','calc_type',$v('calc_type','BirlikUchun'),$types) ?>
<?php foreach(['base_score'=>'Birlik balli','upper_score'=>'Ekspert bahosi chegarasi','cap_score'=>'Davr uchun maksimal jami'] as $key=>$label): ?><?= Ui::input($label,$key,$v($key),'number',['min'=>0,'max'=>9999999.99,'step'=>'.01']) ?><?php endforeach ?>
<?= Ui::input('Hujjat raqami','document_number',$v('document_number'),'text',['maxlength'=>10]) ?><?= Ui::input('Manba sahifasi','source_page',$v('source_page',0),'number',['min'=>0,'max'=>100000]) ?></div>
<?= Ui::area('Qo‘llanish sharti / manba matni','source_text',$v('source_text'),['maxlength'=>20000]) ?><?= Ui::area('Izoh','source_note',$v('source_note'),['maxlength'=>20000]) ?>
<div class="checks"><?php foreach(['once_only'=>'Davrda bir marta','policy_required'=>'Davr qarori bilan ochilsin','bonus'=>'Rag‘batlantirish'] as $key=>$label): ?><label class="check"><?= Html::checkbox($key,(bool)$v($key,$key==='policy_required'?1:0),['uncheck'=>0]) ?> <?= Ui::h($label) ?></label><?php endforeach ?></div>
<?= Html::endForm() ?>
<div class="organization-editor-actions">
<div class="organization-destructive-action"><?php if($edit): ?><?= Html::beginForm(['admin/indicator-edit','id'=>$edit['id']],'post') ?><?= Html::hiddenInput('operation','delete') ?><button class="button danger" type="submit">O‘chirish</button><?= Html::endForm() ?><?php endif ?></div>
<div class="organization-main-actions"><button class="button" type="submit" name="operation" value="save" form="indicator-details-form">Saqlash</button></div>
</div></section>
