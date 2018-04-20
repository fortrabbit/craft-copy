# Craft deploy commands for fortrabbit

...

## Requirements

* Craft 3
* PHP 7.1
* Executable binaries: `php`, `mysqldump`, `git` and `rsync` installed locally
* SSH key installed with fortrabbit (no password auth so far)
* MacOS or Linux (no Windows support so far) 
* Craft and composer installed locally 

## Installation

```
cd your/craft-project

composer require fortrabbit/craft-copy

./craft plugin/install copy
./craft copy/setup
```


## Usage 

**Getting started**
```
# Get help
./craft help copy

# Tell the plugin with fortrabbit App to use
./craft copy/setup

# Various system checks
./craft copy/info
```

**Database**
```
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
```
# Rsync local assets with remote 
./craft copy/assets/up {?assetDir}

# Rsync remote assets with local
./craft copy/db/down {?assetDir}
```

**Code**
```
# Git push
./craft copy/code/up

# Git pull
./craft copy/code/down
```
