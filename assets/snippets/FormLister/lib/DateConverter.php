<?php namespace FormLister;

use DateTime;

/**
 * Trait DateConverter
 * @package FormLister
 */
Trait DateConverter {
    public $dateFormat = '';

    /**
     * @param $value
     * @return bool|DateTime
     */
    public function toTimestamp($value) {
        $date = false;
        if (!empty($value) && !empty($this->dateFormat)) {
            $date = DateTime::createFromFormat($this->dateFormat, $value);
            if ($date !== false) {
                $date = $date->getTimestamp();
            }
        }

        return $date;
    }

    /**
     * @param $value
     */
    public function fromTimestamp($value) {
        $date = false;
        if (!empty($value) && !empty($this->dateFormat)) {
            $date = date($this->dateFormat, $value);
        }

        return $date;
    }
}
