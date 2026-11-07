<?php
namespace app\controllers;
use Yii;
use yii\db\Query;
use app\services\Access;
use app\services\Catalog;
use app\services\Workflow;
class KpiController extends BaseController
{
    private function scoped(): Query
    {
        $q=(new Query())->from(['a'=>'application'])->innerJoin(['u'=>'user'],'u.id=a.employee_id')->innerJoin(['i'=>'indicator'],'i.id=a.indicator_id')->innerJoin(['p'=>'period'],'p.id=a.period_id');
        $ids=Access::employeeIds($this->actor());if($ids!==null)$q->andWhere(['a.employee_id'=>$ids]);
        if(!$this->actor()->isAdmin())$q->andWhere(['or',['<>','a.status','draft'],['a.employee_id'=>$this->actor()->id]]);
        return $q;
    }
    public function actionIndex()
    {
        $periodId=(int)Yii::$app->request->get('period',0);$q=$this->scoped();if($periodId)$q->andWhere(['a.period_id'=>$periodId]);
        $counts=(clone $q)->select(['a.status','n'=>'COUNT(*)'])->groupBy('a.status')->all();
        $score=(clone $q)->andWhere(['a.status'=>'approved'])->sum('a.score_cents');
        $sections=(clone $q)->select(['i.section_name','score'=>'SUM(a.score_cents)'])->andWhere(['a.status'=>'approved'])->groupBy('i.section_name')->all();
        $recent=(clone $q)->select(['a.*','employee'=>'u.name','indicator'=>'i.name','period'=>'p.name'])->orderBy(['a.updated_at'=>SORT_DESC])->limit(8)->all();
        $periods=(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all();$selected=null;
        foreach($periods as $period)if((int)$period['id']===$periodId)$selected=$period;
        $details=\app\services\Dashboard::details($q,$selected,$this->actor()->role!=='teacher');
        return $this->render('index',compact('periodId','counts','score','sections','recent','periods')+$details);
    }
    public function actionApplications()
    {
        $q=$this->scoped();$request=Yii::$app->request;
        if($request->get('mine'))$q->andWhere(['a.employee_id'=>$this->actor()->id]);
        foreach(['status'=>'a.status','period'=>'a.period_id','category'=>'a.category'] as $key=>$column)if($request->get($key)!==null && $request->get($key)!=='')$q->andWhere([$column=>$request->get($key)]);
        $search=mb_substr(trim((string)$request->get('q','')),0,200);if($request->get('appeals'))$q->andWhere(['exists',(new Query())->from(['appeal_review'=>'review'])->where('appeal_review.application_id=a.id')->andWhere(['appeal_review.action'=>'appeal'])]);if($search!=='')$q->andWhere(['or',['like','a.title',$search],['like','u.name',$search],['like','i.code',$search]]);
        if($request->get('queue')){
            $q->andWhere(['a.status'=>['submitted','review']]);
            $all=$q->select(['a.*','employee'=>'u.name','indicator'=>'i.name','period'=>'p.name'])->orderBy(['a.created_at'=>SORT_ASC])->all();
            $all=array_values(array_filter($all,function($a){$steps=json_decode($a['route_snapshot']??'[]',true);$s=$steps[(int)$a['step']]??null;return $s && (int)$a['employee_id']!==(int)$this->actor()->id && in_array($this->actor()->role,json_decode($s['roles'],true),true);}));
            $total=count($all);$page=max(1,(int)$request->get('page',1));$rows=array_slice($all,($page-1)*25,25);
        }else{$total=(int)(clone $q)->count();$page=max(1,(int)$request->get('page',1));$rows=$q->select(['a.*','employee'=>'u.name','indicator'=>'i.name','period'=>'p.name'])->orderBy(['a.updated_at'=>SORT_DESC])->limit(25)->offset(($page-1)*25)->all();}
        return $this->render('applications',compact('rows','total','page')+['periods'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all()]);
    }
    public function actionCreate(?int $id=null)
    {
        $row=$id?Workflow::application($id):['period_id'=>'','indicator_id'=>'','title'=>'','activity_date'=>date('Y-m-d'),'quantity'=>1,'metric_value'=>'','notes'=>''];
        if($id && $row['status']==='approved' && $this->actor()->role!=='superadmin')throw new \yii\web\ForbiddenHttpException('Tasdiqlangan arizani faqat superadministrator tahrirlaydi.');
        if($id && !$this->actor()->isAdmin() && ((int)$row['employee_id']!==(int)$this->actor()->id || !in_array($row['status'],['draft','returned'],true)))throw new \yii\web\ForbiddenHttpException('Tahrirlashga ruxsat yo‘q.');
        $category=$id && $this->actor()->isAdmin()?$row['category']:$this->actor()->category;
        if(Yii::$app->request->isPost){
            $row=array_merge($row,Yii::$app->request->post());
            try{$saved=Workflow::save($this->actor(),Yii::$app->request->post(),\yii\web\UploadedFile::getInstancesByName('evidence'),$id);Yii::$app->session->setFlash('success',$id && $this->actor()->isAdmin()?'Hujjat tahrirlandi. Egasi qayta tekshiruvga yuborishi kerak.':'Qoralama saqlandi. Dalillarni tekshirib, yuborish tugmasini bosing.');return $this->redirect(['view','id'=>$saved]);}catch(\Throwable $e){$this->message($e);}
        }
        return $this->render('create',['row'=>$row,'id'=>$id,'periods'=>(new Query())->from('period')->where($id && $this->actor()->isAdmin()?['or',['closed'=>0],['id'=>$row['period_id']]]:['closed'=>0])->orderBy(['starts'=>SORT_DESC])->all(),'indicators'=>(new Query())->from('indicator')->where(['category'=>$category])->andWhere($id?['or',['deleted_at'=>null],['id'=>$row['indicator_id']]]:['deleted_at'=>null])->orderBy('id')->all()]);
    }
    public function actionView(int $id)
    {
        $row=Workflow::application($id);if(!Access::canView($this->actor(),$row))throw new \yii\web\ForbiddenHttpException('Ruxsat yo‘q.');
        return $this->render('view',['row'=>$row,'employee'=>\app\models\User::findOne($row['employee_id']),'indicator'=>(new Query())->from('indicator')->where(['id'=>$row['indicator_id']])->one(),'period'=>(new Query())->from('period')->where(['id'=>$row['period_id']])->one(),'rule'=>(new Query())->from('period_rule')->where(['period_id'=>$row['period_id'],'indicator_id'=>$row['indicator_id']])->one(),'files'=>(new Query())->from('evidence')->where(['application_id'=>$id])->all(),'history'=>(new Query())->select(['r.*','actor'=>'u.name'])->from(['r'=>'review'])->innerJoin(['u'=>'user'],'u.id=r.actor_id')->where(['r.application_id'=>$id,'r.visible_in_history'=>1])->orderBy('r.id')->all()]);
    }
    public function actionAct(int $id)
    {
        $this->postOnly();try{Workflow::act($this->actor(),$id,(string)Yii::$app->request->post('action'),(string)Yii::$app->request->post('reason',''),Yii::$app->request->post('expert_score'));Yii::$app->session->setFlash('success','Ariza holati yangilandi.');}catch(\Throwable $e){$this->message($e);}return $this->redirect(['view','id'=>$id]);
    }
    public function actionDeleteEvidence(int $id)
    {
        $this->postOnly();$file=(new Query())->from('evidence')->where(['id'=>$id])->one();if(!$file)throw new \yii\web\NotFoundHttpException();
        try{Workflow::removeEvidence($this->actor(),$id);Yii::$app->session->setFlash('success','Dalil olib tashlandi.');}catch(\Throwable $e){$this->message($e);}return $this->redirect(['view','id'=>$file['application_id']]);
    }
    public function actionDownload(int $id)
    {
        $file=(new Query())->from('evidence')->where(['id'=>$id])->one();if(!$file || !Access::canView($this->actor(),Workflow::application((int)$file['application_id'])))throw new \yii\web\NotFoundHttpException('Dalil topilmadi.');
        $path=Yii::getAlias('@runtime/evidence/').$file['storage_name'];if(!is_file($path))throw new \yii\web\NotFoundHttpException('Fayl mavjud emas.');
        return Yii::$app->response->sendFile($path,$file['name'],['mimeType'=>$file['mime'],'inline'=>false]);
    }
    public function actionEmployeePhoto(int $id)
    {
        if(!$this->actor()->isAdmin() && $id!==(int)$this->actor()->id)throw new \yii\web\NotFoundHttpException();
        $person=\app\models\User::findOne(['id'=>$id,'deleted_at'=>null]);if(!$person)throw new \yii\web\NotFoundHttpException();
        $check=Yii::$app->request->get('check')==='1';
        if($check && !$this->actor()->isAdmin())throw new \yii\web\ForbiddenHttpException();
        if($check){
            try{$path=\app\services\HemisPhoto::file($person,true);return $this->asJson(['ok'=>true,'message'=>'Rasm yuklandi.','url'=>\yii\helpers\Url::to(['kpi/employee-photo','id'=>$id])]);}
            catch(\Throwable $e){return $this->asJson(['ok'=>false,'message'=>$e instanceof \DomainException?$e->getMessage():'Rasmni tekshirishda xato.']);}
        }
        try{$path=\app\services\HemisPhoto::file($person);}catch(\Throwable $e){throw new \yii\web\NotFoundHttpException('Rasm yo‘q.');}
        return Yii::$app->response->sendFile($path,'employee-photo',['mimeType'=>(new \finfo(FILEINFO_MIME_TYPE))->file($path),'inline'=>true]);
    }
    public function actionCriteria()
    {
        $category=$this->actor()->role==='teacher'?(string)$this->actor()->category:(string)Yii::$app->request->get('category',$this->actor()->category?:'DSC');$period=(int)Yii::$app->request->get('period',0);
        $q=(new Query())->from(['i'=>'indicator'])->where(['i.category'=>$category,'i.deleted_at'=>null]);
        $q->select(['i.*']);if($period)$q->addSelect(['enabled'=>'r.enabled','decision'=>'r.decision','configured_score'=>'r.base_score'])->leftJoin(['r'=>'period_rule'],'r.indicator_id=i.id AND r.period_id=:p',[':p'=>$period]);
        return $this->render('criteria',['rows'=>$q->orderBy('i.id')->all(),'category'=>$category,'periodId'=>$period,'periods'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all()]);
    }
    public function actionNotifications()
    {
        $userId=(int)$this->actor()->id;$lastRead=\app\services\Notifications::lastRead($userId);$lastAssignedRead=\app\services\Notifications::lastRead($userId,true);
        $rows=\app\services\Notifications::query($userId)->select(['r.*','title'=>'a.title','status'=>'a.status','employee_id'=>'a.employee_id','employee'=>'employee.name','route_snapshot'=>'a.route_snapshot','current_step'=>'a.step','actor'=>'u.name'])->innerJoin(['employee'=>'user'],'employee.id=a.employee_id')->orderBy(['r.id'=>SORT_DESC])->limit(100)->all();
        $ownRead=0;$assignedRead=0;
        foreach($rows as &$row){
            $row['is_assignment']=(int)$row['employee_id']!==$userId;
            $row['unread']=(int)$row['id']>($row['is_assignment']?$lastAssignedRead:$lastRead);
            if($row['is_assignment'])$assignedRead=max($assignedRead,(int)$row['id']);else $ownRead=max($ownRead,(int)$row['id']);
        }unset($row);
        \app\services\Notifications::markRead($userId,$ownRead);\app\services\Notifications::markRead($userId,$assignedRead,true);
        return $this->render('notifications',compact('rows'));
    }
    public function actionNotificationCount()
    {
        return $this->asJson(['unread'=>\app\services\Notifications::unreadCount((int)$this->actor()->id)]);
    }
    public function actionProfile(){return $this->render('profile',['user'=>$this->actor(),'org'=>(new Query())->from('org')->where(['id'=>$this->actor()->org_id])->one()]);}
    public function actionReports()
    {
        $q=$this->scoped();$request=Yii::$app->request;$personal=$this->actor()->role==='teacher';
        foreach(($personal?['period'=>'a.period_id']:['period'=>'a.period_id','category'=>'a.category','employee'=>'a.employee_id']) as $key=>$column)if($request->get($key)!==null && $request->get($key)!=='')$q->andWhere([$column=>$request->get($key)]);
        if(!$personal && $request->get('org')){
            $orgId=(int)$request->get('org');$orgs=(new Query())->select('id')->from('org')->where(['or',['id'=>$orgId],['parent_id'=>$orgId]])->column();
            $children=(new Query())->select('id')->from('org')->where(['parent_id'=>$orgs])->column();$q->andWhere(['u.org_id'=>array_merge($orgs,$children)]);
        }
        $activityQuery=clone $q;
        $q->andWhere(['a.status'=>'approved']);
        $q->leftJoin(['o'=>'org'],'o.id=u.org_id')->leftJoin(['f'=>'org'],'f.id=o.parent_id');
        $group=$personal?'employee':(string)$request->get('group','employee');if(!in_array($group,['employee','department','unit','faculty','university'],true))$group='employee';
        if($group==='employee'){$q->select(['employee_id'=>'u.id','employee'=>'u.name','category'=>'a.category','org'=>'o.name','score_cents'=>'SUM(a.score_cents)','applications'=>'COUNT(*)'])->groupBy(['u.id','u.name','a.category','o.name']);}
        elseif(in_array($group,['department','unit'],true)){$q->andWhere($group==='department'?['o.kind'=>'department','o.type_verified'=>1]:['or',['o.kind'=>'unit'],['o.type_verified'=>0]]);$q->select(['employee_id'=>'o.id','employee'=>'o.name','category'=>new \yii\db\Expression("''"),'org'=>'f.name','score_cents'=>'SUM(a.score_cents)','applications'=>'COUNT(*)'])->groupBy(['o.id','o.name','f.name']);}
        elseif($group==='faculty'){$name=new \yii\db\Expression("CASE WHEN o.kind='faculty' AND o.type_verified=1 THEN o.name WHEN f.kind='faculty' AND f.type_verified=1 THEN f.name ELSE 'Fakultetga biriktirilmagan' END");$key=new \yii\db\Expression("CASE WHEN o.kind='faculty' AND o.type_verified=1 THEN o.id WHEN f.kind='faculty' AND f.type_verified=1 THEN f.id ELSE 0 END");$q->select(['employee_id'=>$key,'employee'=>$name,'category'=>new \yii\db\Expression("''"),'org'=>new \yii\db\Expression("''"),'score_cents'=>'SUM(a.score_cents)','applications'=>'COUNT(*)'])->groupBy([$key,$name]);}
        else{$q->select(['employee_id'=>new \yii\db\Expression('0'),'employee'=>new \yii\db\Expression("'Universitet jami'"),'category'=>new \yii\db\Expression("''"),'org'=>new \yii\db\Expression("''"),'score_cents'=>'SUM(a.score_cents)','applications'=>'COUNT(*)']);}
        $rows=$q->orderBy(['score_cents'=>SORT_DESC,'employee'=>SORT_ASC])->all();
        if($group==='university' && isset($rows[0]) && !(int)$rows[0]['applications'])$rows=[];
        if(in_array($request->get('format'),['csv','xlsx'],true)){
            $table=[[$personal?'№':'O‘rin',$personal?'Xodim':'Xodim / bo‘lim','Toifa','Yuqori bo‘lim','Ball','Tasdiqlangan arizalar']];foreach($rows as $i=>$row)$table[]=[$i+1,$row['employee']??'Belgilanmagan',Catalog::CATEGORIES[$row['category']]??$row['category'],$row['org']??'',number_format($row['score_cents']/100,2,'.',''),$row['applications']];
            return \app\services\Export::download($table,(string)$request->get('format'));
        }
        $activityPage=max(1,(int)$request->get('activity_page',1));$activityTotal=(int)(clone $activityQuery)->count();$activityPage=min($activityPage,max(1,(int)ceil($activityTotal/25)));
        $activityRows=$activityQuery->select(['a.*','employee'=>'u.name','indicator'=>'i.name','period'=>'p.name'])->orderBy(['a.updated_at'=>SORT_DESC,'a.id'=>SORT_DESC])->limit(25)->offset(($activityPage-1)*25)->all();
        return $this->render('reports',compact('activityRows','activityTotal','activityPage')+['rows'=>$rows,'group'=>$group,'periods'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all(),'orgs'=>$personal?[]:(new Query())->from('org')->orderBy('name')->all()]);
    }
}
