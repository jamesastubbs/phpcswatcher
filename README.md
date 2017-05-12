# PHPCSWatcher

A listener which automatically invokes PHP_CodeSniffer when a PHP file has been added/modified.

-----

## Installation

Please use [Composer](https://getcomposer.org) to download this package:

```
composer require --dev jamesastubbs/phpcswatcher
```

This package should only be installed as a dev dependancy and should not be used in a production environment.

## Usage

To run `phpcswatcher`, a symlink will be provided in the `vendor/bin` directory which will allow you to run the command like so:

```
./vendor/bin/phpcswatcher [dir_to_watch]
```
