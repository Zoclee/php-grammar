"""Rebuild the pinned source inventory (names/locations, not a proof of equivalence)."""
import argparse
import hashlib
import json
import re
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('source_directory', type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
revision = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e'
inventory = {'branch': 'PHP-8.5', 'revision': revision, 'files': {}}
lock = json.loads((root / 'tools/php85-source-lock.json').read_text())
for name in ['zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c']:
    data = (args.source_directory / name).read_bytes()
    if hashlib.sha256(data).hexdigest() != lock[name]:
        raise SystemExit(f'{name}: source hash differs from the pinned revision')
    text = data.decode()
    lines = text.splitlines()
    if name.endswith('.y'):
        entries = [{'name': m[1], 'line': i + 1} for i, line in enumerate(lines)
                   if (m := re.match(r'^([a-z][a-z_]*):', line))]
    elif name.endswith('.l'):
        entries = [{'rule': line.rsplit(' {', 1)[0], 'line': i + 1} for i, line in enumerate(lines)
                   if re.match(r'^<[A-Z_,]+>', line)]
    else:
        entries = [{'name': m[1], 'line': i + 1} for i, line in enumerate(lines)
                   if (m := re.search(r'^(?:static )?(?:\w+\s+)+(zend_(?:compile|is_allowed|eval_const)[a-z_]+)\(', line))]
    inventory['files'][name] = {'sha256': hashlib.sha256(data).hexdigest(), 'entries': entries}
inventory['ebnf_productions'] = re.findall(r'^([a-z][a-z0-9-]*) =', (root / 'grammar/8.5/php.ebnf').read_text(), re.M)
(root / 'docs/8.5/source-inventory.json').write_text(json.dumps(inventory, indent=2) + '\n', encoding='utf-8', newline='\r\n')
print({name: len(info['entries']) for name, info in inventory['files'].items()})
print('EBNF productions:', len(inventory['ebnf_productions']))
