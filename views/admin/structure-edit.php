<?php
use app\services\{Ui,Organization};use yii\helpers\Html;
$super=Yii::$app->user->identity->role==='superadmin';$kinds=Organization::KINDS;
$this->title=($edit?'Tahrirlash: ':'Yaratish: ').$kinds[$kind];$v=fn($key,$default='')=>Yii::$app->request->post($key,$edit[$key]??$default);
$map=array_column($rows,null,'id');$labels=Organization::labels($rows);$parents=[];$parentOptions=[];$universities=[];
foreach($rows as $r){if($r['kind']==='university')$universities[$r['id']]=$r['name'];if(in_array($r['kind'],['university','faculty'],true) && (!$edit || (int)$r['id']!==(int)$edit['id'])){$parents[$r['id']]=$labels[$r['id']];$chain=Organization::lineage((int)$r['id'],$rows);$parentOptions[$r['id']]=['data-kind'=>$r['kind'],'data-university'=>$chain['university']['id']??''];}}
$parent=(int)$v('parent_id',(int)Yii::$app->request->get('parent_id',0));$ownBlocked=!empty($edit['blocked_at']);$blocked=$edit?Organization::blockedBy((int)$edit['id'],$rows):null;
$this->registerJsFile(Yii::$app->request->baseUrl.'/js/organization-editor.js?v=2');
$this->registerCssFile(Yii::$app->request->baseUrl.'/css/organization.css?v=3');
?>
<div class="page-heading"><div><span class="eyebrow"><?= Ui::h($kinds[$kind]) ?> REKVIZITLARI</span><h1><?= $edit?Ui::h($edit['name']):'Yangi '.mb_strtolower($kinds[$kind]) ?></h1><p>Rekvizitlarni to‘ldiring. Boshqaruv tugmalari sahifaning pastida joylashgan.</p></div><?= Html::a('← Tuzilmaga qaytish',['admin/structure'],['class'=>'button secondary']) ?></div>
<?php if($blocked): ?><div class="alert warning"><b>Bloklangan:</b> <?= Ui::h($blocked['name']) ?>. Yangi xodim biriktirish va yangi faoliyat yuborish to‘xtatiladi; oldingi hujjatlar saqlanadi.<?php if(!$ownBlocked): ?> Blok yuqori bo‘limdan meros olingan.<?php endif ?></div><?php endif ?>
<section class="card organization-editor"><div class="card-heading"><h2><?= Ui::h($kinds[$kind]) ?> haqida ma’lumot</h2><span class="badge <?= $blocked?'rejected':'approved' ?>"><?= $blocked?'Bloklangan':'Faol' ?></span></div>
<?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']??null,'kind'=>$kind],'post',['id'=>'organization-details-form']) ?>
<?php if(!$super): ?><?= Html::hiddenInput('kind',$kind,['data-org-editor-type'=>true]) ?><?php endif ?><div class="two-columns">
<?= Ui::input('To‘liq nomi','name',$v('name'),'text',['required'=>true,'maxlength'=>200]) ?><?= Ui::input('Qisqa nomi','short_name',$v('short_name'),'text',['maxlength'=>120]) ?>
<?= Ui::input('Ichki kod','code',$v('code'),'text',['maxlength'=>80]) ?><?php if($super): ?><?= Ui::select('Tuzilma turi','kind',$kind,$kinds,['data-org-editor-type'=>true]) ?><?php else: ?><?= Ui::input('Tuzilma turi','kind_label',$kinds[$kind],'text',['readonly'=>true]) ?><?php endif ?>
<?php $chain=$parent?Organization::lineage($parent,$rows):[]; ?>
<div data-university-field<?= $kind==='department'?'':' hidden' ?>><?= Ui::select('Universitet','university_picker',$chain['university']['id']??'',[''=>'Universitetni tanlang']+$universities,['data-university-picker'=>true]) ?></div>
<?= Ui::select('Yuqori tuzilma / tegishli fakultet','parent_id',$parent,[''=>'Yo‘q (faqat universitet uchun)']+$parents,['data-org-editor-parent'=>true,'options'=>$parentOptions]) ?>
<?= Ui::input('HEMIS ID','hemis_id',$v('hemis_id'),'text',['maxlength'=>40,'pattern'=>'[0-9]{1,40}','readonly'=>!$super,'placeholder'=>'Yo‘q']) ?>
<?= Ui::input('Rahbar F.I.Sh.','head_name',$v('head_name'),'text',['maxlength'=>200]) ?><?= Ui::input('Telefon','phone',$v('phone'),'text',['maxlength'=>80]) ?><?= Ui::input('Email','email',$v('email'),'email',['maxlength'=>200]) ?><?= Ui::input('Manzil','address',$v('address'),'text',['maxlength'=>500]) ?>
</div><?= Ui::area('Qo‘shimcha ma’lumot','notes',$v('notes'),['maxlength'=>10000]) ?>
<?php if($edit): ?><div class="organization-system-info"><span>Ichki ID: <b><?= (int)$edit['id'] ?></b></span><span>Manba: <b><?= $edit['hemis_id']?'HEMIS bilan bog‘langan':'Qo‘lda kiritilgan' ?></b></span><span>Bevosita biriktirilgan xodimlar: <b><?= (int)(new \yii\db\Query())->from('user')->where(['org_id'=>$edit['id'],'deleted_at'=>null])->count() ?></b></span></div><?php endif ?>
<?php if($super): ?><label class="check"><?= Html::checkbox('manual_structure',(bool)Yii::$app->request->post('manual_structure',!$edit || !empty($edit['manual_structure']) || !empty($edit['needs_review']) || empty($edit['type_verified'])),['uncheck'=>0]) ?> Tur va yuqori bo‘limni qo‘lda belgilash — HEMIS importi o‘zgartirmasin</label><?php endif ?><?php if($edit && $edit['hemis_type_name']): ?><p class="muted">HEMIS turi: <?= Ui::h($edit['hemis_type_name']) ?> (<?= Ui::h($edit['hemis_type_code']) ?>)</p><?php endif ?><?php if($edit && $edit['classification_note']): ?><div class="alert warning"><?= Ui::h($edit['classification_note']) ?></div><?php endif ?>
<?= Html::endForm() ?>
<div class="organization-editor-actions">
<div class="organization-destructive-action"><?php if($edit && $super): ?><?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']],'post') ?><?= Html::hiddenInput('operation','delete') ?><button class="button danger" type="submit">O‘chirish</button><?= Html::endForm() ?><?php endif ?></div>
<div class="organization-main-actions"><?php if($edit && $super): ?><?= Html::beginForm(['admin/structure-edit','id'=>$edit['id']],'post') ?><?= Html::hiddenInput('operation',$ownBlocked?'unblock':'block') ?><button class="button secondary" type="submit"><?= $ownBlocked?'Blokdan chiqarish':'Bloklash' ?></button><?= Html::endForm() ?><?php endif ?><button class="button" type="submit" name="operation" value="save" form="organization-details-form">Saqlash</button></div>
</div>
</section>
