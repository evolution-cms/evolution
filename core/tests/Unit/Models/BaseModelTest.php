<?php

use EvolutionCMS\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Initialize;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasDefaultAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait BaseModelProbeTrait
{
    public static array $bootCalls = [];
    public array $initialized = [];

    public static function bootBaseModelProbeTrait(): void
    {
        self::$bootCalls[] = 'conventional';
    }

    public function initializeBaseModelProbeTrait(): void
    {
        $this->initialized[] = 'conventional';
    }

    #[Boot]
    public static function markedBoot(): void
    {
        self::$bootCalls[] = 'marked';
    }

    #[Initialize]
    public function markedInitializer(): void
    {
        $this->initialized[] = 'marked';
    }
}

#[Fillable(['title'])]
trait BaseModelAttributeTrait
{
}

class BaseModelPlain extends BaseModel
{
    protected $table = 'plain';
    protected $fillable = ['a'];
    protected $hidden = ['b'];
}

class BaseModelTraits extends BaseModel
{
    use BaseModelProbeTrait;
    use SoftDeletes;
}

#[Table(name: 'attributed', key: 'uuid', keyType: 'string', incrementing: false)]
#[Hidden(['secret'])]
#[WithoutTimestamps]
class BaseModelAttributed extends BaseModel
{
}

class BaseModelInheritsAttributes extends BaseModelAttributed
{
}

class BaseModelTraitAttribute extends BaseModel
{
    use BaseModelAttributeTrait;
}

class BaseModelDefaults extends BaseModel
{
    use HasDefaultAttributes;

    protected function defaults(): array
    {
        return ['status' => 'draft'];
    }
}

class BaseModelAccessors extends BaseModel
{
    protected function title(): Attribute
    {
        return Attribute::get(fn () => 'read');
    }

    protected function secret(): Attribute
    {
        return Attribute::set(fn ($value) => $value);
    }

    public function getLegacyAttribute(): string
    {
        return 'legacy';
    }
}

// The same classes on Eloquent's own Model.
class EloquentPlain extends Model
{
    protected $table = 'plain';
    protected $fillable = ['a'];
    protected $hidden = ['b'];
}

class EloquentTraits extends Model
{
    use BaseModelProbeTrait;
    use SoftDeletes;
}

#[Table(name: 'attributed', key: 'uuid', keyType: 'string', incrementing: false)]
#[Hidden(['secret'])]
#[WithoutTimestamps]
class EloquentAttributed extends Model
{
}

class EloquentTraitAttribute extends Model
{
    use BaseModelAttributeTrait;
}

/** @return array<string, mixed> The setup Eloquent gives an instance. */
function modelSetup(Model $model): array
{
    return [
        'key' => $model->getKeyName(),
        'keyType' => $model->getKeyType(),
        'incrementing' => $model->getIncrementing(),
        'timestamps' => $model->usesTimestamps(),
        'fillable' => $model->getFillable(),
        'guarded' => $model->getGuarded(),
        'hidden' => $model->getHidden(),
        'casts' => $model->getCasts(),
    ];
}

function traitInitializers(string $class): array
{
    $property = new ReflectionProperty(Model::class, 'traitInitializers');
    $methods = array_values($property->getValue()[$class]);
    sort($methods);

    return $methods;
}

beforeEach(fn () => Model::clearBootedModels());

test('a model gets the same setup from BaseModel as from Eloquent', function (string $class, string $twin) {
    expect(modelSetup(new $class()))->toBe(modelSetup(new $twin()));
})->with([
    'plain' => [BaseModelPlain::class, EloquentPlain::class],
    'traits' => [BaseModelTraits::class, EloquentTraits::class],
    'class attributes' => [BaseModelAttributed::class, EloquentAttributed::class],
    'attribute on a trait' => [BaseModelTraitAttribute::class, EloquentTraitAttribute::class],
]);

test('class attributes are read, also from a parent and a trait', function () {
    $attributed = new BaseModelAttributed();
    $inherited = new BaseModelInheritsAttributes();

    expect($attributed->getTable())->toBe('attributed')
        ->and($attributed->getKeyName())->toBe('uuid')
        ->and($attributed->getKeyType())->toBe('string')
        ->and($attributed->getIncrementing())->toBeFalse()
        ->and($attributed->usesTimestamps())->toBeFalse()
        ->and($attributed->getHidden())->toBe(['secret'])
        ->and($inherited->getHidden())->toBe(['secret'])
        ->and($inherited->usesTimestamps())->toBeFalse()
        ->and((new BaseModelTraitAttribute())->getFillable())->toBe(['title']);
});

test('trait boot methods and initializers run by convention and by attribute', function () {
    $twin = new EloquentTraits();
    BaseModelTraits::$bootCalls = [];
    $model = new BaseModelTraits();

    expect(BaseModelTraits::$bootCalls)->toEqualCanonicalizing(['conventional', 'marked'])
        ->and($model->initialized)->toEqualCanonicalizing(['conventional', 'marked'])
        ->and(traitInitializers(BaseModelTraits::class))->toBe(traitInitializers($twin::class));

    new BaseModelTraits();
    expect(BaseModelTraits::$bootCalls)->toHaveCount(2);
});

test('only a model that uses HasDefaultAttributes gets defaults', function () {
    expect((new BaseModelDefaults())->getAttributes())->toBe(['status' => 'draft'])
        ->and((new BaseModelPlain())->getAttributes())->toBe([]);
});

test('accessors are found as Eloquent finds them', function () {
    $model = new BaseModelAccessors();

    expect($model->getMutatedAttributes())->toEqualCanonicalizing(['legacy', 'title'])
        ->and($model->hasAttributeGetMutator('title'))->toBeTrue()
        ->and($model->hasAttributeGetMutator('secret'))->toBeFalse()
        ->and($model->title)->toBe('read');
});

// BaseModel skips these reads: a Laravel update that adds such a method must fail here.
test('Eloquent marks only boot and initialize methods, and declares no accessor', function () {
    $marked = [];
    $accessors = [];
    foreach ((new ReflectionClass(Model::class))->getMethods() as $method) {
        if ($method->getAttributes(Boot::class) !== [] || $method->getAttributes(Initialize::class) !== []) {
            $marked[] = $method->name;
        }
        $type = $method->getReturnType();
        if ($type instanceof ReflectionNamedType && $type->getName() === Attribute::class) {
            $accessors[] = $method->name;
        }
    }

    expect($marked)->not->toBeEmpty()
        ->and(array_filter($marked, static fn ($name) => !str_starts_with($name, 'boot') && !str_starts_with($name, 'initialize')))->toBe([])
        ->and($accessors)->toBe([]);
});

test('every core model extends BaseModel', function () {
    $models = glob(dirname(__DIR__, 3) . '/src/Models/*.php');
    expect($models)->not->toBeEmpty();

    foreach ($models as $file) {
        $source = file_get_contents($file);
        if (basename($file) !== 'BaseModel.php' && preg_match('/^(?:abstract\s+)?class\s+\w+\s+extends\s+(\S+)/m', $source, $match)) {
            expect($match[1])->toBe('BaseModel', basename($file));
        }
    }
});
