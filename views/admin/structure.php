<?php
use app\services\{Ui,Organization};use yii\helpers\Html;
$super=Yii::$app->user->identity->role==='superadmin';$this->title='Universitet tuzilmasi';$v=fn($key,$default='')=>Yii::$app->request->post($key,$edit[$key]??$default);
$kinds=['university'=>'Universitet','faculty'=>'Fakultet','department'=>'Kafedra'];$labels=Organization::labels($rows);$map=array_column($rows,null,'id');
$this->registerJsFile(Yii::$app->request->baseUrl.'/js/admin-organization.js?v=1');
$options=[];foreach($rows as $r)$options[$r['id']]=['data-kind'=>$r['kind'],'data-parent'=>$r['parent_id']??''];
?>
<div class="page-heading"><div><span class="eyebrow">ADMINISTRATOR</span><h1>Universitet tuzilmasi</h1><p>Avval universitet nomini qo‘lda to‘ldiring. Unga fakultetlarni, fakultetlarga kafedralarni biriktiring.</p></div></div>
<?php if($super || $edit): ?><section class="card"><h2><?= $edit?'Bo‘limni tahrirlash':'Yangi bo‘lim' ?></h2><?= Html::beginForm(['admin/structure','id'=>$edit['id']??null],'post',['class'=>'filters','data-org-structure'=>true]) ?>
<?= Ui::input('Nomi','name',$v('name'),'text',['required'=>true,'maxlength'=>200]) ?>
<?= Ui::select('Turi','kind',$v('kind','university'),$kinds,['data-org-kind'=>true]) ?>
<?= Ui::select('Biriktiriladigan universitet / fakultet','parent_id',$v('parent_id'),[''=>'Yo‘q (universitet uchun)']+$labels,['data-org-parent'=>true,'options'=>$options]) ?>
<button class="button">Saqlash</button><?= Html::endForm() ?>
<?php if(!$rows): ?><p class="muted">Universitet hali kiritilmagan. Avval uning to‘liq nomini saqlang.</p><?php endif ?></section><?php endif ?>
<section class="card"><h2>Universitet → fakultet → kafedra</h2><div class="table-wrap"><table><thead><tr><th>Tuzilma</th><th>Turi</th><th>Yuqori bo‘lim</th><th></th></tr></thead><tbody>
<?php foreach($labels as $id=>$label):$r=$map[$id]; ?><tr><td><b><?= Ui::h($label) ?></b></td><td><?= Ui::h($kinds[$r['kind']]) ?></td><td><?= Ui::h($map[$r['parent_id']]['name']??'Yo‘q') ?></td><td><?= Html::a('Tahrirlash',['admin/structure','id'=>$id],['class'=>'text-link']) ?><?php if($super): ?><?= Html::beginForm(['admin/delete-structure','id'=>$id],'post') ?><button class="button small danger" type="submit">O‘chirish</button><?= Html::endForm() ?><?php endif ?></td></tr><?php endforeach ?>
</tbody></table></div></section>
