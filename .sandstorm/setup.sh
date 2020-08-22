#!/bin/bash

# When you change this file, you must take manual action. Read this doc:
# - https://docs.sandstorm.io/en/latest/vagrant-spk/customizing/#setupsh

set -euo pipefail

# Needed for fetching most dependencies:
apt-get install -y git rsync golang

apt-get clean

exit 0
