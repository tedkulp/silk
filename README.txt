Silk
---------------

1 About Silk
-------------

A PHP5 web framework, extracted from CMS Made Simple 2.0.

SEE: http://blog.cmsmadesimple.org/2008/12/31/announcing-the-silk-framework/


2 Getting Started
-----------------

Instructions for installing Silk via git, with or without giternal.


2.1 Giternal
------------

1. Install giternal.  If you want to do git externals the old fashioned way,
that's up to you, you'll find instructions below.  I'm lazy, so I use this.  :)

2. Create a new directory to house your web app.

3. Create a config/giternal.yml.  Make it's contents something like this...

silk:
    repo: git://github.com/tedkulp/silk.git
    path: lib

4. Pull down the silk repo master branch directly from github.
Run: giternal update

5. Copy the autogen script to the right place
Run: cp lib/silk/autogen.sh .

6. Let Silk setup a typical directory structure for an application
Run: sh autogen.sh

OR (if this doesn't work)

Ensure autogen.sh has execute permissions (chmod a+x autogen.sh) and
Run: ./autogen

7. Start developing!

8. To update silk to the latest version just
Run: giternal update


2.2 Git without Giternal
------------------------

If for some reason you can't (or won't) install giternal, you can always use ordinary git commands.

1. Create a new directory to house your web app.

2. Create the git repository in lib/silk
Run: git clone git://github.com/tedkulp/silk.git lib/silk

3. Copy the autogen script to the location you're going to develop your application from.
Run: cp lib/silk/autogen.sh .

4. This will setup a typical directory structure for an application
Run: sh autogen.sh  OR (if this doesn't work) Ensure autogen.sh has execute permissions (chmod a+x autogen.sh) and Run: ./autogen

5. Start Developing!

6. To update silk to the latest version, move to your silk directory then checkout the latest version
Run: cd lib/silk
Run: git pull



Enjoy!
