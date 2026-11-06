<?php
namespace app\controllers;
use Yii;
use DomainException;
use yii\db\Query;
use app\models\User;
use app\services\Catalog;
use app\services\Workflow;
class AdminController extends BaseController
{
    public function beforeAction($action){if(!parent::beforeAction($action))return false;$this->admin();return true;}
    public function actionUsers(?int $id=null)
    {
        $edit=$id?User::findOne(['id'=>$id,'deleted_at'=>null]):null;if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(Yii::$app->request->isPost && !$edit)\app\services\Management::authorize($this->actor());
        if(Yii::$app->request->isPost){try{
            $data=Yii::$app->request->post();$user=$edit?:new User();$super=$this->actor()->role==='superadmin';
            $previousRole=$edit?->role??'unassigned';
            $role=\app\services\AccountPolicy::role($this->actor(),$edit,array_key_exists('role',$data)?(string)$data['role']:null);
            $username=trim((string)($data['username']??''));$name=trim((string)($data['name']??''));$category=(string)($data['category']??'');
            if(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$username))throw new DomainException('Login 3–80 ta lotin harfi, raqam, nuqta, tire yoki pastki chiziqdan iborat bo‘lsin.');
            if(!$name || mb_strlen($name)>200 || !isset(User::ROLES[$role]))throw new DomainException('Ism va rolni to‘g‘ri kiriting.');
            if($category!=='' && !isset(Catalog::CATEGORIES[$category]))throw new DomainException('Toifa noto‘g‘ri.');
            $active=isset($data['active'])?1:0;
            if($edit && (int)$edit->id===(int)$this->actor()->id && (!$active || $role!==$edit->role))throw new DomainException('O‘z hisobingizni o‘chira yoki rolini o‘zgartira olmaysiz.');
            $orgId=(int)($data['org_id']??0);$org=$orgId?(new Query())->from('org')->where(['id'=>$orgId])->one():null;
            if($orgId && (!$edit || (int)$edit->org_id!==$orgId) && \app\services\Organization::blockedBy($orgId))throw new DomainException('Bloklangan bo‘limga yangi xodim biriktirilmaydi.');
            if($orgId && !$org)throw new DomainException('Bo‘lim topilmadi.');
            if(($role==='head' && (!$org || $org['kind']!=='department')) || ($role==='faculty' && (!$org || $org['kind']!=='faculty')))throw new DomainException('Kafedra mudiriga kafedra, fakultet mas’uliga fakultet tanlang.');
            if(User::find()->where(['username'=>$username])->andFilterWhere($id?['<>','id',$id]:[])->exists())throw new DomainException('Bu login band.');
            $password=(string)($data['password']??'');if((!$edit || $password!=='') && (strlen($password)<12 || strlen($password)>128))throw new DomainException('Parol 12–128 belgi bo‘lsin.');
            $hemisId=trim((string)($data['hemis_id']??$edit?->hemis_id??''));if($hemisId!=='' && !preg_match('/^[0-9]{1,40}$/',$hemisId))throw new DomainException('HEMIS ID faqat raqamlardan iborat bo‘lsin.');if($hemisId!=='' && User::find()->where(['hemis_id'=>$hemisId])->andFilterWhere($id?['<>','id',$id]:[])->exists())throw new DomainException('Bu HEMIS ID boshqa hisobga biriktirilgan.');
            $user->hemis_id=$hemisId?:null;
            $user->username=$username;$user->name=$name;$user->role=$role;$user->category=$category?:null;$user->org_id=$orgId?:null;$user->active=$active;
            $user->degree=mb_substr(trim((string)($data['degree']??'')),0,50);$user->position=mb_substr(trim((string)($data['position']??'')),0,100);
            $profile=json_decode($edit?->hemis_profile??'{}',true)?:[];$incoming=Yii::$app->request->post('profile',[]);foreach(['academic_rank','employment_form','employment_staff','employee_type','phone','email'] as $key){if(!array_key_exists($key,$incoming))continue;$value=trim((string)($incoming[$key]??''));$profile[$key]=$value===''?null:mb_substr($value,0,200);}$user->hemis_profile=json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if(!$edit)$user->created_at=time();if($password!==''){$user->setPassword($password);$user->must_change_password=1;}
            $user->save(false);Catalog::audit((int)$this->actor()->id,'user_save',json_encode(['user_id'=>$user->id,'role_before'=>$previousRole,'role_after'=>$role,'org_id'=>$user->org_id],JSON_THROW_ON_ERROR));Yii::$app->session->setFlash('success','Foydalanuvchi saqlandi.');return $this->redirect(['users']);
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('users',['edit'=>$edit,'rows'=>User::find()->where(['deleted_at'=>null])->orderBy('name')->all(),'orgs'=>(new Query())->from('org')->orderBy('name')->all()]);
    }
    public function actionStructure(?int $id=null)
    {
        if($id){if(Yii::$app->request->isPost)return $this->actionStructureEdit($id);return $this->redirect(['structure-edit','id'=>$id]);}
        if(Yii::$app->request->isPost)return $this->actionStructureEdit();
        return $this->render('structure',['rows'=>(new Query())->from('org')->orderBy('name')->all()]);
    }
    public function actionStructureEdit(?int $id=null,string $kind='university')
    {
        $edit=$id?(new Query())->from('org')->where(['id'=>$id])->one():null;
        if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(!$edit)\app\services\Management::authorize($this->actor());
        $kind=$edit['kind']??(string)Yii::$app->request->post('kind',$kind);if(!in_array($kind,['university','faculty','department'],true))throw new \yii\web\BadRequestHttpException('Tuzilma turi noto‘g‘ri.');
        if(Yii::$app->request->isPost){try{
            $operation=(string)Yii::$app->request->post('operation','save');
            if($operation==='delete'){if(!$id)throw new DomainException('Avval bo‘lim yarating.');\app\services\Management::deleteOrganization($this->actor(),$id);Yii::$app->session->setFlash('success','Bo‘lim o‘chirildi.');return $this->redirect(['structure']);}
            if(in_array($operation,['block','unblock'],true)){if(!$id)throw new DomainException('Avval bo‘lim yarating.');\app\services\Organization::setBlocked($this->actor(),$id,$operation==='block');Yii::$app->session->setFlash('success',$operation==='block'?'Bo‘lim bloklandi.':'Bo‘lim blokdan chiqarildi.');return $this->redirect(['structure-edit','id'=>$id]);}
            if($operation!=='save')throw new DomainException('Amal noto‘g‘ri.');
            $saved=\app\services\Organization::saveDetails($this->actor(),Yii::$app->request->post(),$id,$kind);
            Yii::$app->session->setFlash('success','Barcha rekvizitlar saqlandi.');return $this->redirect(['structure-edit','id'=>$saved]);
        }catch(\Throwable $e){if($e instanceof \yii\web\ForbiddenHttpException)throw $e;$this->message($e);}}
        return $this->render('structure-edit',['edit'=>$edit,'kind'=>$kind,'rows'=>(new Query())->from('org')->orderBy('name')->all()]);
    }
    public function actionDeleteStructure(int $id)
    {
        $this->postOnly();\app\services\Management::authorize($this->actor());
        try{\app\services\Management::deleteOrganization($this->actor(),$id);Yii::$app->session->setFlash('success','Bo‘lim o‘chirildi.');}catch(\Throwable $e){$this->message($e);}return $this->redirect(['structure']);
    }
    public function actionDeleteUser(int $id)
    {
        $this->postOnly();\app\services\Management::authorize($this->actor());
        try{\app\services\Management::deleteUser($this->actor(),$id);Yii::$app->session->setFlash('success','Xodim o‘chirildi. Oldingi hujjat va qarorlar tarixi saqlandi.');}catch(\Throwable $e){$this->message($e);}return $this->redirect(['users']);
    }
    public function actionIndicators(?int $id=null)
    {
        \app\services\Management::authorize($this->actor());$edit=$id?(new Query())->from('indicator')->where(['id'=>$id,'deleted_at'=>null])->one():null;
        if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(Yii::$app->request->isPost){try{if(Yii::$app->request->post('operation')==='delete'){if(!$id)throw new DomainException('Mezon tanlanmagan.');\app\services\Management::deleteIndicator($this->actor(),$id);}else \app\services\Management::saveIndicator($this->actor(),Yii::$app->request->post(),$id);Yii::$app->session->setFlash('success','Mezonlar katalogi yangilandi. Davr ballari alohida hisoblash qoidalarida boshqariladi.');return $this->redirect(['indicators']);}catch(\Throwable $e){$this->message($e);}}
        return $this->render('indicators',['edit'=>$edit,'rows'=>(new Query())->from('indicator')->where(['deleted_at'=>null])->orderBy(['category'=>SORT_ASC,'id'=>SORT_ASC])->all()]);
    }
    public function actionPeriods(?int $id=null)
    {
        $edit=$id?(new Query())->from('period')->where(['id'=>$id])->one():null;if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(Yii::$app->request->isPost){try{
            $name=trim((string)Yii::$app->request->post('name'));$starts=(string)Yii::$app->request->post('starts');$ends=(string)Yii::$app->request->post('ends');
            foreach([$starts,$ends] as $date){$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d || $d->format('Y-m-d')!==$date)throw new DomainException('Sanani to‘g‘ri kiriting.');}
            if(!$name || mb_strlen($name)>200 || $starts>$ends)throw new DomainException('Nom va davr oralig‘ini tekshiring.');
            Yii::$app->db->transaction(function()use($id,$edit,$name,$starts,$ends){
                if($id){Workflow::lockPeriod($id);if((new Query())->from('application')->where(['period_id'=>$id])->exists() && ($starts!==$edit['starts'] || $ends!==$edit['ends']))throw new DomainException('Arizali davr sanalarini o‘zgartirib bo‘lmaydi.');}
                $values=['name'=>$name,'starts'=>$starts,'ends'=>$ends,'closed'=>Yii::$app->request->post('closed')?1:0];$cmd=Yii::$app->db->createCommand();
                if($id)$cmd->update('period',$values,['id'=>$id])->execute();else{$cmd->insert('period',$values)->execute();$id=(int)Yii::$app->db->lastInsertID;}
                Catalog::createRules($id);Catalog::audit((int)$this->actor()->id,'period_save',$name);
            });Yii::$app->session->setFlash('success','Davr va uning mezonlari saqlandi.');return $this->redirect(['periods']);
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('periods',['edit'=>$edit,'rows'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all()]);
    }
    public function actionRules()
    {
        if(Yii::$app->request->isPost)\app\services\Management::authorize($this->actor());
        $periodId=(int)Yii::$app->request->get('period',0);$category=(string)Yii::$app->request->get('category','DSC');$editId=(int)Yii::$app->request->get('id',0);
        if(Yii::$app->request->isPost){$this->postOnly();try{
            $rule=(new Query())->from('period_rule')->where(['id'=>$editId,'period_id'=>$periodId])->one();if(!$rule)throw new DomainException('Qoida topilmadi.');
            $indicator=(new Query())->from('indicator')->where(['id'=>$rule['indicator_id'],'deleted_at'=>null])->one();if(!$indicator)throw new DomainException('Mezon o‘chirilgan.');$period=(new Query())->from('period')->where(['id'=>$periodId])->one();if($period['closed'])throw new DomainException('Yopiq davr qoidasi o‘zgartirilmaydi.');
            $decision=trim((string)Yii::$app->request->post('decision'));if(mb_strlen($decision)<10 || mb_strlen($decision)>3000)throw new DomainException('Qaror asosi 10–3000 belgi bo‘lsin (buyruq yoki tasdiqlangan izoh).');
            $type=(string)Yii::$app->request->post('calc_type');if(!in_array($type,['QatiyBall','BirlikUchun','ChegaraliYigindi','Ballgacha','DarajaShkalasi','PulFormulasi','XirshIndeksi'],true))throw new DomainException('Qo‘llab-quvvatlanadigan hisoblash turini tanlang.');
            if(in_array($indicator['calc_type'],['PulFormulasi','XirshIndeksi'],true) && $type!==$indicator['calc_type'])throw new DomainException('Formula turini almashtirib bo‘lmaydi.');
            $values=['enabled'=>Yii::$app->request->post('enabled')?1:0,'calc_type'=>$type,'once_only'=>Yii::$app->request->post('once_only')?1:0,'decision'=>$decision,'revision'=>(int)$rule['revision']+1];
            foreach(['base_score','upper_score','cap_score','formula_divisor'] as $field){$value=Yii::$app->request->post($field,'');if($value!=='' && (!is_numeric($value) || (float)$value<0 || (float)$value>100000000000000 || !preg_match('/^\d+(\.\d{1,2})?$/',(string)$value)))throw new DomainException('Ballar musbat, ko‘pi bilan ikki kasr xonali son bo‘lsin.');if($field!=='formula_divisor' && $value!=='' && (float)$value>1000000)throw new DomainException('Birlik balli va limit 1000000 dan oshmasin.');$values[$field]=$value===''?null:$value;}
            if(in_array($type,['Ballgacha','DarajaShkalasi'],true) && $values['upper_score']===null)throw new DomainException('Yuqori chegara kerak.');
            if(in_array($type,['QatiyBall','BirlikUchun','ChegaraliYigindi'],true) && $values['base_score']===null)throw new DomainException('Birlik balli kerak.');
            if($type==='ChegaraliYigindi' && $values['cap_score']===null)throw new DomainException('Maksimal jami ball kerak.');
            if($type==='QatiyBall' || $type==='XirshIndeksi')$values['once_only']=1;
            $group=trim((string)Yii::$app->request->post('exclusive_group',''));if($group!=='' && !preg_match('/^[A-Za-z0-9_-]{1,80}$/',$group))throw new DomainException('Guruh kodi lotin harflari, raqam, tire yoki pastki chiziqdan iborat bo‘lsin.');$values['exclusive_group']=$group?:null;
            $values['formula_mode']=(string)Yii::$app->request->post('formula_mode','');
            if(in_array($type,['PulFormulasi','XirshIndeksi'],true) && $values['base_score']===null)throw new DomainException('Formula birlik balli kerak.');
            if($type==='PulFormulasi' && ((float)$values['formula_divisor']<=0 || !in_array($values['formula_mode'],['proportional','floor'],true)))throw new DomainException('100 MHTEKM miqdori va hisoblash usulini belgilang.');
            Yii::$app->db->transaction(function()use($periodId,$editId,$values,$indicator,$decision){Workflow::lockPeriod($periodId);Yii::$app->db->createCommand()->update('period_rule',$values,['id'=>$editId])->execute();Catalog::audit((int)$this->actor()->id,'rule_update',$indicator['code'].' '.$decision.' '.json_encode($values,JSON_UNESCAPED_UNICODE));});
            Yii::$app->session->setFlash('success','Qoida saqlandi. Avval yuborilgan arizalar o‘z qoida nusxasini saqlaydi.');return $this->redirect(['rules','period'=>$periodId,'category'=>$category]);
        }catch(\Throwable $e){$this->message($e);}}
        $q=(new Query())->select(['r.*','code'=>'i.code','name'=>'i.name','source_note'=>'i.source_note','source_text'=>'i.source_text','original_type'=>'i.calc_type'])->from(['r'=>'period_rule'])->innerJoin(['i'=>'indicator'],'i.id=r.indicator_id')->where(['r.period_id'=>$periodId,'i.category'=>$category,'i.deleted_at'=>null]);
        $search=mb_substr(trim((string)Yii::$app->request->get('q','')),0,150);if($search!=='')$q->andWhere(['or',['like','i.name',$search],['like','i.code',$search]]);
        return $this->render('rules',['rows'=>$q->orderBy('i.id')->all(),'edit'=>$editId?(new Query())->select(['r.*','name'=>'i.name','source_note'=>'i.source_note','source_text'=>'i.source_text','original_type'=>'i.calc_type'])->from(['r'=>'period_rule'])->innerJoin(['i'=>'indicator'],'i.id=r.indicator_id')->where(['r.id'=>$editId,'r.period_id'=>$periodId,'i.deleted_at'=>null])->one():null,'periodId'=>$periodId,'category'=>$category,'periods'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all()]);
    }
    public function actionRoute()
    {
        if(Yii::$app->request->isPost){try{
            $names=Yii::$app->request->post('names',[]);$roles=Yii::$app->request->post('roles',[]);$steps=[];
            for($i=0;$i<5;$i++){if(trim($names[$i]??'')==='')continue;$rs=$roles[$i]??[];if(!$rs || array_diff($rs,['head','faculty','expert','admin','superadmin']))throw new DomainException('Har bosqichga tekshiruvchi rolini tanlang.');$steps[]=['position'=>count($steps)+1,'name'=>mb_substr(trim($names[$i]),0,200),'roles'=>json_encode(array_values($rs))];}
            if(!$steps)throw new DomainException('Kamida bitta bosqich kerak.');
            $last=json_decode(end($steps)['roles'],true);if(array_diff($last,['admin','superadmin']))throw new DomainException('Oxirgi bosqich faqat administratorlar uchun bo‘lsin.');
            Yii::$app->db->transaction(function()use($steps){Yii::$app->db->createCommand()->delete('route_step')->execute();foreach($steps as $s)Yii::$app->db->createCommand()->insert('route_step',$s)->execute();Catalog::audit((int)$this->actor()->id,'route_update',json_encode($steps,JSON_UNESCAPED_UNICODE));});Yii::$app->session->setFlash('success','Marshrut saqlandi. Avval yuborilgan arizalar oldingi marshrutda qoladi.');return $this->refresh();
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('route',['steps'=>(new Query())->from('route_step')->orderBy('position')->all()]);
    }
    public function actionHemis()
    {
        if(Yii::$app->request->isPost && in_array(Yii::$app->request->post('operation'),['structure','employees'],true))\app\services\Management::authorize($this->actor());
        if(Yii::$app->request->isPost){try{
            $op=(string)Yii::$app->request->post('operation');
            if(in_array($op,['structure','employees'],true) && function_exists('set_time_limit'))@set_time_limit(180);
            if($op==='configure'){\app\services\Hemis::configure((string)Yii::$app->request->post('host'),(string)Yii::$app->request->post('token'));$message='HEMIS sozlamalari saqlandi.';}
            elseif($op==='test'){ $items=\app\services\Hemis::fetch('structure');$message='HEMIS ulanishi ishladi. Tuzilma yozuvlari: '.count($items); }
            elseif(in_array($op,['structure','employees'],true)){
                $items=\app\services\Hemis::fetch($op);$result=$op==='structure'?\app\services\Hemis::applyStructure($items,(int)Yii::$app->request->post('university_id')?:null):\app\services\Hemis::applyEmployees($items);
                if($op==='employees'){$result['finished_at']=date('Y-m-d H:i:s');file_put_contents(Yii::getAlias('@runtime/hemis-import-last.json'),json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);}
                $message='Import: '.$result['saved'].' saqlandi, '.$result['skipped'].' o‘tkazildi.'.(!empty($result['unlinked'])?' Bolimga biriktirilmagan: '.$result['unlinked'].'. Avval tuzilmani import qiling, keyin xodimlarni qayta yuklang.':'');
            }else throw new DomainException('Amal noma’lum.');
            if(in_array($op,['structure','employees'],true) && !empty($result['reasons'])){foreach($result['reasons'] as $reason=>$count)$message.=' '.$reason.': '.$count.'.';}
            Catalog::audit((int)$this->actor()->id,'hemis_'.$op,$message);Yii::$app->session->setFlash($op==='employees' && empty($result['saved'])?'warning':'success',$message);return $this->refresh();
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('hemis',['settings'=>\app\services\Hemis::settings(),'universities'=>(new Query())->from('org')->where(['kind'=>'university'])->all(),'lastImport'=>is_file(Yii::getAlias('@runtime/hemis-import-last.json'))?json_decode(file_get_contents(Yii::getAlias('@runtime/hemis-import-last.json')),true):null]);
    }
    public function actionAudit(){return $this->render('audit',['rows'=>(new Query())->select(['a.*','actor'=>'u.name'])->from(['a'=>'audit'])->leftJoin(['u'=>'user'],'u.id=a.actor_id')->orderBy(['a.id'=>SORT_DESC])->limit(300)->all()]);}
}
