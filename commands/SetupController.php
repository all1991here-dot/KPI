<?php
namespace app\commands;
use Yii;
use app\models\User;
use app\services\Catalog;
use yii\console\Controller;
use yii\db\Query;
class SetupController extends Controller
{
    public function actionInit($username = 'admin'): int
    {
        $path=Yii::getAlias('@app/config/local.php');
        if (!is_file($path)) {
            $key=Yii::$app->security->generateRandomString(64);
            file_put_contents($path,"<?php\nreturn " . var_export(['cookieKey'=>$key],true) . ";\n"); chmod($path,0600);
        }
        $exit=Yii::$app->runAction('migrate',['interactive'=>false]);
        if ($exit) return $exit;
        Yii::$app->db->schema->refresh();
        Catalog::import();
        if (!(new Query())->from('route_step')->exists()) {
            foreach ([[1,'Kafedra tekshiruvi',['head']],[2,'Fakultet / ekspert',['faculty','expert']],[3,'Yakuniy tasdiq',['admin','superadmin']]] as [$pos,$name,$roles]) Yii::$app->db->createCommand()->insert('route_step',['position'=>$pos,'name'=>$name,'roles'=>json_encode($roles)])->execute();
        }
        if (!User::find()->exists()) {
            $password=getenv('KPI_ADMIN_PASSWORD');
            if (!$password) {
                $password=Yii::$app->security->generateRandomString(24);
                $credentials=Yii::getAlias('@runtime/initial-admin.txt');
                file_put_contents($credentials,"Login: ".$username."\nParol: ".$password."\nBirinchi kirishda parolni almashtiring, keyin bu faylni o‘chiring.\n");chmod($credentials,0600);
                $this->stdout("Birinchi kirish ma’lumotlari: runtime/initial-admin.txt (faqat lokal fayl).\n");
            }
            if(strlen($password)<12){$this->stderr("KPI_ADMIN_PASSWORD kamida 12 belgidan iborat bo‘lsin.\n");return 1;}
            $user=new User();$user->username=$username;$user->name='Administrator';$user->role='superadmin';$user->active=1;$user->must_change_password=1;$user->created_at=time();$user->setPassword($password);$user->save(false);
        }
        $this->stdout("KPI bazasi tayyor. Mezonlar: " . (new Query())->from('indicator')->count() . ". Administrator mavjud.\n");
        return 0;
    }
    public function actionResetPassword(string $username): int
    {
        $user=User::findOne(['username'=>$username]);$password=getenv('KPI_ADMIN_PASSWORD');
        if (!$user || !$password || strlen($password)<12) { $this->stderr("Foydalanuvchi va KPI_ADMIN_PASSWORD (12+ belgi) kerak.\n"); return 1; }
        $user->setPassword($password);$user->must_change_password=1;$user->save(false);Catalog::audit(null,'password_reset','User #'.$user->id);return 0;
    }
}
