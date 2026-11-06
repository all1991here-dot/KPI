<?php
use app\services\{Ui,Organization};use yii\helpers\Html;
$super=Yii::$app->user->identity->role==='superadmin';$kinds=['university'=>'Universitet','faculty'=>'Fakultet','department'=>'Kafedra'];
$this->title=($edit?'Tahrirlash: ':'Yaratish: ').$kinds[$kind];$v=fn($key,$default='')=>Yii::$app->request->post($key,$edit[$key]??$default);
$map=array_column($rows,null,'id');$labels=Organization::labels($rows);$parentKind=['university'=>null,'faculty'=>'university','department'=>'faculty'][$kind];$parents=[];
foreach($rows as $r)if($r['kind']===$parentKind)$parents[$r['id']]=$labels[$r['id']];
$parent=(int)$v('parent_id',(int)Yii::$app->request->get('parent_id',0));$ownBlocked=!empty($edit['blocked_at']);$blocked=$edit?Organization::blockedBy((int)$edit['id'],$rows):null;
$this->registerJsFile(Yii::$app->request->baseUrl.'/js/organization-editor.js?v=1');
$this->registerCssFile(Yii::$app->request->baseUrl.'/css/organization.css?v=1');
?>
<div class="page-heading"><div><span class="eyebrow"><?= Ui::h($kinds[$kind]) ?> REKVIZITLARI</span><h1><?= $edit?Ui::h($edit['name']):'Yangi '.mb_strtolower($kinds[$kind]) ?></h1><p>Rekvizitlarni to‘ldiring. Boshqaruv tugmalari sahifaning pastida joylashgan.</p></div><?= Html::a('← Tuzilmaga qaytish',['admin/structure'],['class'=>'button secondary']) ?></div>
<?php if($blocked): ?><div class="alert warning"><b>Bloklangan:</b> <?= Ui::h($blocked['name']) ?>. Yangi xodim biriktirish va yangi faoliyat yuborish to‘xtatiladi; oldingi hujjatlar saqlanadi.<?php if(!$ownBlocked): ?> Blok yuqori bo‘limdan meros olingan.<?php endif ?></div><?php endif ?>
<section class="card organization-editor"><div class="card-heading"><h2><?= Ui::h($kinds[$kind]) ?> haqida ma’lumot</h2><span class="badge <?= $blocked?'rejected':'approved' ?>"><?= $blocked?'Bloklangan':'Faol' ?></span></div>
<?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']??null,'kind'=>$kind],'post',['id'=>'organization-details-form']) ?>
<?= Html::hiddenInput('kind',$kind) ?><div class="two-columns">
<?= Ui::input('To‘liq nomi','name',$v('name'),'text',['required'=>true,'maxlength'=>200]) ?><?= Ui::input('Qisqa nomi','short_name',$v('short_name'),'text',['maxlength'=>120]) ?>
<?= Ui::input('Ichki kod','code',$v('code'),'text',['maxlength'=>80]) ?><?= Ui::input('Tuzilma turi','kind_label',$kinds[$kind],'text',['readonly'=>true]) ?>
<?php if($kind==='department'):$universities=[];$facultyOptions=[];foreach($rows as $r){if($r['kind']==='university')$universities[$r['id']]=$r['name'];if($r['kind']==='faculty')$facultyOptions[$r['id']]=['data-university'=>$r['parent_id']??''];}$currentFaculty=$map[$parent]??null; ?>
<div data-organization-parent-picker style="display:contents"><?= Ui::select('Universitet','university_picker',$currentFaculty['parent_id']??'',[''=>'Universitetni tanlang']+$universities,['data-university-picker'=>true]) ?><?= Ui::select('Tegishli fakultet','parent_id',$parent,[''=>'Fakultetni tanlang']+$parents,['required'=>true,'data-faculty-picker'=>true,'options'=>$facultyOptions]) ?></div>
<?php elseif($parentKind): ?><?= Ui::select('Tegishli universitet','parent_id',$parent,[''=>'Universitetni tanlang']+$parents,['required'=>true]) ?><?php else: ?><?= Html::hiddenInput('parent_id','') ?><?php endif ?>
<?= Ui::input('HEMIS ID','hemis_id',$v('hemis_id'),'text',['maxlength'=>40,'pattern'=>'[0-9]{1,40}','readonly'=>!$super,'placeholder'=>'Yo‘q']) ?>
<?= Ui::input('Rahbar F.I.Sh.','head_name',$v('head_name'),'text',['maxlength'=>200]) ?><?= Ui::input('Telefon','phone',$v('phone'),'text',['maxlength'=>80]) ?><?= Ui::input('Email','email',$v('email'),'email',['maxlength'=>200]) ?><?= Ui::input('Manzil','address',$v('address'),'text',['maxlength'=>500]) ?>
</div><?= Ui::area('Qo‘shimcha ma’lumot','notes',$v('notes'),['maxlength'=>10000]) ?>
<?php if($edit): ?><div class="organization-system-info"><span>Ichki ID: <b><?= (int)$edit['id'] ?></b></span><span>Manba: <b><?= $edit['hemis_id']?'HEMIS bilan bog‘langan':'Qo‘lda kiritilgan' ?></b></span><span>Bevosita biriktirilgan xodimlar: <b><?= (int)(new \yii\db\Query())->from('user')->where(['org_id'=>$edit['id'],'deleted_at'=>null])->count() ?></b></span></div><?php endif ?>
<?= Html::endForm() ?>
<div class="organization-editor-actions">
<div class="organization-destructive-action"><?php if($edit && $super): ?><?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']],'post') ?><?= Html::hiddenInput('operation','delete') ?><button class="button danger" type="submit">O‘chirish</button><?= Html::endForm() ?><?php endif ?></div>
<div class="organization-main-actions"><?php if($edit && $super): ?><?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']],'post') ?><?= Html::hiddenInput('operation',$ownBlocked?'unblock':'block') ?><button class="button secondary" type="submit"><?= $ownBlocked?'Blokdan chiqarish':'Bloklash' ?></button><?= Html::endForm() ?><?php endif ?><button class="button" type="submit" name="operation" value="save" form="organization-details-form">Saqlash</button></div>
</div>
</section>
