<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * VersionControl_SVN_Info allows for XML formatted output. XML_Parser is used to
 * manipulate that output.
 *
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
 * PHP version 5
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @link      http://pear.php.net/package/VersionControl_SVN
 */


/**
 * Ground class for a SVN command.
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   @version@
 * @link      http://pear.php.net/package/VersionControl_SVN
 */
abstract class VersionControl_SVN_Command
{
    /**
     * Indicates whether commands passed to the
     * {@link http://www.php.net/exec exec()} function in the
     * {@link run} method should be passed through
     * {@link http://www.php.net/escapeshellcmd escapeshellcmd()}.
     * NOTE: this variable is ignored on Windows machines!
     *
     * @var boolean $useEscapeshellcmd
     */
    public $useEscapeshellcmd = true;

    /**
     * Location of the svn client binary installed as part of Subversion
     *
     * @var string  $binaryPath
     */
    public $binaryPath = '/usr/local/bin/svn';

    /**
     * String to prepend to command string. Helpful for setting exec() 
     * environment variables, such as: 
     *    export LANG=en_US.utf8 &&
     * ... to support non-ASCII file and directory names.
     * 
     * @var string $prependCmd
     */
    public $prependCmd = '';

    /**
     * Array of switches to use in building svn command
     *
     * @var array $switches
     */
    public $switches = array();

    /**
     * Runtime options being used. 
     *
     * @var array
     */
    public $options = array();
    
    /**
     * Preferred fetchmode. Note that not all subcommands have output available for 
     * each preferred fetchmode. The default cascade is:
     *
     * VERSIONCONTROL_SVN_FETCHMODE_ASSOC
     *  VERSIONCONTROL_SVN_FETCHMODE_RAW
     *
     * If the specified fetchmode isn't available, raw output will be returned.
     * 
     * @var int
     */
    public $fetchmode = VERSIONCONTROL_SVN_FETCHMODE_ASSOC;

    /**
     * SVN subcommand to run.
     * 
     * @var string $commandName
     */
    protected $commandName = '';

    /**
     * Fully prepared command string.
     * 
     * @var string $preparedCmd
     */
    protected $preparedCmd = '';

    /**
     * Keep track of whether XML output is available for a command
     *
     * @var boolean $xmlAvail
     */
    protected $xmlAvail = false;

    /**
     * Error stack.
     *
     * @var PEAR_ErrorStack $errorStack
     */
    protected $errorStack = null;

    protected $validSwitchesValue = array(
        'username',
        'password',
    );

    protected $validSwitchesLong = array(
        'no-auth-cache',
        'non-interactive',
        'trust-server-cert',
        'config-dir',
        'config-option',
    );

    protected $validSwitchesShort = array(
    );

    public function __construct()
    {
        $this->errorStack = PEAR_ErrorStack::singleton('VersionControl_SVN');
        $this->errorStack->setErrorMessageTemplate(
            VersionControl_SVN::declareErrorMessages()
        );
        $className = get_class($this);
        $this->commandName = strtolower(
            substr(
                $className,
                strrpos($className, '_') + 1
            )
        );
    }

    /**
     * Allow for overriding of previously declared options.     
     *
     * @param array $options An associative array of option names and
     *                       their values
     *
     * @return boolean
     */
    public function setOptions($options = array())
    {
        $class = new ReflectionClass($this);

        foreach ($options as $option => $value) {
            try {
                $property = $class->getProperty($option);
            } catch (ReflectionException $e) {
                $property = null;
            }
            if (null !== $property && $property->isPublic()) {
                $this->$option = $value;
            } else {
                $this->errorStack->push(
                    VERSIONCONTROL_SVN_NOTICE_INVALID_OPTION, 'notice', 
                    array('option' => $option)
                );
            }
        }
        
        return true;
    }

    /**
     * Prepare the command switches.
     *
     * This function should be overloaded by the command class.
     *
     * @return boolean
     */
    public function prepare()
    {
        if (!$this->checkCommandRequirements()) {
            return false;
        }
        $this->preProcessSwitches();

        $invalidSwitches = array();
        $cmdParts = array(
            $this->binaryPath,
            $this->commandName
        );

        foreach ($this->switches as $switch => $val) {
            if (in_array($switch, $this->validSwitchesValue)) {
                $cmdParts[] = '--' . $switch . ' ' . $val;
            } elseif (in_array($switch, $this->validSwitchesLong)) {
                if (true === $val) {
                    $cmdParts[] = '--' . $switch;
                }
            } elseif (in_array($switch, $this->validSwitchesShort)) {
                if (true === $val) {
                    $cmdParts[] = '-' . $switch;
                }
            } else {
                $invalidSwitches[] = $switch;
            }
        }

        $this->postProcessSwitches($invalidSwitches);

        $this->preparedCmd = implode(
            ' ' , array_merge($cmdParts, $this->args)
        );

        return true;
    }

    protected function postProcessSwitches($invalidSwitches)
    {
        $invalid = count($invalidSwitches);
        if ($invalid > 0) {
            $params['was'] = 'was';
            $params['is_invalid_switch'] = 'is an invalid switch';
            if ($invalid > 1) {
                $params['was'] = 'were';
                $params['is_invalid_switch'] = 'are invalid switches';
            }
            $params['list'] = $invalidSwitches;
            $params['switches'] = $this->switches;
            $params['commandClass'] = get_class($this);
            $this->errorStack->push(
                VERSIONCONTROL_SVN_NOTICE_INVALID_SWITCH, 'notice', $params
            );
        }
    }

