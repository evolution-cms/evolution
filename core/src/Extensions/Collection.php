<?php namespace EvolutionCMS\Extensions;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use EvolutionCMS\Models\SiteContent;

/**
 * Extended Collection class. Provides some useful methods.
 *
 * @method Entity|null get($key, $default = null)
 * @package Franzose\ClosureTable\Extensions
 */
class Collection extends EloquentCollection
{
    /**
     * Returns a child node at the given position.
     *
     * @param int $position
     *
     * @return SiteContent|null
     */
    public function getChildAt($position)
    {
        return $this->filter(static function (SiteContent $entity) use ($position) {
            return $entity->position === $position;
        })->first();
    }

    /**
     * Returns the first child node.
     *
     * @return SiteContent|null
     */
    public function getFirstChild()
    {
        return $this->getChildAt(0);
    }

    /**
     * Returns the last child node.
     *
     * @return SiteContent|null
     */
    public function getLastChild()
    {
        return $this->sortByDesc(static function (SiteContent $entity) {
            return $entity->position;
        })->first();
    }

    /**
     * Filters the collection by the given positions.
     *
     * @param int $from
     * @param int|null $to
     *
     * @return Collection
     */
    public function getRange($from, $to = null)
    {
        return $this->filter(static function (SiteContent $entity) use ($from, $to) {
            if ($to === null) {
                return $entity->position >= $from;
            }

            return $entity->position >= $from && $entity->position <= $to;
        });
    }

    /**
     * Filters collection to return nodes on the "left"
     * and on the "right" from the node with the given position.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getNeighbors($position)
    {
        return $this->filter(static function (SiteContent $entity) use ($position) {
            return $entity->position === $position - 1 ||
                $entity->position === $position + 1;
        });
    }

    /**
     * Filters collection to return previous siblings of a node with the given position.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getPrevSiblings($position)
    {
        return $this->filter(static function (SiteContent $entity) use ($position) {
            return $entity->position < $position;
        });
    }

    /**
     * Filters collection to return next siblings of a node with the given position.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getNextSiblings($position)
    {
        return $this->filter(static function (SiteContent $entity) use ($position) {
            return $entity->position > $position;
        });
    }

    /**
     * Retrieves children relation.
     *
     * @param $position
     * @return Collection
     */
    public function getChildrenOf($position)
    {
        if (!$this->hasChildren($position)) {
            return new self();
        }

        return $this->getChildAt($position)->children;
    }

    /**
     * Indicates whether an item has children.
     *
     * @param $position
     * @return bool
     */
    public function hasChildren($position)
    {
        $item = $this->getChildAt($position);

        return $item !== null && $item->children->count() > 0;
    }

    /**
     * Makes tree-like collection.
     *
     * @return Collection
     */
    public function toTree()
    {
        $items = $this->items;

        return new self($this->makeTree($items));
    }

    /**
     * Makes tree-like collection.
     *
     * @param null $parent
     * @return Collection
     */
    public function toTreeParent($parent = null)
    {
        $items = $this->items;

        return new self($this->makeTreeParent($items, $parent));
    }

    /**
     * Performs actual tree building.
     *
     * @param SiteContent[] $items
     * @return array
     */
    protected function makeTree(array $items)
    {
        /** @var SiteContent[] $result */
        $result = [];
        $tops = [];

        foreach ($items as $item) {
            $result[$item->getKey()] = $item;
        }

        foreach ($items as $item) {
            $parentId = $item->parent_id;

            if (array_key_exists($parentId, $result)) {
                $result[$parentId]->appendChild($item);
            } else {
                $tops[] = $item;
            }
        }

        return $tops;
    }

    /**
     * Performs actual tree building.
     *
     * @param SiteContent[] $items
     * @param null $parent
     * @return array
     */
    protected function makeTreeParent(array $items, $parent = null)
    {
        if($parent === null){
            return $this->makeTree($items);
        }
        /** @var SiteContent[] $result */
        $result = [];
        $tops = [];

        foreach ($items as $item) {
            $result[$item->getKey()] = $item;
        }

        foreach ($items as $item) {
            $parentId = $item->parent_id;

            if (array_key_exists($parentId, $result)) {
                $result[$parentId]->appendChild($item);
            } else {
                if($parentId == $parent)
                    $tops[] = $item;
            }
        }

        return $tops;
    }
}
