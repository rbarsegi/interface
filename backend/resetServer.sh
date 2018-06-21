#!/bin/bash
source "/var/www/html/backend/configfile"
shopt -s extglob
cd "$datapath"
sudo rm -rf !("$conffile")
shopt -u extglob
