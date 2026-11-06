<?php
use yii\db\Migration;
class m261106_000001_employee_profile extends Migration
{
    public function safeUp(){ $this->addColumn('user','hemis_profile',$this->text()); }
    public function safeDown(){ $this->dropColumn('user','hemis_profile'); }
}
