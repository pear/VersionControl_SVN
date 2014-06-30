--TEST--
test if xml switch is reset between commands (see bug #19910)
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';

$changeset = '325013';
$url = 'https://github.com/pear/VersionControl_SVN/tags/0.5.0';

$switches = array('c' => $changeset);

$svn = VersionControl_SVN::factory(array('log'));

$svn->log->fetchmode = VERSIONCONTROL_SVN_FETCHMODE_XML;
$result = $svn->log->run(array($url));
var_export($result);

$svn->log->fetchmode = VERSIONCONTROL_SVN_FETCHMODE_RAW;
$result = $svn->log->run(array($url));
var_export($result);

echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
'<?xml version="1.0" encoding="UTF-8"?>
<log>
<logentry
   revision="157">
<author>michiel.rook</author>
<date>2012-11-19T18:34:37.000000Z</date>
<msg>Update package.xml for release
</msg>
</logentry>
</log>''------------------------------------------------------------------------
r157 | michiel.rook | 2012-11-19 19:34:37 +0100 (Mon, 19 Nov 2012) | 2 lines

Update package.xml for release

------------------------------------------------------------------------'
tests done

