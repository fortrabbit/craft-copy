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

// dotenv?
if (file_exists($root . '/.env')) {
    $dotenv = new Dotenv\Dotenv($root);
    $dotenv->load();
}

// Bootstrap Craft
/** @var \craft\console\Application $app */
$app = require $root . '/vendor/craftcms/cms/bootstrap/console.php';

if (count($argv) < 2 || stristr($argv[1], '.sql') == false) {
    echo "No import file given";
    exit(1);
}

$file = $argv[1];

if (!file_exists($file)) {
    echo "Import file does not exist";
    exit(1);
}

if ($app->getIsInstalled()) {
    echo "Craft is already installed!" . PHP_EOL;
    if (!in_array('--force', $argv)) {
        echo "Abort. No --force flag given." . PHP_EOL;
        exit(1);
    }
}

if (!$app->getConfig()->getDb()->database) {
    echo "No DB Config found." . PHP_EOL;
    exit(1);
}

$db     = $app->getConfig()->getDb();
$cmd    = 'mysql -u {DB_USER} -p{DB_PASSWORD} -h {DB_SERVER} {DB_DATABASE} < {file} && echo 1';
$tokens = [
    '{file}'        => $file,
    '{DB_USER}'     => $db->user,
    '{DB_PASSWORD}' => $db->password,
    '{DB_SERVER}'   => $db->server,
    '{DB_DATABASE}' => $db->database,
];

$cmd     = str_replace(array_keys($tokens), array_values($tokens), $cmd);
$process = new \Symfony\Component\Process\Process($cmd);
$process->run();

if ($process->isSuccessful()) {
    echo 'OK';
    exit(0);
}

echo "ERROR: ";
echo $process->getErrorOutput();
exit(1);
