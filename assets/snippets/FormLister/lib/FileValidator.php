<?php namespace FormLister;

/**
 * Правила проверки файлов
 * Class FileValidator
 * @package FormLister
 */
class FileValidator
{
    /**
     * @param $value
     * @return bool
     */
    public static function required($value)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    public static function optional($value)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
                if (!$flag) {
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $allowed
     * @return bool
     */
    public static function allowed($value, $allowed)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $ext = strtolower(substr(strrchr($file['name'], '.'), 1));
                $flag = in_array($ext, $allowed);
                if (!$flag) {
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    public static function images($value)
    {
        return self::allowed($value, array("jpg", "jpeg", "png", "gif", "bmp"));
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function maxSize($value, $max)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024, 0);
            $flag = $size < $max;
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function minSize($value, $min)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min;
                if (!$flag) {
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public static function sizeBetween($value, $min, $max)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min && $size < $max;
                if (!$flag) {
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function maxCount($value, $max)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }

        return self::getCount($value) < $max;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function minCount($value, $min)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }

        return self::getCount($value) > $min;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public static function countBetween($value, $min, $max)
    {
        if (!self::isArray($value)) {
            $value = array($value);
        }

        return self::getCount($value) > $min && self::getCount($value) < $max;
    }

    /**
     * @param $value
     * @return bool
     */
    protected static function isArray($value)
    {
        return isset($value[0]);
    }

    /**
     * @param $value
     * @return int
     */
    protected static function getCount($value)
    {
        $out = 0;
        foreach ($value as $file) {
            if (!$file['error'] && is_uploaded_file($file['tmp_name'])) {
                $out++;
            }
        }

        return $out;
    }
}
