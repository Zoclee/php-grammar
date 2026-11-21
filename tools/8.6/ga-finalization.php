<?php
declare(strict_types=1);

// Additive GA observations. Does not change or grant a certification contract.
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use PhpGrammar\Tools\Support as S;

$root = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
chdir($root);
$out = 'docs/8.6/ga-finalization';
$work = '.audit/phase8b';
$commit = '8aaada3f9605d81a38b8240f93c7389dfbc77ef9';
$tag = '48a5ee8a311db69103a6fa25b330e5ff3847af3d';
$rc7 = 'b63cd83da3279445ca4a913f03186141f5c3466e';
$mode = $argv[1] ?? '';
function save(string $path, mixed $data): void {
    if (file_exists($path)) throw new RuntimeException("Refusing to overwrite $path");
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    file_put_contents($path, S::json($data));
}
function command(string $id, array $cmd, bool $required = true): array {
    global $root, $out;
    $log = "$out/$id.log";
    if (file_exists($log)) throw new RuntimeException("Refusing to overwrite $log");
    $r = S::run($cmd, $root, $log);
    $row = ['command' => $cmd, 'exitCode' => $r['exit_code'], 'log' => $log, 'sha256' => hash_file('sha256', $log)];
    save("$out/$id.command.json", $row);
    if ($required && $r['exit_code'] !== 0) throw new RuntimeException("Command failed: $id (see $log)");
    return $row;
}
function acquire(string $id, string $url): string {
    global $out;
    $path = "$out/$id.body";
    if (file_exists($path)) throw new RuntimeException("Refusing to overwrite $path");
    $headers = '';
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'php-grammar-finalization', CURLOPT_TIMEOUT => 120,
        CURLOPT_HEADERFUNCTION => static function ($c, $line) use (&$headers) { $headers .= $line; return strlen($line); }]);
    $body = curl_exec($c);
    $receipt = ['url' => $url, 'effectiveUrl' => curl_getinfo($c, CURLINFO_EFFECTIVE_URL),
        'status' => curl_getinfo($c, CURLINFO_RESPONSE_CODE), 'retrievedAt' => gmdate('c'), 'curlError' => curl_error($c)];
    if ($body === false) $body = '';
    file_put_contents($path, $body);
    file_put_contents("$out/$id.headers", $headers);
    save("$out/$id.receipt.json", $receipt + ['sha256' => hash('sha256', $body), 'headerSha256' => hash('sha256', $headers)]);
    return $body;
}
if ($mode === '--baseline') {
    if (!is_dir($out)) mkdir($out, 0777, true);
    $files = [];
    foreach (['grammar', 'docs', 'tests', 'src', 'tools', 'schema', 'bin'] as $dir) {
        foreach (S::files($dir) as $path) {
            if (str_starts_with($path, "$out/") || $path === 'tools/8.6/ga-finalization.php') continue;
            $files[$path] = hash_file('sha256', $path);
        }
    }
    foreach (['AGENTS.md', 'README.md', 'composer.json', 'composer.lock', 'php-grammar.json', '.gitattributes', '.gitignore', 'phpunit.xml.dist'] as $path) $files[$path] = hash_file('sha256', $path);
    $commands = [];
    foreach (['head' => ['git', 'rev-parse', 'HEAD'], 'status' => ['git', 'status', '--short'], 'tags' => ['git', 'tag', '--list'],
        'remote-tags' => ['git', 'ls-remote', '--tags', 'https://github.com/Zoclee/php-grammar.git'],
        'ga-ref' => ['git', 'ls-remote', 'https://github.com/php/php-src.git', 'refs/tags/php-8.6.0', 'refs/tags/php-8.6.0^{}']] as $id => $cmd) $commands[$id] = command("baseline-$id", $cmd);
    save("$out/baseline.json", ['recordedAt' => gmdate('c'), 'files' => $files, 'commands' => $commands,
        'manifest' => S::read('php-grammar.json'), 'packageVersion' => 'No composer version field; remote tags are release identity',
        'preexistingUntracked' => ['docs/8.6/rc7-source-reconciliation.md', 'docs/8.6/rc7-source-reconciliation/', 'tools/8.6/rc7-source-reconciliation.php']]);
    echo count($files) . " baseline files\n"; exit;
}
if ($mode === '--acquire') {
    $urls = [
        'ga-ref' => 'https://api.github.com/repos/php/php-src/git/ref/tags/php-8.6.0',
        'ga-tag' => "https://api.github.com/repos/php/php-src/git/tags/$tag",
        'ga-commit' => "https://api.github.com/repos/php/php-src/commits/$commit",
        'release' => 'https://www.php.net/releases/8_6_0.php',
        'release-json' => 'https://www.php.net/releases/index.php?json&version=8.6.0',
        'migration' => 'https://www.php.net/manual/en/migration86.php',
        'windows' => 'https://windows.php.net/download/',
        'fix-pr' => 'https://api.github.com/repos/php/php-src/pulls/25046',
        'fix-files' => 'https://api.github.com/repos/php/php-src/pulls/25046/files?per_page=100',
        'issue24792' => 'https://api.github.com/repos/php/php-src/issues/24792',
    ];
    foreach ($urls as $id => $url) { acquire($id, $url); echo "$id acquired\n"; }
    exit;
}
if ($mode === '--sources') {
    if (!is_dir($work)) mkdir($work, 0777, true);
    $git = ['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git'];
    command('fetch-ga', [...$git, 'fetch', '--no-tags', 'https://github.com/php/php-src.git', 'refs/tags/php-8.6.0:refs/tags/php-8.6.0']);
    $ref = json_decode(file_get_contents("$out/ga-ref.body"), true, 512, JSON_THROW_ON_ERROR);
    $annotation = json_decode(file_get_contents("$out/ga-tag.body"), true, 512, JSON_THROW_ON_ERROR);
    if ($ref['object']['sha'] !== $tag || $annotation['object']['sha'] !== $commit || $annotation['tag'] !== 'php-8.6.0') throw new RuntimeException('Unexpected official tag identity');
    command('tag-object', [...$git, 'cat-file', '-p', $tag]);
    command('tag-signature', [...$git, 'verify-tag', $tag], false);
    foreach (['rc7' => $rc7, 'ga' => $commit] as $id => $pin) command("tree-$id", [...$git, 'ls-tree', '-r', $pin]);
    command('changed-files', [...$git, 'diff', '--name-status', $rc7, $commit]);
    command('source-diff', [...$git, 'diff', '--no-ext-diff', $rc7, $commit, '--', 'Zend', 'NEWS', 'UPGRADING', 'UPGRADING.INTERNALS']);
    command('history', [...$git, 'log', '--format=%H %ad %s', '--date=iso-strict', "$rc7..$commit"]);
    $paths = array_keys(S::read('tools/8.6/certification-source-lock.json')['files']);
    $trees = [];
    foreach (['rc7', 'ga'] as $id) {
        foreach (explode("\n", trim(file_get_contents("$out/tree-$id.log"))) as $line) {
            if (preg_match('/^(\d+) blob ([a-f0-9]{40})\t(.+)$/D', $line, $m)) $trees[$id][$m[3]] = ['mode' => $m[1], 'oid' => $m[2]];
        }
    }
    $changes = [];
    foreach (array_unique([...array_keys($trees['rc7']), ...array_keys($trees['ga'])]) as $path) {
        if (($trees['rc7'][$path] ?? null) !== ($trees['ga'][$path] ?? null)) { $changes[$path] = ['rc7' => $trees['rc7'][$path] ?? null, 'ga' => $trees['ga'][$path] ?? null]; $paths[] = $path; }
    }
    $hashes = [];
    foreach (array_unique($paths) as $path) {
        if (!isset($trees['ga'][$path])) continue;
        $dest = "$work/git-ga/$path";
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
        $r = S::run([...$git, 'show', "$commit:$path"], $root, $dest);
        if ($r['exit_code']) throw new RuntimeException("Missing source $path");
        $bytes = file_get_contents($dest);
        if (sha1('blob ' . strlen($bytes) . "\0" . $bytes) !== $trees['ga'][$path]['oid']) throw new RuntimeException("Git blob mismatch $path");
        $hashes[$path] = hash('sha256', $bytes);
    }
    save("$out/source-profile.json", ['contract' => 'php86-ga-source-observation-v1', 'activeCertificationProfile' => false,
        'repository' => 'https://github.com/php/php-src.git', 'tag' => 'php-8.6.0', 'tagObject' => $tag, 'commit' => $commit,
        'githubSignatureVerification' => $annotation['verification'], 'localSignatureLog' => "$out/tag-signature.log", 'files' => $hashes]);
    save("$out/tree-inventory.json", ['rc7' => $rc7, 'ga' => $commit, 'treeCounts' => array_map('count', $trees), 'changes' => $changes]);
    echo count($changes) . ' changed paths; ' . count($hashes) . " selected GA hashes\n"; exit;
}
if ($mode === '--release-metadata') {
    acquire('project-release', 'https://api.github.com/repos/Zoclee/php-grammar/releases/latest');
    acquire('packagist', 'https://repo.packagist.org/p2/php-grammar/php-grammar.json');
    echo "Published release metadata responses retained\n"; exit;
}
if ($mode === '--archives') {
    $items = [
        'php-8.6.0.tar.gz' => ['https://www.php.net/distributions/php-8.6.0.tar.gz', '62337dccde3d9fcc39797d4529f42637613036eaf3875353c206874e5813a3c1'],
        'php86ga.zip' => ['https://downloads.php.net/~windows/releases/archives/php-8.6.0-nts-Win32-vs18-x64.zip', 'd4783fffe1f4ab072600daff1622a7a2df936957a787a338c59e3dbd4d846d98'],
    ];
    foreach ($items as $name => [$url, $expected]) {
        $path = "$work/$name";
        if (!is_file($path)) {
            $f = fopen($path, 'wb'); $c = curl_init($url);
            curl_setopt_array($c, [CURLOPT_FILE => $f, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 240]);
            $ok = curl_exec($c); $status = curl_getinfo($c, CURLINFO_RESPONSE_CODE); fclose($f);
            if (!$ok || $status !== 200) throw new RuntimeException("Download failed $name");
        }
        if (hash_file('sha256', $path) !== $expected) throw new RuntimeException("Archive checksum mismatch $name");
        if (!is_file("$out/$name.json")) save("$out/$name.json", ['url' => $url, 'sha256' => $expected, 'verifiedAt' => gmdate('c')]);
        if ($name === 'php86ga.zip') {
            $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new RuntimeException('Invalid ZIP');
            if (!is_dir("$work/php86ga")) $zip->extractTo("$work/php86ga"); $zip->close();
        } elseif (!is_dir("$work/php-8.6.0")) (new PharData($path))->extractTo($work);
        echo "$name authenticated and extracted\n";
    }
    $files = [];
    foreach (S::read("$out/source-profile.json")['files'] as $path => $hash) {
        $archivePath = "$work/php-8.6.0/$path";
        $actual = is_file($archivePath) ? hash_file('sha256', $archivePath) : null;
        $files[$path] = ['git' => $hash, 'archive' => $actual, 'equal' => $hash === $actual];
    }
    save("$out/archive-correspondence.json", ['files' => $files, 'differences' => array_keys(array_filter($files, static fn($r) => !$r['equal']))]);
    exit;
}
if ($mode === '--build-prepare') {
    $git = ['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git'];
    command('git-source-archive', [...$git, 'archive', '--format=tar', "--output=$work/ga-git.tar", $commit]);
    if (is_dir("$work/source")) throw new RuntimeException('Fresh source directory required');
    mkdir("$work/source"); (new PharData("$work/ga-git.tar"))->extractTo("$work/source");
    $sdkPin = '87567dececcf806c83e67fceb89a3bd206e7f1b1';
    command('sdk-clone', ['git', 'clone', '--no-checkout', 'https://github.com/php/php-sdk-binary-tools.git', "$work/sdk-git"]);
    command('sdk-archive', ['git', '-C', "$work/sdk-git", 'archive', '--format=tar', '--output=' . $root . "/$work/sdk.tar", $sdkPin]);
    mkdir("$work/sdk"); (new PharData("$work/sdk.tar"))->extractTo("$work/sdk");
    $hashes = [];
    foreach (explode("\n", trim(file_get_contents("$out/tree-ga.log"))) as $line) {
        if (!preg_match('/^(\d+) blob ([a-f0-9]{40})\t(.+)$/D', $line, $m)) continue;
        $bytes = file_get_contents("$work/source/{$m[3]}");
        if (sha1('blob ' . strlen($bytes) . "\0" . $bytes) !== $m[2]) throw new RuntimeException("Build source mismatch {$m[3]}");
        $hashes[$m[3]] = hash('sha256', $bytes);
    }
    save("$out/build-source.json", ['sourceCommit' => $commit, 'sdkCommit' => $sdkPin, 'sourceHashes' => $hashes, 'sdkHashes' => S::hashes(S::files("$work/sdk"))]);
    echo count($hashes) . " exact source files authenticated\n"; exit;
}
if ($mode === '--build-normalize') {
    // git archive honors checkout conversion. Extract again with explicit raw-byte settings.
    if (!is_file("$out/git-source-archive-raw.command.json")) {
        command('git-source-archive-raw', ['git', '-c', 'core.autocrlf=false', '-c', 'core.eol=lf', '--git-dir=.audit/rc7-git', 'archive', '--format=tar', "--output=$work/ga-git-raw.tar", $commit]);
        (new PharData("$work/ga-git-raw.tar"))->extractTo("$work/source", null, true);
    }
    $hashes = []; $restored = [];
    foreach (explode("\n", trim(file_get_contents("$out/tree-ga.log"))) as $line) {
        if (!preg_match('/^(\d+) blob ([a-f0-9]{40})\t(.+)$/D', $line, $m)) continue;
        if (str_starts_with($m[3], '"')) $m[3] = stripcslashes(substr($m[3], 1, -1));
        $path = "$work/source/{$m[3]}";
        $bytes = is_file($path) ? file_get_contents($path) : '';
        if (sha1('blob ' . strlen($bytes) . "\0" . $bytes) !== $m[2]) {
            $before = hash('sha256', $bytes);
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
            $r = S::run(['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git', 'show', "$commit:{$m[3]}"], $root, $path);
            $bytes = file_get_contents($path);
            if ($r['exit_code'] || sha1('blob ' . strlen($bytes) . "\0" . $bytes) !== $m[2]) throw new RuntimeException("Cannot restore {$m[3]}");
            $restored[$m[3]] = ['before' => $before, 'after' => hash('sha256', $bytes)];
        }
        $hashes[$m[3]] = hash('sha256', $bytes);
        if (count($hashes) % 5000 === 0) { echo count($hashes) . " source files verified\n"; flush(); }
    }
    save("$out/build-source.json", ['sourceCommit' => $commit, 'sdkCommit' => '87567dececcf806c83e67fceb89a3bd206e7f1b1',
        'checkoutConversion' => 'Raw archive with core.autocrlf=false/core.eol=lf; remaining substitutions restored from Git blobs',
        'restored' => $restored, 'sourceHashes' => $hashes, 'sdkHashes' => S::hashes(S::files("$work/sdk"))]);
    echo count($hashes) . ' source files authenticated; ' . count($restored) . " substitutions restored\n"; exit;
}
if ($mode === '--build') {
    $stage = $argv[2] ?? '';
    $commands = [
        'environment' => "where cl\r\nwhere nmake\r\nwhere bison\r\nwhere re2c\r\ncl\r\nbison --version\r\nre2c --version\r\necho WindowsSDKVersion=%WindowsSDKVersion%\r\necho VCToolsVersion=%VCToolsVersion%\r\nexit /b 0",
        'configure' => "call buildconf.bat\r\nif errorlevel 1 exit /b %errorlevel%\r\ncall configure.bat --disable-all --enable-cli --enable-tokenizer --disable-zts --disable-cgi --disable-phpdbg\r\nexit /b %errorlevel%",
        'compile' => "nmake\r\nexit /b %errorlevel%",
    ];
    if (!isset($commands[$stage])) throw new RuntimeException('Unknown build stage');
    if ($stage !== 'environment' && !is_file("$out/build-source.json")) throw new RuntimeException('Authenticate the complete source before building');
    if ($stage === 'compile' && is_file("$work/source/x64/Release/php.exe")) throw new RuntimeException('A fresh build output is required');
    if (file_exists("$out/build-$stage.json")) throw new RuntimeException('Build stage already recorded');
    $task = "$root/$work/build-$stage.bat";
    $contents = "@echo off\r\ncd /d \"$root/$work/source\"\r\n" . $commands[$stage] . "\r\n";
    file_put_contents($task, $contents);
    $launcher = str_replace('/', '\\', "$root/$work/sdk/phpsdk-vs17-x64.bat");
    $line = 'cmd.exe /d /s /c ""' . $launcher . '" -t "' . str_replace('/', '\\', $task) . '""';
    $started = gmdate('c');
    $process = proc_open($line, [0 => ['pipe', 'r'], 1 => ['file', "$out/build-$stage.stdout.log", 'w'], 2 => ['file', "$out/build-$stage.stderr.log", 'w']], $pipes, $root, null, ['bypass_shell' => true]);
    if (!is_resource($process)) throw new RuntimeException('Build launch failed');
    fclose($pipes[0]); $exit = proc_close($process);
    save("$out/build-$stage.json", ['sourceCommit' => $commit, 'commandLine' => $line, 'taskContents' => $contents,
        'taskSha256' => hash('sha256', $contents), 'started' => $started, 'finished' => gmdate('c'), 'exitCode' => $exit,
        'logs' => S::hashes(["$out/build-$stage.stdout.log", "$out/build-$stage.stderr.log"])]);
    echo "$stage exit $exit\n"; exit($exit);
}
if ($mode === '--verify-source-part') {
    $part = filter_var($argv[2] ?? '', FILTER_VALIDATE_INT);
    if ($part === false || $part < 0 || $part >= 8) throw new RuntimeException('Invalid verification partition');
    $errors = []; $index = 0;
    foreach (S::read("$out/build-source.json")['sourceHashes'] as $path => $hash) {
        if ($index++ % 8 !== $part) continue;
        if (!is_file("$work/source/$path") || hash_file('sha256', "$work/source/$path") !== $hash) $errors[] = "Source changed: $path";
    }
    echo S::json($errors); exit($errors ? 1 : 0);
}
if ($mode === '--seal-build' || $mode === '--verify-build') {
    $auth = S::read("$out/build-source.json"); $errors = [];
    // Independent partitions cover every source once; scratch output is never evidence.
    $workers = [];
    for ($part = 0; $part < 8; ++$part) {
        $log = tempnam(sys_get_temp_dir(), 'php86-verify-');
        $process = proc_open([PHP_BINARY, '-n', __FILE__, '--verify-source-part', (string)$part],
            [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, $root);
        if (!is_resource($process)) throw new RuntimeException('Cannot launch source verification');
        fclose($pipes[0]); $workers[] = [$process, $log];
    }
    foreach ($workers as [$process, $log]) {
        $exit = proc_close($process); $result = json_decode(file_get_contents($log), true); unlink($log);
        if (!is_array($result)) $errors[] = 'Source verification worker failed';
        else { array_push($errors, ...$result); if ($exit !== 0 && !$result) $errors[] = 'Source verification worker exited unsuccessfully'; }
    }
    foreach ($auth['sdkHashes'] as $path => $hash) if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = "SDK changed: $path";
    foreach (['configure', 'compile'] as $stage) {
        $r = S::read("$out/build-$stage.json");
        if ($r['exitCode'] !== 0 || $r['sourceCommit'] !== $commit) $errors[] = "Failed build stage $stage";
        foreach ($r['logs'] as $path => $hash) if (hash_file('sha256', $path) !== $hash) $errors[] = "Build log changed $path";
        if (hash_file('sha256', "$work/build-$stage.bat") !== $r['taskSha256']) $errors[] = "Build task changed $stage";
    }
    $paths = ["$work/source/x64/Release/php.exe", "$work/source/x64/Release/php8.dll", "$out/build-source.json", "$out/build-environment.json", "$out/build-configure.json", "$out/build-compile.json"];
    foreach (['Zend/zend_language_parser.c', 'Zend/zend_language_parser.h', 'Zend/zend_language_scanner.c', 'Zend/zend_ini_parser.c', 'Zend/zend_ini_scanner.c', 'main/php_config.h', 'main/internal_functions.c', 'Makefile', 'config.nice.bat'] as $path) if (is_file("$work/source/$path")) $paths[] = "$work/source/$path";
    $environment = file_get_contents("$out/build-environment.stdout.log");
    foreach (preg_split('/\r?\n/', $environment) as $line) if (preg_match('/^[A-Z]:\\\\.+\.(exe|cmd|bat)$/i', trim($line)) && is_file(trim($line))) $paths[] = str_replace('\\', '/', trim($line));
    $hashes = []; foreach ($paths as $path) if (is_file($path)) $hashes[$path] = hash_file('sha256', $path); else $errors[] = "Missing build output $path";
    if ($mode === '--seal-build') {
        if ($errors) { echo S::json($errors); exit(1); }
        save("$out/build-receipt.json", ['contract' => 'php86-ga-local-build-observation-v1', 'sourceCommit' => $commit, 'sourceFiles' => count($auth['sourceHashes']),
            'hashes' => $hashes, 'trust' => 'Local reviewed controller/host custody, not independent bitwise reproduction; Windows runtime is a host dependency',
            'certificationContractAdopted' => false, 'certificationGranted' => false]);
    } else {
        $r = S::read("$out/build-receipt.json");
        if ($r['sourceCommit'] !== $commit || $r['hashes'] !== $hashes) $errors[] = 'GA build receipt differs';
    }
    echo S::json(['errors' => $errors, 'sourceFiles' => count($auth['sourceHashes']), 'buildHashes' => count($hashes), 'certificationGranted' => false]); exit($errors ? 1 : 0);
}
if ($mode === '--observe') {
    $profile = $argv[2] ?? '';
    if (!in_array($profile, ['distribution', 'exact'], true)) throw new RuntimeException('Choose distribution or exact');
    $php = $profile === 'exact' ? "$work/source/x64/Release/php.exe" : "$work/php86ga/php.exe";
    $binaryHashes = S::hashes([$php, dirname($php) . '/php8.dll']);
    if ($profile === 'exact') {
        $receipt = S::read("$out/build-receipt.json");
        foreach ($binaryHashes as $path => $hash) if (($receipt['hashes'][$path] ?? null) !== $hash) throw new RuntimeException('Build custody mismatch');
    } else {
        if (hash_file('sha256', "$work/php86ga.zip") !== 'd4783fffe1f4ab072600daff1622a7a2df936957a787a338c59e3dbd4d846d98') throw new RuntimeException('Distribution mismatch');
        $zip = new ZipArchive(); $zip->open("$work/php86ga.zip");
        foreach ($binaryHashes as $path => $hash) if (hash('sha256', $zip->getFromName(basename($path))) !== $hash) throw new RuntimeException('Distribution output mismatch');
        $zip->close();
    }
    $oldPath = 'docs/8.6/rc7-source-reconciliation/probes-8.6.json';
    $old = S::read($oldPath); $rows = []; $mismatches = []; $differences = [];
    $inputs = [$oldPath => hash_file('sha256', $oldPath)];
    foreach ($old['inputs'] as $path => $hash) {
        if (!is_file($path) || hash_file('sha256', $path) !== $hash) throw new RuntimeException("Probe input differs: $path");
        $inputs[$path] = $hash;
    }
    foreach ($old['observations'] as $r) {
        $cmd = $r['command']; $cmd[0] = $php;
        array_splice($cmd, 2, 0, ['-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0', '-d', 'zend.multibyte=0']);
        $result = S::run($cmd, $root);
        $expected = $r['historicalExpectedAcceptance']; $diagnostic = $r['expectedDiagnostic'];
        $matches = ($expected === null || (($result['exit_code'] === 0) === $expected)) && ($diagnostic === null || $diagnostic === '' || str_contains($result['output'], $diagnostic));
        $row = ['id' => $r['id'], 'fixture' => $r['fixture'], 'stage' => $r['stage'], 'command' => $cmd,
            'exitCode' => $result['exit_code'], 'output' => $result['output'], 'expectedAcceptance' => $expected, 'expectedDiagnostic' => $diagnostic, 'matches' => $matches,
            'rc7ExitCode' => $r['exitCode'], 'rc7Matches' => $r['matches']];
        $rows[] = $row;
        if (!$matches) $mismatches[] = $r['id'] . ':' . $r['stage'];
        if ($row['exitCode'] !== $r['exitCode'] || $matches !== $r['matches']) $differences[] = $r['id'] . ':' . $r['stage'];
    }
    foreach ($inputs + $binaryHashes as $path => $hash) if (hash_file('sha256', $path) !== $hash) throw new RuntimeException("Input changed during observations: $path");
    save("$out/observations-$profile.json", ['sourceCommit' => $commit, 'profile' => $profile, 'binaryHashes' => $binaryHashes,
        'version' => S::run([$php, '-n', '-v'], $root), 'inputs' => $inputs, 'observations' => $rows, 'mismatches' => $mismatches, 'rc7OutcomeDifferences' => $differences,
        'scope' => 'Replay reviewed RC7 stage/diagnostic expectations on GA; no historical expectation is changed', 'certificationGranted' => false]);
    echo S::json(['observations' => count($rows), 'mismatches' => $mismatches, 'rc7OutcomeDifferences' => $differences]); exit;
}
if ($mode === '--ordinary') {
    require_once __DIR__ . '/../lib/DifferentialInventory.php';
    $php = "$work/source/x64/Release/php.exe";
    $inventory = 'docs/8.6/scanner-grammar-binding-closure/inventory.json';
    $cases = S::read($inventory)['cases']; $rows = []; $errors = [];
    $d = new PhpGrammar\Tools\DifferentialInventory($root);
    foreach ($cases as $r) {
        if (hash_file('sha256', $r['fixture']) !== $r['sha256']) throw new RuntimeException('Fixture changed');
        $cmd = [$php, '-n', '-d', 'short_open_tag=1', '-d', 'zend.multibyte=0', '-d', 'opcache.enable_cli=0', '-d', 'opcache.jit=0', '-d', 'display_errors=1', '-d', 'log_errors=0', '-d', 'error_reporting=-1', '-l', $r['fixture']];
        $lint = S::run($cmd, $root); $native = $d->native($r);
        $expected = $r['expected'];
        $matches = $lint['exit_code'] === $expected['lintExit'] && $d::diagnostics($lint['output']) === $expected['diagnostics']
            && $native === array_intersect_key($expected, array_flip(['lexicalAccepted', 'structuralAccepted']));
        if (!$matches) $errors[] = $r['fixture'];
        $rows[] = ['fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'stage' => $r['stage'], 'expected' => $expected, 'native' => $native, 'command' => $cmd, 'lint' => $lint, 'matches' => $matches];
    }
    save("$out/ordinary.json", ['sourceCommit' => $commit, 'inventory' => $inventory, 'inventorySha256' => hash_file('sha256', $inventory),
        'binaryHashes' => S::hashes([$php, dirname($php) . '/php8.dll']), 'observations' => $rows, 'errors' => $errors, 'certificationGranted' => false]);
    echo S::json(['cases' => count($rows), 'errors' => $errors]); exit($errors ? 1 : 0);
}
if ($mode === '--validate') {
    $php = '.audit/php85/php.exe';
    $base = [$php, '-n', '-d', 'memory_limit=1G'];
    $assessment = 'docs/8.6/certification-contract-remediation/runs/phase7h1-audited/evidence.json';
    $commands = [
        'manifest' => [...$base, 'tools/validate-manifest.php'],
        'traceability' => [...$base, 'tools/8.6/change-traceability.php', '--report', "$out/traceability.json"],
        'coverage' => [...$base, 'bin/grammar-coverage.php', '8.6'],
        'historical-integrity' => [...$base, 'tools/evidence/historical-check.php'],
        'compatibility-check' => [...$base, 'tools/evidence/compatibility.php', '--check', '--report', 'docs/8.5/compatibility/phase7h1-audited/evidence.json'],
        'certification-check' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--check', '--report', $assessment],
        'certification-claims' => [...$base, 'tools/8.6/certification-scanner-differential.php', '--contract', 'php86-certification-v4', '--claims', '--report', $assessment],
        'phpunit' => [...$base, '-d', 'extension=' . $root . '/.audit/php85/ext/php_mbstring.dll', 'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress'],
    ];
    $rows = [];
    foreach ($commands as $id => $cmd) {
        echo "Running $id\n"; flush();
        $rows[$id] = command("validation-$id", $cmd, false);
        echo "$id: {$rows[$id]['exitCode']}\n"; flush();
    }
    save("$out/validation.json", ['interpreter' => S::run([$php, '-n', '-v'], $root), 'commands' => $rows]); exit;
}
if ($mode === '--review') {
    $git = ['git', '-c', 'gc.auto=0', '--git-dir=.audit/rc7-git'];
    command('complete-diff', [...$git, 'diff', '--no-ext-diff', $rc7, $commit]);
    command('proposal-ancestry', [...$git, 'merge-base', '--is-ancestor', '5089bcc83c8193e6a965dd1579767b1d503401ba', $commit], false);
    $core = [];
    foreach (['Zend/zend_language_parser.y', 'Zend/zend_language_scanner.l', 'Zend/zend_compile.c', 'Zend/zend_ast.c', 'Zend/zend_vm_def.h', 'Zend/zend_inheritance.c', 'Zend/zend_property_hooks.c', 'Zend/zend_attributes.c', 'Zend/zend_enum.c', 'Zend/zend_constants.c'] as $path) {
        $before = hash_file('sha256', ".audit/phase8a/rc7/$path");
        $after = hash_file('sha256', "$work/git-ga/$path");
        $core[$path] = ['rc7' => $before, 'ga' => $after, 'identical' => $before === $after];
    }
    $specific = [
        'Zend/zend.c' => 'Fatal-error backtrace creation/destruction only while executor active; runtime lifecycle correction.',
        'Zend/zend_API.c' => 'Incomplete module request shutdown terminates process; runtime lifecycle correction.',
        'Zend/zend_builtin_functions.c' => 'Trampoline argument placement in debug backtraces; runtime memory safety, no argument grammar change.',
        'ext/dom/xml_serializer.c' => 'Unowned descendant namespace data after copy-on-write; XML serialization runtime safety.',
        'ext/phar/util.c' => 'Require OpenSSL signature when a public key exists; archive authentication, not PHP lexical acceptance.',
        'ext/session/mod_files.c' => 'Truncate session file before write; runtime persistence.',
        'ext/session/session.c' => 'Disable upload progress by default, guard runtime ini changes and initialize request fields; session lifecycle.',
        'ext/soap/php_encoding.c' => 'Zero-dimension SOAP array fallback; protocol decoding.',
        'ext/soap/php_xml.c' => 'Keep DTD alive for shared entity content; XML lifecycle.',
        'ext/standard/ftp_fopen_wrapper.c' => 'Exact case-insensitive FTPS scheme test; network protocol selection.',
        'ext/standard/http_fopen_wrapper.c' => 'Exact HTTPS scheme and smart-string header folding; HTTP parsing, not PHP tokenization.',
        'ext/uri/uri_parser_rfc3986.c' => 'Check recomposition lengths and errors; URI data handling.',
        'main/SAPI.c' => 'Consume remaining request input with existing body stream; request handling.',
        'main/fastcgi.c' => 'Reject residual input records and clear connection state; FastCGI framing.',
        'main/main.c' => 'Use date and backtrace state only while modules/executor active; runtime logging.',
        'sapi/cgi/cgi_main.c' => 'Bound PATH_INFO suffix before pointer arithmetic; request routing.',
        'sapi/fpm/fpm/fpm_status.c' => 'Escape method, authentication user and script filename in status output; output encoding.',
        'sapi/phpdbg/phpdbg.c' => 'Initialize frame generator; debugger state.',
        'sapi/phpdbg/phpdbg_frame.c' => 'Trampoline argument offsets and NUL termination; debugger backtrace formatting.',
    ];
    $rows = [];
    foreach (S::read("$out/tree-inventory.json")['changes'] as $path => $identity) {
        $reason = $specific[$path] ?? null; $classification = 'runtime-only change';
        if ($reason === null && (str_contains($path, '/tests/') || str_ends_with($path, '.phpt'))) { $reason = 'Regression data for the associated runtime/security correction; does not add PHP syntax or a compile-time validity predicate.'; $classification = 'runtime regression evidence'; }
        if ($reason === null && str_starts_with($path, 'ext/zend_test/')) { $reason = 'Test-only startup warning instrumentation; no language parser/compiler changes.'; $classification = 'implementation refactoring'; }
        if ($reason === null && in_array($path, ['NEWS', 'UPGRADING'], true)) { $reason = $path === 'NEWS' ? 'GA release/security notes replace prerelease history.' : 'Correct TLS session-resumption RFC URL; no language-rule change.'; $classification = 'documentation'; }
        if ($reason === null && in_array($path, ['.github/workflows/test-suite.yml', 'configure.ac', 'main/php_version.h', 'Zend/zend.h'], true)) { $reason = 'Release/build/version metadata; no grammar change.'; $classification = 'release infrastructure'; }
        if ($reason === null) throw new RuntimeException("Unreviewed changed path $path");
        $rows[] = ['path' => $path, 'identity' => $identity, 'classification' => $classification, 'reason' => $reason,
            'source' => "https://github.com/php/php-src/blob/$commit/$path", 'ebnfChange' => false, 'remainingRisk' => 'Does not adjudicate older PHP86-013 residual discovery.'];
    }
    $compiler = file_get_contents("$work/git-ga/Zend/zend_compile.c");
    $at = strpos($compiler, 'static void zend_compile_var_inner(');
    if ($at === false) $at = strpos($compiler, 'zend_compile_var_inner(');
    file_put_contents("$out/writable-dispatch.txt", substr($compiler, $at, 6000));
    save("$out/review.json", ['rc7' => $rc7, 'ga' => $commit, 'core' => $core, 'changedPaths' => $rows,
        'PHP86-014' => 'PR remains open/unmerged; compiler is byte-identical to RC7. No GA fix or closure.',
        'PHP86-013' => 'This bounded RC7-to-GA review does not close the original PHP-8.5-to-8.6 residual work queue.',
        'additionalEbnfChangesIdentified' => false, 'certificationGranted' => false]);
    echo count($rows) . " changed paths classified\n"; exit;
}
if ($mode === '--source-counts') {
    require_once __DIR__ . '/../lib/ParserReconciliation.php';
    $parser = file_get_contents("$work/git-ga/Zend/zend_language_parser.y");
    preg_match_all('/^([a-z][a-z_]*):/m', $parser, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $productions = [];
    foreach ($matches as $i => $match) {
        $start = $match[0][1] + strlen($match[0][0]);
        $end = $matches[$i + 1][0][1] ?? strpos($parser, "\n%%", $start);
        $productions[$match[1][0]] = count(PhpGrammar\Tools\ParserReconciliation::branches(substr($parser, $start, $end - $start)));
    }
    $scanner = file_get_contents("$work/git-ga/Zend/zend_language_scanner.l");
    preg_match_all('/^<([A-Z_,]+)>[^\n]*/m', $scanner, $headers, PREG_SET_ORDER);
    $states = [];
    foreach ($headers as $header) foreach (explode(',', $header[1]) as $state) $states[$state] = true;
    $r = ['sourceCommit' => $commit, 'parserProductions' => count($productions), 'parserAlternatives' => array_sum($productions),
        'parserBranchCounts' => $productions, 'scannerHeaders' => count($headers), 'scannerStates' => array_keys($states),
        'sourceHashes' => S::hashes(["$work/git-ga/Zend/zend_language_parser.y", "$work/git-ga/Zend/zend_language_scanner.l"]),
        'scope' => 'Recomputed source structure, not scanner-state or AST equivalence certification'];
    save("$out/source-counts.json", $r); unset($r['parserBranchCounts']); echo S::json($r); exit;
}
if ($mode === '--focused') {
    require_once __DIR__ . '/../lib/ExactPinReconciliation.php';
    $oldPath = 'docs/8.6/scanner-grammar-binding-closure/observations.json';
    $old = S::read($oldPath); $rows = []; $differences = [];
    $php = "$work/source/x64/Release/php.exe";
    foreach ($old['observations'] as $r) {
        if (hash_file('sha256', $r['fixture']) !== $r['sha256']) throw new RuntimeException('Focused fixture changed');
        $row = ['group' => $r['group'], 'id' => $r['id'], 'fixture' => $r['fixture'], 'sha256' => $r['sha256']];
        foreach ($r as $key => $value) {
            if (!is_array($value) || !isset($value['command'], $value['exit_code'], $value['output'])) continue;
            $cmd = $value['command']; $cmd[0] = $php;
            // Retained commands use absolute paths from the same workspace; bind the local fixture explicitly.
            $result = S::run($cmd, $root);
            $row[$key] = ['command' => $cmd, ...$result];
            if ($result['exit_code'] !== $value['exit_code']) $differences[] = "{$r['id']}:$key:exit";
            if (str_contains(strtolower($key), 'token') && $result['output'] !== $value['output']) $differences[] = "{$r['id']}:$key:tokens";
        }
        if ($r['group'] === 'binding') {
            $a = PhpGrammar\Tools\ExactPinReconciliation::opcodeLines($row['originalDump']['output']);
            $b = PhpGrammar\Tools\ExactPinReconciliation::opcodeLines($row['forcedDump']['output']);
            $d = PhpGrammar\Tools\ExactPinReconciliation::opcodeLines($row['differentDump']['output']);
            $row['discriminating'] = $a !== [] && $a === $b && $d !== [] && $a !== $d;
            if (!$row['discriminating']) $differences[] = "{$r['id']}:binding";
        }
        $rows[] = $row;
    }
    save("$out/focused.json", ['sourceCommit' => $commit, 'predecessor' => $oldPath, 'predecessorSha256' => hash_file('sha256', $oldPath),
        'binaryHashes' => S::hashes([$php, dirname($php) . '/php8.dll']), 'observations' => $rows, 'differences' => $differences,
        'scope' => 'Fresh GA scanner and discriminating opcode observations; native derivation evidence remains separately tested', 'certificationGranted' => false]);
    echo S::json(['cases' => count($rows), 'groups' => array_count_values(array_column($rows, 'group')), 'differences' => $differences]); exit($differences ? 1 : 0);
}
if ($mode === '--upstream') {
    $php = "$root/$work/source/x64/Release/php.exe";
    $source = "$root/$work/php-8.6.0";
    $tests = ['Zend/tests/GHSA-8mjj-2r7p-4hg2.phpt', 'Zend/tests/array_map_foreach_optimization_function_name.phpt',
        'Zend/tests/closures/gh23911.phpt', 'Zend/tests/partial_application/gh25007.phpt', 'Zend/tests/partial_application/static_pfa_004.phpt',
        'Zend/tests/constexpr/new_named_params_duplicate_leak.phpt', 'Zend/tests/assert/expect_final_abstract_modifiers.phpt',
        'Zend/tests/assert/expect_readonly_anon_class.phpt', 'Zend/tests/attributes/override/constants/anon_failure.phpt'];
    for ($i = 1; $i <= 6; ++$i) $tests[] = sprintf('Zend/tests/first_class_callable/constexpr/trait_scope_%03d.phpt', $i);
    $hashes = []; foreach ($tests as $path) $hashes[$path] = hash_file('sha256', "$source/$path");
    putenv('NO_INTERACTION=1'); putenv('REPORT_EXIT_STATUS=1');
    $cmd = [$php, '-n', 'run-tests.php', '-q', '-n', '-p', $php, ...$tests];
    if (is_file("$out/upstream.log")) throw new RuntimeException('Never overwrite upstream evidence');
    $r = S::run($cmd, $source, "$root/$out/upstream.log");
    save("$out/upstream.json", ['sourceCommit' => $commit, 'command' => $cmd, 'cwd' => $source, 'exitCode' => $r['exit_code'],
        'testHashes' => $hashes, 'log' => "$out/upstream.log", 'logSha256' => hash_file('sha256', "$out/upstream.log"),
        'scope' => 'Reviewed focused Zend regression selection, not the full upstream suite']);
    echo $r['output']; exit($r['exit_code']);
}
if ($mode === '--writable') {
    $php = "$work/source/x64/Release/php.exe";
    $oldPath = 'docs/8.6/rc7-source-reconciliation/proposal-controls.json';
    $old = S::read($oldPath); $rows = [];
    foreach ($old['observations'] as $r) {
        if ($r['version'] !== '8.6.0RC7') continue;
        if (hash_file('sha256', $r['fixture']) !== $r['sha256']) throw new RuntimeException('Writable fixture changed');
        $cmd = $r['command']; $cmd[0] = $php;
        $rows[] = ['id' => $r['id'], 'fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'stage' => $r['stage'],
            'command' => $cmd, 'result' => S::run($cmd, $root), 'rc7ExitCode' => $r['exitCode']];
        if ($r['stage'] === 'lint') {
            $dump = [$php, '-n', '-d', 'opcache.enable_cli=1', '-d', 'opcache.optimization_level=0', '-d', 'opcache.opt_debug_level=65536', '-d', 'opcache.file_update_protection=0', '-l', $r['fixture']];
            $rows[] = ['id' => $r['id'], 'fixture' => $r['fixture'], 'sha256' => $r['sha256'], 'stage' => 'opcode-generation', 'command' => $dump, 'result' => S::run($dump, $root)];
        }
    }
    save("$out/writable.json", ['sourceCommit' => $commit, 'predecessor' => $oldPath, 'predecessorSha256' => hash_file('sha256', $oldPath),
        'observations' => $rows, 'scope' => 'Controlled lint, opcode and execution stages; observations do not decide intended validity', 'certificationGranted' => false]);
    echo count($rows) . " writable-stage observations\n"; exit;
}
if ($mode === '--assess') {
    $legacy = S::read('docs/8.6/certification-gates.json')['gates'];
    $gates = S::read('docs/8.6/certification-gates-v2.json')['gates'];
    if (count($legacy) !== 36 || count($gates) !== 38 || array_slice($gates, 0, 36, true) !== $legacy) throw new RuntimeException('Original obligation accounting changed');
    $evidence = [
        'manifest' => ['validation-manifest.log'], 'traceability' => ['validation-traceability.log'],
        'coverage' => ['validation-coverage.log', 'source-counts.json', 'focused.json'],
        'boundaries' => ['ordinary.json'], 'integration' => ['observations-exact.json', 'focused.json'],
        'pfa' => ['observations-exact.json', 'upstream.json'], 'context' => ['observations-exact.json'],
        'deprecation' => ['observations-exact.json'], 'audit' => ['review.json', 'source-counts.json'],
        'source' => ['source-profile.json', 'archive-correspondence.json'], 'provenance' => ['build-receipt.json'],
        'historical-integrity' => ['validation-historical-integrity.log'], 'current-compatibility' => ['validation-compatibility-check.log'],
        'historical' => ['validation-certification-check.log'], 'freshness' => ['validation-certification-check.log'],
    ];
    $rows = [];
    foreach ($gates as $id => [$kind, $requirement]) {
        $support = str_starts_with($kind, 'test-') ? ['validation-phpunit.log'] : ($evidence[$kind] ?? []);
        $rows[$id] = ['originalKind' => $kind, 'originalRequirement' => $requirement,
            'gaCertificationStatus' => 'not-satisfied',
            'reason' => str_starts_with($kind, 'blocked:') ? substr($kind, 8) . ': original substantive closure remains outstanding'
                : 'Supplemental observations do not constitute an adopted, independently verified GA successor of this release obligation',
            'supplementalEvidence' => array_map(static fn($p) => "$out/$p", $support)];
    }
    $dispositions = [
        'PHP86-013' => 'RC7-to-GA 53-path review is complete within its language-risk scope. The Phase 7E queue still has 198 pending and 17 examined-unresolved hunks, plus grouped/outside-ledger obligations. No whole discovery closure.',
        'PHP86-014' => 'PR 25046 remains open and unmerged; GA compiler is byte-identical to RC7. GA retains assignment-result invalid-opcode behavior and executable reference forms. Intended validity/coherent implementation remains unresolved.',
        'PHP86-015' => 'Maintainer explanation and wontfix decision remain resolved. Fresh GA controls preserve the known traversal boundaries; complete ordered visitation/source correspondence and scope adjudication remain open.',
        'CA86-001' => 'Maintainer explanation remains resolved. The structural get/set restriction still excludes discarded unknown hooks accepted upstream. No independent correction or enforceable bounded exclusion has been adopted.',
        'C6-CONTEXT' => '73 descriptions are not isolated predicates. External contextual enforcement remains disclosed; complete predicate/stage evidence and bounded-claim adjudication remain open.',
        'C6-COVERAGE' => 'Fresh coverage retains 63 unexercised productions and 255 alternatives. Existing per-identity limitations, scanner helper/state and binding obligations remain; no percentage threshold or fixture deletion is used.',
        'C6-PROVENANCE' => 'Authenticated GA source and local exact-source build add new provenance evidence. Complete successful release gates, historical dependencies, fresh compatibility, and an adopted GA certification contract remain absent.',
    ];
    $blockers = [];
    foreach (S::read('docs/8.6/source-audit-dispositions.json')['blockers'] as $row) $blockers[] = [
        'id' => $row['id'], 'originalRequirement' => $row, 'currentDisposition' => $dispositions[$row['id']],
        'blocksCertification' => true, 'releaseImpact' => 'Retain draft; no stable tag or publication'];
    save("$out/gate-assessment.json", ['sourceCommit' => $commit, 'buildReceipt' => "$out/build-receipt.json",
        'originalGateCount' => 36, 'additionalPrerequisites' => 2, 'obligationCount' => 38, 'gates' => $rows, 'blockers' => $blockers,
        'assessmentKind' => 'GA blocked-release preflight, not a replacement certification runner or a gate-pass ledger',
        'certificationGranted' => false, 'decision' => 'NOT SIGNED OFF — RELEASE BLOCKED']);
    echo "38 obligations retained; seven blockers remain; no certification granted\n"; exit;
}
if ($mode === '--final-tests') {
    command('final-phpunit', ['.audit/php85/php.exe', '-n', '-d', 'memory_limit=1G', '-d', 'extension=' . $root . '/.audit/php85/ext/php_mbstring.dll',
        'vendor/phpunit/phpunit/phpunit', '--do-not-cache-result', '--no-progress']);
    command('final-manifest', ['.audit/php85/php.exe', '-n', 'tools/validate-manifest.php']);
    command('final-traceability', ['.audit/php85/php.exe', '-n', 'tools/8.6/change-traceability.php', '--report', "$out/final-traceability.json"]);
    command('final-build-verification', ['.audit/php85/php.exe', '-n', '-d', 'memory_limit=1G', __FILE__, '--verify-build']);
    echo "Final regression and standalone build verification passed\n"; exit;
}
if ($mode === '--seal' || $mode === '--check') {
    $errors = [];
    $baseline = S::read("$out/baseline.json");
    foreach ($baseline['files'] as $path => $hash) if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = "Preexisting file changed: $path";
    $required = ['source-profile.json', 'archive-correspondence.json', 'review.json', 'source-counts.json', 'build-receipt.json',
        'observations-exact.json', 'observations-distribution.json', 'ordinary.json', 'focused.json', 'writable.json', 'upstream.json',
        'validation.json', 'gate-assessment.json', 'final-phpunit.command.json', 'final-build-verification.command.json'];
    foreach ($required as $name) if (!is_file("$out/$name")) $errors[] = "Missing evidence: $name";
    $paths = [...S::files($out), 'tools/8.6/ga-finalization.php', 'docs/8.6/final-certification.md'];
    $paths = array_values(array_filter($paths, static fn($p) => $p !== "$out/artifacts.json")); sort($paths);
    $hashes = [];
    foreach ($paths as $path) {
        if (!is_file($path)) { $errors[] = "Missing artifact: $path"; continue; }
        $hashes[$path] = hash_file('sha256', $path);
        if (str_ends_with($path, '.json')) {
            try { S::read($path); } catch (Throwable $e) { $errors[] = "Invalid JSON: $path"; }
        }
    }
    foreach (['final-phpunit', 'final-manifest', 'final-traceability', 'final-build-verification'] as $id) {
        $row = S::read("$out/$id.command.json");
        if ($row['exitCode'] !== 0 || hash_file('sha256', $row['log']) !== $row['sha256']) $errors[] = "Final verification failed or log changed: $id";
    }
    foreach (['source-profile.json', 'build-receipt.json', 'observations-exact.json', 'ordinary.json', 'focused.json', 'gate-assessment.json'] as $name) {
        $row = S::read("$out/$name");
        if (($row['sourceCommit'] ?? $row['commit'] ?? null) !== $commit) $errors[] = "Incorrect GA identity: $name";
    }
    foreach (S::read("$out/build-receipt.json")['hashes'] as $path => $hash) if (!is_file($path) || hash_file('sha256', $path) !== $hash) $errors[] = "Build artifact changed: $path";
    $sourceProfile = S::read("$out/source-profile.json");
    foreach ($sourceProfile['files'] as $path => $hash) {
        foreach (["$work/git-ga", "$work/php-8.6.0"] as $base) if (!is_file("$base/$path") || hash_file('sha256', "$base/$path") !== $hash) $errors[] = "GA source/archive changed: $base/$path";
    }
    $assessment = S::read("$out/gate-assessment.json");
    if ($assessment['certificationGranted'] !== false || count($assessment['gates']) !== 38 || count($assessment['blockers']) !== 7) $errors[] = 'Incorrect blocked-release assessment';
    foreach (['tools/8.6/ga-finalization.php', 'docs/8.6/final-certification.md', "$out/release-preparation.md"] as $path) {
        $text = file_get_contents($path);
        if (preg_match('/[\t ]+$/m', $text)) $errors[] = "Trailing whitespace: $path";
        if (!str_ends_with($path, '.md')) continue;
        preg_match_all('~\[[^\]]*\]\(([^)]+)\)~', $text, $links);
        foreach ($links[1] as $link) {
            if (preg_match('~^https?://~', $link)) continue;
            $target = explode('#', $link)[0];
            $local = dirname($path) . '/' . $target;
            // The seal is the sole output created after these input checks.
            $pendingSeal = $mode === '--seal' && $local === "$out/artifacts.json";
            if ($target !== '' && !$pendingSeal && !file_exists($local)) $errors[] = "Broken local link in $path: $link";
        }
    }
    if ($mode === '--seal') {
        if (!$errors) save("$out/artifacts.json", ['contract' => 'php86-ga-blocked-finalization-v1', 'files' => $hashes,
            'preservedFiles' => count($baseline['files']), 'certificationGranted' => false,
            'scope' => 'Integrity of this additive blocked-release bundle; no substitution for existing certification contracts']);
    } else {
        $sealed = S::read("$out/artifacts.json");
        if ($sealed['files'] !== $hashes) $errors[] = 'Finalization artifact membership or bytes changed';
    }
    echo S::json(['errors' => $errors, 'preservedFiles' => count($baseline['files']), 'artifactFiles' => count($hashes), 'certificationGranted' => false]); exit($errors ? 1 : 0);
}
fwrite(STDERR, "Specify an acquisition, build, observation or validation operation\n"); exit(2);
