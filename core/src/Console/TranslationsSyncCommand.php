<?php namespace EvolutionCMS\Console;

use Illuminate\Console\Command;

/**
 * Syncs translation file keys existence with the default one (English by default)
 */
class TranslationsSyncCommand extends Command
{
    protected $corePathPatterns = [
        'manager/includes/lang/country/([a-z]{2})_country.inc.php' => '_country_lang',
        'manager/includes/lang/errormsg/([a-z]{2}).inc.php' => '_lang',
        'install/src/lang/([a-z]{2}).inc.php' => '_lang',
        'assets/modules/store/installer/lang/([a-z]{2}).inc.php' => '_lang',
    ];

    protected $userPathPatterns = [
        'manager/includes/lang/override/([a-z]{2}).inc.php' => '_lang',
    ];

    protected $signature = 'translations:sync 
                            {--core : Sync core translation files} 
                            {--base=en : Default language code}
                            {--missing=empty : Missing translations handling, empty|copy)}
                            {--sort : Sort keys}
                            {--write : Write missing keys to translation files}';

    protected $name = 'translations:sync';
    protected $description = 'Sync translation file keys with the default one';

    protected $stats = [
        'processed' => 0,
        'inSync' => 0,
        'withMissing' => 0,
        'withExtra' => 0,
        'totalMissing' => 0,
        'totalExtra' => 0,
        'written' => 0,
        'skipped' => 0
    ];

    /**
     * Cache for base language file paths per pattern
     */
    protected $baseLanguageFiles = [];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $patterns = $this->option('core') ? $this->corePathPatterns : $this->userPathPatterns;
        $baseLanguage = $this->option('base') ? strtolower($this->option('base')) : 'en';
        $writeMode = $this->option('write');

        if ($this->output->isVerbose()) {
            $this->info('Validating base language files...');
        }

        // Validate base language files exist for each pattern
        $validPatterns = $this->validateBaseLanguagePatterns($patterns, $baseLanguage);

        if (empty($validPatterns)) {
            $this->error('No valid patterns found (all base language files missing).');
            return;
        }

        if ($this->output->isVerbose()) {
            $this->info('Collecting translation files...');
        }

        $translationFiles = $this->collectTranslationFiles($validPatterns);

        if (empty($translationFiles)) {
            $this->info('No translation files found.');
            return;
        }

        // Filter out base language files from processing
        $translationFiles = $this->filterBaseLanguageFiles($translationFiles, $baseLanguage);

        if (empty($translationFiles)) {
            $this->info('No translation files to process (only base language files found).');
            return;
        }

        $this->stats['processed'] = count($translationFiles);

        if ($this->output->isVerbose()) {
            $this->info("Processing {$this->stats['processed']} translation files...");
        }

        $bar = $this->output->createProgressBar(count($translationFiles));
        $bar->start();

        foreach ($translationFiles as $fileInfo) {
            if ($this->output->isVeryVerbose()) {
                $this->line('');
                $this->info("Processing: {$fileInfo['relativePath']} ({$fileInfo['language']})");
            }

            $this->processTranslationFile($fileInfo, $writeMode);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->showSummary($writeMode);
    }

    /**
     * Validate base language files exist for each pattern
     */
    protected function validateBaseLanguagePatterns(array $patterns, string $baseLanguage): array
    {
        $validPatterns = [];
        $skippedPatterns = 0;

        foreach ($patterns as $pathPattern => $arrayName) {
            $baseFilePath = $this->getBaseLanguageFilePathForPattern($pathPattern, $baseLanguage);

            if (!$baseFilePath || !file_exists($baseFilePath)) {
                $this->warn("Base language file not found for pattern '{$pathPattern}': {$baseFilePath}");
                $skippedPatterns++;
                continue;
            }

            // Cache the base language file path for this pattern
            $this->baseLanguageFiles[$pathPattern] = $baseFilePath;
            $validPatterns[$pathPattern] = $arrayName;

            if ($this->output->isVerbose()) {
                $this->info("✓ Base language file found for pattern '{$pathPattern}': {$baseFilePath}");
            }
        }

        if ($skippedPatterns > 0) {
            $this->warn("Skipped {$skippedPatterns} patterns due to missing base language files.");
        }

        return $validPatterns;
    }

    /**
     * Get base language file path for a specific pattern
     */
    protected function getBaseLanguageFilePathForPattern(string $pathPattern, string $baseLanguage): ?string
    {
        // Replace the capture group content with base language
        $basePattern = str_replace('([a-z]{2})', $baseLanguage, $pathPattern);
        return EVO_BASE_PATH . $basePattern;
    }

    /**
     * Collect all translation files with their language codes
     */
    protected function collectTranslationFiles(array $patterns): array
    {
        $translationFiles = [];

        foreach ($patterns as $pathPattern => $arrayName) {
            $pathInfo = $this->parsePathPattern($pathPattern);
            $fullPath = EVO_BASE_PATH . $pathInfo['directory'];

            if (!is_dir($fullPath)) {
                if ($this->output->isVerbose()) {
                    $this->warn("Directory not found: {$fullPath}");
                }
                continue;
            }

            $files = $this->scanDirectory($fullPath, $pathInfo['filePattern']);

            foreach ($files as $file) {
                $translationFiles[] = [
                    'file' => $file['path'],
                    'language' => $file['language'],
                    'arrayName' => $arrayName,
                    'pathPattern' => $pathPattern,
                    'relativePath' => str_replace(EVO_BASE_PATH, '', $file['path'])
                ];
            }
        }

        return $translationFiles;
    }

    /**
     * Filter out base language files from the list
     */
    protected function filterBaseLanguageFiles(array $translationFiles, string $baseLanguage): array
    {
        return array_filter($translationFiles, function($fileInfo) use ($baseLanguage) {
            return $fileInfo['language'] !== $baseLanguage;
        });
    }

    /**
     * Parse path pattern to extract directory and file pattern
     */
    protected function parsePathPattern(string $pathPattern): array
    {
        $lastSlashPosition = strrpos($pathPattern, '/');

        if ($lastSlashPosition === false) {
            return [
                'directory' => '',
                'filePattern' => $pathPattern
            ];
        }

        return [
            'directory' => substr($pathPattern, 0, $lastSlashPosition),
            'filePattern' => substr($pathPattern, $lastSlashPosition + 1)
        ];
    }

    /**
     * Scan directory for translation files matching the pattern
     */
    protected function scanDirectory(string $directory, string $filePattern): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        // Convert filePattern to regex - simply wrap it with delimiters
        $regexPattern = '/^' . $filePattern . '$/';
        $directoryContents = scandir($directory);

        foreach ($directoryContents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_file($fullPath) && preg_match($regexPattern, $item, $matches)) {
                $files[] = [
                    'path' => $fullPath,
                    'filename' => $item,
                    'language' => $matches[1] // First capture group
                ];
            }
        }

        return $files;
    }

    /**
     * Process individual translation file
     */
    protected function processTranslationFile(array $fileInfo, bool $writeMode): void
    {
        $filePath = $fileInfo['file'];
        $language = $fileInfo['language'];
        $arrayName = $fileInfo['arrayName'];
        $pathPattern = $fileInfo['pathPattern'];

        // Load the translation file
        if (!file_exists($filePath)) {
            if ($this->output->isVerbose()) {
                $this->error("File not found: {$filePath}");
            }
            return;
        }

        // Include the file and get the translation array
        include $filePath;

        if (!isset(${$arrayName})) {
            if ($this->output->isVerbose()) {
                $this->error("Translation array '{$arrayName}' not found in {$filePath}");
            }
            return;
        }

        $translations = ${$arrayName};

        // Get cached base language file path
        $baseFilePath = $this->baseLanguageFiles[$pathPattern];

        // Load base language file
        include $baseFilePath;
        $baseTranslations = ${$arrayName} ?? [];

        // Compare and sync
        $this->syncTranslations($translations, $baseTranslations, $fileInfo, $writeMode);
    }

    /**
     * Sync translations with base language
     */
    protected function syncTranslations(array $translations, array $baseTranslations, array $fileInfo, bool $writeMode): void
    {
        $filePath = $fileInfo['file'];
        $language = $fileInfo['language'];
        $arrayName = $fileInfo['arrayName'];
        $missingKeys = array_diff_key($baseTranslations, $translations);
        $extraKeys = array_diff_key($translations, $baseTranslations);

        if (empty($missingKeys) && empty($extraKeys)) {
            if ($this->output->isVeryVerbose()) {
                $this->line("  ✓ File is in sync");
            }
            $this->stats['inSync']++;
            return;
        }

        if (!empty($missingKeys)) {
            $this->stats['withMissing']++;
            $this->stats['totalMissing'] += count($missingKeys);

            if ($this->output->isVerbose()) {
                $this->warn("  Missing keys (" . count($missingKeys) . "): " . implode(', ', array_keys($missingKeys)));
            }

            if ($writeMode) {
                $this->writeMissingKeys($filePath, $arrayName, $translations, $missingKeys, $language);
            }
        }

        if (!empty($extraKeys)) {
            $this->stats['withExtra']++;
            $this->stats['totalExtra'] += count($extraKeys);

            if ($this->output->isVerbose()) {
                $this->comment("  Extra keys (" . count($extraKeys) . "): " . implode(', ', array_keys($extraKeys)));
            }
        }
    }

    /**
     * Write missing keys to translation file
     */
    protected function writeMissingKeys(string $filePath, string $arrayName, array $translations, array $missingKeys, string $language): void
    {
        try {
            // Add missing keys with empty string values
            $isCopyMissingMode = $this->option('missing') === 'copy';
            foreach ($missingKeys as $key => $value) {
                $translations[$key] = $isCopyMissingMode ? $value : '';
            }

            // Read existing file content to preserve comments
            $existingContent = file_get_contents($filePath);
            $header = $this->extractOrGenerateHeader($existingContent, $filePath, $language);

            if ($this->option('sort')) {
                ksort($translations, SORT_FLAG_CASE | SORT_NATURAL);
            }

            // Generate the PHP file content
            $content = "<?php\n";
            $content .= $header;
            $content .= "\${$arrayName} = " . var_export($translations, true) . ";\n";

            // Write to file
            if (file_put_contents($filePath, $content) !== false) {
                $this->stats['written']++;
                if ($this->output->isVerbose()) {
                    $this->info("  ✓ Written " . count($missingKeys) . " missing keys to file");
                }
            } else {
                if ($this->output->isVerbose()) {
                    $this->error("  ✗ Failed to write to file: {$filePath}");
                }
            }
        } catch (\Exception $e) {
            if ($this->output->isVerbose()) {
                $this->error("  ✗ Error writing to file: " . $e->getMessage());
            }
        }
    }

    /**
     * Extract existing header comment or generate new one with @date placeholder
     */
    protected function extractOrGenerateHeader(string $content, string $filePath, string $languageCode): string
    {
        // Remove opening PHP tag to work with the content
        $content = preg_replace('/^\s*<\?php\s*/', '', $content);
        $now = date('Y-m-d H:i:s');

        // Try to match existing comment block
        if (preg_match('/^\s*(\/\*\*.*?\*\/)\s*/s', $content, $matches)) {
            $existingComment = $matches[1];

            // Check if @date already exists in the comment
            if (str_contains($existingComment, '@date')) {
                // Replace everything from @date to end of line with "@date " + current timestamp
                $updatedComment = preg_replace('/@date.*$/m', "@date $now", $existingComment);
                return $updatedComment . "\n\n";
            } else {
                // Add @date line before the closing comment
                $updatedComment = preg_replace(
                    '/(\s*\*\/)$/',
                    " * @date $now\n$1",
                    $existingComment
                );
                return $updatedComment . "\n\n";
            }
        }

        $subpackage = explode('/', trim(str_replace(['\\', EVO_BASE_PATH], ['/', ''], $filePath), '/'))[0];
        $versionData = include EVO_CORE_PATH . 'factory/version.php';
        $version = preg_replace('/\.[0-9-_a-z]+?$/', '.x', $versionData['version']);
        $language = class_exists(\Locale::class) ? \Locale::getDisplayLanguage($languageCode, 'en') : $languageCode;

        $header = "/**\n";
        $header .= " * Evolution CMS " . basename($filePath) . " language file\n *\n";
        $header .= " * @author Evolution CMS Team\n";
        $header .= " * @version $version\n";
        $header .= " * @date $now\n *\n";
        $header .= " * @language $language\n * @package Evolution CMS\n * @subpackage $subpackage\n";
        $header .= " * Please commit your language changes here: https://github.com/evolution-cms/evolution\n";
        $header .= " */\n";

        return $header;
    }

    /**
     * Show command execution summary
     */
    protected function showSummary(bool $writeMode): void
    {
        $this->info('Translation sync completed!');
        $this->line('');
        $this->info('Summary:');
        $this->line("  Files processed: {$this->stats['processed']}");
        $this->line("  Files in sync: {$this->stats['inSync']}");
        $this->line("  Files with missing keys: {$this->stats['withMissing']}");
        $this->line("  Files with extra keys: {$this->stats['withExtra']}");
        $this->line("  Total missing keys: {$this->stats['totalMissing']}");
        $this->line("  Total extra keys: {$this->stats['totalExtra']}");

        if ($this->stats['skipped'] > 0) {
            $this->line("  Files skipped: {$this->stats['skipped']}");
        }

        if ($writeMode) {
            $this->line("  Files written: {$this->stats['written']}");
        }
    }

    protected function getOptions()
    {
        return ['core', 'base', 'missing', 'write', 'sort'];
    }
}
