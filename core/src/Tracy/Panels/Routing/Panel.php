<?php namespace EvolutionCMS\Tracy\Panels\Routing;

use Illuminate\Support\Arr;
use EvolutionCMS\Tracy\Panels\AbstractPanel;
use ManagerTheme;
use Illuminate\Support\Facades\Route;

class Panel extends AbstractPanel
{
    /**
     * getAttributes.
     *
     * @return array
     */
    protected function getAttributes()
    {
        $rows = [
            'route' => 404,
        ];
        if ($this->hasEvolutionCMS() === true) {

            if ($this->evolution->isBackend()) {
                $action = Arr::get($_REQUEST ?? [], 'a');
                if ($action !== null) {
                    $rows['route'] = 'action: ' . $action;
                } else {
                    $rows['route'] = 'n/a';
                }

                $controller = ManagerTheme::findController($action);
                if($controller !== null && ! \is_int($controller)) {
                    $rows['controller'] = $controller;
                }
            } else {
                $currentRoute = is_readable(EVO_CORE_PATH . 'custom/routes.php')
                    ? Route::getCurrentRoute()
                    : null;

                if (is_null($currentRoute) === false) {
                    $rows = array_merge([
                        'route' => $currentRoute->uri(),
                    ], $currentRoute->getAction());
                }


                if (!empty($this->evolution->documentMethod) && !empty($this->evolution->documentIdentifier)) {
                    $rows['route'] = $this->evolution->documentMethod . ' : ' . $this->evolution->documentIdentifier;
                }

                if (! empty($this->evolution->documentObject)) {
                    $rows['document'] = $this->evolution->documentObject;
                }
            }
        } else {
            $rows['uri'] = empty(Arr::get($_SERVER ?? [], 'HTTP_HOST')) === true ?
                404 : Arr::get($_SERVER ?? [], 'REQUEST_URI');
        }
        return compact('rows');
    }
}
