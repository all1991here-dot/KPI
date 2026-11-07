<?php
namespace app\services;
use Yii;
use app\models\User;
use yii\db\{Query,Expression};
class Notifications
{
    public const LABELS=['save'=>'Qoralama saqlandi','submit'=>'Ariza tekshiruvga yuborildi','withdraw'=>'Ariza qoralamaga qaytarib olindi','approve'=>'Bosqich tasdiqlandi','return'=>'To‘ldirishga qaytarildi','reject'=>'Ariza rad etildi','appeal'=>'Apellyatsiya yuborildi','revoke'=>'Tasdiq bekor qilindi','admin_edit'=>'Ariza administrator tomonidan tahrirlandi','remove_evidence'=>'Dalil olib tashlandi'];
    private static function ownerQuery(int $userId): Query
    {
        return (new Query())->from(['r'=>'review'])->innerJoin(['a'=>'application'],'a.id=r.application_id')->innerJoin(['u'=>'user'],'u.id=r.actor_id')->where(['a.employee_id'=>$userId,'r.action'=>array_keys(self::LABELS)]);
    }
    public static function assignmentIds(int $userId): array
    {
        $actor=User::findIdentity($userId);
        if(!$actor || !$actor->canReview())return [];
        $latest=(new Query())->from('review')->select(['application_id','event_id'=>'MAX(id)'])->groupBy('application_id');
        $query=(new Query())->from(['a'=>'application'])->innerJoin(['latest'=>$latest],'latest.application_id=a.id')->innerJoin(['period'=>'period'],'period.id=a.period_id')->select(['a.*','event_id'=>'latest.event_id'])->where(['a.status'=>['submitted','review'],'period.closed'=>0])->andWhere(['<>','a.employee_id',$userId]);
        $employees=Access::reviewEmployeeIds($actor);if($employees!==null)$query->andWhere(['a.employee_id'=>$employees]);
        $ids=[];
        foreach($query->all() as $application){
            $steps=json_decode($application['route_snapshot']??'[]',true);$step=$steps[(int)$application['step']]??null;
            if($step && in_array($actor->role,json_decode($step['roles'],true),true))$ids[]=(int)$application['event_id'];
        }
        return $ids;
    }
    public static function query(int $userId): Query
    {
        $query=self::ownerQuery($userId);$assignments=self::assignmentIds($userId);
        if($assignments){
            $latest=(new Query())->from(['last'=>'review'])->select('MAX(last.id)')->where('last.application_id=a.id');
            $query->orWhere(['and',['r.id'=>$assignments],['a.status'=>['submitted','review']],['r.id'=>$latest]]);
        }
        return $query;
    }
    public static function lastRead(int $userId,bool $assignments=false): int
    {
        return (int)(new Query())->from($assignments?'reviewer_notification_receipt':'notification_receipt')->select('last_review_id')->where(['user_id'=>$userId])->scalar();
    }
    public static function unreadCount(int $userId): int
    {
        $own=(int)self::ownerQuery($userId)->andWhere(['>','r.id',self::lastRead($userId)])->count();
        $last=self::lastRead($userId,true);
        return $own+count(array_filter(self::assignmentIds($userId),fn($id)=>$id>$last));
    }
    public static function markRead(int $userId,int $reviewId,bool $assignments=false): void
    {
        if($reviewId<1)return;
        // Separate monotonic cursors keep existing review tasks new after installation.
        Yii::$app->db->createCommand()->upsert($assignments?'reviewer_notification_receipt':'notification_receipt',['user_id'=>$userId,'last_review_id'=>$reviewId],['last_review_id'=>new Expression('CASE WHEN last_review_id < '.$reviewId.' THEN '.$reviewId.' ELSE last_review_id END')])->execute();
    }
}
