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

composer require fortrabbit/craft-copy:^1.0.0-RC1

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
./craft copy/assets/up {config} {assetDir}

# Rsync remote assets with local
./craft copy/assets/down {config} {assetDir}
```

No remote volumes (S3, Object Storage, ..) so far.

### Code

```shell
# Git push
./craft copy/code/up

# Git pull (current remote)
./craft copy/code/down

# Git pull (specific remote)
./craft copy/code/down
```

## Multi Staging

At fortrabbit your set up multiple Apps to create multiple environments for your project. 

### Config

Once your Apps are in place, you connect your local environment with each App.

```
# Run this command to setup a new deployment configuration
./craft copy setup
```

### Usage

```sh
# Copy code and db down from 'production'
php craft copy/code/down production
php craft copy/db/down production

# Make changes
# ...

# Copy code and db up to 'staging'
php craft copy/code/up staging
php craft copy/db/up staging
```

### Run scripts before/after commands

Supported commands:

* code/up
* code/down
* db/up
* db/down
* assets/up
* assets/down

Here you can find some use cases: [config/fortrabbit.example-config.yaml](https://github.com/fortrabbit/craft-copy/blob/master/src/fortrabbit.example-config.yaml)

