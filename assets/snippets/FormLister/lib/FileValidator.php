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
    public function required($value)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    public function optional($value)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $allowed
     * @return bool
     */
    public function allowed($value, $allowed)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $ext = strtolower(array_pop(explode('.', $file['name'])));
                $flag = in_array($ext, $allowed);
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    public function images($value)
    {
        return $this->allowed($value, array("jpg", "jpeg", "png", "gif", "bmp"));
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public function maxSize($value, $max)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024, 0);
            $flag = $size < $max;
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public function minSize($value, $min)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min;
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
    public function sizeBetween($value, $min, $max)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min && $size < $max;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public function maxCount($value, $max)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }

        return count($value) < $max;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public function minCount($value, $min)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }

        return count($value) > $min;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public function countBetween($value, $min, $max)
    {
        if (!$this->isArray($value)) {
            $value = array($value);
        }

        return count($value) > $min && count($value) < $max;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isArray($value)
    {
        return !is_null($value[0]);
    }
}
