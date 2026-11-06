"""Build a minimal deleted HEMIS account recovery update using the tested, backup-first local installer."""
from pathlib import Path
import hashlib
import re
import zipfile
ROOT = Path(__file__).resolve().parents[1]
FILES = ('commands/SetupController.php', 'controllers/AdminController.php', 'controllers/BaseController.php', 'controllers/KpiController.php', 'models/User.php', 'services/Organization.php', 'services/AccountPolicy.php', 'services/EmployeePhoto.php', 'services/Hemis.php', 'services/HemisHttp.php', 'services/Workflow.php', 'migrations/m261106_000001_employee_profile.php', 'views/admin/structure.php', 'views/admin/users.php', 'views/admin/hemis.php', 'views/admin/_employee_profile.php', 'views/admin/_employee_photo.php', 'views/kpi/profile.php', 'views/kpi/create.php', 'views/kpi/view.php', 'web/js/admin-organization.js', 'web/js/employee-photo.js', 'resources/tls/cacert.pem', 'resources/tls/LICENSE.certifi', 'resources/tls/README.md', 'controllers/AuthController.php', 'services/Management.php', 'services/HemisPayload.php', 'services/Catalog.php', 'migrations/m261106_000002_superadmin_management.php', 'views/admin/indicators.php', 'views/admin/rules.php', 'views/kpi/criteria.php', 'views/layouts/main.php', 'views/admin/structure-edit.php', 'web/css/organization.css', 'web/js/organization-editor.js', 'migrations/m261106_000003_organization_details.php', 'views/admin/user-edit.php', 'views/admin/delete-all.php', 'services/OrganizationClassifier.php', 'services/Access.php', 'migrations/m261106_000004_organization_types.php', 'views/kpi/reports.php')

def main():
    installer = (ROOT/'bin/academic-installer.php').read_text()
    whitelist = 'const ACADEMIC_FILES = [\n' + ''.join(f"    '{f}',\n" for f in FILES) + '];'
    installer = re.sub(r'const ACADEMIC_FILES = \[.*?\];', lambda _: whitelist, installer, count=1, flags=re.S)
    installer = installer.replace('ACADEMIC_FILES', 'HEMIS_RECOVERY_FILES').replace('academic-backup-', 'hemis-recovery-backup-')
    installer = installer.replace('FarDU academic installer 2', 'FarDU HEMIS recovery installer 1')
    installer = installer.replace('Paketning 11 ta fayli', f'Paketning {len(FILES)} ta fayli')
    installer = installer.replace('Akademik dizayn ornatildi.', 'Tiklash imkoniyati ornatildi. Start-native.bat ni qayta oching. HEMISda ochirilgan hisoblarni tiklash belgisini tanlab xodimlarni import qiling.')
    backup_code = """
    foreach (['kpi.sqlite','kpi.sqlite-wal','kpi.sqlite-shm'] as $databaseFile) {
        $database=$target.'/runtime/'.$databaseFile;
        if(is_file($database)) {
            if(!is_dir($backup.'/database'))mkdir($backup.'/database',0700,true);
            academicCopy($database,$backup.'/database/'.$databaseFile);
        }
    }
"""
    installer=installer.replace('    foreach (HEMIS_RECOVERY_FILES as $relative) {\n        $destination = $target',backup_code+'    foreach (HEMIS_RECOVERY_FILES as $relative) {\n        $destination = $target',1)
    bat = (ROOT/'bin/install-academic.bat').read_bytes().decode('ascii')
    bat = bat.replace('INSTALL-ACADEMIC', 'INSTALL-HEMIS-RECOVERY').replace('KPI\\web\\css\\academic.css', 'KPI\\services\\Hemis.php')
    bat = bat.replace('Akademik dizayn - installer 2', 'HEMIS hisoblarini qayta tiklash')
    path = ROOT/'releases/KPI-HEMIS-Recovery-update.zip'
    manifest = ''.join(hashlib.sha256((ROOT/f).read_bytes()).hexdigest()+'  KPI/'+f+'\n' for f in FILES)
    with zipfile.ZipFile(path,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for f in FILES: z.write(ROOT/f,'KPI/'+f)
        z.writestr('INSTALL-HEMIS-RECOVERY.php',installer)
        z.writestr('INSTALL-HEMIS-RECOVERY.bat',bat)
        z.write(ROOT/'docs/HEMIS-RECOVERY-UPDATE.md','BIRINCHI-OQING.md')
        z.writestr('files.sha256',manifest)
    with zipfile.ZipFile(path) as z:
        assert z.testzip() is None
        assert len(z.namelist())==len(FILES)+4
        for f in FILES: assert z.read('KPI/'+f)==(ROOT/f).read_bytes()
    path.with_suffix('.sha256').write_text(hashlib.sha256(path.read_bytes()).hexdigest()+'  '+path.name+'\n')
    print(f'{path.name}: {path.stat().st_size} bytes, {len(FILES)} files; no saved settings/database.')
if __name__=='__main__': main()
