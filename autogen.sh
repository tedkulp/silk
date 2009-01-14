#!/bin/sh

mkdir -p tmp
mkdir -p tmp/templates_c
mkdir -p tmp/cache
mkdir -p tmp/configs
mkdir -p tmp/templates

chmod -R 755 tmp

mkdir -p config
mkdir -p components
mkdir -p components/app
mkdir -p components/app/models
mkdir -p components/app/controllers
mkdir -p components/app/views
mkdir -p layouts

mkdir log

chmod -R 755 log

cp lib/silk/index.php .

cp lib/silk/config/routes.php config/
cp lib/silk/config/setup.yml config/
