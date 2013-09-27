//<?php
/**
 * TransAlias
 *
 * Human readible URL translation supporting multiple languages and overrides
 *
 * @category    plugin
 * @version     1.0.2
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @package     modx
 * @subpackage  modx.plugins.transalias
 * @author      Olivier B. Deland, additions by Mike Schell, rfoster
 * @internal    @properties &table_name=Trans table;list;common,russian,dutch,german,czech,utf8,utf8lowercase;utf8lowercase &char_restrict=Restrict alias to;list;lowercase alphanumeric,alphanumeric,legal characters;legal characters &remove_periods=Remove Periods;list;Yes,No;No &word_separator=Word Separator;list;dash,underscore,none;dash &override_tv=Override TV name;string;
 * @internal    @events OnStripAlias
 * @internal    @modx_category Manager and Admin
 * @internal    @installset base, sample
 */

require MODX_BASE_PATH.'assets/plugins/transalias/plugin.transalias.php';