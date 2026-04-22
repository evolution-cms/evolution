<?php namespace EvolutionCMS\Events;

/**
 * Event fired after publishable vendor assets are published.
 *
 * @since 3.5.7
 */
class VendorTagPublished
{
    /**
     * The vendor tag that was published.
     *
     * @var string|null
     * @since 3.5.7
     */
    public $tag;

    /**
     * The publishable paths registered by the tag.
     *
     * @var array
     * @since 3.5.7
     */
    public $paths;

    /**
     * Create a new event instance.
     *
     * @param string|null $tag
     * @param array $paths
     * @since 3.5.7
     */
    public function __construct($tag, array $paths)
    {
        $this->tag = $tag;
        $this->paths = $paths;
    }
}
