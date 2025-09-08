<?php namespace EvolutionCMS\Support;

/**
 * menu->Build('id','parent','name','link','alt','onclick','permission','target','divider 1/0','menuindex')
 */

use EvolutionCMS\Interfaces\MenuInterface;

class Menu implements MenuInterface
{
    /**
     * @var array
     */
    public $defaults = array();
    /**
     * @var
     */
    public $menu;
    /**
     * @var
     */
    public $output;

    /**
     * @param $menu
     * @param array $setting
     * @param bool $show
     */
    public function build($menu, $setting = array(), $show = true)
    {
        $this->defaults['outerClass'] = 'nav';
        $this->defaults['parentClass'] = 'dropdown';
        $this->defaults['parentLinkClass'] = 'dropdown-toggle';
        $this->defaults['parentLinkAttr'] = 'data-toggle="dropdown"';
        $this->defaults['parentLinkIn'] = '<b class="caret"></b>';
        $this->defaults['innerClass'] = 'subnav';

        $this->defaults = $setting + $this->defaults;
        $this->structurise($menu);
        $this->output = $this->drawSub('main', 0);
        if ($show) {
            echo $this->output;
        } else {
            return $this->output;
        }
    }

    /**
     * @param array $menu
     */
    public function structurise($menu)
    {
        $new = array();
        foreach ($menu as $key => $row) {
            $data[$key] = $row[9];
        }

        array_multisort($data, SORT_ASC, $menu);

        foreach ($menu as $key => $value) {
            $new[$value[1]][] = $value;
        }

        $this->menu = $new;
    }

    /**
     * @param int $parentid
     * @param int $level
     * @return string
     */
    public function drawSub($parentid, $level)
    {
        $modx = evo();

        $output = '';

        if (isset($this->menu[$parentid])) {

            $ph = array();
            $countChild = 0;
            $itemTpl = '
			<li id="[+id+]" class="[+li_class+]"><a href="[+href+]" alt="[+alt+]" target="[+target+]" onclick="[+onclick+]"[+a_class+] [+LinkAttr+]>[+itemName+]</a>[+DrawSub+]</li>';
            $outerTpl = '<ul id="[+id+]" class="[+class+]">[+output+]</ul>';
            foreach ($this->menu[$parentid] as $key => $value) {
                if ($value[6] !== '') {
                    $permissions = explode(',', $value[6]);
                    foreach ($permissions as $val) {
                        if (!$modx->hasPermission($val)) {
                            continue;
                        }
                    }
                }

                $countChild++;
                $id = $value[0];
                $ph['id'] = $id;
                $ph['li_class'] = $this->getItemClass($id) . $value[10];
                $ph['href'] = $value[3];
                $ph['alt'] = $value[4];
                $ph['target'] = $value[7];
                $ph['onclick'] = $value[5];
                $ph['a_class'] = $this->getLinkClass($id);
                $ph['LinkAttr'] = $this->getLinkAttr($id);
                $ph['itemName'] = $value[2] . $this->getItemName($id);

                $ph['DrawSub'] = '';

                if (isset($this->menu[$id])) {
                    $level++;
                    $ph['DrawSub'] = $this->drawSub($id, $level);
                    $level--;
                    // Optional buttons
                } else {
                    if (isset($value[11]) && !empty($value[11])) {
                        $optionalButton = '';
                        if (is_array($value[11])) {
                            foreach ($value[11] as $opt) {
                                $optionalButton .= '<' . $opt[0] . ' href="' . $opt[1] . '" class="' . $opt[2] . '" onclick="' . $opt[3] . '" title="' . $opt[4] . '">' . $opt[5] . '</' . $opt[0] . '>';
                            }
                        } else {
                            $opt = $value[11];
                            $optionalButton .= '<' . $opt[0] . ' href="' . $opt[1] . '" class="' . $opt[2] . '" onclick="' . $opt[3] . '" title="' . $opt[4] . '">' . $opt[5] . '</' . $opt[0] . '>';
                        }
                        $ph['DrawSub'] = $optionalButton;
                    }
                }

                $output .= $modx->parseText($itemTpl, $ph);
            }

            $ph = array();
            if ($countChild > 0) {
                $ph['id'] = $level == 0 ? $this->defaults['outerClass'] : '';
                $ph['class'] = $level == 0 ? $this->defaults['outerClass'] : $this->defaults['innerClass'];
                $ph['output'] = $output;
                $output = $modx->parseText($outerTpl, $ph);
            }
        }

        return $output;
    }

    /**
     * @param int $id
     * @return string
     */
    public function getLinkClass($id)
    {
        if (isset($this->menu[$id])) {
            return ' class="' . $this->defaults['parentLinkClass'] . '"';
        } else {
            return '';
        }
    }

    /**
     * @param int $id
     * @return string
     */
    public function getLinkAttr($id)
    {
        if (isset($this->menu[$id])) {
            return $this->defaults['parentLinkAttr'];
        } else {
            return '';
        }
    }

    /**
     * @param int $id
     * @return string
     */
    public function getItemClass($id)
    {
        if (isset($this->menu[$id])) {
            return $this->defaults['parentClass'] . ' ';
        } else {
            return '';
        }
    }

    /**
     * @param int $id
     * @return string
     */
    public function getItemName($id)
    {
        if (isset($this->menu[$id])) {
            return $this->defaults['parentLinkIn'];
        } else {
            return '';
        }
    }
}
