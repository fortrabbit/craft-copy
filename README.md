# Craft deploy commands for fortrabbit

This little command line tool makes your life even better. It helps to speed up common tasks around professional Craft CMS deployment on fortrabbit: Dumb the database, Sync the assets folder, push and pull changes.

## Requirements

* MacOS or Linux (no Windows support so far) 
* Craft 3
* PHP 7.1
* Composer installed
* Executable binaries: `php`, `mysqldump`, `git` and `rsync` installed locally
* SSH key installed with fortrabbit (no password auth so far)

## Demo setup

![demo](https://github.com/fortrabbit/craft-copy/blob/master/demo_setup.gif "Demo")


## Installation

```console
cd your/craft-project

composer require fortrabbit/craft-copy

./craft plugin/install copy
./craft copy/setup
```


## Usage 

**Getting started**
```console
# Get help
./craft help copy

# Tell the plugin with fortrabbit App to use
./craft copy/setup

# Various system checks
./craft copy/info
```

**Database**
```console
# Dump local DB and import it on remote 
./craft copy/db/up

# Dump remote DB and import it locally 
./craft copy/db/down

# Export db 
./craft copy/db/to-file {file}

# Import db 
./craft copy/db/from-file {file}
```

**Assets**
```console
# Rsync local assets with remote 
./craft copy/assets/up {?assetDir}

# Rsync remote assets with local
./craft copy/db/down {?assetDir}
```

**Code**
```console
# Git push
./craft copy/code/up

# Git pull
./craft copy/code/down
```
