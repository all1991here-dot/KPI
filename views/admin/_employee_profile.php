<?php
use app\services\Ui;use yii\db\Query;
$profile=json_decode($person->hemis_profile??'{}',true)?:[];
$map=array_column($organizations??(new Query())->from('org')->all(),null,'id');$chain=[];$org=$map[$person->org_id]??null;$seen=[];
while($org && !isset($seen[$org['id']])){$seen[$org['id']]=true;$chain[$org['kind']]=$org['name'];$org=$map[$org['parent_id']]??null;}
$fields=['F.I.Sh.'=>$person->name,'Universitet'=>$chain['university']??null,'Fakultet'=>$chain['faculty']??null,'Kafedra'=>$chain['department']??null,'Lavozim'=>$person->position,'Ilmiy daraja'=>$person->degree,'Ilmiy unvon'=>$profile['academic_rank']??null,'Ish turi'=>$profile['employment_form']??null,'Stavka'=>$profile['employment_staff']??null,'Xodim turi'=>$profile['employee_type']??null,'Telefon'=>$profile['phone']??null,'Email'=>$profile['email']??null,'HEMIS ID'=>$person->hemis_id];
?>
<?= $this->render('_employee_photo',['person'=>$person]) ?>
<dl class="details"><?php foreach($fields as $label=>$value): ?><div><dt><?= Ui::h($label) ?></dt><dd><?= Ui::h($value!==null && trim((string)$value)!==''?$value:'Yo‘q') ?></dd></div><?php endforeach ?></dl>
