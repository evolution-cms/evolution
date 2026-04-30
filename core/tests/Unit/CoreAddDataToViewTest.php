<?php

use EvolutionCMS\Core;

function makeCoreForViewDataTest(): Core
{
    return (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
}

function makeViewFactoryForViewDataTest(): object
{
    return new class {
        public array $shared = [];

        public function share($key, $value = null)
        {
            if (is_array($key)) {
                $this->shared = array_merge($this->shared, $key);

                return $key;
            }

            $this->shared[$key] = $value;

            return $value;
        }
    };
}

describe('addDataToView', function () {

    test('it stores data and shares it with the view factory', function () {
        $core = makeCoreForViewDataTest();
        $view = makeViewFactoryForViewDataTest();
        $core->instance('view', $view);

        $result = $core->addDataToView([
            'headline' => 'Shared headline',
            'meta' => ['source' => 'unit-test'],
        ]);

        expect($result)->toBe($core)
            ->and($core->getDataForView())->toMatchArray([
                'headline' => 'Shared headline',
                'meta' => ['source' => 'unit-test'],
            ])
            ->and($view->shared)->toMatchArray([
                'headline' => 'Shared headline',
                'meta' => ['source' => 'unit-test'],
            ]);
    });

    test('it ignores non-array data without clearing existing view data', function () {
        $core = makeCoreForViewDataTest();
        $view = makeViewFactoryForViewDataTest();
        $core->instance('view', $view);

        $core->addDataToView(['headline' => 'Shared headline']);

        $result = $core->addDataToView('not-an-array');

        expect($result)->toBe($core)
            ->and($core->getDataForView())->toMatchArray([
                'headline' => 'Shared headline',
            ])
            ->and($view->shared)->toMatchArray([
                'headline' => 'Shared headline',
            ]);
    });
});
