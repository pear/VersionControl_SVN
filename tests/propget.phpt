--TEST--
test propget recursive xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '334899';
$url = 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/PEARbot.php';

$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC);
$switches = array('r' => $changeset);

$svn = VersionControl_Svn::factory(array('propget'), $options);

$result = $svn->propget->run(array('svn:keywords', $url), $switches);

var_export($result);
echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
array (
  'target' => 
  array (
    0 => 
    array (
      'property' => 
      array (
        'text' => 'Id Rev Revision Date LastChangedDate LastChangedRevision Author LastChangedBy HeadURL URL',
        'name' => 'svn:keywords',
      ),
      'path' => 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/PEARbot.php',
    ),
  ),
)
tests done
