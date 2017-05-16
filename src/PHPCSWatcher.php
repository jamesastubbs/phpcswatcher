<?php

namespace PHPCSWatcher;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Event;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;

/**
 * Class  PHPCSWatcher
 *
 * A directory listener which automatically invokes PHP_CodeSniffer
 * when an addition or modification is made to a file.
 *
 * @author     James Stubbs <jamesastubbs@me.com>
 * @license    MIT Licence
 */
class PHPCSWatcher
{
    /**
     * @var  array  Pre-formatted status messages for the event codes.
     */
    protected static $statuses = [
        Event::RESOURCE_DELETED => "\033[0;31mDELETED\033[0m",
        Event::RESOURCE_CREATED => "\033[0;32mCREATED\033[0m",
        Event::RESOURCE_MODIFIED => "\033[0;33mMODIFIED\033[0m"
    ];

    /**
     * @var  string  Directory of the 'phpcs' executable script.
     */
    protected $phpcsDir = null;

    public function __construct()
    {
        $phpcsDir = realpath(__DIR__ . '/../../../bin');

        if ($phpcsDir === false) {
            throw new \Exception("Cannot find 'phpcs' executable. Searched in directory: '$phpcsDir'.");
        }

        $this->phpcsDir = $phpcsDir;
    }

    /**
     * Initialises an event listener watching the directory of '$path'.
     * If a file is added/modified, the 'phpcs' command will automatically be invoked,
     * checking the provoking file.
     * If a file is deleted, only a message is logged as we have nothing to check against.
     *
     * @param  string  $path  The directory to listen to.
     */
    public function watch($path)
    {
        $path = $this->resolvePath($path);

        $watcher = new Watcher(
            new Tracker(),
            new Filesystem()
        );

        $listener = $watcher->watch($path);
        $listener->anything(function ($event, $resource, $path) {
            if (preg_match('/.+\.php$/', $path) === 0) {
                return;
            }

            $code = $event->getCode();
            $status = self::$statuses[$event->getCode()];

            fwrite(STDOUT, "$status - $path:" . PHP_EOL);

            // stop execution since the file has been deleted and there is nothing to check against.
            if ($code === Event::RESOURCE_DELETED) {
                fwrite(STDOUT, PHP_EOL);
                return;
            }

            passthru(
                $this->phpcsDir . '/phpcs --standard=' . realpath(__DIR__ . '/../') . '/psr2_standard.xml --colors -n ' . $path,
                $exitCode
            );

            if ($exitCode === 0) {
                fwrite(STDOUT, "\033[1;32mFile OK \033[0m \n"); // bold green formatting.
            } else {
                fwrite(STDOUT, "\x07"); // CLI beep.
            }

            fwrite(STDOUT, PHP_EOL);
        });

        fwrite(STDOUT, 'Watching for any .php file changes...' . PHP_EOL . PHP_EOL);
        $watcher->start(); // this will keep the application infinitely running.
    }

    /**
     * Takes '$path' and appends the current working directory in front of the '$path' value if required.
     *
     * @param   string  $path  Directory path to resolve.
     *
     * @return  string         The resolved directory.
     */
    protected function resolvePath($path)
    {
        if (substr($path, 0, 1) !== '/' && substr($path, 0, 1) !== '~') {
            $path = getcwd() . '/' . $path;
        }

        return $path = realpath($path);
    }
}
