#  Changelog

## 2.4.4 - 2023-10-27
- Housekeeping

## 2.4.3 - 2023-10-13
- Show git/composer output when using `copy/code/up`

## 2.4.2 - 2023-07-10
- SSH check fails when no keys are SSH configured
- `--interactive=0` works with all/up and all/down commands

## 2.4.1 - 2023-04-19
- Add support for no existing repo with new git client

## 2.4.0 - 2023-04-17
- Replace `cpliakas/git-wrapper` with `gitonomy/gitlib`
- Allow customising commit message made in `code/up` command

## 2.3.2 - 2023-04-05
- Revert mysql command line tweaks

## 2.3.1 - 2023-04-03
- More tweaks on the mysql command line options

## 2.3.0 - 2023-02-06
- use `--defaults-extra-file` to support custom .my.cnf
- ignore `project.yaml` in production to prevent comparison of external and internal Project Config 
- show warning and stop console commands when using in production

## 2.2.0 - 2022-11-08
- show warning if composer plugins are not allowed
- remove project config after applying (via `fortrabbit/craft-auto-migrate`)

## 2.1.1 - 2022-06-16
- add /app as git safe directory, [see pull request](https://github.com/fortrabbit/craft-copy/pull/138)
- build docker image locally, [see pull request](https://github.com/fortrabbit/craft-copy/pull/139)

## 2.1.0 - 2022-05-25
- Adjust ENV vars
- Remove info action

## 2.0.0 - 2022-05-23
- Craft 4 support
- No further changes, but a stable release

## 2.0.0-beta.1 - 2022-03-30
- Craft 4 support

## 1.2.4 - 2022-06-15
- build docker image locally, [see pull request](https://github.com/fortrabbit/craft-copy/pull/139)

## 1.2.3 - 2022-06-14
- correct Github docker image builds

## 1.2.2 - 2022-06-14
- add /app as git safe directory, [see pull request](https://github.com/fortrabbit/craft-copy/pull/138)

## 1.2.1 - 2022-03-07
- Fixed nitro support 
- Added `copy/nitro/debug commmand` for testing ssh

## 1.1.1 - 2022-02-07
- Increased timeout (300s) for before/after deploy scripts eventually

## 1.1.0 - 2022-01-18
- Added support for Craft Nitro [see README.md](https://github.com/fortrabbit/craft-copy#craft-nitro-support)
- Dropped PHP 7.2 support

## 1.0.8 - 2022-01-04
- Support dotenv version 5

## 1.0.7 - 2021-09-24
- Increased timeout for import script to 1000 seconds

## 1.0.6 - 2021-09-24
- Exclude `resourcepaths` table from db backup
- Ignore mysql import errors 🤞🏻
- Better error output

## 1.0.5 - 2021-05-12
- Update craft-auto-migrate to use `project-config/apply`
- Code style cleanup 

## 1.0.4 - 2021-04-08
- Don't throw exception if no local volumes exist
- Remove DB_TABLE_PREFIX check 

## 1.0.3 - 2021-01-11
- Fixed type issue with symfony/process 5.x (use `Process::fromShellCommandline()`)

## 1.0.2 - 2021-01-05
- Fixed type issue with symfony/process 5.x (use `Process::fromShellCommandline()`)
- Fixed code styles using https://github.com/symplify/easy-coding-standard
- Fixed ENV vars check using non-strict comparison

## 1.0.1 - 2020-11-10
- Fixed a bug in the craft copy/all/up command.

## 1.0.0 - 2020-11-02
- Added `copy/volumes/up` command
- Added `copy/volumes/down` command
- Added `copy/folder/up` command
- Added `copy/folder/down` command
- Removed `copy/assets/up` command
- Removed `copy/assets/down` command

## 1.0.0-RC12 - 2020-09-08
- Updated various dependencies


## 1.0.0-RC11 - 2020-05-12
- Updated various dependencies
- Fixed a bug when Craft expects `CRAFT_ENVIRONMENT` 

## 1.0.0-RC10 - 2020-02-19
- Craft 3.4 fix for new config/db.php structure
- Exclude `assettransformindex` table from irgnoredTables
- Make no assumptions about the `assets` directory

## 1.0.0-RC9 - 2019-12-04
- Fixed a bug where a .gitignore file was written instead of .my.cnf

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
