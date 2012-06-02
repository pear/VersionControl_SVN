<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * VersionControl_SVN_Status allows for XML formatted output. XML_Parser is used to
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
 * PHP version 5
 *
 * @category  VersionControl
 * @package   VersionControl_SVN
 * @author    Clay Loveless <clay@killersoft.com>
 * @author    Alexander Opitz <opitz.alexander@gmail.com>
 * @copyright 2004-2007 Clay Loveless
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @link      http://pear.php.net/package/VersionControl_SVN
 */

require_once 'XML/Parser.php';

/**
 * Class VersionControl_SVN_Parser_Status - XML Parser for Subversion status output
 *
 * @category SCM
 * @package  VersionControl_SVN
 * @author   Clay Loveless <clay@killersoft.com>
 * @author   Alexander Opitz <opitz.alexander@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD
 * @version  @version@
 * @link     http://pear.php.net/package/VersionControl_SVN
 */
class VersionControl_SVN_Parser_Status extends XML_Parser
{
    var $commit = array();
    var $reposStatus = array();
    var $wcStatus = array();
    var $entry = array();
    var $path = array();
    var $status = array();

    function startHandler($xp, $element, &$attribs)
    {
        switch ($element) {
        case 'STATUS':
            $this->status = array();
            break;
        case 'TARGET':
            $this->target = array(
                'PATH' => $attribs['PATH'],
            );
            break;
        case 'ENTRY':
            $this->entry = array(
                'PATH' => $attribs['PATH'],
            );
            break;
        case 'WC-STATUS':
            $this->wcStatus = array(
                'ITEM' => $attribs['ITEM'],
                'PROPS' => $attribs['PROPS'],
            );
            if (isset($attribs['REVISION'])) {
                $this->wcStatus['REVISION'] = $attribs['REVISION'];
            }
            break;
        case 'REPOS-STATUS':
            $this->reposStatus = array(
                'ITEM' => $attribs['ITEM'],
                'PROPS' => $attribs['PROPS'],
            );
            break;
        case 'COMMIT':
            $this->commit = array(
                'REVISION' => $attribs['REVISION'],
            );
            break;
        case 'AUTHOR':
        case 'DATE':
            $this->cdata = '';
            break;
        }
    }

    function cdataHandler($xp, $data)
    {
        $this->cdata .= $data;
    }

    function endHandler($xp, $element)
    {
        switch($element) {
        case 'STATUS':
            break;
        case 'TARGET':
            $this->status['TARGET'] = $this->target;
            break;
        case 'ENTRY':
            $this->target['ENTRY'][] = $this->entry;
            break;
        case 'WC-STATUS':
            $this->entry['WC-STATUS'] = $this->wcStatus;
            break;
        case 'COMMIT':
            $this->wcStatus['COMMIT'] = $this->commit;
            break;
        case 'REPOS-STATUS':
            $this->entry['REPOS-STATUS'] = $this->reposStatus;
            break;
        case 'AUTHOR':
        case 'DATE':
            $this->commit[$element] = $this->cdata;
            break;
        }
    }
}
?>