#!/bin/sh

# The MIT License
#
# Copyright (c) 2008 Ted Kulp
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

print_usage_and_quit() {
	echo "Usage: backup.sh [-a|-b] [-s SOURCE_PATH] [-t TARGET_PATH]";
	echo '';
	echo "[-a] Archive all files, including silk library, but not the destination directory ($destination_path directory by default).";
	echo "[-b] Archive all files, including silk library and destination directory.";
	echo "[-s SOURCE_PATH] - Path from where we want to get the backups. Default is '$source_path'"
	echo "[-d DESTINATION_PATH] - Path to backup directory. Default is '$destination_path'"
	echo "[-l SILK_LIBRARY_PATH] - Path pointing to silk library installation. This directory will not be backed up. Default is '$silk_path'"
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
            echo "backup.sh: Directory does not exist: $1";
            error 3;
        fi
    else
        echo "backup.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
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
	if [ ! -n "$1" ] || [ "$1" -ne 0 ]; then
		echo 'backup.sh: An error occurred.';
	fi

    if [ -n "$1" ]; then
        exit "$1";
    else
        exit 1;
    fi
}

# Set up defaults and options
backup_all=0;
backup_backups=0;
source_path='../../../';
silk_path='../';
destination_path='../../../backups';

# Check for --help option
if [ "$1" = '--help' ]; then
	print_usage_and_quit 0;
fi;

# Get options. 
while getopts abs:d:l: opt
do
    case "$opt" in
		a)  if [ $backup_backups -eq 0 ]; then
				backup_all=1; 
			else 
				print_usage_and_quit;
			fi;;
		b)  if [ $backup_all -eq 0 ]; then 
				backup_backups=1; 
			else 
				print_usage_and_quit;
			fi;;
		s)  source_path="$OPTARG";;
		d)  destination_path="$OPTARG";;
		l)  silk_path="$OPTARG";;
    	\?) print_usage_and_quit;;
    esac
done
shift `expr $OPTIND - 1`;

# Verify paths are OK then remove any trailing /

# Source path
verify_dir "$source_path";

has_trailing_slash "$source_path";
len=$?
if [ $len -gt 0 ]; then
	source_path=${source_path%*/};
fi

# Destination path
verify_dir "$destination_path";

has_trailing_slash "$destination_path";
len=$?
if [ $len -gt 0 ]; then
	destination_path=${destination_path%*/};
fi

# Silk path
verify_dir "$silk_path";

has_trailing_slash "$silk_path";
len=$?
if [ $len -gt 0 ]; then
	silk_path=${silk_path%*/};
fi


backup_name=backup_`date +%a_%b_%d_%H%M%S`.tar.gz

	echo 'Creating Backup... please wait...';
if [ "$backup_backups" -eq 1 ]; then
	tar -czf "$destination_path"/"$backup_name" --exclude="$destination_path"/"$backup_name" "$source_path";
	wait
	# because this process will likely attempt to back up the archive as it's being built,
	# we get around this by ignoring a "file changed as we read it error", tar exit code 1 (tar v1.17+).
	result="$?";
	if [ "$result" -ne 1 ] && [ "$result" -ne 0 ]; then
		error $result;
	fi
else 
	if [ "$backup_all" -eq 1 ]; then
		tar -czf "$destination_path"/"$backup_name" --exclude="$destination_path" "$source_path" || error "$?"
	else
		tar -czf "$destination_path"/"$backup_name" --exclude="$destination_path"/"$backup_name" --exclude="$silk_path" --exclude-caches "$source_path" || error "$?"
	fi
fi
tar --list -f "$destination_path"/"$backup_name" || error "$?";  
echo "Backup created: $destination_path/$backup_name";
echo
echo "execute: tar --list -f ""$destination_path"/"$backup_name"" to list backed up files." ;  
echo
echo "Backup Complete."
exit 0
