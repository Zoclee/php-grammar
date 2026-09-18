<?php

declare(strict_types=1);

namespace PhpGrammar\Tools;

final class Support
{
    public const ROOT = __DIR__ . '/../..';
    public const PIN = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e';

    public static function text(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
    }

    public static function read(string $path): array
    {
        return json_decode(self::text(self::ROOT . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function json(mixed $value, bool $unicode = false): string
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        if ($unicode) {
            $flags |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS;
        }
        return preg_replace_callback('/^ +/m', static fn(array $m): string => str_repeat(' ', intdiv(strlen($m[0]), 2)), json_encode($value, $flags)) . "\n";
    }

    public static function emit(string $path, mixed $value, bool $check, string $error, bool $unicode = false): void
    {
        self::emitText($path, self::json($value, $unicode), $check, $error);
    }

    public static function emitText(string $path, string $output, bool $check, string $error): void
    {
        $path = self::ROOT . '/' . $path;
        if ($check) {
            if (!is_file($path) || self::text($path) !== $output) {
                throw new \RuntimeException($error);
            }
        } else {
            file_put_contents($path, str_replace("\n", "\r\n", $output));
        }
    }

    public static function hashes(array $paths): array
    {
        $hashes = [];
        foreach ($paths as $path) {
            $hashes[$path] = hash_file('sha256', self::ROOT . '/' . $path);
        }
        return $hashes;
    }

    /** Strict shared CLI parser: flags, valued options and bounded positional arguments. */
    public static function args(array $defaults = [], array $flags = [], int $min = 0, int $max = 0): array
    {
        global $argv;
        $result = $defaults + array_fill_keys($flags, false) + ['positionals' => []];
        for ($i = 1; $i < count($argv); ++$i) {
            $arg = $argv[$i];
            if (in_array($arg, ['--help', '-h'], true)) {
                echo 'Usage: php ' . $argv[0] . ($max ? ' <path>' : '') . ' ' . implode(' ', array_map(static fn(string $s): string => '[--' . $s . ' VALUE]', array_keys($defaults))) . ' ' . implode(' ', array_map(static fn(string $s): string => '[--' . $s . ']', $flags)) . "\n";
                exit(0);
            }
            if ($arg === '--') {
                array_push($result['positionals'], ...array_slice($argv, $i + 1));
                break;
            }
            if (str_starts_with($arg, '-')) {
                [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, null);
                if (in_array($key, $flags, true) && $value === null) {
                    $result[$key] = true;
                } elseif (array_key_exists($key, $defaults)) {
                    $value ??= $argv[++$i] ?? null;
                    if ($value === null || str_starts_with($value, '--')) {
                        self::usageError('Missing value for --' . $key);
                    }
                    $result[$key] = $value;
                } else {
                    self::usageError('Unknown argument: ' . $arg);
                }
            } else {
                $result['positionals'][] = $arg;
            }
        }
        if (count($result['positionals']) < $min || count($result['positionals']) > $max) {
            self::usageError('Unexpected number of positional arguments');
        }
        return $result;
    }

    private static function usageError(string $message): never
    {
        fwrite(STDERR, $message . "\n");
        exit(2);
    }

    public static function matches(string $pattern, string $text, int $group = 1): array
    {
        preg_match_all($pattern, $text, $matches);
        return $matches[$group];
    }

    public static function sortedUnique(array $items): array
    {
        $items = array_values(array_unique($items));
        sort($items, SORT_STRING);
        return $items;
    }

    /** Keep the existing compact CLI summaries, including booleans and map order. */
    public static function repr(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'True' : 'False';
        }
        if ($value === null) {
            return 'None';
        }
        if (is_string($value)) {
            return "'" . str_replace(['\\', "'", "\n"], ['\\\\', "\\'", '\\n'], $value) . "'";
        }
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $key => $item) {
                $parts[] = (array_is_list($value) ? '' : self::repr($key) . ': ') . self::repr($item);
            }
            return (array_is_list($value) ? '[' : '{') . implode(', ', $parts) . (array_is_list($value) ? ']' : '}');
        }
        return (string) $value;
    }

    public static function summary(array $value): void
    {
        echo self::repr($value) . "\n";
    }

    public static function sources(string $directory, string $mismatch = 'source hash differs from pin'): array
    {
        $result = [];
        foreach (self::read('tools/8.5/source-lock.json') as $name => $hash) {
            $bytes = file_get_contents($directory . '/' . $name);
            if (hash('sha256', $bytes) !== $hash) {
                throw new \RuntimeException($name . ': ' . $mismatch);
            }
            $result[$name] = $bytes;
        }
        return $result;
    }

    public static function definitions(string $source): array
    {
        preg_match_all('~^(?:static |inline |ZEND_API )*[\w *]+\b([a-zA-Z_]\w*)\([^;{}]*\)\s*(?:/\*[^*]*\*/\s*)?\{~m', $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        return $matches;
    }

    public static function functionAt(array $definitions, int $offset, string $default): string
    {
        foreach ($definitions as $definition) {
            if ($definition[0][1] > $offset) {
                break;
            }
            $default = $definition[1][0];
        }
        return $default;
    }

    public static function messages(string $call): string
    {
        return implode(' ', self::matches('~"((?:[^"\\\\]|\\\\.)*)"~', $call));
    }

    public static function files(string $directory): array
    {
        $result = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $result[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($result, SORT_STRING);
        return $result;
    }

    public static function download(string $url): string
    {
        $context = stream_context_create(['http' => ['timeout' => 60, 'header' => "User-Agent: php-grammar-source-audit\r\n"]]);
        return file_get_contents($url, false, $context);
    }

    /** Array commands avoid shell interpolation; stdout/stderr share a durable file. */
    public static function run(array $command, string $cwd, ?string $log = null): array
    {
        $temporary = $log === null;
        $log ??= tempnam(sys_get_temp_dir(), 'php-grammar-');
        try {
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, $cwd);
            if (!is_resource($process)) {
                throw new \RuntimeException('Unable to launch ' . $command[0]);
            }
            fclose($pipes[0]);
            $code = proc_close($process);
            return ['exit_code' => $code, 'output' => file_get_contents($log)];
        } finally {
            if ($temporary && is_file($log)) {
                unlink($log);
            }
        }
    }

    public static function composer(string $command, string $php): array
    {
        if (str_ends_with($command, '.phar')) {
            return [$php, $command];
        }
        // Composer's platform launcher is infrastructure. Prefer its PHP entry point.
        $directories = str_contains($command, '/') || str_contains($command, '\\') ? [dirname($command)] : explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        foreach ($directories as $directory) {
            $phar = rtrim($directory, '/\\') . '/composer.phar';
            if (is_file($phar)) {
                return [$php, $phar];
            }
        }
        return [$command];
    }
}
