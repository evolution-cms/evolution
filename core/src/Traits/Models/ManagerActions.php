<?php namespace EvolutionCMS\Traits\Models;

trait ManagerActions
{
    public function getManagerActionsMap() : array
    {
        return property_exists($this, 'managerActionsMap') ? $this->managerActionsMap : [];
    }

    public function makeUrl($type, bool $full = false, array $options = []) :string
    {
        $map = $this->getManagerActionsMap();
        switch (true) {
            case isset($map[$type]) && is_scalar($map[$type]):
                $out = '?' . http_build_query(array_merge($options, [
                        'a' => $map[$type]
                    ]));
                break;
            case $this->exists && isset($map['id'][$type]) &&
                is_scalar($map['id'][$type]):
                $out = '?' . http_build_query(array_merge($options, [
                        'a' => $map['id'][$type],
                        'id' => $this->getKey()
                    ]));
                break;
            default:
                $out = '#';
        }

        return ($full ? EVO_MANAGER_URL : '') . $out;
    }
}
