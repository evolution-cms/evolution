<?php namespace EvolutionCMS\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\PaginationServiceProvider as IlluminatePaginationServiceProvider;

class PaginationServiceProvider extends IlluminatePaginationServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        Paginator::viewFactoryResolver(function () {
            return $this->app['view'];
        });

        Paginator::currentPathResolver(function () {
            return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        });

        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = get_by_key($_REQUEST, $pageName, 1, 'is_scalar');

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });
    }
}
