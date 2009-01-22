#!/bin/sh

source_path='../../../'
target_path='../../../backups'
silk_path='../'
destination_path='../../../tmp'

print_usage_and_quit() {
	echo "Usage: $0 [-s SOURCE_PATH] [-t TARGET_PATH]";
	echo '';
	echo "[-s SOURCE_PATH] - Path from where we want to get the backups. Default is '../../../'"
	echo "[-d DESTINATION_PATH] - Path to backup directory. Default is '../../../backups'"
	echo "[-t TEMP_DIR_PATH] - Path to temp directory. Default is ../../../tmp"
	echo "[-l SILK_LIBRARY_PATH] - Path pointing to silk library installation. Default is '../'"
	echo "Do not put a trailing slash on path names. e.g. '../..' instead of '../../'";
	echo '';
	error 2;
}
#echo "Backup: $*";
check_dir_exists() {	
#	echo "Checking: $1"
	if [ $1 ]; then
		if [ ! -d $1 ]; then
			echo "backup.sh: Directory does not exist: $1";
			error 3;
		fi
	else
		echo "create_backup.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
	fi
}

verify_dir() {
#	checkdir= fix_trailing_forwardslash $1;
	check_dir_exists $1;
#	return checkdir;
}

error() {
	echo 'create_backup.sh: An error occurred.';
	if [ $1 ]; then
		exit $1;
	else
		exit 1;
	fi
}

if [ $1 ]; then
	if [ $1 == 'help' ] || [ $1 == '--help' ]; then
		print_usage_and_quit;
	fi
fi
while getopts s:d:t:l: opt
do
    case "$opt" in
		s) 	source_path="$OPTARG";;
		t) 	temp_path="$OPTARG";;
		d)  destination_path="$OPTARG";;
		l)  silk_path="$OPTARG";;
    	\?) print_usage_and_quit;;
    esac
done
shift `expr $OPTIND - 1`

echo 
# Verify all the paths are OK
verify_dir $source_path;
verify_dir $destination_path;
verify_dir $temp_path;
verify_dir $silk_path;

echo 'Creating Backup... please wait...';
backup_name=backup_`date +%a_%b_%d_%H%M%S`.tar.gz
echo "Backup name = $destination_path/$backup_name";
tar -czvf $temp_path/$backup_name --exclude=$silk_path --exclude-caches $source_path/ || error $?
#touch $target_path/$backup_name || error $?
mv $temp_path/$backup_name $destination_path || error $?
echo "Backup created: $destination_path/$backup_name";
exit 0
