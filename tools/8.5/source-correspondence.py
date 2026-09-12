"""Fetch immutable upstream evidence; compare the normative pin and oracle release.

Network is used only by --fetch. --check verifies cached source bytes and the
committed report. Release-source identity is not binary provenance.
"""
import argparse
import difflib
import hashlib
import json
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
PIN = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e'
RELEASE = '34308a6666b2d489c509541ea9befea9e2b42348'
FILES = ['Zend/' + name for name in (
    'zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c',
    'zend_ast.c', 'zend_inheritance.c', 'zend_enum.c', 'zend_attributes.c')]
parser = argparse.ArgumentParser(__doc__)
parser.add_argument('--cache', type=Path, default=ROOT / '.audit/phase7-sources')
parser.add_argument('--fetch', action='store_true')
parser.add_argument('--check', action='store_true')
args = parser.parse_args()
rows = []
lock = json.loads((ROOT / 'tools/8.5/source-lock.json').read_text())
for name in FILES:
    versions = []
    for revision in [PIN, RELEASE]:
        path = args.cache / revision / name
        url = f'https://raw.githubusercontent.com/php/php-src/{revision}/{name}'
        if args.fetch:
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(urllib.request.urlopen(url, timeout=60).read())
        data = path.read_bytes()
        versions.append(data)
        if revision == PIN and Path(name).name in lock:
            if hashlib.sha256(data).hexdigest() != lock[Path(name).name]:
                raise SystemExit(f'Pinned hash mismatch: {name}')
    rows.append({'file': name, 'pin_sha256': hashlib.sha256(versions[0]).hexdigest(),
                 'release_sha256': hashlib.sha256(versions[1]).hexdigest(),
                 'identical': versions[0] == versions[1],
                 'diff_release_to_pin': list(difflib.unified_diff(
                     versions[1].decode().splitlines(), versions[0].decode().splitlines(),
                     fromfile=RELEASE + '/' + name, tofile=PIN + '/' + name, lineterm=''))})
report = {'source_pin': PIN, 'release': 'php-8.5.10', 'release_revision': RELEASE,
          'binary_revision': 'unverified; PHP_VERSION reports 8.5.10',
          'exact_pin_executable': False,
          'scope': 'Seven audited translation units only; headers, build options, generated files and binary provenance are not established by this comparison.',
          'files': rows}
output = json.dumps(report, indent=2) + '\n'
path = ROOT / 'docs/8.5/source-correspondence.json'
if args.check:
    if path.read_text() != output:
        raise SystemExit('Source correspondence report is stale')
else:
    path.write_text(output, encoding='utf-8', newline='\r\n')
print({'compared_files': len(rows), 'identical': sum(r['identical'] for r in rows),
       'exact_pin_executable': False})
