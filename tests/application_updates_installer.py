"""Exercise the shipped update, backups, repeat installation and corrupt-file rejection."""
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
work = Path(tempfile.mkdtemp(prefix='application-updates-installer-', dir=ROOT/'runtime'))
package = work/'package'
target = work/'Mavjud KPI'
try:
    with zipfile.ZipFile(ROOT/'releases/KPI-Ariza-Holati-Bildirishnoma-update.zip') as archive:
        archive.extractall(package)
    for name in ['start-native.bat','vendor/autoload.php','config/common.php','views/layouts/main.php',
                 'controllers/KpiController.php','views/kpi/reports.php','config/local.php',
                 'runtime/kpi.sqlite','runtime/hemis.json']:
        path=target/name;path.parent.mkdir(parents=True,exist_ok=True);path.write_text('saved '+name)
    def run(success):
        mapped=lambda p:'/app/'+str(p.relative_to(ROOT))
        result=subprocess.run(['docker','run','--rm','--user','1000:1000','--entrypoint','php',
                               '-v',str(ROOT)+':/app','yiisoftware/yii2-php:8.3-apache','-n',
                               mapped(package/'INSTALL-STATUS.php'),mapped(target)],capture_output=True,text=True,timeout=60)
        assert (result.returncode==0)==success,result.stdout+result.stderr
        for name in ['runtime/kpi.sqlite','runtime/hemis.json','config/local.php']:
            assert (target/name).read_text()=='saved '+name
        return result.stdout+result.stderr
    assert 'TAYYOR' in run(True)
    backup=next((target/'runtime').glob('application-status-backup-*'))
    assert (backup/'originals/controllers/KpiController.php').read_text()=='saved controllers/KpiController.php'
    assert (backup/'database/kpi.sqlite').read_text()=='saved runtime/kpi.sqlite'
    for path in (package/'KPI').rglob('*'):
        if path.is_file():assert path.read_bytes()==(target/path.relative_to(package/'KPI')).read_bytes()
    assert 'TAYYOR' in run(True)
    before=(target/'controllers/KpiController.php').read_bytes()
    (package/'KPI/controllers/KpiController.php').write_bytes(b'corrupt')
    assert 'Paket fayli buzilgan' in run(False)
    assert (target/'controllers/KpiController.php').read_bytes()==before
    print('PASS: package installs, backs up originals/database, repeats safely, preserves settings and rejects corrupt payloads.')
finally:
    shutil.rmtree(work)
