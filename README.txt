Silk

A PHP5 web framework, extracted from CMS Made Simple 2.0.

Getting Started
---------------

1. Install giternal.  If you want to do git externals the old fashioned way,
that's up to you.  I'm lazy, so I use this.  :)

2. Create a new directory to house your web app.

3. Create a config/giternal.yml.  Make it's contents something like:
silk:
  repo: git://github.com/tedkulp/silk.git
  path: lib/silk

4. Run: giternal updat
   This should pull down the silk repo master branch directly
   from github.

5. Run: cp lib/silk/autogen.sh .

6. Run: cp lib/silk/index.php .

7. Start developing.  To update silk to the latest version, just: gitneral update


Enjoy!
