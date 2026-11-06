"""Build a minimal active HEMIS employee and photo update using the tested, backup-first local installer."""
from pathlib import Path
import hashlib
import re
import zipfile
ROOT = Path(__file__).resolve().parents[1]
FILES = ('views/layouts/main.php', 'web/js/app.js', 'web/css/galaxy.css')

def main():
    installer = (ROOT/'bin/academic-installer.php').read_text()
    whitelist = 'const ACADEMIC_FILES = [\n' + ''.join(f"    '{f}',\n" for f in FILES) + '];'
    installer = re.sub(r'const ACADEMIC_FILES = \[.*?\];', lambda _: whitelist, installer, count=1, flags=re.S)
    installer = installer.replace('ACADEMIC_FILES', 'GALAXY_FILES').replace('academic-backup-', 'galaxy-backup-')
    installer = installer.replace('FarDU academic installer 2', 'FarDU Sokin galaktika installer 1')
    installer = installer.replace('Paketning 11 ta fayli', f'Paketning {len(FILES)} ta fayli')
    installer = installer.replace('Akademik dizayn ornatildi.', 'Sokin galaktika dizayni ornatildi. Start-native.bat ni qayta oching va Ctrl+F5 bosing.')
    backup_code = """
    foreach (['kpi.sqlite','kpi.sqlite-wal','kpi.sqlite-shm'] as $databaseFile) {
        $database=$target.'/runtime/'.$databaseFile;
        if(is_file($database)) {
            if(!is_dir($backup.'/database'))mkdir($backup.'/database',0700,true);
            academicCopy($database,$backup.'/database/'.$databaseFile);
        }
    }
"""
    installer=installer.replace('    foreach (GALAXY_FILES as $relative) {\n        $destination = $target',backup_code+'    foreach (GALAXY_FILES as $relative) {\n        $destination = $target',1)
    bat = (ROOT/'bin/install-academic.bat').read_bytes().decode('ascii')
    bat = bat.replace('INSTALL-ACADEMIC', 'INSTALL-GALAXY').replace('KPI\\web\\css\\academic.css', 'KPI\\web\\css\\galaxy.css')
    bat = bat.replace('Akademik dizayn - installer 2', 'Sokin galaktika dizayni')
    path = ROOT/'releases/KPI-Sokin-Galaktika-update.zip'
    manifest = ''.join(hashlib.sha256((ROOT/f).read_bytes()).hexdigest()+'  KPI/'+f+'\n' for f in FILES)
    with zipfile.ZipFile(path,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for f in FILES: z.write(ROOT/f,'KPI/'+f)
        z.writestr('INSTALL-GALAXY.php',installer)
        z.writestr('INSTALL-GALAXY.bat',bat)
        z.write(ROOT/'docs/GALAXY-UPDATE.md','BIRINCHI-OQING.md')
        z.writestr('files.sha256',manifest)
    with zipfile.ZipFile(path) as z:
        assert z.testzip() is None
        assert len(z.namelist())==len(FILES)+4
        for f in FILES: assert z.read('KPI/'+f)==(ROOT/f).read_bytes()
    path.with_suffix('.sha256').write_text(hashlib.sha256(path.read_bytes()).hexdigest()+'  '+path.name+'\n')
    print(f'{path.name}: {path.stat().st_size} bytes, {len(FILES)} files; no saved settings/database.')
if __name__=='__main__': main()
