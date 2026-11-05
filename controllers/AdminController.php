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
        $edit=$id?User::findOne($id):null;if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(Yii::$app->request->isPost){try{
            $data=Yii::$app->request->post();$user=$edit?:new User();$super=$this->actor()->role==='superadmin';
            if(!$super && (($edit && $edit->isAdmin()) || in_array($data['role']??'',['admin','superadmin'],true)))throw new DomainException('Administrator hisoblarini faqat superadministrator boshqaradi.');
            $username=trim((string)($data['username']??''));$name=trim((string)($data['name']??''));$role=(string)($data['role']??'');$category=(string)($data['category']??'');
            if(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$username))throw new DomainException('Login 3–80 ta lotin harfi, raqam, nuqta, tire yoki pastki chiziqdan iborat bo‘lsin.');
            if(!$name || mb_strlen($name)>200 || !isset(User::ROLES[$role]))throw new DomainException('Ism va rolni to‘g‘ri kiriting.');
            if($category!=='' && !isset(Catalog::CATEGORIES[$category]))throw new DomainException('Toifa noto‘g‘ri.');
            $active=isset($data['active'])?1:0;
            if($edit && (int)$edit->id===(int)$this->actor()->id && (!$active || $role!==$edit->role))throw new DomainException('O‘z hisobingizni o‘chira yoki rolini o‘zgartira olmaysiz.');
            $orgId=(int)($data['org_id']??0);$org=$orgId?(new Query())->from('org')->where(['id'=>$orgId])->one():null;
            if($orgId && !$org)throw new DomainException('Bo‘lim topilmadi.');
            if(($role==='head' && (!$org || $org['kind']!=='department')) || ($role==='faculty' && (!$org || $org['kind']!=='faculty')))throw new DomainException('Kafedra mudiriga kafedra, fakultet mas’uliga fakultet tanlang.');
            if(User::find()->where(['username'=>$username])->andFilterWhere($id?['<>','id',$id]:[])->exists())throw new DomainException('Bu login band.');
            $password=(string)($data['password']??'');if((!$edit || $password!=='') && (strlen($password)<12 || strlen($password)>128))throw new DomainException('Parol 12–128 belgi bo‘lsin.');
            $user->username=$username;$user->name=$name;$user->role=$role;$user->category=$category?:null;$user->org_id=$orgId?:null;$user->active=$active;
            $user->degree=mb_substr(trim((string)($data['degree']??'')),0,50);$user->position=mb_substr(trim((string)($data['position']??'')),0,100);
            if(!$edit)$user->created_at=time();if($password!==''){$user->setPassword($password);$user->must_change_password=1;}
            $user->save(false);Catalog::audit((int)$this->actor()->id,'user_save','User #'.$user->id);Yii::$app->session->setFlash('success','Foydalanuvchi saqlandi.');return $this->redirect(['users']);
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('users',['edit'=>$edit,'rows'=>User::find()->orderBy('name')->all(),'orgs'=>(new Query())->from('org')->orderBy('name')->all()]);
    }
    public function actionStructure(?int $id=null)
    {
        $edit=$id?(new Query())->from('org')->where(['id'=>$id])->one():null;
        if($id && !$edit)throw new \yii\web\NotFoundHttpException();
        if(Yii::$app->request->isPost){try{
            $name=trim((string)Yii::$app->request->post('name'));$kind=(string)Yii::$app->request->post('kind');$parent=(int)Yii::$app->request->post('parent_id');
            if($name==='' || mb_strlen($name)>200 || !in_array($kind,['university','faculty','department'],true))throw new DomainException('Nom va turini to‘g‘ri kiriting.');
            $p=$parent?(new Query())->from('org')->where(['id'=>$parent])->one():null;
            if(($kind==='faculty' && (!$p || $p['kind']!=='university')) || ($kind==='department' && (!$p || $p['kind']!=='faculty')) || ($kind==='university' && $parent))throw new DomainException('Tuzilma: universitet → fakultet → kafedra.');
            if($id && $kind!==$edit['kind'])throw new DomainException('Mavjud bo‘lim turi o‘zgartirilmaydi; yangi bo‘lim yarating.');
            $values=['name'=>$name,'kind'=>$kind,'parent_id'=>$parent?:null];$cmd=Yii::$app->db->createCommand();if($id)$cmd->update('org',$values,['id'=>$id])->execute();else $cmd->insert('org',$values)->execute();
            Catalog::audit((int)$this->actor()->id,'org_save',$name);Yii::$app->session->setFlash('success','Tuzilma saqlandi.');return $this->redirect(['structure']);
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('structure',['edit'=>$edit,'rows'=>(new Query())->from('org')->orderBy(['kind'=>SORT_DESC,'name'=>SORT_ASC])->all()]);
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
        $periodId=(int)Yii::$app->request->get('period',0);$category=(string)Yii::$app->request->get('category','DSC');$editId=(int)Yii::$app->request->get('id',0);
        if(Yii::$app->request->isPost){$this->postOnly();try{
            $rule=(new Query())->from('period_rule')->where(['id'=>$editId,'period_id'=>$periodId])->one();if(!$rule)throw new DomainException('Qoida topilmadi.');
            $indicator=(new Query())->from('indicator')->where(['id'=>$rule['indicator_id']])->one();$period=(new Query())->from('period')->where(['id'=>$periodId])->one();if($period['closed'])throw new DomainException('Yopiq davr qoidasi o‘zgartirilmaydi.');
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
        $q=(new Query())->select(['r.*','code'=>'i.code','name'=>'i.name','source_note'=>'i.source_note','source_text'=>'i.source_text','original_type'=>'i.calc_type'])->from(['r'=>'period_rule'])->innerJoin(['i'=>'indicator'],'i.id=r.indicator_id')->where(['r.period_id'=>$periodId,'i.category'=>$category]);
        $search=mb_substr(trim((string)Yii::$app->request->get('q','')),0,150);if($search!=='')$q->andWhere(['or',['like','i.name',$search],['like','i.code',$search]]);
        return $this->render('rules',['rows'=>$q->orderBy('i.id')->all(),'edit'=>$editId?(new Query())->select(['r.*','name'=>'i.name','source_note'=>'i.source_note','source_text'=>'i.source_text','original_type'=>'i.calc_type'])->from(['r'=>'period_rule'])->innerJoin(['i'=>'indicator'],'i.id=r.indicator_id')->where(['r.id'=>$editId,'r.period_id'=>$periodId])->one():null,'periodId'=>$periodId,'category'=>$category,'periods'=>(new Query())->from('period')->orderBy(['starts'=>SORT_DESC])->all()]);
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
        if(Yii::$app->request->isPost){try{
            $op=(string)Yii::$app->request->post('operation');
            if($op==='configure'){\app\services\Hemis::configure((string)Yii::$app->request->post('host'),(string)Yii::$app->request->post('token'));$message='HEMIS sozlamalari saqlandi.';}
            elseif($op==='test'){ $items=\app\services\Hemis::fetch('structure');$message='HEMIS ulanishi ishladi. Tuzilma yozuvlari: '.count($items); }
            elseif(in_array($op,['structure','employees'],true)){
                $items=\app\services\Hemis::fetch($op);$result=$op==='structure'?\app\services\Hemis::applyStructure($items):\app\services\Hemis::applyEmployees($items);
                $message='Import: '.$result['saved'].' saqlandi, '.$result['skipped'].' o‘tkazildi.';
            }else throw new DomainException('Amal noma’lum.');
            Catalog::audit((int)$this->actor()->id,'hemis_'.$op,$message);Yii::$app->session->setFlash('success',$message);return $this->refresh();
        }catch(\Throwable $e){$this->message($e);}}
        return $this->render('hemis',['settings'=>\app\services\Hemis::settings()]);
    }
    public function actionAudit(){return $this->render('audit',['rows'=>(new Query())->select(['a.*','actor'=>'u.name'])->from(['a'=>'audit'])->leftJoin(['u'=>'user'],'u.id=a.actor_id')->orderBy(['a.id'=>SORT_DESC])->limit(300)->all()]);}
}
