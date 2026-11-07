<?php
require __DIR__.'/http-fixtures.php';
use app\models\User;
$teacher=User::findOne(['username'=>'http_teacher']);$teacher->hemis_profile=json_encode(['photo_url'=>'https://student.fdu.uz/static/slow-teacher.png']);$teacher->save(false);
foreach(['operator'=>'admin','unassigned'=>'unassigned'] as $name=>$role){$user=new User();$user->username='http_'.$name;$user->name='HTTP '.$name;$user->role=$role;$user->category='PHD';$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->password_hash=$teacher->password_hash;$user->auth_key=Yii::$app->security->generateRandomString();$user->save(false);}
$i=0;
foreach(['teacher'=>180,'head'=>20,'faculty'=>15,'expert'=>10,'admin'=>10,'superadmin'=>5,'management'=>5,'unassigned'=>5] as $role=>$number){
    for($j=0;$j<$number;$j++){
        $user=new User();$user->username='mass_'.str_pad((string)$i,4,'0',STR_PAD_LEFT);$user->name='Mass user '.str_pad((string)$i,4,'0',STR_PAD_LEFT);$user->role=$role;$user->category='DARAJASIZ';$user->org_id=$role==='faculty'?2:(in_array($role,['teacher','head'],true)?3:null);$user->active=$i%7?1:0;$user->must_change_password=0;$user->created_at=time();$user->password_hash=$teacher->password_hash;$user->auth_key=Yii::$app->security->generateRandomString();$user->hemis_profile=json_encode(['photo_url'=>'https://student.fdu.uz/static/slow-photo-'.$i.'.png']);$user->save(false);$i++;
    }
}
echo "Admin performance fixtures ready: 250 extra users with photo metadata.\n";
