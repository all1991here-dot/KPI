<?php
use yii\db\Migration;
class m261106_000004_organization_types extends Migration
{
    public function safeUp(){
        $this->addColumn('org','hemis_type_code',$this->string(80));$this->addColumn('org','hemis_type_name',$this->string(200));$this->addColumn('org','hemis_parent_id',$this->string(80));
        $this->addColumn('org','type_verified',$this->boolean()->notNull()->defaultValue(1));
        $this->addColumn('org','needs_review',$this->boolean()->notNull()->defaultValue(0));$this->addColumn('org','classification_note',$this->text());$this->addColumn('org','manual_structure',$this->boolean()->notNull()->defaultValue(0));
        $this->db->schema->refreshTableSchema('org');\app\services\OrganizationClassifier::repairLegacy();
    }
    public function safeDown(){foreach(['hemis_type_code','hemis_type_name','hemis_parent_id','type_verified','needs_review','classification_note','manual_structure'] as $name)$this->dropColumn('org',$name);}
}
