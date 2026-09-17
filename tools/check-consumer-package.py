"""Archive and install into an isolated Composer consumer; outputs stay in .audit/."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(__doc__)
parser.add_argument('--php', default='php')
parser.add_argument('--composer', default='composer')
args = parser.parse_args()
php = shutil.which(args.php) or str(Path(args.php).resolve())
composer = [php, str(Path(args.composer).resolve())] if args.composer.endswith('.phar') else [args.composer]
(ROOT / '.audit').mkdir(exist_ok=True)
work = Path(tempfile.mkdtemp(prefix='consumer-package-', dir=ROOT / '.audit'))


def run(command, cwd):
    result = subprocess.run(['rtk', 'proxy', *command], cwd=cwd, text=True,
                            stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    if result.returncode:
        raise SystemExit(result.stdout)
    return result.stdout


run(composer + ['archive', '--format=zip', '--dir=' + str(work), '--file=package'], ROOT)
package = work / 'package'
with zipfile.ZipFile(work / 'package.zip') as archive:
    names = set(archive.namelist())
    manifest = json.loads(archive.read('php-grammar.json'))
    required = ['composer.json', 'LICENSE', 'php-grammar.json', 'schema/php-grammar.schema.json',
                'docs/api.md', 'docs/usage.md', 'docs/versioning.md', 'docs/grammar-conventions.md',
                'bin/php-grammar']
    required += [str(p.relative_to(ROOT)).replace('\\', '/') for p in (ROOT / 'src').rglob('*.php')]
    for version in manifest['versions']:
        required += [version['grammar'], version['documentation'], *version.get('metadata', {}).values()]
    missing = set(required) - names
    if missing:
        raise SystemExit(f'Archive missing public files: {sorted(missing)}')
    if any(name.startswith(('.audit/', '.phpunit.cache/', 'vendor/')) for name in names):
        raise SystemExit('Archive contains local scratch/cache/vendor files')
    # Verify extraction targets remain under this isolated workspace before writing.
    if any(not (package / name).resolve().is_relative_to(package.resolve()) for name in names):
        raise SystemExit('Archive contains an unsafe extraction path')
    archive.extractall(package)
app = work / 'app'
app.mkdir()
(app / 'composer.json').write_text(json.dumps({
    'name': 'php-grammar/consumer-example',
    'repositories': [{'type': 'path', 'url': str(package), 'options': {
        'symlink': False, 'versions': {'php-grammar/php-grammar': 'dev-main'}}}],
    'require': {'php-grammar/php-grammar': '@dev'},
}))
run(composer + ['install', '--no-dev', '--no-interaction', '--no-plugins', '--no-scripts'], app)
(app / 'example.php').write_text('''<?php
require __DIR__ . '/vendor/autoload.php';
use Composer\\InstalledVersions;
use PhpGrammar\\Repository\\RepositoryManifest;
use PhpGrammar\\Php\\Conformance\\GrammarRepository;
use PhpGrammar\\Php\\Conformance\\PhpGrammarMatcher;
$root = InstalledVersions::getInstallPath('php-grammar/php-grammar');
$manifest = RepositoryManifest::fromRepositoryRoot($root);
$repository = new GrammarRepository($manifest);
foreach ($manifest->versions() as $version) {
    $grammar = $repository->load($version);
    if (!$grammar->hasProduction('expression') || !$repository->productionIndex($version)->sections()) {
        throw new RuntimeException('Missing production/section metadata');
    }
    $package = $manifest->package($version);
    if (!is_file($manifest->absolutePath($package->documentationPath))) {
        throw new RuntimeException('Missing specification');
    }
}
$matcher = PhpGrammarMatcher::forManifest($manifest);
if (!$matcher->matchesRule('8.5', 'expression', '$a + 1')->matched
    || $matcher->matchesRule('8.5', 'expression', '$a +')->matched
    || !$matcher->matches('8.5', '<?php echo 1;')->matched) {
    throw new RuntimeException('Unexpected match result');
}
echo "Installed consumer workflow: PASS\\n";
''')
print(run([php, 'example.php'], app).strip())
print(run([php, str(app / 'vendor/bin/php-grammar'), 'versions'], app).strip())
print(f'Archive contents and installed Composer CLI: PASS; artifacts: {work}')
