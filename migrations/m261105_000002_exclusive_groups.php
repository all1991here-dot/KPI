<?php
use yii\db\Migration;
class m261105_000002_exclusive_groups extends Migration
{
    public function safeUp(){if(!$this->db->schema->getTableSchema('period_rule',true)->getColumn('exclusive_group'))$this->addColumn('period_rule','exclusive_group',$this->string(80));}
    public function safeDown(){ $this->dropColumn('period_rule','exclusive_group'); }
}
