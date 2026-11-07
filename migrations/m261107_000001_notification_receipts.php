<?php
use yii\db\Migration;
class m261107_000001_notification_receipts extends Migration
{
    public function safeUp(){
        if($this->db->schema->getTableSchema('notification_receipt',true)===null){
            $this->createIndex('review_application_updates','review',['application_id','id']);
            $this->createTable('notification_receipt',['user_id'=>$this->primaryKey(),'last_review_id'=>$this->integer()->notNull()->defaultValue(0)]);
        }
    }
    public function safeDown(){ $this->dropTable('notification_receipt');$this->dropIndex('review_application_updates','review'); }
}
