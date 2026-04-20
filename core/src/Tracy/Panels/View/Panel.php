<?php namespace EvolutionCMS\Tracy\Panels\View;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use EvolutionCMS\Tracy\Panels\AbstractPanel;

class Panel extends AbstractPanel
{
    /**
     * $limit.
     *
     * @var int
     */
    public $limit = 50;
    /**
     * $views.
     *
     * @var array
     */
    protected $views = [];
    /**
     * subscribe.
     */
    protected function subscribe()
    {
        $this->evolution['events']->listen('composing:*', function ($key, $payload) {
            $this->logView($payload[0]);
        });
    }
    /**
     * logView.
     *
     * @param  \Illuminate\Contracts\View\View
     * @return void
     */
    protected function logView($view)
    {
        $name = $view->getName();
        $data = $this->limitCollection(Arr::except($view->gatherData(), ['__env', 'app']));
        $path = static::editorLink($view->getPath());
        preg_match('/href=\"(.+)\"/', $path, $m);
        $path = (count($m) > 1) ? '(<a href="'.$m[1].'">source</a>)' : '';
        $this->views[] = compact('name', 'data', 'path');
    }
    /**
     * limitCollection.
     *
     * @param array $data
     * @return array
     */
    protected function limitCollection($data)
    {
        $results = [];
        foreach ($data as $key => $value) {
            if (is_array($value) === true && count($value) > $this->limit) {
                $value = array_slice($value, 0, $this->limit);
            }
            if ($value instanceof Collection && $value->count() > $this->limit) {
                $value = $value->take($this->limit);
            }
            $results[$key] = $value;
        }
        return $results;
    }
    /**
     * getAttributes.
     *
     * @return array
     */
    protected function getAttributes()
    {
        return [
            'rows' => $this->views,
        ];
    }
}
