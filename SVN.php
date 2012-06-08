<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * +----------------------------------------------------------------------+
 * | PHP version 5                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2004-2007, Clay Loveless                               |
 * | All rights reserved.                                                 |
 * +----------------------------------------------------------------------+
 * | This LICENSE is in the BSD license style.                            |
 * | http://www.opensource.org/licenses/bsd-license.php                   |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * |  * Redistributions of source code must retain the above copyright    |
 * |    notice, this list of conditions and the following disclaimer.     |
 * |                                                                      |
 * |  * Redistributions in binary form must reproduce the above           |
 * |    copyright notice, this list of conditions and the following       |
 * |    disclaimer in the documentation and/or other materials provided   |
 * |    with the distribution.                                            |
 * |                                                                      |
 * |  * Neither the name of Clay Loveless nor the names of contributors   |
 * |    may be used to endorse or promote products derived from this      |
 * |    software without specific prior written permission.               |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.killersoft.com/LICENSE.txt BSD License
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

// {{{ Error Management
require_once 'PEAR/ErrorStack.php';
require_once 'System.php';

// error & notice constants
define('VERSIONCONTROL_SVN_ERROR', -1);
define('VERSIONCONTROL_SVN_ERROR_NO_VERSION', -2);
define('VERSIONCONTROL_SVN_ERROR_NO_REVISION', -3);
define('VERSIONCONTROL_SVN_ERROR_UNKNOWN_CMD', -4);
define('VERSIONCONTROL_SVN_ERROR_NOT_IMPLEMENTED', -5);
define('VERSIONCONTROL_SVN_ERROR_NO_SWITCHES', -6);
define('VERSIONCONTROL_SVN_ERROR_UNDEFINED', -7);
define('VERSIONCONTROL_SVN_ERROR_REQUIRED_SWITCH_MISSING', -8);
define('VERSIONCONTROL_SVN_ERROR_MIN_ARGS', -9);
define('VERSIONCONTROL_SVN_ERROR_EXEC', -10);
define('VERSIONCONTROL_SVN_NOTICE', -999);
define('VERSIONCONTROL_SVN_NOTICE_INVALID_SWITCH', -901);
define('VERSIONCONTROL_SVN_NOTICE_INVALID_OPTION', -902);

// }}}
// {{{ fetch modes

/**
 * Note on the fetch modes -- as the project matures, more of these modes
 * will be implemented. At the time of initial release only the 
 * Log and List commands implement anything other than basic
 * RAW output.
 */

/**
 * This is a special constant that tells VersionControl_SVN the user hasn't specified
 * any particular get mode, so the default should be used.
 */
define('VERSIONCONTROL_SVN_FETCHMODE_DEFAULT', 0);

/**
 * Responses returned in associative array format
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ASSOC', 1);

/**
 * Responses returned as object properties
 */
define('VERSIONCONTROL_SVN_FETCHMODE_OBJECT', 2);

/**
 * Responses returned as raw XML (as passed-thru from svn --xml command responses)
 */
define('VERSIONCONTROL_SVN_FETCHMODE_XML', 3);

/**
 * Responses returned as string - unmodified from command-line output
 */
define('VERSIONCONTROL_SVN_FETCHMODE_RAW', 4);

/**
 * Responses returned as raw output, but all available output parsing methods
 * are performed and stored in the {@link output} property.
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ALL', 5);

/**
 * Responses returned as numbered array
 */
define('VERSIONCONTROL_SVN_FETCHMODE_ARRAY', 6);

// }}}

