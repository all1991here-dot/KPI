<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
class BaseController extends Controller
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) return false;
        Yii::$app->response->headers->set('X-Content-Type-Options','nosniff');
        Yii::$app->response->headers->set('X-Frame-Options','DENY');
        Yii::$app->response->headers->set('Referrer-Policy','same-origin');
        Yii::$app->response->headers->set('Cache-Control','no-store');
        if(Yii::$app->user->isGuest){$this->redirect(['auth/login']);return false;}
        if(!empty(Yii::$app->params['passwordChangeEnabled']) && Yii::$app->user->identity->must_change_password && $action->uniqueId!=='auth/password' && $action->uniqueId!=='auth/logout'){$this->redirect(['auth/password']);return false;}
        if(Yii::$app->user->identity->role==='unassigned' && !in_array($action->uniqueId,['auth/password','auth/logout','kpi/profile','kpi/employee-photo'],true))throw new ForbiddenHttpException('Rol biriktirilmagan. Superadministratorga murojaat qiling.');
        return true;
    }
    public function afterAction($action,$result)
    {
        if(Yii::$app->request->isPost){
            $identity=Yii::$app->user->identity;
            \app\services\Catalog::audit($identity?(int)$identity->id:null,'request_post',json_encode(['route'=>$action->uniqueId,'object_id'=>Yii::$app->request->get('id'),'operation'=>mb_substr((string)Yii::$app->request->post('operation',Yii::$app->request->post('action','')),0,80),'http_status'=>Yii::$app->response->statusCode],JSON_UNESCAPED_UNICODE));
        }
        return parent::afterAction($action,$result);
    }
    protected function actor(): \app\models\User { return Yii::$app->user->identity; }
    protected function admin(): void { if(!$this->actor()->isAdmin()){\app\services\Catalog::audit((int)$this->actor()->id,'access_denied',json_encode(['route'=>$this->route]));throw new ForbiddenHttpException('Administrator huquqi kerak.');} }
    protected function postOnly(): void { if(!Yii::$app->request->isPost)throw new \yii\web\MethodNotAllowedHttpException('Faqat POST.'); }
    protected function message(\Throwable $error): void
    {
        \app\services\Catalog::audit(Yii::$app->user->isGuest?null:(int)$this->actor()->id,'operation_failed',json_encode(['route'=>$this->route,'object_id'=>Yii::$app->request->get('id'),'error_type'=>get_class($error),'message'=>$error instanceof \DomainException?$error->getMessage():'Amal bajarilmadi'],JSON_UNESCAPED_UNICODE));
        if($error instanceof \DomainException)Yii::$app->session->setFlash('error',$error->getMessage());
        else {Yii::error($error->getMessage(),__METHOD__);Yii::$app->session->setFlash('error','Amal bajarilmadi. Kiritilgan ma’lumotlar va server jurnalini tekshiring.');}
    }
}
