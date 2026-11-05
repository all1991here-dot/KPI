"""Build the approved FarDU academic update; require the original branding assets."""
from pathlib import Path
import hashlib
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = (
    'controllers/KpiController.php', 'services/Dashboard.php', 'services/Ui.php',
    'views/layouts/main.php', 'views/auth/login.php', 'views/kpi/index.php',
    'web/css/design.css', 'web/css/academic.css', 'web/js/app.js',
    'web/images/fardu-campus.jpg', 'web/images/fardu-logo.png',
)


def main():
    missing = [name for name in FILES if not (ROOT / name).is_file()]
    if missing:
        raise SystemExit('Package not created. Missing original files: ' + ', '.join(missing))
    path = ROOT / 'releases/KPI-FarDU-Academic-update.zip'
    manifest = ''.join(hashlib.sha256((ROOT / name).read_bytes()).hexdigest()
                       + '  KPI/' + name + '\n' for name in FILES)
    with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in FILES:
            archive.write(ROOT / name, 'KPI/' + name)
        archive.write(ROOT / 'bin/install-academic.bat', 'INSTALL-ACADEMIC.bat')
        archive.write(ROOT / 'docs/ACADEMIC.md', 'BIRINCHI-OQING.md')
        archive.writestr('files.sha256', manifest)
    with zipfile.ZipFile(path) as archive:
        assert archive.testzip() is None
        assert {name.removeprefix('KPI/') for name in archive.namelist() if name.startswith('KPI/')} == set(FILES)
        for name in FILES:
            assert archive.read('KPI/' + name) == (ROOT / name).read_bytes()
    path.with_suffix('.sha256').write_text(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + path.name + '\n')
    print(f'{path.name}: {path.stat().st_size} bytes, {len(FILES)} application files. No database or private settings.')


if __name__ == '__main__':
    main()
