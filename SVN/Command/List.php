<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
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
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'VersionControl/SVN/Command.php';

/**
 * Subversion List command manager class
 *
 * List directory entries in the repository
 *
 * If the 'target' option is omitted, '.' is assumed, meaning the
 * repository URL of the current working directory.
 *
 * $switches is an array containing one or more command line options
 * defined by the following associative keys:
 *
 * <code>
 *
 * array(
 *  'r [revision]'  =>  'ARG (some commands also take ARG1:ARG2 range)
 *                        A revision argument can be one of:
 *                           NUMBER       revision number
 *                           "{" DATE "}" revision at start of the date
 *                           "HEAD"       latest in repository
 *                           "BASE"       base rev of item's working copy
 *                           "COMMITTED"  last commit at or before BASE
 *                           "PREV"       revision just before COMMITTED',
 *                      // either 'r' or 'revision' may be used
 *  'v [verbose]'   =>  true|false,
 *                      // prints extra information
 *  'R'             =>  true|false,
 *                      // descend recursively
 *  'recursive'     =>  true|false,
 *                      // descend recursively
 *  'username'      =>  'Subversion repository login',
 *  'password'      =>  'Subversion repository password',
 *  'no-auth-cache' =>  true|false,
 *                      // Do not cache authentication tokens
 *  'config-dir'    =>  'Path to a Subversion configuration directory'
 * );
 *
 * </code>
 *
 * With the 'verbose' option set to true, the following fields show the
 * status of the item:
 * 
 *     Revision number of the last commit
 *     Author of the last commit
 *     Size (in bytes)
 *     Date and time of the last commit
 * 
 *
 * Usage example:
 * <code>
 * <?php
 * require_once 'VersionControl/SVN.php';
 *
 * // Setup error handling -- always a good idea!
 * $svnstack = &PEAR_ErrorStack::singleton('VersionControl_SVN');
 *
 * // Set up runtime options. Will be passed to all 
 * // subclasses.
 * $options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW);
 *
 * // Pass array of subcommands we need to factory
 * $svn = VersionControl_SVN::factory(array('list'), $options);
 *
 * // Define any switches and aguments we may need
 * $switches = array('R' => true, 'username' => 'user', 'password' => 'pass');
 * $args = array('svn://svn.example.com/repos/TestProject');
 *
 * // Run command
 * if ($output = $svn->list->run($args, $switches)) {
 *     print_r($output);
 * } else {
 *     if (count($errs = $svnstack->getErrors())) { 
 *         foreach ($errs as $err) {
 *             echo '<br />'.$err['message']."<br />\n";
 *             echo "Command used: " . $err['params']['cmd'];
 *         }
 *     }
 * }
 * ?>
 * </code>
 *
 * Note: Subversion does not offer an XML output option for this subcommand
 *
 * @category VersionControl
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  @version@
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Command_List extends VersionControl_SVN_Command
{
    /**
     * Command-line arguments that should be passed 
     * <b>outside</b> of those specified in {@link switches}.
     *
     * @var     array
     * @access  public
     */
    var $args = array();
    
    /**
     * Minimum number of args required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion}, 
     * Subversion Complete Reference for details on arguments for this subcommand.
     * @var     int
     * @access  public
     */
    var $min_args = 0;
    
    /**
     * Switches required by this subcommand.
     * See {@link http://svnbook.red-bean.com/svnbook/ Version Control with Subversion}, 
     * Subversion Complete Reference for details on arguments for this subcommand.
     * @var     array
     * @access  public
     */
    var $required_switches = array();

    /**
     * Keep track of whether XML output is available for a command
     *
     * @var boolean $xmlAvail
     */
    protected $xmlAvail = true;

    /**
     * Constuctor of command. Adds available switches.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validSwitchesValue = array_merge(
            $this->validSwitchesValue,
            array(
                'r', 'revision',
                'depth',
            )
        );

        $this->validSwitches = array_merge(
            $this->validSwitches,
            array(
                'v', 'verbose',
                'R', 'recursive',
                'incremental',
                'xml',
            )
        );
    }

    /**
     * Helper method for parseOutput that parses output into an associative or numbered array
     *
     * @param   array   $items  Item list from the svn list command (already split into an array
     *                          by exec)
     * @return  array
     * @access  public
     */
    function parseOutputArray($items)
    {
        $parsed = array();
        
        // check switches for verbose output
        $verbose = false;
        if ((isset($this->switches['v']) && $this->switches['v'] === true) ||
            (isset($this->switches['verbose']) && $this->switches['verbose'] === true)) {
            $verbose = true;   
        }
            

        if ($verbose) {
            // Must trim off verbose information PRIOR to natcasesort
            $path_items = array();
            $item_vdata = array();
            foreach ($items as $item) {
                // Regex should work with svn list's "%b %d %H:%M" and "%b %d  %Y" date formats
                preg_match("/\s*(\d+) \s?(\S+)\s+(\d+)? (\w{3} +\d{2} +\d{2}:?\d{2}) (.*)/", $item, $matches);
                $path_items[] = $matches[5];
                $item_vdata[] = array(
                                'revision'  => $matches[1],
                                'author'    => $matches[2],
                                'size'      => $matches[3],
                                'date'      => $matches[4]
                              );
            }
            $items = $path_items;
        }
        natcasesort($items);
        
        if ($this->fetchmode == VERSIONCONTROL_SVN_FETCHMODE_ASSOC) {
            $branch_arrays = array();
            foreach ($items as $key => $path) {
                $dir = dirname($path);
                $branches = explode('/', $path);
                $branch = array();
                $last = end($branches);
                $type = 'F';
                if ($last == '') {
                    // Directories have an empty item in the last array slot
                    $type = 'D';
                    $name = prev($branches);
                }
                foreach ($branches as $leaf) {
                    if ($leaf == $last) {
                        if ($type == 'D') {
                            $branch[$dir] = array('name' => array($name), 'type' => array($type));
                        } else {
                            $branch[$dir] = array('name' => array($leaf), 'type' => array($type));
                        }
                    } else {
                        $branch[$dir] = array('name' => array($leaf), 'type' => array('D'));
                    }
                    if ($verbose) {
                        $branch[$dir] = array_merge($branch[$dir], $item_vdata[$key]);
                    }
                }
                $branch_arrays[] = $branch;
            }
            
            foreach ($branch_arrays as $branch) {
                $parsed = array_merge_recursive($parsed, $branch);
            }
        } else {
            foreach ($items as $key => $path) {
                $item = array();
                if (substr($path, -1) == '/') {
                    $item['type'] = 'D';
                    $path = substr($path, 0, -1);
                } else {
                    $item['type'] = 'F';
                }
                $item['name'] = $path;
                if ($verbose) {
                    $item = array_merge($item, $item_vdata[$key]);
                }
                $parsed[] = $item;
            }
        }
        
        return $parsed;
    }
    
}

// }}
?>