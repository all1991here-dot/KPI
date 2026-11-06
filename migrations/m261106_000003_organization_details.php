<?php
use yii\db\Migration;
class m261106_000003_organization_details extends Migration
{
    public function safeUp(){
        foreach(['short_name'=>120,'code'=>80,'head_name'=>200,'phone'=>80,'email'=>200,'address'=>500] as $name=>$length)$this->addColumn('org',$name,$this->string($length));
        $this->addColumn('org','notes',$this->text());$this->addColumn('org','blocked_at',$this->integer());
    }
    public function safeDown(){foreach(['short_name','code','head_name','phone','email','address','notes','blocked_at'] as $name)$this->dropColumn('org',$name);}
}
