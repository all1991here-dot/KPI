<?php
if(!str_contains(getenv('KPI_RUNTIME_DIR')?:'','/http-test-'))exit(1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));new yii\console\Application(require dirname(__DIR__).'/config/console.php');
use app\services\{Catalog,Audit};use app\models\User;
$u=User::findOne(['username'=>'http_admin']);
foreach(['operator'=>'admin','faculty'=>'faculty'] as $name=>$role){$n=new User();$n->username='http_'.$name;$n->name=$name;$n->role=$role;$n->category='PHD';$n->org_id=3;$n->active=1;$n->must_change_password=0;$n->created_at=time();$n->setPassword('OnlyForIsolated-HttpTests2026!');$n->save(false);}
for($i=0;$i<321;$i++)Catalog::audit((int)$u->id,'audit_test',json_encode(['sequence'=>$i,'password'=>'NEVER_EXPOSE_ME','nested'=>['api_key'=>'NEVER_EXPOSE_ME']]));
$last=(new yii\db\Query())->from('audit')->orderBy(['id'=>SORT_DESC])->one();if(str_contains($last['details'],'NEVER_EXPOSE_ME'))throw new Exception('Secret leaked');
echo "Audit pagination and redaction fixtures ready.\n";
