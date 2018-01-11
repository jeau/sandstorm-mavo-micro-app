#!/bin/bash

# When you change this file, you must take manual action. Read this doc:
# - https://docs.sandstorm.io/en/latest/vagrant-spk/customizing/#setupsh

set -euo pipefail
# This is the ideal place to do things like:
#
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y -q php5-cli rsync git clang autoconf pkg-config libtool 

#
# If the packages you're installing here need some configuration adjustments,
# this is also a good place to do that:
#
#sed --in-place='' \
#            --expression 's/^user www-data/#user www-data/' \
#            --expression 's#^pid /run/nginx.pid#pid /var/run/nginx.pid#' \
#            --expression 's/^\s*error_log.*/error_log stderr;/' \
#            --expression 's/^\s*access_log.*/access_log off;/' \
#            /etc/nginx/nginx.conf

# By default, this script does nothing.  You'll have to modify it as
# appropriate for your application.

### Download & compile capnproto and the Sandstorm getPublicId helper.

# First, get capnproto from master and install it to
# /usr/local/bin. This requires a C++ compiler. We opt for clang
# because that's what Sandstorm is typically compiled with.
if [ ! -e /usr/local/bin/capnp ] ; then
    cd /tmp
    if [ ! -e capnproto ]; then git clone https://github.com/sandstorm-io/capnproto; fi
    cd capnproto
    git checkout v0.6.1 
    cd c++
    autoreconf -i
    ./configure
    make -j2
    sudo make install
    cd ..
    rm -Rf capnproto
fi

# Second, compile the small C++ program within
# /opt/app/sandstorm-integration.
if [ ! -e /opt/app/sandstorm-integration/getPublicId ] ; then
    pushd /opt/app/sandstorm-integration
    make
    make clean
fi
### All done.

ls /opt/app/sandstorm-integration/
apt-get autoremove
apt-get clean

exit 0
