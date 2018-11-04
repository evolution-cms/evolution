<?php namespace FormLister;

/**
 * Class Filters
 * @package FormLister
 */
class Filters {
    /**
     * @param $value
     * @return string
     */
    public static function trim($value) {
        return is_scalar($value) ? trim($value) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function rtrim($value) {
        return is_scalar($value) ? rtrim($value) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function ltrim($value) {
        return is_scalar($value) ? ltrim($value) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public  static function stripTags($value) {
        return is_scalar($value) ? strip_tags($value) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function lcfirst($value) {
        return is_scalar($value) ? mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function ucfirst($value) {
        return is_scalar($value) ? mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function ucwords($value) {
        return is_scalar($value) ? mb_convert_case($value, MB_CASE_TITLE, "UTF-8") : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function upper($value) {
        return is_scalar($value) ? mb_strtoupper($value) : '';
    }

    /**
     * @param $value
     * @return string
     */
    public static function lower($value) {
        return is_scalar($value) ? mb_strtolower($value) : '';
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public static function email($value) {
        return is_scalar($value) ? filter_var($value, FILTER_SANITIZE_EMAIL) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function phone($value) {
        return is_scalar($value) ? preg_replace('/[^\D\(\)\s\+-]/', '', $value) : '';
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public static function url($value) {
        return is_scalar($value) ? filter_var($value, FILTER_SANITIZE_URL) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function alpha($value) {
        return is_scalar($value) ? preg_replace('/[^\pL]/', '', $value) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function numeric($value) {
        return is_scalar($value) ? preg_replace('/[\D]/', '', $value) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function alphaNumeric($value) {
        return is_scalar($value) ? preg_replace('/[^\pL\d]/', '', $value) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function removeExtraSpaces ($value) {
        return is_scalar($value) ? preg_replace('/\s+/', ' ', $value) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function int($value) {
        return is_scalar($value) ? filter_var($value, FILTER_SANITIZE_NUMBER_INT) : '';
    }

    /**
     * @param $value
     * @return null|string|string[]
     */
    public static function float($value) {
        return is_scalar($value) ? filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC) : '';
    }

    /**
     * @param $value
     * @return int
     */
    public static function castInt($value) {
        return (int)$value;
    }

    /**
     * @param $value
     * @return float
     */
    public static function castFloat($value) {
        if (is_scalar($value)) {
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/\.(?=.*\.)/', '', $value);
        } else {
            $value = 0;
        }
        return (float)$value;
    }
}
