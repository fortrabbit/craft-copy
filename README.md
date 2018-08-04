# Craft Copy Plugin (beta)

This little command line tool helps to speed up common tasks around Craft CMS deployment on [fortrabbit](https://www.fortrabbit.com/):

* dump the database,
* sync the assets folder,
* push and pull code changes.

## Requirements

* macOS or Linux (no Windows support so far)
* Craft 3
* PHP 7.1
* Composer installed
* Executable binaries: `php`, `mysqldump`, `git` and `rsync` installed locally
* SSH key installed with fortrabbit (no password auth so far)

## Demo setup

![demo](https://github.com/fortrabbit/craft-copy/blob/master/demo_setup.gif "Demo")


## Installation (locally)

```shell
cd your/craft-project

composer config platform --unset

composer require fortrabbit/craft-copy:^1.0.0-beta

./craft install/plugin copy
./craft copy/setup
```


## Usage

### Getting started

```shell
# Get help
./craft help copy

# Tell the plugin which fortrabbit App to use
./craft copy/setup

# Environment checks
./craft copy
./craft copy/info
```

### Database

```shell
# Dump local DB and import it on remote
./craft copy/db/up

# Dump remote DB and import it locally
./craft copy/db/down

# Export DB
./craft copy/db/to-file {file}

# Import DB
./craft copy/db/from-file {file}
```

### Assets

```shell
# Rsync local assets with remote
./craft copy/assets/up {?assetDir}

# Rsync remote assets with local
./craft copy/assets/down {?assetDir}
```

No remote volumes (S3, Object Storage, ..) so far.

### Code

```shell
# Git push
./craft copy/code/up

# Git pull (current remote)
./craft copy/code/down

# Git pull (specific remote)
./craft copy/code/down {remote} {branch}
```
