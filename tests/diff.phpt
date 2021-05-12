--TEST--
test diff xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '325013';
$url = 'http://svn.php.net/repository/pear';

$options = array('fetchmode' => VersionControl_SVN::FETCHMODE_ASSOC);
$switches = array('summarize' => true, 'c' => $changeset);

$svn = VersionControl_SVN::factory(array('diff'), $options);

$result = $svn->diff->run(array($url), $switches);

var_export($result);
echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
array (
  'path' => 
  array (
    0 => 
    array (
      'text' => 'http://svn.php.net/repository/pear/packages/Archive_Tar/trunk/package.xml',
      'kind' => 'file',
      'props' => 'none',
      'item' => 'modified',
    ),
  ),
)
tests done

