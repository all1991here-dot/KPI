"""Package the animated digital network with the existing backup-first galaxy installer."""
from pathlib import Path
import hashlib
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = ('views/layouts/main.php', 'web/js/app.js', 'web/css/galaxy.css')

def main():
    output = ROOT / 'releases/KPI-Raqamli-Tarmoq-update.zip'
    with zipfile.ZipFile(ROOT / 'releases/KPI-Sokin-Galaktika-update.zip') as template:
        contents = {name: template.read(name) for name in ('INSTALL-GALAXY.php', 'INSTALL-GALAXY.bat')}
    for name in FILES:
        contents['KPI/' + name] = (ROOT / name).read_bytes()
    contents['BIRINCHI-OQING.md'] = (ROOT / 'docs/RAQAMLI-TARMOQ.md').read_bytes()
    contents['files.sha256'] = ''.join(
        hashlib.sha256(contents['KPI/' + name]).hexdigest() + '  KPI/' + name + '\n'
        for name in FILES
    ).encode()
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
