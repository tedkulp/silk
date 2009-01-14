#!/bin/sh

mkdir tmp
mkdir tmp/templates_c
mkdir tmp/cache
mkdir tmp/configs
mkdir tmp/templates

chmod -R 755 tmp

mkdir components
mkdir components/app
mkdir components/app/models
mkdir components/app/controllers
mkdir components/app/views
mkdir layouts

mkdir log

chmod -R 755 log

cp lib/silk/index.php .

cp lib/silk/config/routes.php config/
cp lib/silk/config/setup.yml config/
