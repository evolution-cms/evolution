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
                $parameters = array_merge($options, [
                        'a' => $map[$type]
                    ]);
                break;
            case $this->exists && isset($map['id'][$type]) &&
                is_scalar($map['id'][$type]):
                $parameters = array_merge($options, [
                        'a' => $map['id'][$type],
                        'id' => $this->getKey()
                    ]);
                break;
            default:
                $parameters = null;
        }

        if ($parameters !== null && in_array($type, [
            'actions.save', 'actions.enable', 'actions.disable',
            'actions.delete', 'actions.duplicate', 'actions.run',
        ], true)) {
            $parameters['_token'] = csrf_token();
        }

        $out = $parameters === null ? '#' : '?' . http_build_query($parameters);

        return ($full ? EVO_MANAGER_URL : '') . $out;
    }
}
