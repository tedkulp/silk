#!/bin/sh

echo 'Setting up typical Silk Framework application directory structure and proper permissions...';
mkdir -p tmp
mkdir -p tmp/templates_c
mkdir -p tmp/cache
mkdir -p tmp/configs
mkdir -p tmp/templates

chmod -R 777 tmp

mkdir -p config
mkdir -p components
mkdir -p components/app
mkdir -p components/app/models
mkdir -p components/app/controllers
mkdir -p components/app/views
mkdir -p layouts

mkdir -p log

chmod -R 777 log

cp lib/silk/index.php .
cp lib/silk/.htaccess .



# keep a single backup of old config if it happens to already exist
if [ -f config/setup.yml ]; then
	# check if the files differ
	cmp -s lib/silk/config/setup.yml config/setup.yml;
	if [ $? == 1 && $doBackup]; then
	
		echo -e "\nThe file config/setup.yml exists and has changed. Do you want to backup this file before replacing? Y/N"
		read backup_setup;
		case $backup_setup in
		[yY]*)
			mv config/setup.yml config/setup.yml.backup;
			echo 'File config/setup.yml copied to setup.yml.backup'; 
			echo 'File default setup.yml written to config/setup.yml.';
			echo 'You might want to "diff config/setup.yml config/setup.yml.backup" before reverting to your old setup.'; 
			break;;
		*)
			echo 'File config/setup.yml overwritten. Old setup.yml not backed up.';
			break;;
		esac
	fi
fi

cp lib/silk/config/setup.yml config/;
cp lib/silk/config/routes.php config/
echo -e "\nDONE";
exit 0;
