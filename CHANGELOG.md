#  Changelog

## 1.0.0-RC8 - 2019-11-15
- More verbose SSH errors
- Default .my.cnf to prevent GTID_PURGED errors

## 1.0.0-RC5 - 2019-02-01
- Fixed: use the correct environment name for config files
- The non-existing `/storage` folder is created on the remote

## 1.0.0-RC4 - 2019-02-01
- `copy/setup` is more resilient 
- `copy/db/up --force` does not require the plugin to be enabled on the remote 
- `copy/code/up` shows recent commits 

## 1.0.0-RC3 - 2019-01-31
- changed wordings
- changed setup flow
- better Craft 3.1 project config support

## 1.0.0-RC2 - 2018-11-26

- Run migrations automatically via `fortrabbit/craft-auto-migrate`
- Add configured git upstream automatically

## 1.0.0-RC1 - 2018-11-16

- Removed support for config/copy.php
- Removed `--app` option
- Removed `--env` option
- Changed signature of most commands, first argument is `{config}`
- Added YAML Config
  - Support for `before` and `after` scripts
  - Support for custom `ssh_url`


## 1.0.0-beta5 - 2018-10-18

- increased ssh timeout to 1200 seconds
- verbose mysql import errors

## 1.0.0-beta4 - 2018-08-27

- multi-staging support for `copy/info`
- multi-staging help in README.md

## 1.0.0-beta3 - 2018-08-21

- code clean up, thanks @XhmikosR
- configurable ssh upload & download commands
- support for multi-staging, see src/config.example.php

## 1.0.0-beta2 - 2018-05-29

- Fixed broken dependency: ostark/yii2-artisan-bridge


## 1.0.0-beta1 - 2018-05-29

Initial (beta) release. Supported commands:

- `php craft copy`                 Environment check
- `php craft copy/setup`           Setup
- `php craft copy/assets/down`     Sync assets down
- `php craft copy/assets/up`       Sync assets up
- `php craft copy/code/down`       Git pull
- `php craft copy/code/up`         Git push
- `php craft copy/db/down`         Sync database down
- `php craft copy/db/up`           Sync database up
- `php craft copy/db/from-file`    Import database
- `php craft copy/db/to-file`      Export database
