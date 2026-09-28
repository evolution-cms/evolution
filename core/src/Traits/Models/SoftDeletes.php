<?php namespace EvolutionCMS\Traits\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes as BaseSoftDeletes;
use EvolutionCMS\Shit\SoftDeletingScope;

trait SoftDeletes{
    use BaseSoftDeletes {
        bootSoftDeletes as baseBootSoftDeletes;
        restore as baseRestore;
    }

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = 0;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Keep the deleted-at column as its stored Unix timestamp in array form.
     *
     * The base trait casts that column to datetime, so toArray() would build a
     * Carbon instance only to print it as an ISO string: every uncached page
     * paid for loading Carbon, and templates got a value unlike every other
     * date field. Reading the attribute still returns Carbon.
     *
     * @param array<string, mixed> $attributes
     * @param array<int, string> $mutatedAttributes
     * @return array<string, mixed>
     */
    protected function addCastAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        $column = $this->getDeletedAtColumn();
        if (!array_key_exists($column, $attributes)
            || in_array($column, $mutatedAttributes, true)
            || ($this->getCasts()[$column] ?? null) !== 'datetime'
        ) {
            return parent::addCastAttributesToArray($attributes, $mutatedAttributes);
        }

        $raw = $attributes[$column];
        if ($raw instanceof DateTimeInterface) {
            $raw = $raw->getTimestamp();
        } elseif (is_numeric($raw)) {
            $raw = (int) $raw;
        }

        // The parent only rewrites keys it is given, so the column goes back
        // where it was and the array keeps its order.
        $position = array_search($column, array_keys($attributes), true);
        unset($attributes[$column]);
        $attributes = parent::addCastAttributesToArray($attributes, $mutatedAttributes);

        return array_slice($attributes, 0, $position, true)
            + [$column => $raw]
            + array_slice($attributes, $position, null, true);
    }
}
