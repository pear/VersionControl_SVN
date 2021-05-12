--TEST--
test log xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '325013';
$url = 'http://svn.php.net/repository/pear';

$options = array('fetchmode' => VersionControl_SVN::FETCHMODE_ASSOC);
$switches = array('c' => $changeset);

$svn = VersionControl_SVN::factory(array('log'), $options);

$result = $svn->log->run(array($url), $switches);

var_export($result);
echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
array (
  'logentry' => 
  array (
    0 => 
    array (
      'author' => 'mrook',
      'date' => '2012-04-10T17:29:01.795698Z',
      'msg' => 'Update changelog',
      'revision' => '325013',
    ),
  ),
)
tests done

