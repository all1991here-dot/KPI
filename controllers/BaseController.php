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
        if(Yii::$app->user->identity->must_change_password && $action->uniqueId!=='auth/password' && $action->uniqueId!=='auth/logout'){$this->redirect(['auth/password']);return false;}
        return true;
    }
    protected function actor(): \app\models\User { return Yii::$app->user->identity; }
    protected function admin(): void { if(!$this->actor()->isAdmin())throw new ForbiddenHttpException('Administrator huquqi kerak.'); }
    protected function postOnly(): void { if(!Yii::$app->request->isPost)throw new \yii\web\MethodNotAllowedHttpException('Faqat POST.'); }
    protected function message(\Throwable $error): void
    {
        if($error instanceof \DomainException)Yii::$app->session->setFlash('error',$error->getMessage());
        else {Yii::error($error->getMessage(),__METHOD__);Yii::$app->session->setFlash('error','Amal bajarilmadi. Kiritilgan ma’lumotlar va server jurnalini tekshiring.');}
    }
}
