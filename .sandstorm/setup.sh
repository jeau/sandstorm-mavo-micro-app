#!/bin/bash

# When you change this file, you must take manual action. Read this doc:
# - https://docs.sandstorm.io/en/latest/vagrant-spk/customizing/#setupsh

set -euo pipefail

# The version of golang in the main repo is *ancient* (1.3.x); let's get
# ourselves a newer version:

echo 'deb http://httpredir.debian.org/debian/ stretch-backports main' >> \
	/etc/apt/sources.list.d/backports.list
apt-get update
apt-get -t stretch-backports install -y golang

# Needed for fetching most dependencies:
apt-get install -y git rsync

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
fi

# Second, compile the small C++ program within
# /opt/app/sandstorm-integration.
if [ ! -e /opt/app/sandstorm-integration/getPublicId ] ; then
    pushd /opt/app/sandstorm-integration
    make
fi
### All done.

ls /opt/app/sandstorm-integration/

apt-get clean

exit 0
