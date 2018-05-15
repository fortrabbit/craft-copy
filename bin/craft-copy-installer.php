#!/usr/bin/env php
<?php

// Detect the project root
$root = $_SERVER["PWD"] ?? __DIR__;
while(!file_exists($root . '/craft')) {
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
$app = require $root . '/vendor/craftcms/cms/bootstrap/console.php';

installer();


function installer()
{
    if (\Craft::$app->getIsInstalled()) {
        echo "Craft is already installed!" . PHP_EOL;

        return install_copy();
    }


    $migration = new \craft\migrations\Install([
        'username' => 'dummy',
        'password' => \craft\helpers\StringHelper::randomString(),
        'email'    => 'dummy@domain.tld',
        'site'     => new \craft\models\Site([
            'name'     => 'dummy',
            'handle'   => 'default',
            'hasUrls'  => true,
            'baseUrl'  => '@web',
            'language' => 'en-US',
        ])
    ]);

    // Run the install migration
    echo '*** installing Craft' . PHP_EOL;
    $migrator = \Craft::$app->getMigrator();

    try {
        $migrator->migrateUp($migration);
    } catch (\yii\base\Exception $e) {
        die("Failed to install Craft" . PHP_EOL);
    }


    echo "Installed Craft successfully" . PHP_EOL;

    // Mark all existing migrations as applied
    foreach ($migrator->getNewMigrations() as $name) {
        $migrator->addMigrationHistory($name);
    }

    return install_copy();
}

function install_copy()
{
    if (\Craft::$app->plugins->installPlugin('copy')) {
        echo "Copy plugin installed successfully." . PHP_EOL;
        return 0;
    }

    echo "Failed to install Copy plugin." . PHP_EOL;
    return 1;

}




