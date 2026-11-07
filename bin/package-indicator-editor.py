"""Build the separate KPI indicator editor update with the backup-first installer."""
from pathlib import Path
import hashlib
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = ('controllers/AdminController.php', 'views/admin/indicators.php',
         'views/admin/indicator-edit.php', 'web/css/organization.css')

def main():
    output = ROOT / 'releases/KPI-Mezon-Tahrirlash-update.zip'
    with zipfile.ZipFile(ROOT / 'releases/KPI-Sokin-Galaktika-update.zip') as template:
        installer = template.read('INSTALL-GALAXY.php').decode()
        bat = template.read('INSTALL-GALAXY.bat').decode('ascii')
    whitelist = 'const GALAXY_FILES = [\n' + ''.join(f"    '{name}',\n" for name in FILES) + '];'
    installer = re.sub(r'const GALAXY_FILES = \[.*?\];', lambda _: whitelist, installer, count=1, flags=re.S)
    installer = installer.replace('Paketning 3 ta fayli', 'Paketning 4 ta fayli')
    installer = installer.replace('galaxy-backup-', 'indicator-editor-backup-')
    installer = installer.replace('Sokin galaktika dizayni ornatildi.', 'Mezon tahrirlash oynasi yangilandi.')
    bat = bat.replace('INSTALL-GALAXY', 'INSTALL-MEZON').replace('Sokin galaktika dizayni', 'Mezon tahrirlash oynasi')
    bat = bat.replace('KPI\\web\\css\\galaxy.css', 'KPI\\views\\admin\\indicator-edit.php')
    contents = {'INSTALL-MEZON.php': installer.encode(), 'INSTALL-MEZON.bat': bat.encode('ascii')}
    for name in FILES:
        contents['KPI/' + name] = (ROOT / name).read_bytes()
    contents['BIRINCHI-OQING.md'] = (ROOT / 'docs/MEZON-TAHRIRLASH.md').read_bytes()
    contents['files.sha256'] = ''.join(hashlib.sha256(contents['KPI/' + name]).hexdigest() + '  KPI/' + name + '\n' for name in FILES).encode()
    with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in contents.items():
            archive.writestr(name, data)
    with zipfile.ZipFile(output) as archive:
        assert archive.testzip() is None
        for line in archive.read('files.sha256').decode().splitlines():
            expected, name = line.split('  ', 1)
            assert hashlib.sha256(archive.read(name)).hexdigest() == expected
    output.with_suffix('.sha256').write_text(hashlib.sha256(output.read_bytes()).hexdigest() + '  ' + output.name + '\n')
    print(f'{output.name}: {output.stat().st_size} bytes; ZIP and SHA256 checks passed.')

if __name__ == '__main__':
    main()
