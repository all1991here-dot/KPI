<?php
use app\services\Ui;use yii\db\Query;
$profile=json_decode($person->hemis_profile??'{}',true)?:[];
$chain=array_map(fn($r)=>$r['name'],\app\services\Organization::lineage($person->org_id?(int)$person->org_id:null,$organizations??(new Query())->from('org')->all()));
$fields=['F.I.Sh.'=>$person->name,'Universitet'=>$chain['university']??null,'Fakultet'=>$chain['faculty']??null,'Kafedra'=>$chain['department']??null,'Bo‘lim'=>$chain['unit']??null,'Lavozim'=>$person->position,'Ilmiy daraja'=>$person->degree,'Ilmiy unvon'=>$profile['academic_rank']??null,'Ish turi'=>$profile['employment_form']??null,'Stavka'=>$profile['employment_staff']??null,'Xodim turi'=>$profile['employee_type']??null,'Telefon'=>$profile['phone']??null,'Email'=>$profile['email']??null,'HEMIS ID'=>$person->hemis_id];
?>
<?= $this->render('_employee_photo',['person'=>$person]) ?>
<dl class="details"><?php foreach($fields as $label=>$value): ?><div><dt><?= Ui::h($label) ?></dt><dd><?= Ui::h($value!==null && trim((string)$value)!==''?$value:'Yo‘q') ?></dd></div><?php endforeach ?></dl>
