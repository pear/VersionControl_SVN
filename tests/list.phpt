--TEST--
test list xml
--SKIPIF--
--FILE--
<?php
require_once __DIR__ . '/setup.php.inc';

$changeset = '325013';
$url = 'https://github.com/pear/VersionControl_SVN/tags/0.5.0/docs';

$options = array('fetchmode' => VersionControl_SVN::FETCHMODE_ASSOC);
$switches = array();

$svn = VersionControl_SVN::factory(array('list'), $options);

$result = $svn->list->run(array($url), $switches);

var_export($result);
echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
array (
  'list' => 
  array (
    0 => 
    array (
      'entry' => 
      array (
        0 => 
        array (
          'name' => 'LICENSE',
          'size' => '1495',
          'commit' => 
          array (
            'author' => 'michiel.rook',
            'date' => '2012-11-19T18:34:37.000000Z',
            'revision' => '157',
          ),
          'kind' => 'file',
        ),
        1 => 
        array (
          'name' => 'examples',
          'commit' => 
          array (
            'author' => 'michiel.rook',
            'date' => '2012-11-19T18:34:37.000000Z',
            'revision' => '157',
          ),
          'kind' => 'dir',
        ),
        2 => 
        array (
          'name' => 'tutorials',
          'commit' => 
          array (
            'author' => 'michiel.rook',
            'date' => '2012-11-19T18:34:37.000000Z',
            'revision' => '157',
          ),
          'kind' => 'dir',
        ),
      ),
      'path' => 'https://github.com/pear/VersionControl_SVN/tags/0.5.0/docs',
    ),
  ),
)
tests done

