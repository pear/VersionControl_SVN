--TEST--
test propget recursive xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '334899';
$url = 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/';

$options = array('fetchmode' => VersionControl_SVN::FETCHMODE_ASSOC);
$switches = array('recursive' => true, 'r' => $changeset);

$svn = VersionControl_Svn::factory(array('proplist'), $options);

$result = $svn->proplist->run(array($url), $switches);

function custom_array_sort($a, $b, $idx) {
	if (($r = strnatcasecmp($a[$idx], $b[$idx])) === 0) {
		return strnatcmp($a[$idx], $b[$idx]);
	}
	return $r;
}

$propertySort = function ($a, $b) {
	return custom_array_sort($a, $b, 'name');
};
$targetSort   = function ($a, $b) {
	return custom_array_sort($a, $b, 'path');
};

foreach (array_keys($result['target']) as $key) {
	usort($result['target'][$key]['property'], $propertySort);
}
usort($result['target'], $targetSort);

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
          'name' => 'svn:keywords',
        ),
      ),
      'path' => 'http://svn.php.net/repository/pear/pearbot/tags/pearbot_0_1/config.php',
    ),
    1 => 
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
