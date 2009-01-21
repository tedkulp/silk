#!/bin/sh

echo 'Setting up typical Silk Framework application directory structure and proper permissions...';
#mkdir -p tmp
#mkdir -p tmp/templates_c
#mkdir -p tmp/cache
#mkdir -p tmp/configs
#mkdir -p tmp/templates

#chmod -R 777 tmp

#mkdir -p config
#mkdir -p components
#mkdir -p components/app
#mkdir -p components/app/models
#mkdir -p components/app/controllers
#mkdir -p components/app/views
#mkdir -p layouts

#mkdir -p log

#chmod -R 777 log

# check command line arguments

#test $# -lt $ARGS && echo "Usage: `basename $0` [-b|--backup] [-p|--preserve_config] [-f INSTALL_PATH]" && exit $E_BADARGS

preserve_all=0
install_path='../../../../'
preserve_config=0

print_usage_and_quit() {
	echo "Usage: $0 [-a|-c] [-f INSTALL_PATH]";
	echo "\n-a Backup all existing files if they are to be overwritten. Includes configuration files";
	echo "-c Backup only configuration files.";
	exit 1;
}

#test $# 

#number_args=$#;



while getopts acf: opt
do
    case "$opt" in
    	a)  preserve_all=1;;
    	c)  preserve_config=1;;
		f) 	$install_path=$OPTARG; 
			if [ !-d $install_path ]; then 
				print_usage_and_quit;
			fi
			;;
    	\?) print_usage_and_quit;;
    esac
done
shift `expr $OPTIND - 1`


#while [ $# -gt 0 ]
#do
#    echo "$1"
#    shift
#	if [ ]; then
#		
#	fi
#done

if [ $preserve_config -eq 1 ] && [ -d $install_path/config ]; then
#	cp -R $INSTALL_PATH
	
	echo '0';
	find . -type d \( -name config \) -prune -o -name '.htaccess' -prune -o -print0 | cpio --null -pd --quiet --unconditional $install_path
#	find . -print;
else 
	if [ $preserve_all -eq 1 ]; then 
	#	cp --backup=simple --suffix=.backup --recursive ./ $install_path;
		echo '1';
	else
#		cp --force --recursive ./ $install_path;
		echo '2'
	fi
fi


#exit 0;


#case [ $1 ]

#cp lib/silk/index.php .
#cp lib/silk/.htaccess .


# keep a single backup of old config if it happens to already exist
#if [ -f config/setup.yml ]; then
	# check if the files differ
#	cmp -s lib/silk/config/setup.yml config/setup.yml;
#	if [ $? == 1 ]; then
#		echo -e "\nThe file config/setup.yml exists and has changed. Do you want to backup this file before replacing? Y/N"
#		read backup_setup;
#		case $backup_setup in
#		[yY]*)
#			mv config/setup.yml config/setup.yml.backup;
#			echo 'File config/setup.yml copied to setup.yml.backup'; 
#			echo 'File default setup.yml written to config/setup.yml.';
#			echo 'You might want to "diff config/setup.yml config/setup.yml.backup" before reverting to your old setup.'; 
#			break;;
#		*)
#			echo 'File config/setup.yml overwritten. Old setup.yml not backed up.';
#			break;;
#		esac
#	fi
#fi

#cp lib/silk/config/setup.yml config/;
#cp lib/silk/config/routes.php config/;
ls -1a $install_path
echo -e "\nDONE";
exit 0
