_NOTE_: This package is looking for a new maintainer. Are you interested? Let me know at mrook AT php DOT net.

VersionControl_SVN
==================

[![.github/workflows/build.yml](https://github.com/pear/VersionControl_SVN/actions/workflows/build.yml/badge.svg)](https://github.com/pear/VersionControl_SVN/actions/workflows/build.yml)

VersionControl_SVN is a simple OO-style interface for Subversion,
the free/open-source version control system.

Some of VersionControl_SVN's features:

* Full support of svn command-line client's subcommands.
* Multi-object factory.
* Source fully documented with PHPDoc.
* Stable, extensible interface.
* Collection of helpful quickstart examples and tutorials.

This package is hosted at http://pear.php.net/package/VersionControl_SVN

Please report all new issues via the PEAR bug tracker.

Pull requests are welcome!


Testing, building
-----------------

To test, run either
$ phpunit tests/
  or
$ pear run-tests -r

To build, simply
$ pear package

To install from scratch
$ pear install package.xml

To upgrade
$ pear upgrade -f package.xml
