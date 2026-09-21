<?php

declare(strict_types=1);

$roots = ['src', 'public', 'scripts', 'tests', 'views'];
$root = dirname(__DIR__);
$failures = 0;

foreach ($roots as $directory) {
    $path = $root . DIRECTORY_SEPARATOR . $directory;
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-l', $file->getPathname()],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (!is_resource($process)) {
            fwrite(STDERR, 'Unable to lint ' . $file->getPathname() . PHP_EOL);
            $failures++;
            continue;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            fwrite(STDERR, ($stdout ?: '') . ($stderr ?: ''));
            $failures++;
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "Lint failed for {$failures} file(s)." . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "PHP lint passed." . PHP_EOL);
