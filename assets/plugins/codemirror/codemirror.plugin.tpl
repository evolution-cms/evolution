/**
 * CodeMirror
 *
 * JavaScript library that can be used to create a relatively pleasant editor interface based on CodeMirrir 3.13
 *
 * @events:
 * - OnDocFormRender
 * - OnChunkFormRender
 * - OnModFormRender
 * - OnPluginFormRender
 * - OnSnipFormRender
 * - OnTempFormRender
 * @category    plugin
 * @version     1.2b
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @package     modx
 * @author      hansek from www.modxcms.cz <http://www.modxcms.cz>, update Mihanik71
 * @internal    @events OnDocFormRender,OnChunkFormRender,OnModFormRender,OnPluginFormRender,OnSnipFormRender,OnTempFormRender
 * @internal    @modx_category Manager and Admin
 * @internal    @properties &theme=Theme;list;default,ambiance,blackboard,cobalt,eclipse,elegant,erlang-dark,lesser-dark,midnight,monokai,neat,night,rubyblue,solarized,twilight,vibrant-ink,xq-dark,xq-light; &indentUnit=Indent unit;int;4 &tabSize=The width of a tab character;int;4 &lineWrapping=lineWrapping;list;true,false;true &matchBrackets=matchBrackets;list;true,false;false &activeLine=activeLine;list;true,false;false &emmet=emmet;list;true,false;true &search=search;list;true,false;true
 * @internal    @installset base
 */

$_CM_BASE = 'assets/plugins/codemirror/';

$_CM_URL = $modx->config['site_url'] . $_CM_BASE;

require(MODX_BASE_PATH. $_CM_BASE .'codemirror.plugin.php');
