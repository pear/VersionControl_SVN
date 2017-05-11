--TEST--
test upgrade xml
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';

$svn = VersionControl_SVN::factory(array('upgrade'));
$result = array();
try {
    $result = $svn->upgrade->run();
} catch (Exception $ex) {

}

var_export($result);
echo "\ntests done\n";
?>
--CLEAN--
--EXPECT--
array (
)
tests done

