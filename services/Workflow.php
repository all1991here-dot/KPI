<?php
namespace app\services;
use Yii;
use DomainException;
use app\models\User;
use yii\db\Query;
use yii\web\UploadedFile;
class Workflow
{
    public const STATUSES = ['draft'=>'Qoralama','submitted'=>'Yuborilgan','review'=>'Tekshiruvda','returned'=>'Qaytarilgan','approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan'];
    public static function application(int $id): array
    {
        $row=(new Query())->from('application')->where(['id'=>$id])->one();
        if (!$row) throw new \yii\web\NotFoundHttpException('Ariza topilmadi.');
        return $row;
    }
    public static function save(User $actor, array $data, array $files, ?int $id=null): int
    {
        $old=$id?self::application($id):null;
        if ($old && !$actor->isAdmin() && ((int)$old['employee_id']!==(int)$actor->id || !in_array($old['status'],['draft','returned'],true))) throw new DomainException('Faqat o‘zingizning qoralama yoki qaytarilgan arizangizni tahrirlaysiz.');
        $employee=$old?User::findOne($old['employee_id']):$actor;
        if(!$employee)throw new DomainException('Ariza egasi topilmadi.');
        $category=$old && $actor->isAdmin()?$old['category']:$employee->category;
        $period=(new Query())->from('period')->where(['id'=>(int)($data['period_id']??0)])->one();
        $indicator=(new Query())->from('indicator')->where(['id'=>(int)($data['indicator_id']??0),'category'=>$category])->one();
        if (!$period || ($period['closed'] && !($old && $actor->isAdmin()))) throw new DomainException('Ochiq baholash davrini tanlang.');
        if (!$indicator) throw new DomainException('Profilingiz toifasiga mos ko‘rsatkichni tanlang.');
        $title=trim((string)($data['title']??''));$date=(string)($data['activity_date']??'');$quantity=filter_var($data['quantity']??null,FILTER_VALIDATE_INT);
        if ($title==='' || mb_strlen($title)>250) throw new DomainException('Faoliyat nomi 1–250 belgidan iborat bo‘lsin.');
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if (!$parsed || $parsed->format('Y-m-d')!==$date || $date<$period['starts'] || $date>$period['ends']) throw new DomainException('Faoliyat sanasi tanlangan davr ichida bo‘lishi kerak.');
        if ($quantity===false || $quantity<1 || $quantity>100000) throw new DomainException('Miqdor musbat butun son bo‘lishi kerak (maksimal 100000).');
        $notes=trim((string)($data['notes']??''));if(mb_strlen($notes)>10000)throw new DomainException('Izoh juda uzun.');
        $metric=$data['metric_value']??'';
        if($metric!=='' && (!preg_match('/^\d{1,13}(\.\d{1,2})?$/',(string)$metric) || (float)$metric>1000000000000))throw new DomainException('Formula qiymatini to‘g‘ri kiriting.');
        $metric=$metric===''?null:$metric;
        $prepared=[];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || $file->error!==UPLOAD_ERR_OK || !$file->tempName) throw new DomainException('Fayl yuklanmadi.');
            if ($file->size<1 || $file->size>10485760) throw new DomainException('Dalil fayli 10 MiB dan oshmasligi kerak.');
            $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName);$map=['application/pdf'=>'pdf','image/png'=>'png','image/jpeg'=>'jpg'];
            if (!isset($map[$mime])) throw new DomainException('Faqat haqiqiy PDF, PNG yoki JPG fayl yuklang.');
            $prepared[]=[$file,$mime,$map[$mime]];
        }
        $count=$id?(int)(new Query())->from('evidence')->where(['application_id'=>$id])->count():0;
        if ($count+count($prepared)>10) throw new DomainException('Har arizaga maksimal 10 ta dalil biriktiring.');
        $paths=[];
        try {
            return Yii::$app->db->transaction(function()use($actor,$data,$old,$id,$period,$indicator,$category,$title,$date,$quantity,$notes,$metric,$prepared,&$paths){
                self::lockPeriod((int)$period['id']);
                if ($id) { self::lockPeriod((int)$old['period_id']);$fresh=self::application($id);if(!$actor->isAdmin() && !in_array($fresh['status'],['draft','returned'],true))throw new DomainException('Ariza holati o‘zgardi. Sahifani yangilang.'); }
                $values=['period_id'=>$period['id'],'indicator_id'=>$indicator['id'],'category'=>$category,'title'=>$title,'activity_date'=>$date,'quantity'=>$quantity,'metric_value'=>$metric,'notes'=>$notes,'updated_at'=>time(),'expert_score'=>null,'score_cents'=>0,'rule_snapshot'=>null,'route_snapshot'=>null,'step'=>0];
                if($id && $actor->isAdmin()){$balance=(int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents');if($balance)self::ledger($id,$actor,-$balance,'Administrator tahriri: avvalgi ball bekor qilindi.');$values['status']='draft';Catalog::audit((int)$actor->id,'application_admin_edit',json_encode(['id'=>$id,'before'=>$fresh,'after'=>$values],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));self::history($id,$actor,'admin_edit','Administrator tahriri. Oldingi holat: '.$fresh['status'].'. Qayta tasdiqlash talab etiladi.',0);}
                if ($id) Yii::$app->db->createCommand()->update('application',$values,['id'=>$id])->execute();
                else {Yii::$app->db->createCommand()->insert('application',$values+['employee_id'=>$actor->id,'uuid'=>Yii::$app->security->generateRandomString(32),'status'=>'draft','created_at'=>time()])->execute();$id=(int)Yii::$app->db->getLastInsertID();}
                $dir=Yii::getAlias('@runtime/evidence');if(!is_dir($dir))mkdir($dir,0700,true);
                foreach($prepared as [$file,$mime,$ext]){
                    $storage=Yii::$app->security->generateRandomString(40).'.'.$ext;$path=$dir.'/'.$storage;
                    if(!$file->saveAs($path))throw new DomainException('Dalilni saqlab bo‘lmadi.');$paths[]=$path;chmod($path,0600);
                    Yii::$app->db->createCommand()->insert('evidence',['application_id'=>$id,'name'=>mb_substr(basename(str_replace('\\','/',$file->name)),0,250),'storage_name'=>$storage,'mime'=>$mime,'size'=>filesize($path),'sha256'=>hash_file('sha256',$path)])->execute();
                }
                self::history($id,$actor,'save','Qoralama saqlandi.',0);return $id;
            });
        }catch(\Throwable $e){foreach($paths as $path)@unlink($path);throw $e;}
    }
    public static function removeEvidence(User $actor,int $id): void
    {
        $file=(new Query())->from('evidence')->where(['id'=>$id])->one();if(!$file)throw new DomainException('Dalil topilmadi.');
        $app=self::application((int)$file['application_id']);
        if (!$actor->isAdmin() && ((int)$app['employee_id']!==(int)$actor->id || !in_array($app['status'],['draft','returned'],true)))throw new DomainException('Dalilni o‘chirishga ruxsat yo‘q.');
        Yii::$app->db->transaction(function()use($actor,$id,$file,$app){self::lockPeriod((int)$app['period_id']);$fresh=self::application((int)$app['id']);if(!$actor->isAdmin() && !in_array($fresh['status'],['draft','returned'],true))throw new DomainException('Ariza holati o‘zgardi.');if($actor->isAdmin()){$balance=(int)(new Query())->from('score_entry')->where(['application_id'=>$app['id']])->sum('amount_cents');if($balance)self::ledger((int)$app['id'],$actor,-$balance,'Dalil administrator tomonidan tahrirlandi.');Yii::$app->db->createCommand()->update('application',['status'=>'draft','step'=>0,'score_cents'=>0,'expert_score'=>null,'rule_snapshot'=>null,'route_snapshot'=>null,'updated_at'=>time()],['id'=>$app['id']])->execute();}Yii::$app->db->createCommand()->delete('evidence',['id'=>$id])->execute();self::history((int)$app['id'],$actor,'remove_evidence','Dalil olib tashlandi.',0);});
        @unlink(Yii::getAlias('@runtime/evidence/').$file['storage_name']);
    }
    public static function act(User $actor,int $id,string $action,string $reason='', $expert=null): void
    {
        Yii::$app->db->transaction(function()use($actor,$id,$action,$reason,$expert){
            $app=self::application($id);self::lockPeriod((int)$app['period_id']);$app=self::application($id);
            $period=(new Query())->from('period')->where(['id'=>$app['period_id']])->one();
            if($period['closed'])throw new DomainException('Baholash davri yopilgan.');
            $reason=trim($reason);if(mb_strlen($reason)>2000)throw new DomainException('Izoh 2000 belgidan oshmasin.');
            $owner=(int)$app['employee_id']===(int)$actor->id;
            if(in_array($action,['submit','appeal'],true)){
                if(!$owner)throw new DomainException('Faqat ariza egasi yuborishi mumkin.');
                $allowed=$action==='appeal'?['returned','rejected']:['draft','returned'];
                if(!in_array($app['status'],$allowed,true))throw new DomainException('Bu holatda ariza yuborilmaydi.');
                if($action==='appeal' && $reason==='')throw new DomainException('Apellyatsiya sababini kiriting.');
                $employee=User::findOne($app['employee_id']);
                if(!$employee || !$employee->active || $employee->category!==$app['category'])throw new DomainException('Xodim toifasi o‘zgargan. Arizani yangilang.');
                $rule=(new Query())->from('period_rule')->where(['period_id'=>$app['period_id'],'indicator_id'=>$app['indicator_id']])->one();
                if(!$rule || !$rule['enabled'])throw new DomainException('Ko‘rsatkich qoidasi hali tasdiqlanmagan. Administratorga murojaat qiling.');
                $files=(new Query())->from('evidence')->where(['application_id'=>$id])->all();
                if(!$files)throw new DomainException('Kamida bitta dalil yuklang.');
                self::checkDuplicates($app,$files);
                $steps=(new Query())->from('route_step')->orderBy('position')->all();if(!$steps)throw new DomainException('Tasdiqlash marshruti sozlanmagan.');
                $app['rule_snapshot']=json_encode($rule,JSON_UNESCAPED_UNICODE);$app['route_snapshot']=json_encode($steps,JSON_UNESCAPED_UNICODE);
                $app['status']='submitted';$app['step']=0;$app['expert_score']=null;
                if(in_array($rule['calc_type'],['Ballgacha','DarajaShkalasi','QatiyBall','PulFormulasi','XirshIndeksi'],true) && (int)$app['quantity']!==1)throw new DomainException('Bu ko‘rsatkich uchun miqdor 1 bo‘lishi kerak.');
                $app['score_cents']=self::preview($app,$rule);
            }elseif($action==='withdraw'){
                if(!$owner || $app['status']!=='submitted')throw new DomainException('Faqat tekshiruv boshlanmagan arizani qaytarib olasiz.');
                $app['status']='draft';$app['step']=0;$app['score_cents']=0;
            }else{
                if(!Access::canView($actor,$app) || $owner)throw new DomainException('O‘z arizangizni tekshira olmaysiz yoki ruxsat yo‘q.');
                $steps=json_decode($app['route_snapshot']??'[]',true);$index=(int)$app['step'];
                $step=$action==='revoke'?($steps?end($steps):null):($steps[$index]??null);
                if(!$step || !in_array($actor->role,json_decode($step['roles'],true),true))throw new DomainException('Bu tekshiruv bosqichi sizga tegishli emas.');
                if($reason==='')throw new DomainException('Qaror izohini kiriting.');
                if($action==='revoke'){
                    if($app['status']!=='approved')throw new DomainException('Faqat tasdiqlangan qaror bekor qilinadi.');
                    $balance=(int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents');
                    self::ledger($id,$actor,-$balance,$reason);$app['score_cents']=0;$app['status']='review';$app['step']=count($steps)-1;
                }else{
                    if(!in_array($app['status'],['submitted','review'],true))throw new DomainException('Ariza tekshiruv holatida emas.');
                    if($action==='return'){$app['status']='returned';$app['step']=0;$app['score_cents']=0;}
                    elseif($action==='reject'){$app['status']='rejected';$app['step']=0;$app['score_cents']=0;}
                    elseif($action==='approve'){
                        $rule=json_decode($app['rule_snapshot'],true);
                        if(in_array($rule['calc_type'],['Ballgacha','DarajaShkalasi'],true) && $expert!==null && $expert!==''){
                            if(!is_numeric($expert))throw new DomainException('Ekspert bahosi son bo‘lsin.');$app['expert_score']=$expert;
                        }
                        if($index===count($steps)-1){
                            self::checkDuplicates($app,(new Query())->from('evidence')->where(['application_id'=>$id])->all());
                            self::checkExclusive($app,$rule);
                            [$used,$count]=self::used($app);
                            $score=ScoreCalculator::calculate($rule,(int)$app['quantity'],$app['expert_score'],$used,$count,$app['metric_value']);
                            $app['score_cents']=$score;$app['status']='approved';self::ledger($id,$actor,$score,$reason);
                        }else{$app['status']='review';$app['step']=$index+1;}
                    }else throw new DomainException('Amal noma’lum.');
                }
            }
            $values=array_intersect_key($app,array_flip(['status','step','score_cents','rule_snapshot','route_snapshot','expert_score']));$values['updated_at']=time();
            Yii::$app->db->createCommand()->update('application',$values,['id'=>$id])->execute();
            self::history($id,$actor,$action,$reason?:'Ariza yuborildi.',(int)$app['step']);
        });
    }
    public static function lockPeriod(int $id): void
    {
        // Acquire the shared period write lock before reading aggregate limits.
        Yii::$app->db->createCommand()->update('period',['closed'=>new \yii\db\Expression('closed')],['id'=>$id])->execute();
    }
    private static function used(array $app): array
    {
        $query=(new Query())->from('application')->where(['employee_id'=>$app['employee_id'],'period_id'=>$app['period_id'],'indicator_id'=>$app['indicator_id'],'status'=>'approved'])->andWhere(['<>','id',$app['id']]);
        return [(int)(clone $query)->sum('score_cents'),(int)$query->count()];
    }
    private static function checkExclusive(array $app,array $rule): void
    {
        if(empty($rule['exclusive_group']))return;
        $rows=(new Query())->select(['rule_snapshot'])->from('application')->where(['employee_id'=>$app['employee_id'],'period_id'=>$app['period_id'],'status'=>'approved'])->andWhere(['<>','id',$app['id']])->all();
        foreach($rows as $row){$snapshot=json_decode($row['rule_snapshot'],true);if(($snapshot['exclusive_group']??'')===$rule['exclusive_group'])throw new DomainException('Bu o‘zaro istisno guruhida boshqa natija tasdiqlangan. Avval oldingi qarorni ko‘rib chiqing.');}
    }
    private static function preview(array $app,array $rule): int
    {
        if(in_array($rule['calc_type'],['Ballgacha','DarajaShkalasi'],true))return 0;
        [$used,$count]=self::used($app);return ScoreCalculator::calculate($rule,(int)$app['quantity'],null,$used,$count,$app['metric_value']);
    }
    private static function checkDuplicates(array $app,array $files): void
    {
        $hashes=array_column($files,'sha256');if(!$hashes)throw new DomainException('Dalil topilmadi.');
        if(count($hashes)!==count(array_unique($hashes)))throw new DomainException('Bir xil dalil ikki marta biriktirilgan.');
        $found=(new Query())->from(['e'=>'evidence'])->innerJoin(['a'=>'application'],'a.id=e.application_id')->where(['a.employee_id'=>$app['employee_id'],'a.period_id'=>$app['period_id'],'a.indicator_id'=>$app['indicator_id'],'a.status'=>['submitted','review','approved'],'e.sha256'=>$hashes])->andWhere(['<>','a.id',$app['id']])->exists();
        if($found)throw new DomainException('Bu dalil shu ko‘rsatkich uchun boshqa faol arizada mavjud.');
    }
    private static function history(int $id,User $actor,string $action,string $reason,int $step): void
    {
        Yii::$app->db->createCommand()->insert('review',['application_id'=>$id,'actor_id'=>$actor->id,'action'=>$action,'step'=>$step,'reason'=>$reason,'created_at'=>time()])->execute();
    }
    private static function ledger(int $id,User $actor,int $amount,string $reason): void
    {
        Yii::$app->db->createCommand()->insert('score_entry',['application_id'=>$id,'actor_id'=>$actor->id,'amount_cents'=>$amount,'reason'=>$reason,'created_at'=>time()])->execute();
    }
}
