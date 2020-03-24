# Craft Copy Plugin (RC10)

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
composer require fortrabbit/craft-copy:^1.0.0-RC10

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


## Advanced usage

Don't stop. Read on to fully understand Craft Copy!


### Different types of copy for different types of data

As noted above, Craft Copy can help to bring together the different data types required to run Craft CMS. Each data type is unique, so is the transport layer. Here are more details so you can better understand what's going on behind the scenes.


#### Template and dependencies code via Git

Craft Copy offers a light weight Git wrapper with auto-suggestions for Composer updates and other candy. This is the most optionial part of Craft Copy. The direction will be in most case up only (push code), since you will develop locally first and then push changes up to the fortrabbit App. Since Git is transport layer and version history, those changes are non-destructive. You can always roll back.

The `composer.json` is also managed in Git and when you push a change to that file, Composer will run during deployment on fortrabbit. That's not Craft Copy but a fortrabbit feature. So you don't need to login by SSH and run Composer manually. Also you should not trigger any updates with the Craft CMS Control Panel on the fortrabbit App itself.


#### Assets

Any asset files and folders, including image transformations can be synced up and down with the assets command. Here rsync will be used. The transport flags are set to be non-destructive. No files will be deleted and you can safely run this operation in any directon without having to fear any data loss. You might need to keep your assset library clean from time to time. Take care to have your assets excluded from Git.


#### Database

The MySQL database is getting copied over by using `mysqldump`. So it basically will export the current status of the database as an `file.sql` and will replace the other database with that file. In other words: This can be a desctrutive operation. You need to make sure that any content changes affecting the database, like new entries or editing entries are only done in one enviornment, either locally or on the fortrabbit App. It can not merge changes, like with assets or code. Good news s, that Craft Copy will always create a snapshot file which you can use to roll back.


### Project config

Craft CMS is offering a `project.yml` which is a master to hold your configuraton data. We highly recommend to enable the Project config via `general.php`. That way you can completley separate the configuraton of the database from the database contents. In other words, you can make structural changes to the database structure locally, by adding new fields or modifying fields and still sync those changes up, even when database contents are updated on the App. The `project.yml` is controlled via Git and therfore will be pushed along with code updates via Git. 

Make sure that your local development enviornment stays the master for structural changes. A best practice is to disable admin changes on the App itself by setting `'allowAdminChanges' => false` for production in `general.php`. Also see the [Craft CMS help](https://docs.craftcms.com/v3/config/config-settings.html#allowadminchanges) on that setting.


### Automatic migrations

Craft Copy incorporates another plugin called [Craft auto migrate](https://github.com/fortrabbit/craft-auto-migrate). It makes sure that database migrations will always run when deploying. That means that every time you'll push code via Git a database migration will be triggered, so that changes from `project.yml` will always be applied right away, without the need to trigger them manually by clicking the apply changes button with the Control Panel.


### Multi staging

At fortrabbit your set up multiple Apps to create multiple environments for your project. 

#### Multi staging config

Once your Apps are in place, you connect your local environment with each App.

```
# Run this command to setup a new deployment configuration
php craft copy/setup
```

The setup command creates a config files the Craft `/config` folder. You can modify and share them across your team.

### Multi staging usage

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

#### Run scripts before/after commands

Supported commands:

* code/up
* code/down
* db/up
* db/down
* assets/up
* assets/down

Here you can find some use cases: [config/fortrabbit.example-config.yaml](https://github.com/fortrabbit/craft-copy/blob/master/src/fortrabbit.example-config.yaml)





## Quirks and known issues

### Asset folder location

As noted above, it is currently expected by deafult, that your file uploads are stored in a folder called `assets` within the folder `web`. That a common practice anyways. 

It is planned to change that in a future update.

### Image transforms are not copied

Currently, the image transforms table is not copied when copying the database. That means, that Craft CMS assumes that all images need to regenerated once you have imported the database. That will trigger imageMagick to squeeze your images again and can slow down the website for the first unlucky user. After all transforms have been re-run, the images are delivered quickly.

It is planned to change that in a future update.


## Troubleshooting

The setup is usually straight forward when the [system requirements](#requirements) are fulfilled. However, depending on your local setup, you may run into errors. Many errors are MAMP related and easy to fix:

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
