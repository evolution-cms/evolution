<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Database\Eloquent\Attributes\Initialize;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasDefaultAttributes;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Eloquent's model setup without the reflection it spends on classes that have nothing to find.
 *
 * Laravel 13 looks for class attributes (#[Table], #[Fillable]...) on every new instance and scans
 * every method of a class for #[Boot] / #[Initialize] when it boots. A frontend request boots a
 * class from scratch each time, and a listing builds thousands of instances. The result stays what
 * Eloquent computes: tests/Unit/Models/BaseModelTest.php compares the two.
 */
abstract class BaseModel extends Model
{
    /** @var array<class-string, bool> Classes with no PHP attribute on them, their parents or traits. */
    private static array $attributeFree = [];

    /** @var array<class-string, bool> */
    private static array $usesDefaultAttributes = [];

    /**
     * The scan of the parent method, in its order, reading attributes only where they can be:
     * in the models themselves, and in Eloquent on the boot* and initialize* methods.
     */
    protected static function bootTraits()
    {
        $class = static::class;
        $booted = [];
        $initializers = [];

        static::$traitInitializers[$class] = [];

        $uses = class_uses_recursive($class);
        $bootMethods = array_flip(array_map(static fn ($trait) => 'boot'.class_basename($trait), $uses));
        $initMethods = array_flip(array_map(static fn ($trait) => 'initialize'.class_basename($trait), $uses));

        foreach ((new ReflectionClass($class))->getMethods() as $method) {
            $name = $method->name;
            $mayBeMarked = $method->class !== Model::class
                || str_starts_with($name, 'boot')
                || str_starts_with($name, 'initialize');

            if (! isset($booted[$name]) && (isset($bootMethods[$name]) || $mayBeMarked) && $method->isStatic()
                && (isset($bootMethods[$name]) || $method->getAttributes(Boot::class) !== [])) {
                $method->invoke(null);

                $booted[$name] = true;
            }

            if (isset($initMethods[$name]) || ($mayBeMarked && $method->getAttributes(Initialize::class) !== [])) {
                $initializers[$name] = $name;
            }
        }

        static::$traitInitializers[$class] = array_values($initializers);
    }

    public function initializeModelAttributes()
    {
        if (! self::isAttributeFree(static::class)) {
            parent::initializeModelAttributes();
        }
    }

    protected static function resolveClassAttribute(string $attributeClass, ?string $property = null, ?string $class = null)
    {
        if (self::isAttributeFree($class ?? static::class)) {
            return null;
        }

        return parent::resolveClassAttribute($attributeClass, $property, $class);
    }

    protected function mergeDefaultAttributes()
    {
        if (self::$usesDefaultAttributes[static::class]
            ??= in_array(HasDefaultAttributes::class, class_uses_recursive(static::class), true)) {
            parent::mergeDefaultAttributes();
        }
    }

    /**
     * The parent method without Eloquent's own methods, none of which is an accessor.
     */
    protected static function getAttributeMarkedMutatorMethods($class)
    {
        $instance = is_object($class) ? $class : new $class;
        $methods = [];

        foreach ((new ReflectionClass($instance))->getMethods() as $method) {
            if ($method->class !== Model::class && self::returnsAccessor($method, $instance)) {
                $methods[] = $method->name;
            }
        }

        return $methods;
    }

    private static function returnsAccessor(ReflectionMethod $method, object $instance): bool
    {
        $type = $method->getReturnType();

        return $type instanceof ReflectionNamedType
            && $type->getName() === Attribute::class
            && is_callable($method->invoke($instance)->get);
    }

    /**
     * Whether no lookup of Model::resolveClassAttribute() can find anything: it reads the attributes
     * of a class, its direct traits and its parents.
     */
    private static function isAttributeFree(string $class): bool
    {
        if (isset(self::$attributeFree[$class])) {
            return self::$attributeFree[$class];
        }

        $reflection = new ReflectionClass($class);
        $free = $reflection->getAttributes() === [];
        foreach ($free ? $reflection->getTraits() : [] as $trait) {
            $free = $free && $trait->getAttributes() === [];
        }
        $parent = $reflection->getParentClass();

        return self::$attributeFree[$class] = $free && ($parent === false || self::isAttributeFree($parent->getName()));
    }
}
