"""Build the role-based KPI visibility update with the backup-first installer."""
from pathlib import Path
import hashlib
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = ('services/Access.php', 'services/Workflow.php', 'views/kpi/view.php', 'migrations/m261107_000003_review_visibility.php', 'controllers/KpiController.php', 'services/Notifications.php',
         'migrations/m261107_000001_notification_receipts.php', 'migrations/m261107_000002_reviewer_notification_receipts.php', 'views/layouts/main.php',
         'views/kpi/reports.php', 'views/kpi/_report_applications.php',
         'views/kpi/notifications.php', 'web/css/academic.css', 'web/js/app.js')

def main():
    output = ROOT / 'releases/KPI-Rollar-Boyicha-Korinish-update.zip'
    with zipfile.ZipFile(ROOT / 'releases/KPI-Sokin-Galaktika-update.zip') as template:
        installer = template.read('INSTALL-GALAXY.php').decode()
        bat = template.read('INSTALL-GALAXY.bat').decode('ascii')
    whitelist = 'const GALAXY_FILES = [\n' + ''.join(f"    '{name}',\n" for name in FILES) + '];'
    installer = re.sub(r'const GALAXY_FILES = \[.*?\];', lambda _: whitelist, installer, count=1, flags=re.S)
    installer = installer.replace('Paketning 3 ta fayli', f'Paketning {len(FILES)} ta fayli')
    installer = installer.replace('galaxy-backup-', 'role-scope-backup-')
    installer = installer.replace('Sokin galaktika dizayni ornatildi.', 'KPI malumotlari rol boyicha sozlandi.')
    bat = bat.replace('INSTALL-GALAXY', 'INSTALL-ROLES').replace('Sokin galaktika dizayni', 'KPI rollar boyicha korinish')
    bat = bat.replace('KPI\\web\\css\\galaxy.css', 'KPI\\services\\Notifications.php')
    contents = {'INSTALL-ROLES.php': installer.encode(), 'INSTALL-ROLES.bat': bat.encode('ascii')}
    for name in FILES:
        contents['KPI/' + name] = (ROOT / name).read_bytes()
    contents['BIRINCHI-OQING.md'] = (ROOT / 'docs/ROLLAR-BOYICHA-KORINISH.md').read_bytes()
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
