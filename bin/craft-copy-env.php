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

// dotenv? 5.x vs 3.x vs 2.x
if (file_exists($root . '/.env')) {
    if (method_exists('\Dotenv\Dotenv', 'createUnsafeImmutable')) {
        \Dotenv\Dotenv::createUnsafeImmutable($root)->safeLoad();
    } elseif (method_exists('\Dotenv\Dotenv', 'create')) {
        \Dotenv\Dotenv::create($root)->load();
    } else {
        (new \Dotenv\Dotenv($root))->load();
    }
}

// ENV basics
$env = [
    'ENVIRONMENT'     => getenv('ENVIRONMENT'),
    'SECURITY_KEY'    => getenv('SECURITY_KEY'),
    'DB_TABLE_PREFIX' => getenv('DB_TABLE_PREFIX'),
    'DB_SERVER'       => getenv('DB_SERVER'),
];

// S3
if (getenv('S3_SECRET')) {
    $env += [
        "S3_API_KEY" => getenv("S3_API_KEY"),
        "S3_SECRET"  => getenv("S3_SECRET"),
        "S3_BUCKET"  => getenv("S3_BUCKET"),
        "S3_REGION"  => getenv("S3_REGION")
    ];
}

// OBJECT_STORAGE
if (getenv('OBJECT_STORAGE_SECRET')) {
    $env += [
        "OBJECT_STORAGE_KEY"    => getenv("OBJECT_STORAGE_KEY"),
        "OBJECT_STORAGE_SECRET" => getenv("OBJECT_STORAGE_SECRET"),
        "OBJECT_STORAGE_BUCKET" => getenv("OBJECT_STORAGE_BUCKET"),
        "OBJECT_STORAGE_REGION" => getenv("OBJECT_STORAGE_REGION"),
        "OBJECT_STORAGE_HOST"   => getenv("OBJECT_STORAGE_HOST"),
        "OBJECT_STORAGE_SERVER" => getenv("OBJECT_STORAGE_SERVER")
    ];
}

echo json_encode($env, JSON_PRETTY_PRINT);
