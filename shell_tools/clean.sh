#!/bin/sh

print_usage_and_quit() {
    echo "Usage: $0 [TARGET_PATH] [-a|-r] [EXCLUDE_PATH1] [EXCLUDE_PATH2] ... ";
    echo "[TARGET_PATH] - Path to clean. Default is '.'"
    echo "[-a] - Option, append exclude paths to default paths.";
    echo "[-r] - Option, replace default paths with exclude paths.";
    echo "[EXCLUDE_PATH] - Paths/Files to exclude from cleaning. Default is 'config' & 'backup'";

    echo "Do not put a trailing slash on path names. e.g. '../..' instead of '../../'";
    echo '';
    error 2;
}

check_dir_exists() {
#   echo "Checking: $1"
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
#   checkdir= fix_trailing_forwardslash $1;
    check_dir_exists $1;
#   return checkdir;
}

error() {
    echo 'clean.sh: An error occurred.';
    if [ $1 ]; then
        exit $1;
    else
        exit 1;
    fi
}

cmd_string='';
target_path='.';
mode='';

if [ $1 ]; then
#print help on --help
	if [ "$1" = '--help' ]; then
		echo "$1 eq 'help'?"
		print_usage_and_quit;
	else
	# get the mode
		if [ "$1" == '-a' ] || [ "$1" == '-r' ]; then
			echo "Setting mode to: $1";
			mode=$1;
		else
	# if $1 is not a mode, it must be the target path.
		verify_dir $1 || error;
		target_path=$1;
		echo "\$target_path: $target_path";
		fi
	fi
fi

# check the second param if first was not the mode
if [ "$mode" == '' ] && [ $2 ]; then
	if [ "$2" == '-a' ] || [ "$2" == '-r' ]; then
		mode=$2
		shift;
	else
		print_usage_and_quit;
	fi
fi

if [ "$mode" == '' ]; then
echo "\$mode == ''"
else
echo "mode neq '', $mode."
fi


# we haven't found a mode
if [ "$mode" == '' ]; then
	echo "You need to supply a mode. -a or -r";
	print_usage_and_quit;
else
	shift;
fi




# set up command string
if [ "$mode" = '-a' ]; then
	cmd_string="find . -not -name \\config\* -not -name \\backup\* -not -name \\lib\* "
else
	cmd_string="find .";

fi


# get exclude paths 
echo
while test "$1" != ""

do
echo "\$1 = $1"
	echo "appending $1 to cmd string...";
	cmd_string="${cmd_string} -not -name \\${1}\\*";

shift

done
echo

cd $target_path;
# cmd_string="${cmd_string} -print"
echo ${cmd_string};
echo "WARNING: The following are about to be deleted: "
${cmd_string} -print;
echo "Are you sure you want to delete these files? Y/N"
read answer

case $answer in
	#[yY]*) echo 'Removing...'; ${cmd_string} -delete || cd -; error $?; echo 'Removal Complete.';;
	*) echo "Exiting without removing any files...";;
esac
cd -;
exit 0
