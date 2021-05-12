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
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'VersionControl/SVN/Exception.php';
require_once 'System.php';

/**
 * Ground class for a SVN command.
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Michiel Rook <mrook@php.net>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
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
     * Use exec or passthru to get results from command.
     *
     * @var bool $passthru
     */
    public $passthru = false;

    /**
     * Location of the svn client binary installed as part of Subversion
     *
     * @var string $binaryPath
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
     * @var array $options
     */
    public $options = array();

    /**
     * Command-line arguments that should be passed
     * <b>outside</b> of those specified in {@link switches}.
     *
     * @var array $args
     */
    public $args = array();

    /**
     * Preferred fetchmode. Note that not all subcommands have output available for
     * each preferred fetchmode. The default cascade is:
     *
     * VersionControl_SVN::FETCHMODE_ASSOC
     * VersionControl_SVN::FETCHMODE_RAW
     *
     * If the specified fetchmode isn't available, raw output will be returned.
     * 
     * @var int $fetchmode
     */
    public $fetchmode = VersionControl_SVN::FETCHMODE_ASSOC;

    /**
     * Default username to use for connections.
     *
     * @var string $username
     */
    public $username = null;

    /**
     * Default password to use for connections.
     *
     * @var string $password
     */
    public $password = null;

    /**
     * Default config-dir to use for connections.
     *
     * @var string $configDir
     */
    public $configDir = null;

    /**
     * Default config-option to use for connections.
     *
     * @var string $configOption
     */
    public $configOption = null;

    /**
     * Default no-auth-cache to use for connections.
     *
     * @var string $noAuthCache
     */
    public $noAuthCache = null;

    /**
     * Default trust-server-cert to use for connections.
     *
     * @var string $trustServerCert
     */
    public $trustServerCert = false;

    /**
     * Switches required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var array $requiredSwitches
     */
    protected $requiredSwitches = array();

    /**
     * Minimum number of args required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion},
     * Subversion Complete Reference for details on arguments for this subcommand.
     *
     * @var int $minArgs
     */
    protected $minArgs = 0;

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
     * Useable switches for command with parameters.
     */
    protected $validSwitchesValue = array(
        'username',
        'password',
        'config-dir',
        'config-option',
    );

    /**
     * Useable switches for command without parameters.
     */
    protected $validSwitches = array(
        'no-auth-cache',
        'non-interactive',
        'trust-server-cert',
    );

    /**
     * Constructor. Can't be called directly as class is abstract.
     */
    public function __construct()
    {
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
     * @return VersionControl_SVN_Command Themself.
     * @throws VersionControl_SVN_Exception If option isn't available.
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
                throw new VersionControl_SVN_Exception(
                    '"' . $option . '" is not a valid option',
                    VersionControl_SVN_Exception::INVALID_OPTION
                );
            }
        }

        return $this;
    }

    /**
     * Prepare the command switches.
     *
     * This function should be overloaded by the command class.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If preparing failed.
     */
    public function prepare()
    {
        $this->checkCommandRequirements();
        $this->preProcessSwitches();

        $invalidSwitches = array();
        $cmdParts = array(
            $this->binaryPath,
            $this->commandName
        );

        foreach ($this->switches as $switch => $val) {
            if (1 === strlen($switch)) {
                $switchPrefix = '-';
            } else {
                $switchPrefix = '--';
            }
            if (in_array($switch, $this->validSwitchesValue)) {
                $cmdParts[] = $switchPrefix . $switch . ' ' . $this->escapeshellarg($val);
            } elseif (in_array($switch, $this->validSwitches)) {
                if (true === $val) {
                    $cmdParts[] = $switchPrefix . $switch;
                }
            } else {
                $invalidSwitches[] = $switch;
            }
        }

        $this->postProcessSwitches($invalidSwitches);

        $this->preparedCmd = implode(' ', array_merge($cmdParts, $this->args));
    }

    /**
     * Called after handling switches.
     *
     * @param array $invalidSwitches Invalid switches found while processing.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If switch(s) is/are invalid.
     */
    protected function postProcessSwitches($invalidSwitches)
    {
        $invalid = count($invalidSwitches);
        if ($invalid > 0) {
            $invalides = implode(',', $invalidSwitches);
            if ($invalid > 1) {
                $error = '"' . $invalides . '" are invalid switches';
            } else {
                $error = '"' . $invalides . '" is a invalid switch';
            }
            $error .= ' for class "' . get_class($this) . '".';
            throw new VersionControl_SVN_Exception(
                $error,
                VersionControl_SVN_Exception::INVALID_SWITCH
            );
        }
    }


    /**
     * Called before handling switches.
     *
     * @return void
     */
    protected function preProcessSwitches()
    {
        if ($this->xmlAvail
            && ($this->fetchmode == VersionControl_SVN::FETCHMODE_ARRAY
            || $this->fetchmode == VersionControl_SVN::FETCHMODE_ASSOC
            || $this->fetchmode == VersionControl_SVN::FETCHMODE_OBJECT
            || $this->fetchmode == VersionControl_SVN::FETCHMODE_XML)
        ) {
            $this->switches['xml'] = true;
        } else {
            unset($this->switches['xml']);
        }

        $this->switches['non-interactive'] = true;

        $this->fillSwitch('username', $this->username);
        $this->fillSwitch('password', $this->password);
        $this->fillSwitch('config-dir', $this->configDir);
        $this->fillSwitch('config-option', $this->configOption);
        $this->fillSwitch('no-auth-cache', $this->noAuthCache);
        $this->fillSwitch('trust-server-cert', $this->trustServerCert);
    }

    /**
     * Fills the switches array on given name with value if not already set and value is not null.
     *
     * @param string $switchName Name of the switch.
     * @param string $value      Value for the switch.
     *
     * @return void
     */
    protected function fillSwitch($switchName, $value)
    {
        if (!isset($this->switches[$switchName])
            && null !== $value
        ) {
            $this->switches[$switchName] = $value;
        }
    }


    /**
     * Standardized validation of requirements for a command class.
     *
     * @return void
     * @throws VersionControl_SVN_Exception If command requirements not resolved.
     */
    public function checkCommandRequirements()
    {
        // Check for minimum arguments
        if (count($this->args) < $this->minArgs) {
            throw new VersionControl_SVN_Exception(
                'svn command requires at least ' . $this->minArgs . ' argument(s)',
                VersionControl_SVN_Exception::MIN_ARGS
            );
        }

        // Check for presence of required switches
        if (!empty($this->requiredSwitches)) {
            $missing    = array();
            $switches   = $this->switches;
            $reqsw      = $this->requiredSwitches;
            foreach ($reqsw as $req) {
                $found = false;
                $good_switches = explode('|', $req);
                foreach ($good_switches as $gsw) {
                    if (isset($switches[$gsw])) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $missing[] = '(' . $req . ')';
                }
            }
            $num_missing = count($missing);
            if ($num_missing > 0) {
                throw new VersionControl_SVN_Exception(
                    'svn command requires the following switch(es): ' . implode(', ', $missing),
                    VersionControl_SVN_Exception::SWITCH_MISSING
                );
            }
        }
    }

    /**
     * Run the command with the defined switches.
     *
     * @param array $args     Arguments to pass to Subversion
     * @param array $switches Switches to pass to Subversion
     *
     * @return mixed $fetchmode specified output on success.
     * @throws VersionControl_SVN_Exception If command failed.
     */
    public function run($args = array(), $switches = array())
    {
        if (!file_exists($this->binaryPath)) {
            $system = new System();
            $this->binaryPath = $system->which('svn');
        }

        if (!empty($switches)) {
            $this->switches = $switches;
        }
        $this->args = array_map(array($this, 'escapeshellarg'), $args);

        // Always prepare, allows for obj re-use. (Request #5021)
        $this->prepare();

        $out       = array();
        // @var integer $returnVar Return number from shell execution.
        $returnVar = null;

        $cmd = $this->preparedCmd;

        // On Windows, don't use escapeshellcmd, and double-quote $cmd
        // so it's executed as
        // cmd /c ""C:\Program Files\SVN\bin\svn.exe" info "C:\Program Files\dev\trunk""
        if (0 === stripos(PHP_OS, 'WIN')) {
            $cmd = str_replace(
                $this->binaryPath,
                $this->escapeshellarg(str_replace('/', '\\', $this->binaryPath)),
                $cmd
            );

            if (!$this->passthru) {
                exec("cmd /c \"$cmd 2>&1\"", $out, $returnVar);
            } else {
                passthru("cmd /c \"$cmd 2>&1\"", $returnVar);
            }
        } else {
            if ($this->useEscapeshellcmd) {
                $cmd = escapeshellcmd($cmd);
            }
            if (!$this->passthru) {
                exec("{$this->prependCmd}$cmd 2>&1", $out, $returnVar);
            } else {
                passthru("{$this->prependCmd}$cmd 2>&1", $returnVar);
            }
        }

        if ($returnVar > 0) {
            throw new VersionControl_SVN_Exception(
                'Execution of command failed returning: ' . $returnVar
                . "\n" . implode("\n", $out),
                VersionControl_SVN_Exception::EXEC
            );
        }

        return $this->parseOutput($out);
    }

    /**
     * Handles output parsing of standard and verbose output of command.
     *
     * @param array $out Array of output captured by exec command in {@link run}
     *
     * @return mixed Returns output requested by fetchmode (if available), or
     *               raw output if desired fetchmode is not available.
     */
    public function parseOutput($out)
    {
        switch($this->fetchmode) {
            case VersionControl_SVN::FETCHMODE_ARRAY:
            case VersionControl_SVN::FETCHMODE_ASSOC:
            case VersionControl_SVN::FETCHMODE_OBJECT:
                $class = 'VersionControl_SVN_Parser_XML_' . ucfirst($this->commandName);
                if (class_exists($class)) {
                    $class = 'VersionControl_SVN_Parser_XML_'
                        . ucfirst($this->commandName);

                    $parser = new $class;

                    $parsedData = $parser->getParsed(implode("\n", $out));
                    if ($this->fetchmode == VersionControl_SVN::FETCHMODE_OBJECT) {
                        return (object) $parsedData;
                    }
                    return $parsedData;
                } else {
                    throw new VersionControl_SVN_Exception(sprintf(
                        "Could not find parser for command output: '%s'",
                        $this->commandName
                    ), VersionControl_SVN_Exception::ERROR);
                }
                break;
            case VersionControl_SVN::FETCHMODE_RAW:
            case VersionControl_SVN::FETCHMODE_XML:
            default:
                // What you get with VersionControl_SVN::FETCHMODE_DEFAULT
                return implode("\n", $out);
                break;
        }
    }

    /**
     * Escape a single value in accordance with CommandLineToArgV() for Windows
     *
     * @param string $value
     * @return string
     * @throws VersionControl_SVN_Exception If command failed.
     * @see https://docs.microsoft.com/en-us/previous-versions/17w5ykft(v=vs.85)
     */
    private function escapeshellarg($value)
    {
        $value = (string)$value;
        if (0 === stripos(PHP_OS, 'WIN')) {
            static $expr = '(
			[\x00-\x20\x7F"] # control chars, whitespace or double quote
		  | \\\\++ (?=("|$)) # backslashes followed by a quote or at the end
		)ux';

            if ($value === '') {
                return '""';
            }

            $quote = false;
            $replacer = function($match) use($value, &$quote) {
                switch ($match[0][0]) { // only inspect the first byte of the match

                    case '"': // double quotes are escaped and must be quoted
                        $match[0] = '\\"';
                    case ' ': case "\t": // spaces and tabs are ok but must be quoted
                    $quote = true;
                    return $match[0];

                    case '\\': // matching backslashes are escaped if quoted
                        return $match[0] . $match[0];

                    default: throw new VersionControl_SVN_Exception(sprintf(
                        "Invalid byte at offset %d: 0x%02X",
                        strpos($value, $match[0]), ord($match[0])
                    ), VersionControl_SVN_Exception::ERROR);
                }
            };

            $escaped = preg_replace_callback($expr, $replacer, (string)$value);

            if ($escaped === null) {
                throw preg_last_error() === PREG_BAD_UTF8_ERROR
                    ? new VersionControl_SVN_Exception("Invalid UTF-8 string", VersionControl_SVN_Exception::ERROR)
                    : new VersionControl_SVN_Exception("PCRE error: " . preg_last_error(), VersionControl_SVN_Exception::ERROR);
            }

            return $quote // only quote when needed
                ? '"' . $escaped . '"'
                : $value;

        } else {
            return escapeshellarg($value);
        }
    }
}
?>
