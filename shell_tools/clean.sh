#!/bin/bash

# Set up defaults and options
cmd='';
target_path='../../..';
mode='';
silk_path="$target_path"/lib/silk;


print_usage_and_quit() {
	echo 
    echo "Usage: clean.sh [TARGET_path] [-a|-r] [-l SILK_LIBRARY_PATH] [EXCLUDE_path1] [EXCLUDE_path2] ... ";
    echo "[-a] - Option, append exclude paths to default paths.";
    echo "[-r] - Option, replace default paths with exclude paths.";
    echo "[-t TARGET_PATH] - path to clean. Default is '$target_path'"
	echo "[-l SILK_LIBRARY_PATH] - Path pointing to silk library installation. Default is '$silk_path'"
    echo "[EXCLUDE_path] - paths/Files to exclude from cleaning. Be aware you will probably want to add a * after each entry to allow removal of files in directories under the one given by path. Defaults are 'config/*', 'config', '$silk_path', '../$silk_path', 'backup' & 'backup/*'.
Note the use of *.";
    echo '';
	if [ -n "$1" ]; then
		error "$1";
	else
		error 0;
	fi
	
}

check_dir_exists() {
    if [ -n "$1" ]; then
        if [ ! -d "$1" ]; then
            echo "clean.sh: Directory does not exist: $1";
            error 3;
        fi
    else
        echo "clean.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
		error 5;
    fi
}

verify_dir() {
    check_dir_exists "$1";
}

# returns 0 if no trailing slash, length of string otherwise.
has_trailing_slash() {
	has_slash=`expr "$1" : '.*/$'`
	return $has_slash;
}

error() {
	if [ ! -n "$1" ] || [ $1 -ne 0 ]; then
		echo 'clean.sh: An error occurred.';
	fi

    if [ -n "$1" ]; then
        exit "$1";
    else
        exit 1;
    fi
}
# Check for --help option
if [ "$1" = '--help' ]; then
	print_usage_and_quit 0;
fi;

# Get options 
while getopts art:l: opt
do
    case "$opt" in
    	a)  
			if [ "$mode" != "" ]; then
				print_usage_and_quit; 
			else 
				mode='a';
			fi;; 
    	r)  if [ "$mode" != "" ]; then
				 print_usage_and_quit; 
			else 
				mode='r'; 
			fi;;
		l)  silk_path="$OPTARG";;
		t)  target_path="$OPTARG";;
    esac
done
shift `expr $OPTIND - 1`


# Verify paths are OK then remove any trailing /
verify_dir "$silk_path";

has_trailing_slash "$silk_path";
len="$?"
if [ $len -gt 0 ]; then
	silk_path=${silk_path%*/};
fi


# Get the parent dir of the silk_path. Prevent an annoying, but harmless message reported when trying to remove parent directory of silk lib.
# Will probably still cause problems if silk is installed in a funny location, but will work for typical installs..
parent_of_silk_path=`expr $silk_path : '\(.*\)/.*'`

# Set up the command to issue along with it's arguments as an array
# If we don't use an array, (and instead use a string) we have complex quoting issues 
# involving the use of * required at the end of each path to exclude it's children too
if [ "$mode" = 'a' ] || [ "$mode" = "" ]; then
	cmd=(find "$target_path" ! -path "$target_path" ! -path "$target_path"/backups/\* ! -path "$target_path"/backups ! -path "$parent_of_silk_path" ! -path "$silk_path"/\* ! -path "$silk_path");
else
	cmd=(find "${target_path}");
fi

# Get any exclude paths 
while test "$1" != ""
do
	insert_path=`expr "${1}" : '\(.*[^/]\)'`;
	insert=(! -path "$insert_path");
	count=${#cmd[@]}
	num_new_items=${#insert[@]}

	# this loop merges the arrays
	for (( i=0;i<$num_new_items;i++)); do
		cmd[$count]=${insert[${i}]};
		let count+=1;
	done

	shift;

done

count=${#cmd[@]}
# List files to be removed and prompt for removal.
echo "WARNING: Any files/directories listed below are about to be deleted:"
echo
"${cmd[@]}" -print | more;

wait
echo
echo "Are you sure you want to proceed? If there are any entries listed above, they will be deleted. Y/N"


#echo $new_cmd;
read answer

case "$answer" in
	[yY]*) echo; echo 'Removing...'; "${cmd[@]}" -delete || error $?; echo 'Removal Complete.';;
	*) echo "Exiting without removing any files..."; exit 9;;
esac

echo
echo "Remaining contents of $target_path:"
# Print out our handiwork.
ls -1a "$target_path" || error "$?";
echo
exit 0
