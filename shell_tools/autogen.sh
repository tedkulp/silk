#!/bin/sh

print_usage_and_quit() {
	echo "Usage: $0 [-b|-c] [-r] [-i INSTALL_PATH] [-l LIBRARY_PATH] [-s SKELETON_NAME]";
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
	check_dir_exists $1;
}

error() {
	echo 'autogen.sh: An error occurred.';
	if [ $1 ]; then
		exit $1;
	else
		exit 1;
	fi
}


echo "Setting up application skeleton $skeleton...";
echo '';

backup=0
preserve_config=0;

install_path='../../..';
silk_path="$install_path/lib/silk";
skeleton='default';
skeleton_path='';
remove=0;

if [ $1 ]; then
	if [ $1 == 'help' ] || [ $1 == '--help' ]; then
		print_usage_and_quit;
	fi
fi

while getopts brcl:s:i: opt
do
    case "$opt" in
    	b)  backup=1;;
    	c)  preserve_config=1;;
		l)  silk_path="$OPTARG";; 
		i)  install_path="$OPTARG";;
		s) 	skeleton="$OPTARG";;
		r)  remove=1;;
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

if [ "$backup" -eq 1 ]; then 
# backup our important files
	echo;	
	sh create_backup.sh -s $install_path -d $install_path/backups -t $install_path/tmp -l $silk_path || error;
	echo;
fi

# Prevent an annoying, but harmless message reported when trying to remove parent directory of silk lib.
# Will probably still cause problems if silk is installed in a funny location, but will work for typical installs..
parent_of_silk_path=`expr $silk_path : '\(.*\)/.*'`

# Remove any files we aren't using
if [ "$remove" -eq 1 ]; then
	sh clean.sh $install_path -r $install_path $parent_of_silk_path $silk_path\* $install_path/backups\* || exit $?;
fi  

if [ $preserve_config -eq 1 ] && [ -d $install_path/config ] && [ -d $skeleton_path/config ]; then
	find $skeleton_path -depth ! -path $skeleton_path/config\* -and -type d -print -exec cp -rpfv '{}' $install_path \ || error $?;
#	cp -R $INSTALL_PATH
	
	#echo '0';
#	find $skeleton_path/ -type d \( -name config \) -prune -o -name '.htaccess' -prune -o -print0 | cpio --null -pd --quiet --unconditional $install_path
	#cp --force --backup=numbered -R $install_path/config/* $install_path/config/;
	#cp --force --backup=numbered $install_path/.htaccess $install_path/;
#	find . -print;
	echo;
else
	echo 'Copying...';
	cp --force --recursive $skeleton_path/* $install_path || error $?;
	echo
	ls -1a $install_path || error $?;
	echo
	echo 'Copy complete.';
fi
echo
echo -e "Installation of application skeleton: $skeleton... Complete.";
exit 0
