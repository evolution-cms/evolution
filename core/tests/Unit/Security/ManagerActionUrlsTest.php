<?php

use EvolutionCMS\Traits\Models\ManagerActions;

function managerActionUrlFixture(): object
{
    return new class {
        use ManagerActions;

        public bool $exists = true;

        protected array $managerActionsMap = [
            'id' => [
                'actions.edit' => 108,
                'actions.save' => 109,
                'actions.enable' => 109,
                'actions.disable' => 109,
                'actions.delete' => 110,
                'actions.duplicate' => 111,
                'actions.run' => 112,
            ],
        ];

        public function getKey(): int
        {
            return 7;
        }
    };
}

test('model links to mutating manager actions carry the current session token', function (string $type, int $action) {
    $previous = $_SESSION ?? null;
    $_SESSION = ['_token' => 'current-action-token'];

    try {
        $url = managerActionUrlFixture()->makeUrl($type, false, ['_token' => 'stale-token']);
        parse_str(ltrim($url, '?'), $query);

        expect($query)->toBe(['_token' => 'current-action-token', 'a' => (string) $action, 'id' => '7']);
    } finally {
        if ($previous === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $previous;
        }
    }
})->with([
    ['actions.save', 109],
    ['actions.enable', 109],
    ['actions.disable', 109],
    ['actions.delete', 110],
    ['actions.duplicate', 111],
    ['actions.run', 112],
]);

test('read-only and unknown model actions do not need a session token', function () {
    $model = managerActionUrlFixture();

    expect($model->makeUrl('actions.edit'))->toBe('?a=108&id=7')
        ->and($model->makeUrl('unknown'))->toBe('#');

    $model->exists = false;
    expect($model->makeUrl('actions.run'))->toBe('#');
});
