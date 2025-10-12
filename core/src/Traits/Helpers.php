<?php namespace EvolutionCMS\Traits;

use Carbon\Carbon;

trait Helpers
{
    abstract public function getConfig($name = '', $default = null);

    /**
     * @param null|int $time
     * @return int
     */
    public function timestamp($time = null) : int
    {
        return ($time === null ? time() : (int)$time) + $this->getConfig('server_offset_time');
    }

    /**
     * @return Carbon
     */
    public function now() : Carbon
    {
        return Carbon::now()->addSeconds(
            evo()->getConfig('server_offset_time')
        );
    }

    public function normalizeFormat($withTime = true) : string
    {
        return str_replace('%', '', $this->toDateFormat(0, 'formatOnly')) .
            ($withTime ? ' H:i:s' : '');
    }
}
