<?php
use yii\db\Migration;
class m261107_000004_audit_indexes extends Migration
{
    public function safeUp(){
        $this->createIndex('audit_created_id','audit',['created_at','id']);
        $this->createIndex('audit_actor_created','audit',['actor_id','created_at']);
        $this->createIndex('audit_action_created','audit',['action','created_at']);
        $this->createIndex('review_created_id','review',['created_at','id']);
    }
    public function safeDown(){
        $this->dropIndex('review_created_id','review');$this->dropIndex('audit_action_created','audit');$this->dropIndex('audit_actor_created','audit');$this->dropIndex('audit_created_id','audit');
    }
}
