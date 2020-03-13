<?php namespace FormLister;

/**
 * Class Validator
 * @package FormLister
 *  */
class Validator
{
    /**
     * @param $value
     * @return bool
     */
    public static function required($value) {
        return !in_array($value, array(null, ''), true);
    }

    /**
     * @param $value
     * @param $format
     * @return bool
     */
    public static function date($value, $format) {
        if (!is_scalar($value)) {
            return false;
        }
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) == $value;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function min($value, $min)
    {
        return is_scalar($value) && $value >= $min;
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function max($value, $max)
    {
        return is_scalar($value) && $value <= $max;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function greater($value, $min)
    {
        return is_scalar($value) && $value > $min;
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function less($value, $max)
    {
        return is_scalar($value) && $value < $max;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public static function between($value, $min, $max)
    {
        return (is_scalar($value) && $value >= $min && $value <= $max);
    }

    /**
     * @param $value
     * @param $allowed
     * @return bool
     */
    public static function equals($value, $allowed)
    {
        return is_scalar($value) && $value === $allowed;
    }

    /**
     * @param $value
     * @param array $allowed
     * @return bool
     */
    public static function in($value, $allowed)
    {
        return is_scalar($value) && in_array($value, $allowed, true);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function alpha($value)
    {
        return (bool) is_scalar($value) && preg_match('/^\pL++$/uD', $value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function numeric($value)
    {
        return (bool) is_scalar($value) && preg_match('#^[0-9]*$#',$value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function alphaNumeric($value)
    {
        return (bool) is_scalar($value) && preg_match('/^[\pL\pN]++$/uD', $value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function slug($value)
    {
        return (bool) is_scalar($value) && preg_match('/^[\pL\pN\-\_]++$/uD', $value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function decimal($value)
    {
        return (bool) is_scalar($value) && preg_match('/^[0-9]+(?:\.[0-9]+)?$/D', $value);
    }


    /**
     * @param $value
     * @return bool
     */
    public static function phone($value)
    {
        return (bool) is_scalar($value) && preg_match('#^[0-9\(\)\+ \-]*$#',$value);
    }

    /**
     * @param $value
     * @param $regexp
     * @return bool
     */
    public static function matches($value,$regexp)
    {
        return (bool) is_scalar($value) && preg_match($regexp,$value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function url($value)
    {
        return (bool) is_scalar($value) && preg_match(
            '~^
                [-a-z0-9+.]++://
                (?!-)[-a-z0-9]{1,63}+(?<!-)
                (?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
                (?::\d{1,5}+)?
                (?:/.*)?
            $~iDx',
            $value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function email($value)
    {
        return (bool) is_scalar($value) && filter_var(self::sanitizeEmail($value), FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $value
     * @param $length
     * @return bool
     */
    public static function length($value, $length)
    {
        return self::getLength($value) === $length;
    }

    /**
     * @param $value
     * @param $minLength
     * @return bool
     */
    public static function minLength($value, $minLength)
    {
        return self::getLength($value) >= $minLength;
    }

    /**
     * @param $value
     * @param $maxLength
     * @return bool
     */
    public static function maxLength($value, $maxLength)
    {
        return self::getLength($value) <= $maxLength;
    }

    /**
     * @param $value
     * @param $minLength
     * @param $maxLength
     * @return bool
     */
    public static function lengthBetween($value, $minLength, $maxLength)
    {
        if (!is_scalar($value)) {
            return false;
        }
        $length = self::getLength($value);

        return ($length >= $minLength && $length <= $maxLength);
    }

    /**
     * @param $value
     * @param $minSize
     * @return bool
     */
    public static function minCount($value, $minSize) {
        return is_array($value) && count($value) >= $minSize;
    }

    /**
     * @param $value
     * @param $maxSize
     * @return bool
     */
    public static function maxCount($value, $maxSize) {
        return is_array($value) && count($value) <= $maxSize;
    }

    /**
     * @param $value
     * @param $minSize
     * @param $maxSize
     * @return bool
     */
    public static function countBetween($value, $minSize, $maxSize) {
        return (is_array($value) && count($value) >= $minSize && count($value) <= $maxSize);
    }

    /**
     * @param $string
     * @return int
     */
    protected static function getLength($string)
    {
        return strlen(utf8_decode($string));
    }

    /**
     * @param $email
     * @return string
     */
    protected static function sanitizeEmail($email) {
        if (function_exists('idn_to_ascii')) {
            $_email = explode('@', $email);
            $_email[1] = idn_to_ascii($_email[1]);
            $email = implode('@', $_email);
        }

        return $email;
    }
}
