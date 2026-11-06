"""Build a minimal login animation update using the tested, backup-first local installer."""
from pathlib import Path
import hashlib
import re
import zipfile
ROOT = Path(__file__).resolve().parents[1]
FILES = ('views/auth/login.php', 'views/auth/_robot.php', 'web/css/login-animation.css', 'web/js/login-animation.js')

def main():
    installer = (ROOT/'bin/academic-installer.php').read_text()
    whitelist = 'const ACADEMIC_FILES = [\n' + ''.join(f"    '{f}',\n" for f in FILES) + '];'
    installer = re.sub(r'const ACADEMIC_FILES = \[.*?\];', lambda _: whitelist, installer, count=1, flags=re.S)
    installer = installer.replace('ACADEMIC_FILES', 'LOGIN_ANIMATION_FILES').replace('academic-backup-', 'login-animation-backup-')
    installer = installer.replace('FarDU academic installer 2', 'FarDU login animation installer 1')
    installer = installer.replace('Paketning 11 ta fayli', f'Paketning {len(FILES)} ta fayli')
    installer = installer.replace('Akademik dizayn ornatildi.', 'Kirish foni animatsiyasi ornatildi.')
    bat = (ROOT/'bin/install-academic.bat').read_bytes().decode('ascii')
    bat = bat.replace('INSTALL-ACADEMIC', 'INSTALL-LOGIN-ANIMATION').replace('KPI\\web\\css\\academic.css', 'KPI\\web\\js\\login-animation.js')
    bat = bat.replace('Akademik dizayn - installer 2', 'Kirish foni animatsiyasi')
    path = ROOT/'releases/KPI-Login-Animation.zip'
    manifest = ''.join(hashlib.sha256((ROOT/f).read_bytes()).hexdigest()+'  KPI/'+f+'\n' for f in FILES)
    with zipfile.ZipFile(path,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for f in FILES: z.write(ROOT/f,'KPI/'+f)
        z.writestr('INSTALL-LOGIN-ANIMATION.php',installer)
        z.writestr('INSTALL-LOGIN-ANIMATION.bat',bat)
        z.write(ROOT/'docs/LOGIN-ANIMATION.md','BIRINCHI-OQING.md')
        z.writestr('files.sha256',manifest)
    with zipfile.ZipFile(path) as z:
        assert z.testzip() is None
        assert len(z.namelist())==len(FILES)+4
        for f in FILES: assert z.read('KPI/'+f)==(ROOT/f).read_bytes()
    path.with_suffix('.sha256').write_text(hashlib.sha256(path.read_bytes()).hexdigest()+'  '+path.name+'\n')
    print(f'{path.name}: {path.stat().st_size} bytes, {len(FILES)} files; no saved settings/database.')
if __name__=='__main__': main()