    protected function preProcessSwitches()
    {
        if ($this->xmlAvail
            && ($this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_ARRAY
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_ASSOC
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_OBJECT
            || $this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_XML))
        {
            $this->switches['xml'] = true;
        }
        $this->switches['non-interactive'] = true;
    }


    /**
     * Standardized validation of requirements for a command class.
     *
     * @return mixed   true if all requirements are met, false if 
     *                  requirements are not met. Details of failures
     *                  are pushed into the PEAR_ErrorStack for VersionControl_SVN
     */
    public function checkCommandRequirements()
    {
        // Set up error push parameters to avoid any notices about undefined indexes
        $params['options']     = $this->options;
        $params['switches']    = $this->switches;
        $params['args']        = $this->args;
        $params['commandName'] = $this->commandName;
        $params['cmd']         = '';
        
        // Check for minimum arguments
        if (sizeof($this->args) < $this->min_args) {
            $params['argstr'] = $this->min_args > 1 ? 'arguments' : 'argument';
            $params['min_args'] = $this->min_args;
            $this->errorStack->push(VERSIONCONTROL_SVN_ERROR_MIN_ARGS, 'error', $params);
            return false;
        }
        
        // Check for presence of required switches
        if (sizeof($this->required_switches) > 0) {
            $missing    = array();
            $switches   = $this->switches;
            $reqsw      = $this->required_switches;
            foreach ($reqsw as $req) {
                $found = false;
                $good_switches = explode('|', $req);
                foreach ($good_switches as $gsw) {
                    if (isset($switches[$gsw])) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $missing[] = '('.$req.')';
                }
            }
            $num_missing = count($missing);
            if ($num_missing > 0) {
                $params['switchstr'] = $num_missing > 1 ? 'switches' : 'switch';
                $params['missing'] = $missing;
                
                $this->errorStack->push(
                    VERSIONCONTROL_SVN_ERROR_REQUIRED_SWITCH_MISSING,
                    'error',
                    $params
                );
                
                return false;
            }
        }
        return true;
    }

    /**
     * Run the command with the defined switches.
     *
     * @param array $args     Arguments to pass to Subversion
     * @param array $switches Switches to pass to Subversion
     *
     * @return  mixed   $fetchmode specified output on success,
     *                  or false on failure.
     */
    public function run($args = array(), $switches = array())
    {
        if (!file_exists($this->binaryPath)) {
            $this->binaryPath = System::which('svn');
        }
        
        if (sizeof($switches) > 0) {
            $this->switches = $switches;
        }
        if (sizeof($args) > 0) {
            foreach (array_keys($args) as $k) {
                $this->args[$k] = escapeshellarg($args[$k]);
            }
        }
        
        // Always prepare, allows for obj re-use. (Request #5021)
        $this->prepare();
        
        $out        = array();
        $ret_var    = null;
        
        $cmd = $this->preparedCmd;

        // On Windows, don't use escapeshellcmd, and double-quote $cmd
        // so it's executed as 
        // cmd /c ""C:\Program Files\SVN\bin\svn.exe" info "C:\Program Files\dev\trunk""
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = str_replace($this->binaryPath, escapeshellarg($this->binaryPath), $cmd);
            
            if (!$this->passthru) {
                exec("$cmd 2>&1", $out, $ret_var);
            } else {
                passthru("$cmd 2>&1", $ret_var);
            }
        } else {
            if ($this->useEscapeshellcmd) {
                $cmd = escapeshellcmd($cmd);
            }
var_dump($cmd);
            if (!$this->passthru) {
                exec("{$this->prependCmd}$cmd 2>&1", $out, $ret_var);
            } else {
                passthru("{$this->prependCmd}$cmd 2>&1", $ret_var);
            }
        }

        if ($ret_var > 0) {
            $params['options']  = $this->options;
            $params['switches'] = $this->switches;
            $params['args']     = $this->args;
            $params['cmd']      = $cmd;
            foreach ($out as $line) {
                $params['errstr'] = $line;
                $this->errorStack->push(VERSIONCONTROL_SVN_ERROR_EXEC, 'error', $params);
            }
            return false;
        }

        return $this->parseOutput($out);
    }

    /**
     * Handles output parsing of standard and verbose output of command.
     *
     * @param array $out Array of output captured by exec command in {@link run}
     *
     * @return  mixed   Returns output requested by fetchmode (if available), or 
     *                  raw output if desired fetchmode is not available.
     */
    public function parseOutput($out)
    {
        $dir = realpath(dirname(__FILE__)) . '/Parsers';
        switch($this->fetchmode) {
            case VERSIONCONTROL_SVN_FETCHMODE_ARRAY:
            case VERSIONCONTROL_SVN_FETCHMODE_ASSOC:
            case VERSIONCONTROL_SVN_FETCHMODE_OBJECT:
                $file = $dir . '/' . ucfirst($this->commandName) . '.php';
                if (file_exists($file)) {
                    $class = 'VersionControl_SVN_Parser_'
                        . ucfirst($this->commandName);

                    include_once $file;
                    $parser = new $class;

                    $result = $parser->parseString(join("\n", $out));
                    if ($this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_OBJECT) {
                        return (object) $result;
                    }
                    return $result;
                    break;
                }
            case VERSIONCONTROL_SVN_FETCHMODE_RAW:
            case VERSIONCONTROL_SVN_FETCHMODE_XML:
            default:
                // What you get with VERSIONCONTROL_SVN_FETCHMODE_DEFAULT
                return join("\n", $out);
                break;
        }
    }
}
?>
