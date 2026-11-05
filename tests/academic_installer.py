"""Exercise the delivered ZIP installer with PHP core (-n), without app bootstrap."""
from pathlib import Path
import subprocess, tempfile, zipfile, shutil
ROOT = Path(__file__).resolve().parents[1]
work = Path(tempfile.mkdtemp(prefix='academic-installer-', dir=ROOT / 'runtime'))
pkg = work / 'package'
with zipfile.ZipFile(ROOT / 'releases/KPI-FarDU-Academic-update.zip') as z:
    z.extractall(pkg)
target = work / 'Мавжуд KPI test'
for name in ['start-native.bat', 'vendor/autoload.php', 'config/common.php', 'config/local.php', 'runtime/kpi.sqlite', 'views/layouts/main.php']:
    file = target / name
    file.parent.mkdir(parents=True, exist_ok=True)
    file.write_bytes(('original ' + name).encode())
def run(ok, phrase):
    def mapped(path): return '/app/' + str(path.relative_to(ROOT))
    result = subprocess.run(['docker','run','--rm','--entrypoint','php','--user','1000:1000','--mount',f'type=bind,src={ROOT},dst=/app','-w','/app','yiisoftware/yii2-php:8.3-apache','-n',mapped(pkg/'INSTALL-ACADEMIC.php'),mapped(target)], capture_output=True, text=True, timeout=120)
    assert (result.returncode == 0) == ok, result.stdout + result.stderr
    assert phrase in result.stdout + result.stderr, result.stdout + result.stderr
    print("PASS:", phrase, flush=True)
    for name in ['config/local.php','runtime/kpi.sqlite']:
        assert (target/name).read_bytes() == ('original '+name).encode()
run(True,'TAYYOR')
backups=list((target/'runtime').glob('academic-backup-*'))
assert (backups[0]/'originals/views/layouts/main.php').read_bytes()==b'original views/layouts/main.php'
for file in (pkg/'KPI').rglob('*'):
    if file.is_file(): assert file.read_bytes()==(target/file.relative_to(pkg/'KPI')).read_bytes()
run(True,'TAYYOR')
snapshot={str(f.relative_to(target)):f.read_bytes() for f in target.rglob('*') if f.is_file() and 'academic-backup-' not in str(f)}
payload=pkg/'KPI/web/css/academic.css'; original=payload.read_bytes()
payload.write_bytes(b'corrupt')
run(False,'Paket fayli buzilgan')
payload.unlink()
run(False,'Paket fayli topilmadi')
payload.write_bytes(original)
file=target/'views/layouts/main.php'; file.chmod(0o444)
run(False,'Faylga yozish mumkin emas')
file.chmod(0o644)
for name,data in snapshot.items(): assert (target/name).read_bytes()==data
(target/'start-native.bat').unlink()
run(False,'mavjud KPI dasturi emas')
shutil.rmtree(work)
print('PASS: install, repeated install, verified backup, corrupt/missing payload, read-only destination, wrong target; private data preserved.')
