# Craft Copy Plugin (1.1)

This little command line tool helps to speed up common tasks around Craft CMS deployment on [fortrabbit](https://www.fortrabbit.com/). Craft Copy syncs your local development environment with your fortrabbit App — up and down. It conveniently deploys code changes and synchronizes latest images and database entries. This Craft CMS plugin will be installed locally and on the fortrabbit App.


## Demos

![demo](https://github.com/fortrabbit/craft-copy/blob/master/resources/craft-copy-db-up.gif "Database sync")

![demo](https://github.com/fortrabbit/craft-copy/blob/master/resources/craft-copy-code-up.gif "Code sync")

[Here](https://www.youtube.com/watch?v=h8g5t-X6ya4) is a video introdcution (1.5 minutes).


## Requirements

* A local development environment including: Craft 3+, PHP 7.3+ and Composer. 
* The following binaries should be executable: `php`, `mysqldump`, `git` and `rsync`
* A SSH key installed should with your fortrabbit Account (no password auth so far)
* You need to have an App with fortrabbit

Craft Copy works for Universal Apps and Professional Apps. Asset synchronisation is only available for Universal Apps with local asset volumes.


## Installation

Best install Craft Copy in the terminal on your local computer with Composer like so:

```shell
# Jump into your local Craft CMS folder
cd your/craft-project

# Require Craft Copy via Composer
composer config platform --unset
composer require fortrabbit/craft-copy -W

# With the latest version of composer (2.2 or higher) you may see this prompt: 
# Do you trust "fortrabbit/craft-auto-migrate" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?] 
#
# Confirm with `y`

# Install and enable the plugin with Craft CMS
php craft plugin/install copy
```

You will be guided through a form to connect your local App with the App on fortrabbit. Craft Copy is available in the [Craft CMS plugin store](https://plugins.craftcms.com/copy).


## Usage

Craft Copy will always be executed on your local computer in your local development environment.

The optional `{stage}` parameter defines which fortrabbit App the command applies to. It is useful when working with multiple fortrabbit Apps as stages. The default parameter is the app name. See multi-staging section below for more.

Some commands are interactive, meaning that you will need to confirm certain potential dangerous operations. You can disable such manual interactions by adding the `--interactive=0` flag in most cases. The short alias version is `-i=0`.


### Setup

```shell
# Connect your development environment with your fortrabbit App
php craft copy/setup
```


#### Config file

The setup command creates a configuration file within the Craft `/config` folder. The file name pattern is `fortrabbit.{stage}.yml`, where `{stage}` is how you have defined the fortrabbit App environment in the [setup](#setup). The default for `{stage}` is the App name, commonly this can also be `production`. The file is version controlled and can be shared with the team and includes all basic settings, plus some extras, like [before/after](#beforeafter-commands) commands. When using [multi-staging](#multi-staging) a configuration file will be created for each fortrabbit App. Also see the [example file](https://github.com/fortrabbit/craft-copy/blob/master/src/fortrabbit.example.yaml).


### Get help

```shell
# See a list of available commands
php craft help copy

# Run environment checks
php craft copy
```


### Database

```shell
# Dump local DB (backup fortrabbit db) and import it to the fortrabbit App
php craft copy/db/up {stage}

# Dump local DB and import it to the fortrabbit App (useful if the fortrabbit db is broken)
php craft copy/db/up {stage}  --force

# Dump fortrabbit DB and import it locally
php craft copy/db/down {stage}
```




### Code

While you can use `git push` as well to deploy code by Git to your fortrabbit App, the Craft Copy code commands offer some additional extras: It will check for uncommitted changes and initialize the Git repo if required.

```shell
# Push code changes to the fortrabbit App
php craft copy/code/up {stage}

# Pull code changes from your fortrabbit App
php craft copy/code/down {stage}
```


### Asset Volumes

Assets in Craft CMS are media files that are uploaded by editors and managed by the CMS. Assets are getting stored in volumes and are not part of Git.

```shell
# Sync up a local volume to fortrabbit App
php craft copy/volumes/up {stage} {?volumeHandle}

# Sync down a vlume from the fortrabbit App to local
php craft copy/volumes/down {stage} {?volumeHandle}
```

* The "File System Path" for the Volume setting within the Craft CMS control panel should not be a relative path. You can use the `@webroot` alias.
* Remote volumes (S3, Object Storage …) are not supported so far


#### Options and arguments

* {volumeHandle} is the handle name of your asset volume in Craft CMS
* To sync all volumes, don't provide a volumeHandle (and add `-i=0` to avoid questions)
* To test what will actually be synced you can add the `-n` option to trigger a dry-run



### Folders

You can also synchronize folders which are not in Git or not an Asset Volume. A common use case is to sync up build artifacts such as minified JS or CSS to your fortrabbit App. This can be coupled with [before/after commands](#beforeafter-commands).

```shell
# Sync up a folder from local to your fortrabbit App
php craft copy/folder/up {stage} {folder}

# Example usage
php craft copy/folder/up production web/build
```

* The `{folder}` is your relative path seen from the craft executable (project root)


### Copy all

Often you want to get all the latest content from the App or maybe even push all local changes up to the App. You can use the all command for that:

```shell
# Sync database, volumes and git from local to your fortrabbit App
php craft copy/all/up

# Sync database, volumes and git from your fortrabbit App to local
php craft copy/all/down
```

* This is not including the folder action by default.


## Advanced usage

Don't stop. Read on to fully understand Craft Copy!


### Automatic migrations

Craft Copy incorporates another package called [Craft auto migrate](https://github.com/fortrabbit/craft-auto-migrate). It makes sure that database migrations will always run when deploying to the fortrabbit App. That means that every time you'll push code via Git, a database migration will be triggered and changes from `project.yml` will be applied right away, without the need to click the apply changes button with the Control Panel. 

The other way around, when pulling down changes, a database migration will also run. This is configured via a default [after command](#beforeafter-commands) in the [config file](#config-file).


### Multi staging

At fortrabbit you can set up multiple Apps to create multiple environments for your project. See the [multi-staging help article](https://help.fortrabbit.com/multi-staging).


#### Multi staging config

Once your Apps are in place, you connect your local environment with each App.

```shell
# Run this command to setup a new deployment configuration for each stage
php craft copy/setup
```

The setup command creates a [config file](#config-file) for each App.


#### Multi staging usage

```shell
# Copy code and db down from 'production'
php craft copy/code/down production
php craft copy/db/down production

# Make changes
# ...

# Copy code and db up to 'staging'
php craft copy/code/up staging
php craft copy/db/up staging
```

### Before/after commands

You can run any script before or after you run common up/down commands with Craft Copy.

* Place the before/after scripts in your [Craft Copy config file](#config-file). 
* The before/after commands will run on your local machine, not on the fortrabbit App. To run scripts while deploying, consider the Composer `post-install-cmd`.


#### Supported commands

* code/up
* code/down
* db/up
* db/down
* volumes/up
* volumes/down
* folder/up
* folder/down


#### Before/after example

Automate your deployment pipeline. Every time you push up new code with `code/up` also minify javascript and css and sync it up as well:

```yml
before:
  # Before deploying code by Git, please:
  code/up:
    # 1. Run your NPM production build
    - "npm run prod"
    # 2. Sync the results of the build up
    - "php craft copy/folder/up production web/build/prod -i=0"
```

Here is a full config file example: [config/fortrabbit.example.yaml](https://github.com/fortrabbit/craft-copy/blob/master/src/fortrabbit.example.yaml)


### Database to file

There is also a command to create a local copy of your database to a file. You can create an `.sql` file and also import back such file into the database. Here is the command:


```shell
# Export DB
php craft copy/db/to-file filename

# Import DB
php craft copy/db/from-file filename
# Filename is a required parameter
```

Note that there are also similar Craft CLI commands for this: `php craft backup/db` and `php craft restore/db`.


### Using Craft Copy in Docker environments

You may want to run Craft Copy with in a Docker container. You will need the following installed inside the container: 

 - mysqldump
 - mysql (client)
 - ssh (client)
 - Access to the SSH keys you saved with your fortrabbit Account - for example like [so](https://medium.com/trabe/use-your-local-ssh-keys-inside-a-docker-container-ea1d117515dc)



## How it works

With fortrabbit you can already use Git to deploy code without any extras or plugins. When deploying code by Git Composer also is getting triggered. Craft Copy enhances on that by adding support for files that are excluded from Git such as assets in volumes, folders and database contents. 

Craft Copy can help to bring together the different data types required to run Craft CMS. Each data type is unique, so is the transport layer. Here are more details so you can better understand what's going on behind the scenes:



### Template and dependencies code via Git

Craft Copy offers a light weight Git wrapper with auto-suggestions for Composer updates and other candy. This is the most optional part of Craft Copy. The direction will be in most case up only (push code), since you will develop locally first and then push changes up to the fortrabbit App. Since Git is transport layer and version history, those changes are non-destructive. You can always roll back.

The `composer.json` is also managed in Git and when you push a change to that file, Composer will run during deployment on fortrabbit. That's not Craft Copy but a fortrabbit feature. So you don't need to login by SSH and run Composer manually. Also you should not trigger any updates with the Craft CMS Control Panel on the fortrabbit App itself.


### Asset Volumes

Any asset files and folders, including image transformations that can be synced up and down with the volumes command. Here rsync will be used. The transport flags are set to be non-destructive. No files will be deleted and you can safely run this operation in any direction without having to fear any data loss. You might need to keep your assets library clean from time to time. 


### Database

The MySQL database is getting copied over by using `mysqldump`. So it basically will export the current status of the database as an `file.sql` and will replace the other database with that file. In other words: This can be a destructive operation. You need to make sure that any content changes affecting the database, like new entries or editing entries are only done in one environment, either locally or on the fortrabbit App. It can not merge changes, like with assets or code. Good news is, that Craft Copy will always create a snapshot file which you can use to roll back.


#### my.conf file

Craft Copy creates a `my.conf` file. It sets some defaults to ensure maximal compability when working with different MySQL versions. See the [annotated file here](https://github.com/fortrabbit/craft-copy/blob/master/src/.my.cnf.example) and read about SUPER priviliges [here](https://help.fortrabbit.test/mysql-troubleshooting#toc-access-denied-missing-super-privileges).

### Craft Nitro support

Craft Copy supports Nitro development environments on Mac and Linux hosts, but an additional setup step is needed, along with a small change to your workflow when running Craft Copy commands. This is because Nitro containers lack the dependencies Craft Copy requires in order to transfer files/data between stages, and don't mount your SSH keys from your host machine (required to connect to your fortrabbit app).

#### Enabling Nitro support

**1. Install the Craft Copy plugin in your Nitro app as normal:**

```bash
nitro composer config platform --unset
nitro composer require fortrabbit/craft-copy -W
nitro craft plugin/install copy
```

**2. Generate the wrapper script**

```bash
nitro craft copy/nitro/setup
```

This will create a new shell script in your project root called `nitro-craft`. This works in essentially the same way as `nitro craft`, but runs in a Docker container that both adds the required dependencies and forwards your host's ssh-agent so that it is available to make git/rsync etc work.

You should check this file into version control. You will need to regenerate the script (by running `nitro craft copy/nitro/setup` again) if you change the PHP version you are using in Nitro for this site. This is because Nitro uses different containers for different versions of PHP, and Craft Copy needs to use the same container name for everything to work.

#### Running Craft Copy commands under Nitro

Just use `./nitro-craft` instead of `nitro craft`. Example:


```
# Without Nitro
nitro craft copy/info
# With Nitro
./nitro-craft copy/info
```

**Note** All `nitro craft` commands should work when run through `./nitro-craft`, not just Craft Copy commands.

#### Platform support

Craft Copy works with Craft Nitro 2.x under Mac and Linux.

Windows support is untested at the moment (sorry!). It _should probably_ work under WSL but ssh-agent forwarding may be buggy. PR's welcome!

## Troubleshooting

The setup is usually straight forward when the [system requirements](#requirements) are fulfilled. However, depending on your local setup, you may run into errors. Many errors are MAMP related and easy to fix:


### Local MySQL connection error

```shell
php craft install/plugin copy
  *** installing copy
  *** failed to install copy: Craft CMS can’t connect to the database with the credentials in config/db.php.
```

**Fix:** Ensure "[Allow network access to MySQL](https://craftcms.stackexchange.com/a/26396/4538)" is ticked in MAMP.


### The mysqldump command does not exist

The `mysqldump` client is a command line program to backup mysql databases. It is usually included with MySQL installations. Find out if you can access mysqldump:

```shell
which mysqldump
  mysqldump not found
```

**Possible fix:** Add the MAMP bin path to your Bash profile:

```shell
echo 'export PATH=/Applications/MAMP/Library/bin:$PATH' >>~/.bash_profile
```


### PHP cli version is lower than 7.1

Find out the php version on the command line:

```shell
php -v
  PHP 7.0.8 (cli) (built: Jun 26 2016 12:30:44) ( NTS )
  Copyright (c) 1997-2016 The PHP Group
  Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies
     with Zend OPcache v7.0.8, Copyright (c) 1999-2016, by Zend Technologies
```

**Fix:** Add MAMP php bin path to your Bash profile:

```shell
echo 'export PATH=/Applications/MAMP/bin/php/php7.2.1/bin:$PATH' >>~/.bash_profile
```


### Composer version conflict

When installing the plugin via Composer you may see an error like this:

```shell
composer require fortrabbit/craft-copy:^1.0.0
  ./composer.json has been updated
  Loading composer repositories with package information
  Updating dependencies (including require-dev)
  Your requirements could not be resolved to an installable set of packages.
  
  Problem 1
  - Installation request for fortrabbit/craft-copy ^1.0.0 -> satisfiable by fortrabbit/craft-copy[1.0.0].
  - Conclusion: remove symfony/console v3.3.6
  - Conclusion: don't install symfony/console v3.3.6
  - fortrabbit/craft-copy 1.0.0 requires symfony/yaml ^4.1
  [...]
   Problem 99
```

**Fix:** Update all existing dependencies:

```shell
composer config platform --unset
composer update
php craft migrate/all
```
