#!/bin/bash
set -euo pipefail

# Create a bunch of folders under the clean /var that php and nginx expect to exist
mkdir -p /var/lib/php5/sessions

# Wipe /var/run, since pidfiles and socket files from previous launches should go away
rm -rf /var/run
mkdir -p /var/run

# Mavo

mkdir -p /var/mavo
mkdir -p /var/www
mkdir -p /var/www/images

if [ ! -d "/var/mavo/content" ]; then
    rsync -r /opt/app/content/ /var/mavo/content
fi

if [ ! -L "/var/mavo/index.php" ]; then
    ln -s /opt/app/index.php /var/mavo/index.php
fi

if [ ! -L "/var/mavo/mavo-php.js" ]; then
    ln -s /opt/app/mavo-php.js /var/mavo/mavo-php.js
fi

if [ ! -L "/var/mavo/mavo-backend.php" ]; then
    ln -s /opt/app/mavo-backend.php /var/mavo/mavo-backend.php
fi

if [ ! -L "/var/mavo/publish.php" ]; then
    ln -s /opt/app/publish.php /var/mavo/publish.php
fi

if [ ! -L "/var/mavo/include" ]; then
    ln -s /opt/app/include /var/mavo/include
fi

if [ ! -L "/var/mavo/repo" ]; then
    ln -s /var/mavo/content/default /var/mavo/repo
fi

if [ ! -L "/var/mavo/images" ]; then
    ln -s /var/mavo/repo/images /var/mavo/images
fi

cd /var/mavo

#
#   * Start a process in the foreground listening on port 8000 for HTTP requests.

php -S 127.0.0.1:8000

exit 0
