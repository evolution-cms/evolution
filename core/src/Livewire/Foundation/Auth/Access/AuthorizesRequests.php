<?php

namespace EvolutionCMS\Livewire\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;

trait AuthorizesRequests
{
    public function authorize($ability, $arguments = [])
    {
        return $this->getGate()->authorize($ability, $arguments);
    }

    public function authorizeForUser($user, $ability, $arguments = [])
    {
        return $this->getGate()->forUser($user)->authorize($ability, $arguments);
    }

    protected function getGate(): Gate
    {
        if (function_exists('app')) {
            $app = app();
            if (is_object($app) && method_exists($app, 'make')) {
                return $app->make(Gate::class);
            }
        }

        throw new \RuntimeException('Gate contract is not bound.');
    }
}
