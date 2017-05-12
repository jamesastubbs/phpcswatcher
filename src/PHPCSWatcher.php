<?php

namespace PHPCSWatcher;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;

class PHPCSWatcher
{
    public function watch($path)
    {
        $path = $this->resolvePath($path);

        $watcher = new Watcher(
            new Tracker(),
            new Filesystem()
        );

        $listener = $watcher->watch($path);
        $listener->anything(function($event, $resource, $path) {
            if (preg_match('/.+\.php$/', $path) === 0) {
                return;
            }

            fwrite(STDOUT, "$path changed:" . PHP_EOL);
            passthru(realpath(
                __DIR__ .
                '/../../../squizlabs/php_codesniffer/bin'
            ) . '/phpcs --standard=' . realpath(__DIR__ . '/../') . '/psr2_standard.xml --colors -n ' . $path, $exitCode);

            if ($exitCode === 0) {
                fwrite(STDOUT, "\033[1;32mFile OK \033[0m \n"); // bold green formatting.
            } else {
                fwrite(STDOUT, "\x07"); // CLI beep.
            }

            fwrite(STDOUT,  PHP_EOL);
        });

        fwrite(STDOUT, 'Watching...' . PHP_EOL . PHP_EOL);
        $watcher->start();
    }

    protected function resolvePath($path)
    {
        if (substr($path, 0, 1) !== '/' && substr($path, 0, 1) !== '~') {
            $path = getcwd() . '/' . $path;
        }

        return $path = realpath($path);
    }
}
