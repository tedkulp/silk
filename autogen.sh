#!/bin/sh

mkdir tmp
mkdir tmp/templates_c
mkdir tmp/cache
mkdir tmp/configs
mkdir tmp/templates

chmod -R 755 tmp

mkdir app
mkdir app/models
mkdir app/controllers
mkdir app/views

mkdir log

chmod -R 755 log

cp lib/silk/index.php .

cp lib/silk/config/routes.php config/
cp lib/silk/config/setup.yml config/
