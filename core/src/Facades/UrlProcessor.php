<?php namespace EvolutionCMS\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * UrlProcessor Facade
 *
 * Provides a static interface to the UrlProcessor service for generating URLs,
 * managing document listings, and handling URL routing in EvolutionCMS.
 *
 * This facade allows you to generate friendly URLs for documents, manage
 * document and alias listings, and perform URL-related operations without
 * directly instantiating the UrlProcessor class.
 *
 * @package EvolutionCMS\Facades
 * @mixin \EvolutionCMS\UrlProcessor
 *
 * @example
 * ```php
 * // Generate a URL for a document
 * $url = UrlProcessor::makeUrl(123);
 *
 * // Generate URL with custom alias and parameters
 * $url = UrlProcessor::makeUrl(123, 'custom-alias', 'param=value');
 *
 * // Get document listing for URL routing
 * $listing = UrlProcessor::getFacadeRoot()->documentListing;
 *
 * // Get aliases mapping
 * $aliases = UrlProcessor::getAliases();
 * ```
 */
class UrlProcessor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'UrlProcessor';
    }
}
