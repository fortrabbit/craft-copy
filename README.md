
# Craft Copy Plugin (RC8)

This little command line tool helps to speed up common tasks around Craft CMS deployment on [fortrabbit](https://www.fortrabbit.com/). Craft Copy syncs your local development environment with your fortrabbit App — up and down. It conveniently deploys deploys code changes and synchronizes latest images and database entries. This Craft CMS plugin will be installed locally and on the fortrabbit App.

## Demos

![demo](https://github.com/fortrabbit/craft-copy/blob/master/resources/craft-copy-db-up.gif "Database sync")

![demo](https://github.com/fortrabbit/craft-copy/blob/master/resources/craft-copy-code-up.gif "Code sync")

## Workflow

### Initial development

We assume that your are having a local development environment, where Craft CMS websites are developed mainly. Especially when getting started with a fresh project, **local is the master**. 

### Go live

Craft Copy comes in when you want to deploy your application to an App at fortrabbit. Initially,  when going live, or handing off the project to a content editor or showcasing it to a client. Craft Copy will help to deploy the application easily. 

### Maintain

Craft Copy is especially useful when your website project is evolving. Say your client is updating contents on the live application while you are tweaking design or working on a new feature. Craft Copy helps you merging the latest contents into your local development environment.

## How it works

With fortrabbit you can already use Git to deploy code, including Composer. Craft Copy enhances on that by adding support for assets and database contents. There are four data types: 

1. **Template code** — configuration, templates and other logic you are writing is under version control and deployed via **Git**
2. **Dependencies** — The Craft CMS core code and all plugins (including this one) are in the vendor folder, managed by **Composer (via Git)**
3. **Assets** — Images and other media uploaded via the Craft Control Panel in the assets folder are excluded from Git and **rsynced** (Uni Apps only)
4. **Database** — MySQL contents are dumped, downloaded and imported with **mysqldump**

## Requirements

You'll need a local development environment (macOS or Linux) including:

* Craft 3+
* PHP 7.1+
* Composer
* Executable binaries: `php`, `mysqldump`, `git` and `rsync`
* SSH key installed with fortrabbit (no password auth so far)

And of course you will need an App with fortrabbit. Craft Copy works for Universal Apps and Professional Apps. Asset synchronisation is only available for Universal Apps with local asset volumes.

## Installation

Craft Copy is available in the [Craft CMS plugin store](https://plugins.craftcms.com/copy). Best install Craft Copy **locally** in the terminal with Composer like so:

```shell
# Jump into your local Craft CMS folder
cd your/craft-project

# Require Craft Copy via Composer
composer config platform --unset
composer require fortrabbit/craft-copy:^1.0.0-RC8

# Install the plugin with Craft CMS
php craft install/plugin copy

# Initialize the setup
php craft copy/setup
```

You will be guided through a form to connect your local App with the App on fortrabbit.

## Usage

### Getting started

```shell
# Get help
php craft help copy

# Tell the plugin which fortrabbit App to use
php craft copy/setup

# Environment checks
php craft copy
php craft copy/info
```

### Database

```shell
# Dump local DB (backup remote db) and import it on remote
php craft copy/db/up

# Dump local DB and import it on remote (useful if the remote db is broken)
php craft copy/db/up --force

# Dump remote DB and import it locally
php craft copy/db/down

# Export DB
php craft copy/db/to-file {file}

# Import DB
php craft copy/db/from-file {file}
```

### Code

```shell
# Git push
php craft copy/code/up

# Git pull 
php craft copy/code/down
```

### Assets

```shell
# Rsync local assets with remote
php craft copy/assets/up {config} {?assetDir}

# Rsync remote assets with local
php craft copy/assets/down {config} {?assetDir}
```

* {assetDir} defaults to `web/assets`
* No remote volumes (S3, Object Storage, ..) so far.



## Multi Staging

At fortrabbit your set up multiple Apps to create multiple environments for your project. 

### Config

Once your Apps are in place, you connect your local environment with each App.

```
# Run this command to setup a new deployment configuration
php craft copy/setup
```

The setup command creates a config files the Craft `/config` folder. You can modify and share them across your team.

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

## Run scripts before/after commands

Supported commands:

* code/up
* code/down
* db/up
* db/down
* assets/up
* assets/down

Here you can find some use cases: [config/fortrabbit.example-config.yaml](https://github.com/fortrabbit/craft-copy/blob/master/src/fortrabbit.example-config.yaml)

## Troubleshooting

The setup is usually straight forward when the [system requirements](#requirements) are fulfilled. However, depending on your local setup, you may run into errors. May errors are MAMP related and easy to fix:

### Local MySQL connection error

```
php craft install/plugin copy
  *** installing copy
  *** failed to install copy: Craft CMS can’t connect to the database with the credentials in config/db.php.
```
**Fix:** Ensure "[Allow network access to MySQL](https://craftcms.stackexchange.com/a/26396/4538)" is ticked in MAMP.

### The mysqldump command does not exist

Find out if you can access mysqldump:
```
which mysqldump
  mysqldump not found
```

**Fix:** Add the MAMP bin path to your Bash profile
```
echo 'export PATH=/Applications/MAMP/Library/bin:$PATH' >>~/.bash_profile
```

### PHP cli version is lower than 7.1

Find out the php version on the command line:
```
php -v
  PHP 7.0.8 (cli) (built: Jun 26 2016 12:30:44) ( NTS )
  Copyright (c) 1997-2016 The PHP Group
  Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies
     with Zend OPcache v7.0.8, Copyright (c) 1999-2016, by Zend Technologies
```

**Fix:** Add MAMP php bin path to your Bash profile
```
echo 'export PATH=/Applications/MAMP/bin/php/php7.2.1/bin:$PATH' >>~/.bash_profile
```

### Composer version conflict

When installing the plugin via composer you may see an error like this:
```
composer require fortrabbit/craft-copy:^1.0.0-RC5
  ./composer.json has been updated
  Loading composer repositories with package information
  Updating dependencies (including require-dev)
  Your requirements could not be resolved to an installable set of packages.
  
  Problem 1
  - Installation request for fortrabbit/craft-copy ^1.0.0-RC5 -> satisfiable by fortrabbit/craft-copy[1.0.0-RC5].
  - Conclusion: remove symfony/console v3.3.6
  - Conclusion: don't install symfony/console v3.3.6
  - fortrabbit/craft-copy 1.0.0-RC5 requires symfony/yaml ^4.1
  [...]
   Problem 99
```

**Fix:** Update all existing dependencies
```
composer config platform --unset
composer update
php craft migrate/all
```
