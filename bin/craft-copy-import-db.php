#!/usr/bin/env php
<?php

// Detect the project root
$root = $_SERVER["PWD"] ?? __DIR__;
while (!file_exists($root . '/craft')) {
    $root .= '/..';
    if (substr_count($root, '/..') > 5) {
        die('Unable to find the project root: craft binary is missing.');
    }
}


// Composer autoloader
require_once $root . '/vendor/autoload.php';

define('CRAFT_VENDOR_PATH', $root . '/vendor');
define('CRAFT_BASE_PATH', $root);
define('YII_DEBUG', false);

// dotenv? 3.x vs 2.x
if (file_exists($root . '/.env')) {
    $dotenv = (method_exists('\Dotenv\Dotenv', 'create'))
        ? \Dotenv\Dotenv::create($root)
        : new \Dotenv\Dotenv($root);
    $dotenv->load();
}

if (count($argv) < 2 || stristr($argv[1], '.sql') == false) {
    echo "No import file given";
    exit(1);
}

// Assure the ./storage folder exist
$storage = $root . '/storage';
if (!is_dir($storage)) {
    if (!mkdir($storage)) {
        echo "Unable to create $storage";
        exit(1);
    }
}

$file = $argv[1];

if (!file_exists($file)) {
    echo "Import file does not exist";
    exit(1);
}

$credentialsFile = "/tmp/mysql-extra.cnf";
$credentialsFileContent = [
      "[client]",
      "user=" . getenv('DB_USER'),
      "password=" . getenv('DB_PASSWORD'),
      "host=" . getenv('DB_SERVER')
];

if (false === file_put_contents($credentialsFile, join(PHP_EOL, $credentialsFileContent))) {
    echo "ERROR: unable to write $credentialsFile";
    exit(1);
}

$tokens = [
    '{FILE}' => $file,
    '{EXTRA_FILE}' => $credentialsFile,
    '{DB_DATABASE}' => getenv('DB_DATABASE'),
];

$cmd = 'mysql --defaults-extra-file={EXTRA_FILE} --force {DB_DATABASE} < {FILE} && echo 1';
$cmd = str_replace(array_keys($tokens), array_values($tokens), $cmd);

$process = \Symfony\Component\Process\Process::fromShellCommandline($cmd);
$process->run();

unlink($credentialsFile);

if ($stderr = $process->getErrorOutput()) {
    fwrite(STDERR,  'ERROR (sql):' . PHP_EOL);
    fwrite(STDERR,  substr($stderr, 0, 200));
    exit(1);
}

if ($process->isSuccessful()) {
    echo 'OK';
    exit(0);
}

fwrite(STDERR, 'ERROR (unknown)');
exit(1);
