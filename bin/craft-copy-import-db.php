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

$file = $argv[1];

if (!file_exists($file)) {
    echo "Import file does not exist";
    exit(1);
}


$cmd = 'mysql -u {DB_USER} -p{DB_PASSWORD} -h {DB_SERVER} {DB_DATABASE} < {file} && echo 1';
$tokens = [
    '{file}' => $file,
    '{DB_USER}' => getenv('DB_USER'),
    '{DB_PASSWORD}' => getenv('DB_PASSWORD'),
    '{DB_SERVER}' => getenv('DB_SERVER'),
    '{DB_DATABASE}' => getenv('DB_DATABASE'),
];

$cmd = str_replace(array_keys($tokens), array_values($tokens), $cmd);
$process = \Symfony\Component\Process\Process::fromShellCommandline($cmd);
$process->run();

if ($process->isSuccessful()) {
    echo 'OK';
    exit(0);
}

echo "ERROR: ";
echo $process->getErrorOutput();
exit(1);
