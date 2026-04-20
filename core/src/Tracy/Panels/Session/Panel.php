<?php namespace EvolutionCMS\Tracy\Panels\Session;

use EvolutionCMS\Tracy\Panels\AbstractPanel;

class Panel extends AbstractPanel
{
    /**
     * getAttributes.
     *
     * @return array
     */
    protected function getAttributes()
    {
        $rows = [];
        if ($this->hasEvolutionCMS() === true && \defined('SESSION_COOKIE_NAME')) {
            $rows = [
                'cookieId' => SESSION_COOKIE_NAME
            ];
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $rows['sessionId'] = session_id();
            $rows['data'] = $_SESSION ?? [];
        }
        return compact('rows');
    }
}
