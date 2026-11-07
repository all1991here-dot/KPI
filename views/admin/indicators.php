<?php use app\services\{Ui,Catalog};use yii\helpers\Html;
$this->title='KPI mezonlarini boshqarish';
$types=['QatiyBall'=>'Bir martalik ball','BirlikUchun'=>'Har bir natija uchun','ChegaraliYigindi'=>'Limitli yig‘indi','Ballgacha'=>'Ekspert bahosi','DarajaShkalasi'=>'Sifat shkalasi','PulFormulasi'=>'Grant summasi','XirshIndeksi'=>'Xirsh indeksi'];
?>
<div class="page-heading"><div><span class="eyebrow">SUPERADMINISTRATOR</span><h1>KPI mezonlarini boshqarish</h1><p>Mezonni tahrirlash uchun ro‘yxatdan tanlang.</p></div><div class="form-actions"><?= Html::a('Davr ballari va qoidalari',['admin/rules'],['class'=>'button secondary']) ?><?= Html::a('Yangi mezon',['admin/indicator-edit'],['class'=>'button']) ?></div></div>
<section class="card"><h2>Amaldagi mezonlar: <?= count($rows) ?></h2><div class="table-wrap"><table><thead><tr><th>Kod / toifa</th><th>Mezon</th><th>Hisoblash</th><th>Amallar</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= Ui::h($r['code']) ?><small><?= Ui::h(Catalog::CATEGORIES[$r['category']]) ?></small></td><td><b><?= Ui::h($r['name']) ?></b><small><?= Ui::h($r['section_name']) ?></small></td><td><?= Ui::h($types[$r['calc_type']]??$r['calc_type']) ?></td><td><?= Html::a('Tahrirlash',['admin/indicator-edit','id'=>$r['id']],['class'=>'text-link']) ?></td></tr><?php endforeach ?>
</tbody></table></div></section>
