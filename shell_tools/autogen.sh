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
	echo "Usage: autogen.sh [-b|-c] [-r] [-i INSTALL_PATH] [-l LIBRARY_PATH] [-s SKELETON_NAME]";
	echo '';
	echo "[-b] - Backup all existing files if they are to be overwritten. Includes configuration files";
	echo "[-c] - Do not write over only configuration files in INSTALL_PATH/config, or INSTALL_PATH/.htacces.";
	echo "[-r] - Remove any existing files before copying skeleton"
	echo "[-i INSTALL_PATH] - Path in which to to install the skeleton to. Default is '$install_path'"
	echo "[-l SILK_LIBRARY_PATH] - Path pointing to silk library installation. Default is ' $silk_path'"
	echo "[-s SKELETON_NAME] - Name of application skeleton to install. Default is 'default' See SILK_LIBRARY_PATH/skeletons for other options."
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
            echo "autogen.sh: Directory does not exist: $1";
            error 3;
        fi
    else
        echo "autogen.sh: Function 'check_dir_exists' requires a parameter: dirname... $1";
		error 5;
    fi
}

verify_dir() {
    check_dir_exists $1;
}

# returns 0 if no trailing slash, length of string otherwise.
has_trailing_slash() {
	has_slash=`expr "$1" : '.*/$'`
	return $has_slash;
}

error() {
	if [ ! -n "$1" ] || [ "$1" -ne 0 ]; then
		echo 'autogen.sh: An error occurred.';
	fi

    if [ ! -n "$1" ]; then
        exit "$1";
    else
        exit 1;
    fi
}


# Set up defaults and options
backup=0;
preserve_config=0;

install_path='../../..';
silk_path="$install_path/lib/silk";
skeleton='default';
skeleton_path='';
remove=0;

# Check for --help option
if [ "$1" = '--help' ]; then
	print_usage_and_quit 0;
fi;


echo "Installing application skeleton: '"$skeleton"'...";
echo '';

# Get options. 
while getopts brcl:s:i: opt
do
    case "$opt" in
    	b)  backup=1;;
    	c)  preserve_config=1;;
		l)  silk_path="$OPTARG";; 
		i)  install_path="$OPTARG";;
		s)  skeleton_path="$OPTARG";;
		r)  remove=1;;
    	\?) print_usage_and_quit;;
    esac
done
shift `expr $OPTIND - 1`;

# Verify paths are OK then remove any trailing /

# Install path
verify_dir "$install_path";

has_trailing_slash "$install_path";
len="$?"
if [ "$len" -gt 0 ]; then
	silk_path=${install_path%*/};
fi

# Silk library path
verify_dir "$silk_path";

has_trailing_slash "$silk_path";
len="$?"
if [ "$len" -gt 0 ]; then
	silk_path=${silk_path%*/};
fi

# Location of skeleton directory
skeleton_path="$silk_path"/skeleton/"$skeleton";
verify_dir "$skeleton_path";

has_trailing_slash "$skeleton_path";
len="$?"
if [ "$len" -gt 0 ]; then
	silk_path=${skeleton_path%*/};
fi

# Prevent an annoying, but harmless message reported when trying to remove parent directory of silk lib.
# Will probably still cause problems if silk is installed in a funny location, but will work for typical installs..
parent_of_silk_path=`expr "$silk_path" : '\(.*\)/.*'`

verify_dir "$parent_of_silk_path";

has_trailing_slash "$parent_of_silk_path";
len="$?"
if [ "$len" -gt 0 ]; then
	silk_path=${parent_of_silk_path%*/};
fi

# Perform Actions

# Backup our important files
# See backup.sh --help for info on these parameters
if [ "$backup" -eq 1 ]; then 
	sh backup.sh -s "$install_path" -d "$install_path"/backups -l "$silk_path" || error "$?";
	echo;
fi

# Create a name for htaccess, that's unlikly to cause name collisions, in case we need to move it around.
htaccess_name=htaccess_`date +%a_%b_%d_%H%M%S`;

# Remove/Clean out files before copy
if [ "$remove" -eq 1 ]; then
	# Always pass the path to the silk library (-l) otherwise we might delete our hard work by accident,
	# especially if silk is not installed at the default lib/silk location.
	# Note clean automatically protects the silk install path and backups. 
	# You can use the -a option here on on clean.sh to protect additional directories 
	# Example: sh clean.sh -l "$silk_path" -a ../../../log

	# See clean.sh --help for info on these parameters

	# If required, we preserve the config files at this point
	if [ "$preserve_config" -eq 1 ] && [ -d "$install_path"/config ]; then 
		
		if [ -f "$install_path"/.htaccess ]; then  
			# Protect .htaccess by renaming and moving it into config (we'll move it out later.)
			mv "$install_path"/.htaccess "$install_path"/config/$htaccess_name;
		fi
		sh clean.sh -l "$silk_path" "$install_path" "$install_path"/config/\* "$install_path"/config;
		# Test if we answered NO to proceed 
				
		result="$?"
		if [ "$result" -eq 9 ] || [ "$result" -eq 0 ]; then
			if [ "$result" -eq 9 ]; then
				exit 0;	
			fi
		else
		  error "$result"; 
		fi
	else
		sh clean.sh -l "$silk_path" "$install_path"; 
		# Test if we answered NO to clean.sh "do you want to proceed" message, so we can exit cleanly
		result="$?"
		if [ "$result" -eq 9 ] || [ "$result" -eq 0 ]; then
			if [ "$result" -eq 9 ]; then
				exit 0;	
			fi
		else
		  error "$result"; 
		fi

	fi	
fi  

echo "Copying $skeleton to $install_path";
if [ "$preserve_config" -eq 1 ] && [ -d "$install_path"/config ] && [ -d "$skeleton_path"/config ]; then
	
	# Rename the old config folder (note silly name to avoid collisions)	
	old_name=config_`date +%a_%b_%d_%H%M%S`;
	mv "$install_path"/config "$install_path"/$old_name || error "$?";

	# Copy across everything
	cd "$skeleton_path" || error;
	tar cf - . | (cd $OLDPWD; cd "$install_path" && tar xBf -)
	cd $OLDPWD || error;

	# Delete the new config
	rm -Rf "$install_path"/config || error "$?";

	# Rename the old back to config
	mv "$install_path"/$old_name "$install_path"/config || error "$?";


	if [ -f "$install_path"/config/$htaccess_name ]; then  
		# copy back the .htaccess file.
		mv -f "$install_path"/config/$htaccess_name "$install_path"/.htaccess;
	fi
	# I tried to get it just copying using find, but to no avail.
#	find "$skeleton_path" -maxdepth 1 ! -path "$skeleton_path" ! -path "$skeleton_path"/config/\* ! -path "$skeleton_path"/config -and -print0 | cpio --null -pvd "$install_path" || error "$?";

else
	# Just copy across everything
	cd "$skeleton_path" || error;
	tar cf - . | (cd $OLDPWD; cd "$install_path" && tar xBf -);
	cd $OLDPWD || error;
fi

echo 'Copy complete.';
echo
echo 'Contents of '"$install_path"':'
# Print out our handiwork.
ls -1a "$install_path" || error "$?";
echo
echo -e "Installation of application skeleton: '"$skeleton"'... Complete.";
exit 0
