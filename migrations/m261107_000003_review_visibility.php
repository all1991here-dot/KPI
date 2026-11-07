<?php
use yii\db\{Migration,Query};
class m261107_000003_review_visibility extends Migration
{
    public function safeUp(){
        if(!isset($this->db->schema->getTableSchema('review',true)->columns['visible_in_history'])){
            $this->addColumn('review','visible_in_history',$this->boolean()->notNull()->defaultValue(1));
            // Earlier events have no actor-role snapshot; classify existing rows by current role.
            $superadmins=(new Query())->select('id')->from('user')->where(['role'=>'superadmin']);
            $this->db->createCommand()->update('review',['visible_in_history'=>0],['actor_id'=>$superadmins])->execute();
        }
    }
    public function safeDown(){ $this->dropColumn('review','visible_in_history'); }
}
