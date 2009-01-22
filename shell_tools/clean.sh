#!/bin/sh

print_usage_and_quit() {
    echo "Usage: $0 [TARGET_path] [-a|-r] [EXCLUDE_path1] [EXCLUDE_path2] ... ";
    echo "[TARGET_path] - path to clean. Default is '.'"
    echo "[-a] - Option, append exclude paths to default paths.";
    echo "[-r] - Option, replace default paths with exclude paths.";
    echo "[EXCLUDE_path] - paths/Files to exclude from cleaning. Be aware you will probably want to add a * after each entry to allow removal of files in directories under the one given by path. Default is 'config*', lib* & 'backup*'";
    echo "Do not put a trailing slash on path names. e.g. '../..' instead of '../../'";
    echo '';
    error 2;
}

check_dir_exists() {
#   echo "Checking: $1"
    if [ $1 ]; then
        if [ ! -d $1 ]; then
            echo "clean.sh: Directory does not exist: $1";
            error 3;
        fi
    else
        echo "clean.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
    fi
}

verify_dir() {
#   checkdir= fix_trailing_forwardslash $1;
    check_dir_exists $1;
}

error() {
    echo 'clean.sh: An error occurred.';
    if [ $1 ]; then
        exit $1;
    else
        exit 1;
    fi
}
# set up defaults and options
cmd='';
target_path='.';
mode='';

if [ $1 ]; then
#print help on --help
	if [ "$1" = '--help' ]; then
		print_usage_and_quit;
	else
	# get the mode
		if [ "$1" == '-a' ] || [ "$1" == '-r' ]; then
			mode=$1;
		else
	# if $1 is not a mode, it must be the target path.
		verify_dir $1 || error;
		target_path=$1;
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

# we haven't found a mode
if [ "$mode" == '' ]; then
	echo "You must supply a mode. -a or -r";
	print_usage_and_quit;
else
	shift;
fi

# Set up the command to issue along with it's arguments as an array
# If we don't use an array, (and instead use a string) we have complex quoting issues 
# involving the use of * required at the end of each path to exclude it's children too
if [ "$mode" = '-a' ]; then
	cmd=(find "$target_path" -not -path "$target_path" /config* -not -path "$target_path" /backup* -not -name lib*);
else
	cmd=(find ${target_path});
fi

# get exclude paths 

while test "$1" != ""
do

insert=(-not -path "${1}");
count=${#cmd[@]}
num_new_items=${#insert[@]}
# this loop merges the arrays
for (( i=0;i<$num_new_items;i++)); do
    cmd[$count]=${insert[${i}]};
	let count+=1;
done

shift

done

count=${#cmd[@]}

# List files to be removed and prompt for removal.
echo "WARNING: The following are about to be deleted:"
"${cmd[@]}" -print | more;

wait
echo
echo "(If blank, there are no files to remove.)";
echo "Are you sure you want to delete these files? Y/N"
#echo $new_cmd;
read answer

case $answer in
	[yY]*) echo 'Removing...'; "${cmd[@]}" -delete || error $?; echo 'Removal Complete.';;
	*) echo "Exiting without removing any files..."; exit -1;;
esac

echo

exit 0
