<?php
namespace app\commands;
use Yii;
use yii\console\Controller;
class BackupController extends Controller
{
    public function actionCreate(): int
    {
        if(getenv('KPI_BACKUP_MAINTENANCE')!=='1'){$this->stderr("Avval web xizmatini to‘xtating, keyin KPI_BACKUP_MAINTENANCE=1 bilan bajaring.\n");return 1;}
        if(Yii::$app->db->driverName!=='sqlite'){$this->stderr("MySQL uchun mysqldump va runtime/evidence hamda config/local.php nusxasini ishlating.\n");return 1;}
        $dir=Yii::getAlias('@app/backups');if(!is_dir($dir))mkdir($dir,0700,true);
        $stamp=date('Ymd-His').'-'.bin2hex(random_bytes(3));$dbFile=$dir.'/'.$stamp.'.sqlite';
        Yii::$app->db->createCommand('VACUUM INTO :path',[':path'=>$dbFile])->execute();
        $path=$dir.'/kpi-'.$stamp.'.zip';$zip=new \ZipArchive();if($zip->open($path,\ZipArchive::CREATE)!==true)throw new \RuntimeException('Backup ochilmadi.');
        $zip->addFile($dbFile,'runtime/kpi.sqlite');$zip->addFile(Yii::getAlias('@app/config/local.php'),'config/local.php');
        $hemis=Yii::getAlias('@runtime/hemis.json');if(is_file($hemis))$zip->addFile($hemis,'runtime/hemis.json');
        $evidence=Yii::getAlias('@runtime/evidence');if(is_dir($evidence))foreach(new \DirectoryIterator($evidence) as $file)if($file->isFile()&&!$file->isLink())$zip->addFile($file->getPathname(),'runtime/evidence/'.$file->getFilename());
        $zip->close();unlink($dbFile);chmod($path,0600);$this->stdout("Zaxira saqlandi: ".$path."\nMaxfiy sozlamalar bor; xavfsiz saqlang.\n");return 0;
    }
}
