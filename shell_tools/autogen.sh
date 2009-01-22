#!/bin/sh

echo "Setting up application skeleton $skeleton...";
echo '';
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
preserve_config=0;

install_path='../../..';
silk_path="$install_path/lib/silk";
skeleton='default';
skeleton_path='';

print_usage_and_quit() {
	echo "Usage: $0 [-a|-c] [-r] [-i INSTALL_PATH] [-l LIBRARY_PATH] [-s SKELETON_NAME]";
	echo '';
	echo "[-b] - Backup all existing files if they are to be overwritten. Includes configuration files";
	echo "[-c] - Backup only configuration files.";
	echo "[-r] - Remove any existing files before copying skeleton"
	echo "[-i INSTALL_PATH] - Path in which to to install the skeleton to. Default is '../../../'"
	echo "[-l SILK_LIBRARY_PATH] - Path pointing to silk library installation. Default is '\$install_path/lib/silk'"
	echo "[-s SKELETON_NAME] - Name of application skeleton to install. Default is 'default' See \$library_path/skeletons for other options."
	echo "Do not put a trailing slash on path names. e.g. '../..' instead of '../../'";
	echo '';
	error 2;
}

#fix_trailing_forwardslash() {
	#echo "Checking for trailing / in $1";
#	fixed=$1
#	case $fixed in
#		*/) fixed=`echo "$fixed" | sed -ie 's/.$//'`;;
#		*) ;;
#	esac
#	echo $fixed; 
	
#}

check_dir_exists() {	
	if [ $1 ]; then
		if [ ! -d $1 ]; then
			echo "autogen.sh: Directory does not exist: $1";
			error 3;
		fi
	else
		echo "autogen.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
	fi
}

verify_dir() {
#	checkdir= fix_trailing_forwardslash $1;
	check_dir_exists $1;
#	return checkdir;
}

error() {
	echo 'autogen.sh: An error occurred.';
	if [ $1 ]; then
		exit $1;
	else
		exit 1;
	fi
}

#test $# 

#number_args=$#;
if [ $1 ]; then
	if [ $1 == 'help' ] || [ $1 == '--help' ]; then
		print_usage_and_quit;
	fi
fi

while getopts acl:s:i: opt
do
    case "$opt" in
    	a)  preserve_all=1;;
    	c)  preserve_config=1;;
		l)  silk_path="$OPTARG";; 
		i)  install_path="$OPTARG";;
		s) 	skeleton="$OPTARG";;
    	\?) print_usage_and_quit;;
    esac
done
shift `expr $OPTIND - 1`

# Location of skeleton directory
skeleton_path=$silk_path/skeleton/$skeleton


# Verify all the paths are OK
verify_dir $install_path;
verify_dir $silk_path;
verify_dir $skeleton_path;

if [ $preserve_config -eq 1 ] && [ -d $install_path/config ]; then
#	cp -R $INSTALL_PATH
	
	echo '0';
#	find $skeleton_path/ -type d \( -name config \) -prune -o -name '.htaccess' -prune -o -print0 | cpio --null -pd --quiet --unconditional $install_path
	#cp --force --backup=numbered -R $install_path/config/* $install_path/config/;
	#cp --force --backup=numbered $install_path/.htaccess $install_path/;
#	find . -print;
else 
	if [ $preserve_all -eq 1 ]; then 
		#sh $silk_path/backup.sh 
	#	echo 'Creating Backup... please wait...';
	#	if [ -f $install_path/backup.tar.gz ]; then	
			
		#	cp --backup=numbered --force $install_path/backup.tar.gz $install_path;
		#	mv backup_`date +%a_%b_%d_%H:%M:%S`.tar.gz
	#	fi
	#	backup_name=backup_`date +%a_%b_%d_%H%M%S`.tar.gz
#		echo "Backup name = $backup_name";
#		tar -czvf $install_path/$backup_name --exclude-caches $install_path/
#		echo 'Backup created.';
#		cp --backup=simple --suffix=.backup --recursive $skeleton_path/* $install_path;
		echo '';	
		sh create_backup.sh -s $install_path -d $install_path/backups -t $install_path/tmp -l $silk_path || error;
		echo '';
	fi
fi
echo 'Copying...';
cp --force --recursive $skeleton_path/* $install_path || error $?;
ls -1a $install_path
echo 'Copy complete.';

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
echo -e "Installation of application skeleton: $skeleton... Complete.";
exit 0
