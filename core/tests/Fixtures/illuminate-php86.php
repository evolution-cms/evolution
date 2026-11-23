<?php

// An independent runtime process: all diagnostics are failures, including dependency loading.
error_reporting(E_ALL);
set_error_handler(static function (int $level, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require dirname(__DIR__, 2) . '/vendor/autoload.php';

if (($argv[1] ?? '') === 'strings') {
    $results = [];
    foreach (json_decode(file_get_contents(__DIR__ . '/illuminate-strings-laravel12.json'), true, flags: JSON_THROW_ON_ERROR) as $case) {
        $input = $case['input'];
        $results[] = [
            'input' => $input,
            'studly' => Illuminate\Support\Str::studly($input),
            'headline' => Illuminate\Support\Str::headline($input),
            'initials' => Illuminate\Support\Str::initials($input),
            'initialsUpper' => Illuminate\Support\Str::initials($input, true),
            'apa' => Illuminate\Support\Str::apa($input),
        ];
    }
    echo json_encode($results, JSON_THROW_ON_ERROR);
    exit;
}

if (($argv[1] ?? '') === 'objects') {
    $container = new Illuminate\Container\Container();
    $container->bind('nested', static fn () => new stdClass());
    $container->bind('factory', static fn ($app) => $app->make('nested'));
    $first = $container->make('factory');
    $second = $container->make('factory');
    $container->singleton('shared', static fn () => new stdClass());
    $container->bind('fails', static function (): never { throw new RuntimeException('expected'); });
    try {
        $container->make('fails');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() !== 'expected') throw $exception;
    }
    $results = ['factories' => $first !== $second && $container->make('factory') instanceof stdClass,
        'singleton' => $container->make('shared') === $container->make('shared')];

    $GLOBALS['php86OnceCalls'] = 0;
    $remember = static function (mixed $value): mixed {
        return once(static function () use ($value): mixed {
            $GLOBALS['php86OnceCalls']++;
            return $value;
        });
    };
    $results['once'] = $remember($first) === $first && $remember($first) === $first
        && $remember($second) === $second && $remember(spl_object_id($first)) === spl_object_id($first)
        && $GLOBALS['php86OnceCalls'] === 3;

    $db = new Illuminate\Database\Capsule\Manager();
    $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $db->setAsGlobal();
    $db->bootEloquent();
    $db->getConnection()->getSchemaBuilder()->create('records', static function ($table): void {
        $table->integer('id'); $table->boolean('active');
    });
    $db->getConnection()->table('records')->insert([['id' => 1, 'active' => 0], ['id' => 2, 'active' => 1]]);
    /** In-memory model qualifying anonymous closure scopes. @since 3.6.0 */
    $model = new class extends Illuminate\Database\Eloquent\Model {
        protected $table = 'records';
        public $timestamps = false;
    };
    $scope = static fn ($query) => $query->where('active', 1);
    $model::addGlobalScope($scope);
    $model::addGlobalScope($scope);
    $results['scopes'] = $model::query()->pluck('id')->all() === [2]
        && count($model->getGlobalScopes()) === 1
        && $model::query()->withoutGlobalScopes()->orderBy('id')->pluck('id')->all() === [1, 2];
    echo json_encode($results, JSON_THROW_ON_ERROR);
    exit;
}

$directory = sys_get_temp_dir() . '/evo-session-contract-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$database = new Illuminate\Database\Capsule\Manager();
$database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$database->getConnection()->getSchemaBuilder()->create('sessions', static function ($table): void {
    $table->string('id')->primary(); $table->text('payload'); $table->integer('last_activity');
});
$request = Symfony\Component\HttpFoundation\Request::create('/');
// illuminate/cookie is optional. ID validation/read need only the request, not its response queue.
$cookie = (new ReflectionClass(Illuminate\Session\CookieSessionHandler::class))->newInstanceWithoutConstructor();
$cookie->setRequest($request);
$handlers = [
    'file' => new Illuminate\Session\FileSessionHandler(new Illuminate\Filesystem\Filesystem(), $directory, 10),
    'array' => new Illuminate\Session\ArraySessionHandler(10),
    'cache' => new Illuminate\Session\CacheBasedSessionHandler(new Illuminate\Cache\Repository(new Illuminate\Cache\ArrayStore()), 10),
    'database' => new Illuminate\Session\DatabaseSessionHandler($database->getConnection(), 'sessions', 10),
    'cookie' => $cookie,
    'null' => new Illuminate\Session\NullSessionHandler(),
];

try {
    $results = [];
    foreach ($handlers as $name => $handler) {
        $id = $handler->create_sid();
        $other = $handler->create_sid();
        $before = $handler->validateId($id);
        if ($name !== 'cookie') {
            $handler->write($id, 'preserved payload');
        }
        if ($name === 'cookie') {
            // A queued response cookie becomes a request cookie on the next request.
            $request->cookies->set($id, json_encode(['data' => 'preserved payload', 'expires' => time() + 600]));
        }
        $results[$name] = [
            'idsValid' => (bool) preg_match('/^[a-zA-Z0-9,-]+$/D', $id) && $id !== $other,
            'before' => $before,
            'after' => $handler->validateId($id),
            'payload' => $handler->read($id),
        ];
        if ($name !== 'cookie') {
            $handler->destroy($id);
        }
        if ($name === 'cookie') {
            $request->cookies->remove($id);
        }
        $results[$name]['afterDestroy'] = $handler->validateId($id);
    }
    echo json_encode($results, JSON_THROW_ON_ERROR);
} finally {
    (new Illuminate\Filesystem\Filesystem())->deleteDirectory($directory);
}
