<?php
use yii\db\Migration;
class m261107_000002_reviewer_notification_receipts extends Migration
{
    public function safeUp(){
        if($this->db->schema->getTableSchema('reviewer_notification_receipt',true)===null)$this->createTable('reviewer_notification_receipt',['user_id'=>$this->primaryKey(),'last_review_id'=>$this->integer()->notNull()->defaultValue(0)]);
    }
    public function safeDown(){ $this->dropTable('reviewer_notification_receipt'); }
}
