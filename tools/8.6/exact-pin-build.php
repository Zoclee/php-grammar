<?php
declare(strict_types=1);

// Workspace-local native Windows build steps. Acquisition/authentication is
// deliberately separate; this command never installs tools or patches sources.
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/Certification86.php';
require_once __DIR__ . '/../lib/ExactPinProvenance.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\Certification86 as C;
use PhpGrammar\Tools\ExactPinProvenance as P;
$args = S::args(['stage' => '', 'revision' => '']);
$root = str_replace('\\', '/', realpath(S::ROOT));
if (!preg_match('~^[A-Za-z]:/[A-Za-z0-9_ ./-]+$~D', $root)) throw new RuntimeException('Workspace path cannot be safely passed to the Windows SDK launcher');
$work = $root . '/.audit/php86-exact';
$out = $root . '/docs/8.6/exact-pin-build';
if (PHP_OS_FAMILY !== 'Windows' || !in_array($args['stage'], ['environment', 'configure', 'compile'], true)
    || !preg_match('/^[a-z0-9-]+$/D', $args['revision'])) throw new RuntimeException('Require Windows, reviewed stage, and a unique revision label');
$id = $args['stage'] . '-' . $args['revision'];
foreach (['json', 'stdout.log', 'stderr.log'] as $suffix) if (file_exists($out . '/' . $id . '.' . $suffix)) throw new RuntimeException('Refusing to overwrite build evidence');
$c = new C($root);
$auth = $c->read('docs/8.6/exact-pin-build/authentication.json');
if (($auth['source']['commit'] ?? null) !== C::PIN || ($auth['sourceLockErrors'] ?? null) !== []) throw new RuntimeException('Authenticate source before build');
$errors = $c->sourceErrors($c->read('tools/8.6/certification-source-lock.json'), $work . '/source');
if ($errors) throw new RuntimeException(implode('; ', $errors));
$commands = [
    'environment' => "where cl\r\nwhere nmake\r\nwhere bison\r\nwhere re2c\r\ncl\r\nbison --version\r\nre2c --version\r\necho WindowsSDKVersion=%WindowsSDKVersion%\r\necho VCToolsVersion=%VCToolsVersion%\r\nexit /b 0",
    'configure' => "call buildconf.bat\r\nif errorlevel 1 exit /b %errorlevel%\r\ncall configure.bat " . P::CONFIGURE . "\r\nexit /b %errorlevel%",
    'compile' => "nmake\r\nexit /b %errorlevel%",
];
if ($args['stage'] === 'compile' && is_file($work . '/source/x64/Release/php.exe')) throw new RuntimeException('Output executable already exists; a fresh isolated build is required');
$task = $work . '/' . $id . '.bat';
$taskBytes = "@echo off\r\ncd /d \"$work/source\"\r\n" . $commands[$args['stage']] . "\r\n";
file_put_contents($task, $taskBytes);
$command = ['cmd.exe', '/d', '/c', str_replace('/', '\\', $work . '/sdk/phpsdk-vs17-x64.bat'), '-t', str_replace('/', '\\', $task)];
$commandLine = 'cmd.exe /d /s /c ""' . $command[3] . '" -t "' . $command[5] . '""';
$record = ['command' => $command, 'cwd' => $work, 'started' => gmdate('c'), 'task' => $task,
    'windowsCommandLine' => $commandLine,
    'taskSha256' => hash('sha256', $taskBytes), 'taskContents' => $taskBytes,
    'controllerSha256' => hash_file('sha256', __FILE__), 'sourceCommit' => C::PIN];
$process = proc_open($commandLine, [0 => ['pipe', 'r'], 1 => ['file', "$out/$id.stdout.log", 'w'], 2 => ['file', "$out/$id.stderr.log", 'w']], $pipes, $work, null, ['bypass_shell' => true]);
if (!is_resource($process)) throw new RuntimeException('Cannot start build command');
fclose($pipes[0]);
$record['exit_code'] = proc_close($process);
$record['finished'] = gmdate('c');
foreach (['stdout', 'stderr'] as $stream) $record[$stream] = ['path' => "docs/8.6/exact-pin-build/$id.$stream.log", 'sha256' => hash_file('sha256', "$out/$id.$stream.log")];
file_put_contents("$out/$id.json", S::json($record));
echo S::json($record);
exit($record['exit_code']);
