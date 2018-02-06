# Craft deploy commands for fortrabbit

...

## Requirements

* Craft 3
* Executable binaries: `php`, `mysqldump` and `rsync` installed locally
* SSH key installed with fortrabbit (no password auth so far)
* MacOS or Linux (no Windows support so far) 

## Installation

```
cd your/craft-project

composer require fortrabbit/craft-sync

./craft plugin/install sync
./craft sync/setup
```


## Usage 
```
# Get help
./craft help sync

# Tell the plugin with fortrabbit App to use
./craft sync/setup

# Dump local DB and import it on remote 
./craft sync/db/up --force

# Dump remote DB and import it locally 
./craft sync/db/down --force

# Rsync local assets with remote 
./craft sync/assets/up --force

# Rsync remote assets with local
./craft sync/db/down --force


```
