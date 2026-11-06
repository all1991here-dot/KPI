<?php
namespace app\controllers;
use Yii;
use app\models\User;
use app\services\Catalog;
use yii\db\Query;
class AuthController extends BaseController
{
    public function beforeAction($action)
    {
        if(in_array($action->id,['login','error'],true))return \yii\web\Controller::beforeAction($action);
        return parent::beforeAction($action);
    }
    public function actionLogin()
    {
        if(!Yii::$app->user->isGuest)return $this->redirect(['kpi/index']);
        $error='';
        if(Yii::$app->request->isPost){
            $username=mb_substr(trim((string)Yii::$app->request->post('username','')),0,100);
            $password=(string)Yii::$app->request->post('password','');
            $fingerprint=hash('sha256',mb_strtolower($username).'|'.Yii::$app->request->userIP);
            $ipFingerprint=hash('sha256','ip|'.Yii::$app->request->userIP);
            $fail=(new Query())->from('login_attempt')->where(['fingerprint'=>$fingerprint])->andWhere(['>','created_at',time()-900])->count();
            $ipFail=(new Query())->from('login_attempt')->where(['fingerprint'=>$ipFingerprint])->andWhere(['>','created_at',time()-900])->count();
            $user=User::findOne(['username'=>$username,'active'=>1,'deleted_at'=>null]);
            if($fail>=8 || $ipFail>=50)$error='Urinishlar ko‘p. 15 daqiqadan so‘ng qayta urinib ko‘ring.';
            elseif($user && Yii::$app->security->validatePassword($password,$user->password_hash)){
                Yii::$app->db->createCommand()->delete('login_attempt',['fingerprint'=>$fingerprint])->execute();
                Yii::$app->session->regenerateID(true);Yii::$app->user->login($user);Catalog::audit((int)$user->id,'login','Tizimga kirish');return $this->redirect(['kpi/index']);
            }else{
                foreach([$fingerprint,$ipFingerprint] as $fp)Yii::$app->db->createCommand()->insert('login_attempt',['fingerprint'=>$fp,'created_at'=>time()])->execute();
                Yii::$app->db->createCommand()->delete('login_attempt',['<','created_at',time()-86400])->execute();$error='Login yoki parol noto‘g‘ri.';
            }
        }
        return $this->render('login',['error'=>$error]);
    }
    public function actionLogout(){ $this->postOnly();Yii::$app->user->logout();return $this->redirect(['auth/login']); }
    public function actionPassword()
    {
        $error='';$user=$this->actor();
        if(Yii::$app->request->isPost){
            $old=(string)Yii::$app->request->post('old_password','');$password=(string)Yii::$app->request->post('password','');
            if(!Yii::$app->security->validatePassword($old,$user->password_hash))$error='Joriy parol noto‘g‘ri.';
            elseif(strlen($password)<12 || strlen($password)>128)$error='Yangi parol 12–128 belgidan iborat bo‘lsin.';
            elseif($password!==(string)Yii::$app->request->post('confirmation',''))$error='Yangi parollar mos emas.';
            elseif($old===$password)$error='Yangi parol avvalgisidan farq qilsin.';
            else{$user->setPassword($password);$user->must_change_password=0;$user->save(false);Yii::$app->user->login($user);Catalog::audit((int)$user->id,'password_change','Shaxsiy parol yangilandi');Yii::$app->session->regenerateID(true);Yii::$app->session->setFlash('success','Parol yangilandi.');return $this->redirect(['kpi/index']);}
        }
        return $this->render('password',['error'=>$error]);
    }
    public function actionError(){ $error=Yii::$app->errorHandler->exception;return $this->render('error',['error'=>$error]); }
}
