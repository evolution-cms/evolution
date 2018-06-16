<?php

use EvolutionCMS\Core as DocumentParser;
/**
 * Class sqlHelper
 */
class sqlHelper
{

    /**
     * @param $field
     * @param string $table
     * @return bool|float|int|string
     */
    public static function tildeField($field, $table = '')
    {
        $out = '';
        if (!empty($field) && is_scalar($field)) {
            if (!empty($table) && is_scalar($table) && $tmp = strpos($field, $table)) {
                $tmp = substr($field, $tmp + strlen($table), 1);
                if ($tmp != '.' && $tmp != '`') {
                    $field = $table . "." . $field;
                } else {
                    $out = $field;
                }
            } elseif (empty($table) && strpos($field, "`")) {
                $out = $field;
            }
            if (empty($out)) {
                $field = explode(".", $field);
                foreach ($field as &$f) {
                    $f = "`" . str_replace("`", "", $f) . "`";
                }
                $out = implode(".", $field);
            }
        }

        return $out;
    }

    /**
     * @param $string
     * @param string $mode
     * @return mixed|string
     */
    public static function trimLogicalOp($string, $mode = '')
    {
        $regex = 'AND|and|OR|or|\&\&|\|\||NOT|not|\!';
        switch ($mode) {
            case 'right':
                $regex = '\s+(' . $regex . ')\s*$';
                break;
            case 'left':
                $regex = '^\s*(' . $regex . ')\s+';
                break;
            default:
                $regex = '(^\s*(' . $regex . ')\s+)|(\s+(' . $regex . ')\s*$)';
                break;
        }

        return is_scalar($string) ? preg_replace("/{$regex}/", "", $string) : "";
    }

    /**
     * Экранирование строки в SQL запросе LIKE
     * @see: http://stackoverflow.com/a/3683868/2323306
     *
     * @param DocumentParser $modx
     * @param string $field поле по которому осуществляется поиск
     * @param string $value искомое значение
     * @param string $escape экранирующий символ
     * @param string $tpl шаблон подстановки значения в SQL запрос
     * @return string строка для подстановки в SQL запрос
     */
    public static function LikeEscape(DocumentParser $modx, $field, $value, $escape = '=', $tpl = '%[+value+]%')
    {
        $str = '';
        $escaped = false;
        if (!empty($field) && is_string($field) && is_scalar($value) && $value !== '') {
            $field = sqlHelper::tildeField($field);
            if (is_scalar($escape) && !empty($escape) && !in_array($escape, array("_", "%", "'"))) {
                $str = str_replace(
                    array($escape, '_', '%'),
                    array($escape . $escape, $escape . '_', $escape . '%'),
                    $value
                );
                $escaped = true;
            }
            $str = $modx->getDatabase()->escape($str);
            $str = str_replace('[+value+]', $str, $tpl);

            if ($escaped) {
                $str = "{$field} LIKE '{$str}' ESCAPE '{$escape}'";
            } else {
                $str = "{$field} LIKE '{$str}'";
            }
        }

        return $str;
    }

}
