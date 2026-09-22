<?php

namespace EvolutionCMS\Support\DocumentSave;

/**
 * Turns the posted form field of one template variable into the value to store.
 * @since 3.5.9
 */
final class TemplateVariableInput
{
    private const URL_SCHEMES = ['feed://', 'ftp://', 'http://', 'https://', 'mailto:'];

    /**
     * Null means the stored row has to go: the field is empty, "0" or equal to the TV default.
     *
     * @param array $tv row with id, type and default_text
     * @param array $input the posted form
     */
    public static function value(array $tv, array $input): ?string
    {
        $key = 'tv' . $tv['id'];
        $raw = $input[$key] ?? '';

        switch ($tv['type']) {
            case 'url':
                $value = is_scalar($raw) ? (string) $raw : '';
                $prefix = $input[$key . '_prefix'] ?? '--';
                if ($prefix != '--') {
                    $value = $prefix . str_replace(self::URL_SCHEMES, '', $value);
                }
                break;
            case 'file':
                $value = is_scalar($raw) ? (string) $raw : '';
                break;
            default:
                // checkboxes and multiple selects post arrays
                $value = is_array($raw) ? implode('||', array_values($raw)) : (string) $raw;
        }

        if (empty($value) || $value == ($tv['default_text'] ?? '')) {
            return null;
        }

        return $value;
    }

    /**
     * Desired value per TV id for every TV the user may edit.
     *
     * @param array $tvs rows from TemplateVariableValues::forTemplate()
     * @return array<int, string|null>
     */
    public static function values(array $tvs, array $input): array
    {
        $values = [];
        foreach ($tvs as $tv) {
            $values[(int) $tv['id']] = self::value($tv, $input);
        }

        return $values;
    }
}
