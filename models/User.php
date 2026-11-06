<?php
namespace app\models;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
class User extends ActiveRecord implements IdentityInterface
{
    public const ROLES = ['unassigned'=>'Rol biriktirilmagan', 'teacher' => 'O‘qituvchi', 'head' => 'Kafedra mudiri', 'faculty' => 'Fakultet mas’uli', 'expert' => 'Ekspert', 'admin' => 'KPI administratori', 'superadmin' => 'Superadministrator', 'management' => 'Rahbariyat'];
    public static function tableName() { return '{{%user}}'; }
    public static function findIdentity($id) { return static::findOne(['id' => $id, 'active' => 1]); }
    public static function findIdentityByAccessToken($token, $type = null) { return null; }
    public function getId() { return $this->id; }
    public function getAuthKey() { return $this->auth_key; }
    public function validateAuthKey($authKey) { return hash_equals($this->auth_key, (string)$authKey); }
    public function isAdmin(): bool { return in_array($this->role, ['admin', 'superadmin'], true); }
    public function canReview(): bool { return $this->isAdmin() || in_array($this->role, ['head','faculty','expert'], true); }
    public function setPassword(string $password): void { $this->password_hash = Yii::$app->security->generatePasswordHash($password); $this->auth_key = Yii::$app->security->generateRandomString(); }
}
