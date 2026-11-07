<?php
namespace app\services;
use Yii;
use yii\db\{Query,Expression};
class Notifications
{
    public const LABELS=['save'=>'Qoralama saqlandi','submit'=>'Ariza tekshiruvga yuborildi','withdraw'=>'Ariza qoralamaga qaytarib olindi','approve'=>'Bosqich tasdiqlandi','return'=>'To‘ldirishga qaytarildi','reject'=>'Ariza rad etildi','appeal'=>'Apellyatsiya yuborildi','revoke'=>'Tasdiq bekor qilindi','admin_edit'=>'Ariza administrator tomonidan tahrirlandi','remove_evidence'=>'Dalil olib tashlandi'];
    public static function query(int $userId): Query
    {
        return (new Query())->from(['r'=>'review'])->innerJoin(['a'=>'application'],'a.id=r.application_id')->innerJoin(['u'=>'user'],'u.id=r.actor_id')->where(['a.employee_id'=>$userId,'r.action'=>array_keys(self::LABELS)]);
    }
    public static function lastRead(int $userId): int
    {
        return (int)(new Query())->from('notification_receipt')->select('last_review_id')->where(['user_id'=>$userId])->scalar();
    }
    public static function unreadCount(int $userId): int
    {
        return (int)self::query($userId)->andWhere(['>','r.id',self::lastRead($userId)])->count();
    }
    public static function markRead(int $userId,int $reviewId): void
    {
        if($reviewId<1)return;
        // Monotonic cursor: an older tab must not make newer messages unread again.
        Yii::$app->db->createCommand()->upsert('notification_receipt',['user_id'=>$userId,'last_review_id'=>$reviewId],['last_review_id'=>new Expression('CASE WHEN last_review_id < '.$reviewId.' THEN '.$reviewId.' ELSE last_review_id END')])->execute();
    }
}
