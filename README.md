Silk Framework
==============

The Silk Framework is a PHP 5.3+, MVC web framework which was
originally taken from CMS Made Simple 2.0, but has been reworked
many times since then.

Features
--------

Silk tries to do as much as it needs to and nothing more. The focus
is resting squarely on being the plumbing of a web application,
without handling anything that requires configuration or would act
differently for every application.

* MVC based workflow w/ sensible defaults
* Intelligent route handling
* Doctrine2 ORM w/ many databases supported
* Seamless MongoDB support (**coming soon**)
* Easily exandable w/ extensions
* Extensible form API w/ smart defaults and workflow
* Built on a library similar to [Rack](http://rack.rubyforge.org/) for easy 
  middleware-based expandability
* Unit testing w/ [PHPUnit](https://github.com/sebastianbergmann/phpunit/)
  built-in
* [Smarty](http://www.smarty.net/) templates used through (php-based templates work as well)
* Command line focused workflow with easily added commands via simple
  PHP scripts.
* caching system (memcached, memory, xcache, apc, database) (**coming
  soon**)

### What kind of stuff doesn't it do?

Things below are the types of things Silk won't do. Since it's easily
expandable via extensions, it becomes unnecessary to handle things that
will have too many options. Things like:

* Users/Groups
* Permissions/Authentication

Please don't submit features/issues with stuff that's too high level.
I'll just tell you make an extension. k?thx!

Requirements
------------

* PHP 5.3+

### Recommended

* Database (mysql, mongodb, postgresql, sqlite, oracle, mssql, db2)
* Command line
* Caching mechanism (memcached, xcache, apc)
* PEAR (to install phpunit)

Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b kick_ass_feature`)
3. Commit your changes (`git commit -am "Added Kick Ass Feature"`)
4. Push to the branch (`git push origin kick_ass_feature`)
5. Create an [Issue][1] with a link to your branch
6. Bask in the glory of your cleverness and wait

[1]: http://github.com/tedkulp/silk/issues
