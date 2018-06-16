<?php

/**
 * @deprecated use EvolutionCMS\Support\Menu
 */
class EVOmenu extends EvolutionCMS\Support\Menu
{
    public function Build($menu, $setting = array())
    {
        parent::build($menu, $setting);
    }

    /**
     * @param array $menu
     */
    public function Structurise($menu)
    {
        parent::structurise($menu);
    }

    /**
     * @param int $parentid
     * @param int $level
     * @return string
     */
    public function DrawSub($parentid, $level)
    {

        return parent::drawSub($parentid, $level);
    }

    /**
     * @param int $id
     * @return string
     */
    public function get_li_class($id)
    {
        return parent::getItemClass($id);
    }

    /**
     * @param int $id
     * @return string
     */
    public function get_a_class($id)
    {
        return parent::getLinkClass($id);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getLinkAttr($id)
    {
        return parent::getLinkAttr($id);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getItemName($id)
    {
        return parent::getItemName($id);
    }
}
