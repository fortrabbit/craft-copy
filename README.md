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

composer require fortrabbit/craft-copy

./craft plugin/install copy
./craft sync/setup
```


## Usage 
```
# Get help
./craft help copy

# Tell the plugin with fortrabbit App to use
./craft copy/setup

# Dump local DB and import it on remote 
./craft copy/db/up --force

# Dump remote DB and import it locally 
./craft copy/db/down --force

# Export db 
./craft copy/db/to-file

# Import db 
./craft copy/db/from-file

# Rsync local assets with remote 
./craft copy/assets/up --force

# Rsync remote assets with local
./craft copy/db/down --force


```