/**
 * Simple OO interface for Subversion 
 *
 * @tutorial  VersionControl_SVN.pkg
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.killersoft.com/LICENSE.txt BSD License
 * @version   @version@
 * @link      http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN
{

    // {{{ Public Properties
    
    /**
     * Reference array of subcommand shortcuts. Provided for convenience for 
     * those who prefer the shortcuts they're used to using with the svn
     * command-line tools.
     *
     * You may specify your own shortcuts by passing them in to the factory.
     * For example:
     *
     * <code>
     * <?php
     * require_once 'VersionControl/SVN.php';
     *
     * $options['shortcuts'] = array('boot' => 'Delete', 'checkin' => 'Commit');
     *
     * $svn = VersionControl_SVN::factory(array('boot', 'checkin'), $options);
     *
     * $switches = array(
     *                 'username' => 'user', 'password' => 'pass', 'force' => true
     *             );
     * $args = array('svn://svn.example.com/repos/TestProject/file_to_delete.txt');
     *
     * $svn->boot->run($switches, $args);
     *
     * ?>
     * </code>
     *
     * @var     array
     */
    public static $shortcuts = array(
        'praise'    => 'Blame',
        'annotate'  => 'Blame',
        'ann'       => 'Blame',
        'co'        => 'Checkout',
        'ci'        => 'Commit',
        'cp'        => 'Copy',
        'del'       => 'Delete',
        'remove'    => 'Delete',
        'rm'        => 'Delete',
        'di'        => 'Diff',
        'ls'        => 'List',
        'mv'        => 'Move',
        'rename'    => 'Move',
        'ren'       => 'Move',
        'pdel'      => 'Propdel',
        'pd'        => 'Propdel',
        'pget'      => 'Propget',
        'pg'        => 'Propget',
        'plist'     => 'Proplist',
        'pl'        => 'Proplist',
        'pset'      => 'Propset',
        'ps'        => 'Propset',
        'stat'      => 'Status',
        'st'        => 'Status',
        'sw'        => 'Switch',
        'up'        => 'Update'
    );

    // }}}
    // {{{ errorMessages()
    
    /**
     * Set up VersionControl_SVN error message templates for PEAR_ErrorStack.
     *
     * @return  array
     */
    public static function declareErrorMessages()
    {
        $messages = array(
            VERSIONCONTROL_SVN_ERROR => '%errstr%',
            VERSIONCONTROL_SVN_ERROR_EXEC => '%errstr% (cmd: %cmd%)',
            VERSIONCONTROL_SVN_ERROR_NO_VERSION => 
                'undefined 2',
            VERSIONCONTROL_SVN_ERROR_NO_REVISION => 
                'undefined 3',
            VERSIONCONTROL_SVN_ERROR_UNKNOWN_CMD => 
                '\'%command%\' is not a known VersionControl_SVN command.',
            VERSIONCONTROL_SVN_ERROR_NOT_IMPLEMENTED => 
                '\'%method%\' is not implemented in the %class% class.',
            VERSIONCONTROL_SVN_ERROR_NO_SWITCHES => 
                'undefined 6',
            VERSIONCONTROL_SVN_ERROR_REQUIRED_SWITCH_MISSING => 
                'svn %_svn_cmd% requires the following %switchstr%: %missing%',
            VERSIONCONTROL_SVN_ERROR_MIN_ARGS => 
                'svn %_svn_cmd% requires at least %min_args% %argstr%',
            VERSIONCONTROL_SVN_NOTICE => '%notice%',
            VERSIONCONTROL_SVN_NOTICE_INVALID_SWITCH => 
                '\'%list%\' %is_invalid_switch% for %CommandClass% '
                . 'and %was% ignored. Please refer to the documentation.',
            VERSIONCONTROL_SVN_NOTICE_INVALID_OPTION =>
                '\'%option%\' is not a valid option, and was ignored.'
        );
        
        return $messages;
    }
    
    // {{{ factory()
    
    /**
     * Create a new VersionControl_SVN command object.
     *
     * $options is an array containing multiple options
     * defined by the following associative keys:
     *
     * <code>
     *
     * array(
     *  'url'           => 'Subversion repository URL',
     *  'username'      => 'Subversion repository login',
     *  'password'      => 'Subversion repository password',
     *  'config_dir'    => 'Path to a Subversion configuration directory',
     *                     // [DEFAULT: null]
     *  'dry_run'       => true/false, 
     *                     // [DEFAULT: false]
     *  'encoding'      => 'Language encoding to use for commit messages', 
     *                     // [DEFAULT: null]
     *  'svn_path'      => 'Path to the svn client binary installed as part of Subversion',
     *                     // [DEFAULT: /usr/local/bin/svn]
     * )
     *
     * </code>
     *
     * Example 1.
     * <code>
     * <?php
     * require_once 'VersionControl/SVN.php';
     *
     * $options = array(
     *      'url'        => 'https://www.example.com/repos',
     *      'path'       => 'your_project',
     *      'username'   => 'your_login',
     *      'password'   => 'your_password',
     * );
     * 
     * // Run a log command
     * $svn = VersionControl_SVN::factory('log', $options);
     *
     * print_r($svn->run());
     * ?>
     * </code>
     *
     * @param string $command The Subversion command
     * @param array  $options An associative array of option names and
     *                        their values
     *
     * @return  mixed   a newly created VersionControl_SVN command object, or PEAR_ErrorStack
     *                  constant on error
     */
    public static function factory($command, $options = array())
    {
        $stack = PEAR_ErrorStack::singleton('VersionControl_SVN');
        $stack->setErrorMessageTemplate(VersionControl_SVN::declareErrorMessages());
        if (is_string($command) && strtoupper($command) == '__ALL__') {
            unset($command);
            $command = array();
            $command = VersionControl_SVN::fetchCommands();
        }
        if (is_array($command)) {
            $objects = new stdClass;
            foreach ($command as $cmd) {
                $obj = VersionControl_SVN::init($cmd, $options);
                $objects->$cmd = $obj;
            }
            return $objects;
        } else {
            $obj = VersionControl_SVN::init($command, $options);
            return $obj;
        }
    }
    
    // }}}
    // {{{ init()
    
    /**
     * Initialize an object wrapper for a Subversion subcommand.
     *
     * @param string $command The Subversion command
     * @param array  $options An associative array of option names and
     *                        their values
     *
     * @return  mixed   object on success, false on failure
     */
    public static function init($command, $options)
    {
        // Check for shortcuts for commands
        $shortcuts = self::$shortcuts;
        
        if (isset($options['shortcuts']) && is_array($options['shortcuts'])) {
            foreach ($options['shortcuts'] as $key => $val) {
                $shortcuts[strtolower($key)] = $val;       
            }
        }
        
        $cmd   = isset($shortcuts[strtolower($command)])
            ? $shortcuts[strtolower($command)]
            : $command;
        $cmd   = ucfirst(strtolower($cmd));
        $class = 'VersionControl_SVN_Command_' . $cmd;
        
        if (include_once realpath(dirname(__FILE__)) . "/SVN/Command/{$cmd}.php") {
            if (class_exists($class)) {
                $obj = new $class;
                $obj->options = $options;
                $obj->setOptions($options);
                return $obj;
            }
        }
        
        PEAR_ErrorStack::staticPush(
            'VersionControl_SVN', VERSIONCONTROL_SVN_ERROR_UNKNOWN_CMD, 'error', 
            array('command' => $command, 'options' => $options)
        );
        
        return false;
    }
    
    // }}}
    // {{{ fetchCommands()
    
    /**
     * Scan through the SVN directory looking for subclasses.
     *
     * @return  mixed    array on success, false on failure
     */
    public function fetchCommands()
    {
        $commands = array();
        $dir = realpath(dirname(__FILE__)) . '/SVN/Command';
        $dp = @opendir($dir);
        if (empty($dp)) {
            PEAR_ErrorStack::staticPush(
                'VersionControl_SVN', VERSIONCONTROL_SVN_ERROR, 'error', 
                array('errstr' => "fetchCommands: opendir($dir) failed")
            );
            
            return false;
        }
        while ($entry = readdir($dp)) {
            if ($entry{0} == '.' || substr($entry, -4) != '.php') {
                continue;
            }
            
            $commands[] = substr($entry, 0, -4);
        }
        
        closedir($dp);
        
        return $commands;
    }
    
    // }}}
    // {{{ apiVersion()
    
    /**
     * Return the VersionControl_SVN API version
     *
     * @return  string  the VersionControl_SVN API version number
     */
    public function apiVersion()
    {
        return '@version@';
    }
    
    // }}}
}

// }}}
?>