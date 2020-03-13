<?php
/**
 * site_content_tags controller with TagSaver plugin
 * @see http://modx.im/blog/addons/374.html
 *
 * @category controller
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 */

include_once(dirname(__FILE__) . "/site_content.php");

/**
 * Class site_content_tagsDocLister
 */
class site_content_tagsDocLister extends site_contentDocLister
{
    /**
     * @var array
     */
    private $tag = array();

    /**
     * site_content_tagsDocLister constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     * @param null $startTime
     */
    public function __construct($modx, $cfg = array(), $startTime = null)
    {
        parent::__construct($modx, $cfg, $startTime);
        $this->whereTag();
    }

    /**
     * @abstract
     */
    public function getUrl($id = 0)
    {
        $id = ((int)$id > 0) ? (int)$id : $this->getCurrentMODXPageID();

        $link = $this->checkExtender('request') ? $this->extender['request']->getLink() : "";
        $tag = $this->checkTag();
        if ($tag !== false && is_array($tag) && $tag['mode'] == 'get') {
            if (is_array($tag['tag'])) {
                $tag['tag'] = implode($this->getCFGDef('tagsSeparator', '||'), $tag['tag']);
            }
            $link .= "&tag=" . urlencode($tag['tag']);
        }
        $url = ($id == $this->modx->config['site_start']) ? $this->modx->config['site_url'] . ($link != '' ? "?{$link}" : "") : $this->modx->makeUrl(
            $id,
            '',
            $link,
            'full'
        );

        return $url;
    }

    /**
     * @return array|bool
     */
    private function getTag()
    {
        $tags = $this->getCFGDef('tagsData', '');
        $this->tag = array();
        if ($tags != '') {
            $tmp = explode(":", $tags, 2);
            if (count($tmp) == 2) {
                switch ($tmp[0]) {
                    case 'get':
                        $tag = (isset($_GET[$tmp[1]]) && !is_array($_GET[$tmp[1]])) ? $_GET[$tmp[1]] : '';
                        break;
                    case 'static':
                    default:
                        $tag = $tmp[1];
                        break;
                }

                $separator = $this->getCFGDef('tagsSeparator', '||');
                if (!empty($tag) && !empty($separator)) {
                    $_tag = array_map('trim', explode($separator, $tag));
                    if (count($_tag) > 1) {
                        $tag = $_tag;
                    }
                }

                $this->tag = array("mode" => $tmp[0], "tag" => $tag);
                $this->toPlaceholders($this->sanitarData($tag), 1, "tag");
            }
        }

        return $this->checkTag();
    }

    /**
     * @param bool $reconst
     * @return array|bool
     */
    private function checkTag($reconst = false)
    {
        $data = (is_array($this->tag) && count($this->tag) == 2 && isset($this->tag['tag']) && $this->tag['tag'] != '') ? $this->tag : false;
        if ($data === false && $reconst === true) {
            $data = $this->getTag();
        }

        return $data;
    }

    /**
     * @return array|mixed
     */
    private function whereTag()
    {
        $tag = $this->checkTag(true);
        if ($tag !== false) {
            $join = "RIGHT JOIN " . $this->getTable('site_content_tags', 'ct') . " on ct.doc_id=c.id
					RIGHT JOIN " . $this->getTable('tags', 't') . " on t.id=ct.tag_id";
            if (is_array($tag['tag'])) {
                $where = "t.`name` IN (" . $this->sanitarIn($tag['tag']) . ")";
            } else {
                $where = "t.`name`='" . $this->modx->db->escape($tag['tag']) . "'";
            }
            $where .= ($this->getCFGDef('tagsData', '') > 0) ? "AND ct.tv_id=" . (int)$this->getCFGDef(
                'tagsData',
                ''
            ) : "";

            if (!empty($this->_filters['where'])) {
                $this->_filters['where'] .= " AND " . $where;
            } else {
                $this->_filters['where'] = $where;
            }

            if (!empty($this->_filters['join'])) {
                $this->_filters['join'] .= ' ' . $join;
            } else {
                $this->_filters['join'] = $join;
            }
        }

        return $this->_filters;
    }

}
