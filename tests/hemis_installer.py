"""Test the exact shipped repair on an existing app, preserving a saved API key."""
from pathlib import Path
import subprocess, tempfile, zipfile, shutil
ROOT=Path(__file__).resolve().parents[1]
work=Path(tempfile.mkdtemp(prefix='hemis-installer-',dir=ROOT/'runtime'))
pkg=work/'package';target=work/'Мавжуд KPI'
with zipfile.ZipFile(ROOT/'releases/KPI-HEMIS-fix.zip') as z: z.extractall(pkg)
originals=['start-native.bat','vendor/autoload.php','config/common.php','views/layouts/main.php','services/Hemis.php','config/local.php','runtime/kpi.sqlite','runtime/hemis.json']
for name in originals:
    file=target/name;file.parent.mkdir(parents=True,exist_ok=True);file.write_text('saved '+name)
def run(ok,contains):
    mapped=lambda p:'/app/'+str(p.relative_to(ROOT))
    result=subprocess.run(['docker','exec','--user','1000:1000','-w','/app','kpi-app-1','php','-n',mapped(pkg/'INSTALL-HEMIS.php'),mapped(target)],capture_output=True,text=True,timeout=60)
    assert (result.returncode==0)==ok,result.stdout+result.stderr
    assert contains in result.stdout+result.stderr,result.stdout+result.stderr
    for name in ['runtime/hemis.json','runtime/kpi.sqlite','config/local.php']:
        assert (target/name).read_text()=='saved '+name
    print('PASS:',contains,flush=True)
run(True,'TAYYOR')
backup=next((target/'runtime').glob('hemis-backup-*'))
assert (backup/'originals/services/Hemis.php').read_text()=='saved services/Hemis.php'
for file in (pkg/'KPI').rglob('*'):
    if file.is_file(): assert file.read_bytes()==(target/file.relative_to(pkg/'KPI')).read_bytes()
run(True,'TAYYOR')
file=pkg/'KPI/resources/tls/cacert.pem';file.write_bytes(b'corrupt')
run(False,'Paket fayli buzilgan')
file.unlink();run(False,'Paket fayli topilmadi')
shutil.rmtree(work)
print('PASS: repair installer preserves saved API key, database and local configuration.')
