<?php
use yii\db\Migration;
class m261105_000001_initial extends Migration
{
    public function createTable($table,$columns,$options=null)
    {
        if($this->db->driverName==='sqlite'){
            $relations=['org'=>['parent_id'=>'org(id)'],'user'=>['org_id'=>'org(id)'],'period_rule'=>['period_id'=>'period(id)','indicator_id'=>'indicator(id)'],'application'=>['employee_id'=>'user(id)','period_id'=>'period(id)','indicator_id'=>'indicator(id)'],'evidence'=>['application_id'=>'application(id)'],'review'=>['application_id'=>'application(id)','actor_id'=>'user(id)'],'score_entry'=>['application_id'=>'application(id)','actor_id'=>'user(id)']];
            foreach($relations[$table]??[] as $column=>$reference)$columns[]='FOREIGN KEY ('.$column.') REFERENCES '.$reference;
        }
        parent::createTable($table,$columns,$options);
    }
    public function addForeignKey($name,$table,$columns,$refTable,$refColumns,$delete=null,$update=null)
    {
        if($this->db->driverName!=='sqlite')parent::addForeignKey($name,$table,$columns,$refTable,$refColumns,$delete,$update);
    }
    public function safeUp()
    {
        $this->createTable('org', ['id'=>$this->primaryKey(),'name'=>$this->string(200)->notNull(),'kind'=>$this->string(20)->notNull(),'parent_id'=>$this->integer()]);
        $this->addForeignKey('org_parent','org','parent_id','org','id');
        $this->createTable('user', ['id'=>$this->primaryKey(),'username'=>$this->string(100)->notNull()->unique(),'name'=>$this->string(200)->notNull(),'password_hash'=>$this->string()->notNull(),'auth_key'=>$this->string(64)->notNull(),'role'=>$this->string(30)->notNull(),'org_id'=>$this->integer(),'category'=>$this->string(20),'degree'=>$this->string(50),'position'=>$this->string(100),'active'=>$this->boolean()->notNull()->defaultValue(1),'must_change_password'=>$this->boolean()->notNull()->defaultValue(1),'created_at'=>$this->integer()->notNull()]);
        $this->addForeignKey('user_org','user','org_id','org','id');
        $this->createTable('period', ['id'=>$this->primaryKey(),'name'=>$this->string(200)->notNull(),'starts'=>$this->date()->notNull(),'ends'=>$this->date()->notNull(),'closed'=>$this->boolean()->notNull()->defaultValue(0)]);
        $this->createTable('indicator', ['id'=>$this->primaryKey(),'code'=>$this->string(120)->notNull()->unique(),'category'=>$this->string(20)->notNull(),'section'=>$this->string(40)->notNull(),'section_name'=>$this->string(250)->notNull(),'criterion_code'=>$this->string(120)->notNull(),'criterion_name'=>$this->text()->notNull(),'document_number'=>$this->string(10),'name'=>$this->text()->notNull(),'source_text'=>$this->text(),'source_note'=>$this->text(),'source_page'=>$this->integer(),'calc_type'=>$this->string(40)->notNull(),'base_score'=>$this->decimal(12,2),'upper_score'=>$this->decimal(12,2),'cap_score'=>$this->decimal(12,2),'once_only'=>$this->boolean()->notNull(),'policy_required'=>$this->boolean()->notNull(),'bonus'=>$this->boolean()->notNull()]);
        $this->createTable('period_rule', ['id'=>$this->primaryKey(),'period_id'=>$this->integer()->notNull(),'indicator_id'=>$this->integer()->notNull(),'enabled'=>$this->boolean()->notNull(),'calc_type'=>$this->string(40)->notNull(),'base_score'=>$this->decimal(12,2),'upper_score'=>$this->decimal(12,2),'cap_score'=>$this->decimal(12,2),'once_only'=>$this->boolean()->notNull(),'decision'=>$this->text(),'revision'=>$this->integer()->notNull()->defaultValue(1)]);
        $this->createIndex('period_indicator','period_rule',['period_id','indicator_id'],true);
        $this->addForeignKey('rule_period','period_rule','period_id','period','id');
        $this->addForeignKey('rule_indicator','period_rule','indicator_id','indicator','id');
        $this->createTable('route_step', ['id'=>$this->primaryKey(),'position'=>$this->integer()->notNull()->unique(),'name'=>$this->string(200)->notNull(),'roles'=>$this->text()->notNull()]);
        $this->createTable('application', ['id'=>$this->primaryKey(),'uuid'=>$this->string(64)->notNull()->unique(),'employee_id'=>$this->integer()->notNull(),'period_id'=>$this->integer()->notNull(),'indicator_id'=>$this->integer()->notNull(),'category'=>$this->string(20)->notNull(),'title'=>$this->string(250)->notNull(),'activity_date'=>$this->date()->notNull(),'quantity'=>$this->integer()->notNull(),'expert_score'=>$this->decimal(12,2),'status'=>$this->string(30)->notNull()->defaultValue('draft'),'step'=>$this->integer()->notNull()->defaultValue(0),'score_cents'=>$this->integer()->notNull()->defaultValue(0),'rule_snapshot'=>$this->text(),'route_snapshot'=>$this->text(),'notes'=>$this->text(),'created_at'=>$this->integer()->notNull(),'updated_at'=>$this->integer()->notNull()]);
        foreach (['employee_id'=>['user','id'],'period_id'=>['period','id'],'indicator_id'=>['indicator','id']] as $col=>$ref) $this->addForeignKey('app_'.$col,'application',$col,$ref[0],$ref[1]);
        $this->createIndex('app_score_group','application',['employee_id','period_id','indicator_id','status']);
        $this->createTable('evidence', ['id'=>$this->primaryKey(),'application_id'=>$this->integer()->notNull(),'name'=>$this->string(250)->notNull(),'storage_name'=>$this->string(100)->notNull(),'mime'=>$this->string(80)->notNull(),'size'=>$this->integer()->notNull(),'sha256'=>$this->string(64)->notNull()]);
        $this->addForeignKey('evidence_app','evidence','application_id','application','id');
        $this->createTable('review', ['id'=>$this->primaryKey(),'application_id'=>$this->integer()->notNull(),'actor_id'=>$this->integer()->notNull(),'action'=>$this->string(30)->notNull(),'step'=>$this->integer()->notNull(),'reason'=>$this->text()->notNull(),'created_at'=>$this->integer()->notNull()]);
        $this->addForeignKey('review_app','review','application_id','application','id');
        $this->addForeignKey('review_actor','review','actor_id','user','id');
        $this->createTable('score_entry', ['id'=>$this->primaryKey(),'application_id'=>$this->integer()->notNull(),'actor_id'=>$this->integer()->notNull(),'amount_cents'=>$this->integer()->notNull(),'reason'=>$this->text()->notNull(),'created_at'=>$this->integer()->notNull()]);
        $this->addForeignKey('score_app','score_entry','application_id','application','id');
        $this->createTable('audit', ['id'=>$this->primaryKey(),'actor_id'=>$this->integer(),'action'=>$this->string(80)->notNull(),'details'=>$this->text()->notNull(),'created_at'=>$this->integer()->notNull()]);
        $this->createTable('login_attempt', ['id'=>$this->primaryKey(),'fingerprint'=>$this->string(64)->notNull(),'created_at'=>$this->integer()->notNull()]);
        $this->createIndex('login_fingerprint','login_attempt',['fingerprint','created_at']);
        $this->addColumn('org','hemis_id',$this->string(80));
        $this->addColumn('user','hemis_id',$this->string(80));
        $this->createIndex('org_hemis','org','hemis_id',true);
        $this->createIndex('user_hemis','user','hemis_id',true);
        $this->addColumn('period_rule','formula_divisor',$this->decimal(20,2));
        $this->addColumn('period_rule','formula_mode',$this->string(30));
        $this->addColumn('application','metric_value',$this->decimal(20,2));
        $this->addColumn('period_rule','exclusive_group',$this->string(80));
    }
    public function safeDown()
    {
        foreach (['login_attempt','audit','score_entry','review','evidence','application','route_step','period_rule','indicator','period','user','org'] as $table) $this->dropTable($table);
    }
}
