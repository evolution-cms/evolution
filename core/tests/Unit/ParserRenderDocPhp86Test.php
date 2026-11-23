<?php

use EvolutionCMS\Core;
use EvolutionCMS\Parser;

test('renderDoc selects templates without emitting PHP 8.6 diagnostics', function () {
    /** Supplies one resource and predictable template content without bootstrapping a site. */
    $core = new class extends Core {
        public $db;

        /** Point template lookups at an in-memory test double. */
        public function __construct()
        {
            $this->db = new class {
                /** Return distinguishable content for the requested template ID. */
                public function getValue(string $query): string
                {
                    return 'template ' . substr($query, strrpos($query, ' ') + 1);
                }
            };
        }

        /** Return a resource with an assigned template. */
        public function getDocumentObject($method, $identifier, $isPrepareResponse = false)
        {
            return ['type' => 'document', 'template' => 7];
        }

        /** Provide the name used in the template query. */
        public function getFullTableName($tbl)
        {
            return $tbl;
        }

        /** Read the fixture configuration without resolving the application container. */
        public function getConfig($name = '', $default = null)
        {
            return $this->config[$name] ?? $default;
        }

        /** Keep the test focused on template selection. */
        public function parseDocumentSource($source)
        {
            return $source;
        }

        /** Avoid depending on the site's URL configuration. */
        public function rewriteUrls($documentSource)
        {
            return $documentSource;
        }
    };

    $parser = Parser::getInstance($core);
    set_error_handler(static function (int $level, string $message, string $file, int $line): never {
        throw new ErrorException($message, 0, $level, $file, $line);
    }, E_WARNING | E_DEPRECATED);
    try {
        expect($parser->renderDoc(1, false, 5))->toBe('template 5')
            ->and($parser->renderDoc(1, false, '5'))->toBe('5')
            ->and($parser->renderDoc(1))->toBe('template 7');
    } finally {
        restore_error_handler();
    }
});
