"""Run the bounded PHP 8.5 certification release gates, with durable logs.

Use an installed PHP 8.5 CLI with ext-ast and PHPUnit extensions. PHPRC may
select its ini. No sources are fetched and no dependency is installed here.
"""
import argparse
import json
import os
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(__doc__)
parser.add_argument('--php', default='php')
parser.add_argument('--composer', default='composer')
parser.add_argument('--source-directory', type=Path, default=ROOT / '.audit')
parser.add_argument('--source-cache', type=Path, default=ROOT / '.audit/phase7-sources')
parser.add_argument('--logs', type=Path, default=ROOT / '.audit/phase7-validation')
args = parser.parse_args()
php = shutil.which(args.php) or str(Path(args.php).resolve())
env = dict(os.environ)
env['PATH'] = str(Path(php).parent) + os.pathsep + env['PATH']
composer = [php, args.composer] if args.composer.endswith('.phar') else [args.composer]
p = [php, '-d', 'zend.multibyte=0']
py = [sys.executable]
commands = [
    ('composer-validate', composer + ['validate', '--strict']),
    ('phpunit', composer + ['test', '--', '--no-progress']),
    ('grammar-coverage', composer + ['grammar:coverage']),
    ('lexer-coverage', composer + ['lexer:coverage']),
    ('ordinary-differential', p + ['bin/php85-conformance.php', php]),
    ('explicit-ast', p + ['tools/8.5/ast-conformance.php']),
    ('systematic-structure', p + ['tools/8.5/systematic-structure.php']),
    ('declaration-folding', p + ['tools/8.5/boundary-folding.php', php]),
    ('phase6-matrices', composer + ['conformance:phase6']),
    ('phase6-matrix-freshness', p + ['tools/8.5/phase6.php', '--check']),
    ('scanner-product', p + ['tools/8.5/scanner-product.php']),
    ('lexer-short-enabled', p + ['-d', 'short_open_tag=1', 'tools/8.5/lexer-differential.php']),
    ('lexer-short-disabled', p + ['-d', 'short_open_tag=0', 'tools/8.5/lexer-differential.php']),
    ('positive-report-freshness', p + ['tools/8.5/coverage-report.php', '--check']),
    ('negative-report-freshness', p + ['tools/8.5/negative-report.php', '--check']),
    ('compiler-source-hashes', py + ['tools/8.5/compiler-boundaries.py', str(args.source_directory), '--check']),
    ('parser-reconciliation', py + ['tools/8.5/reconcile.py', str(args.source_directory), '--check']),
    ('phase6-evidence-freshness', py + ['tools/8.5/phase6-evidence.py', '--check']),
    ('interpolation-binding', p + ['tools/8.5/interpolation-binding.php', '--check']),
    ('diagnostic-predicates', p + ['tools/8.5/diagnostic-witnesses.php', '--check']),
    ('source-correspondence', py + ['tools/8.5/source-correspondence.py', '--cache', str(args.source_cache), '--check']),
    ('final-certification', py + ['tools/8.5/certification.py', '--source-cache', str(args.source_cache), '--check']),
    ('diff-whitespace', ['git', 'diff', '--check']),
]
args.logs.mkdir(parents=True, exist_ok=True)
results = []
for name, command in commands:
    print(f'Running {name}', flush=True)
    try:
        with (args.logs / (name + '.log')).open('w', encoding='utf-8') as log:
            code = subprocess.call(['rtk', 'proxy', *command], cwd=ROOT, env=env, stdout=log, stderr=subprocess.STDOUT)
    except OSError as error:
        print(str(error), flush=True)
        code = 127
    results.append({'gate': name, 'exit_code': code})
    print(f'{name}: {"PASS" if code == 0 else "FAIL"}', flush=True)
(args.logs / 'results.json').write_text(json.dumps(results, indent=2) + '\n')
print(f'{sum(r["exit_code"] == 0 for r in results)}/{len(results)} gates passed; logs: {args.logs}', flush=True)
sys.exit(0 if all(r['exit_code'] == 0 for r in results) else 1)
