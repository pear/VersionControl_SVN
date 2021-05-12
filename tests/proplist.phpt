--TEST--
test propget recursive xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '334899';
$url = 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/PEARbot.php';

$options = array('fetchmode' => VersionControl_SVN::FETCHMODE_ASSOC);
$switches = array('r' => $changeset);

$svn = VersionControl_Svn::factory(array('proplist'), $options);

$result = $svn->proplist->run(array($url), $switches);

usort($result['target'][0]['property'], function ($a, $b) {
	if (($r = strnatcasecmp($a['name'], $b['name'])) === 0) {
		return strnatcmp($a['name'], $b['name']);
	}
	return $r;
});

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
        0 => 
        array (
          'name' => 'cvs2svn:cvs-rev',
        ),
        1 => 
        array (
          'name' => 'svn:eol-style',
        ),
        2 => 
        array (
          'name' => 'svn:executable',
        ),
        3 => 
        array (
          'name' => 'svn:keywords',
        ),
      ),
      'path' => 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/PEARbot.php',
    ),
  ),
)
tests done
