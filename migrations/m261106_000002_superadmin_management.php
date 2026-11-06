<?php
use yii\db\Migration;
class m261106_000002_superadmin_management extends Migration
{
    public function safeUp(){ $this->addColumn('user','deleted_at',$this->integer());$this->addColumn('indicator','deleted_at',$this->integer()); }
    public function safeDown(){ $this->dropColumn('indicator','deleted_at');$this->dropColumn('user','deleted_at'); }
}
