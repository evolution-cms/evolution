<?php

/**
 * Class APIhelpers
 */
class APIhelpers
{

    /**
     * Преобразует первый символ в нижний регистр
     * @param $str
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    public static function mb_lcfirst($str, $encoding = 'UTF-8')
    {
        return mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr(
            $str,
            1,
            mb_strlen($str),
            $encoding
        );
    }

    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     * @param string $str - строка
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    public static function mb_ucfirst($str, $encoding = 'UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr(
            $str,
            1,
            mb_strlen($str),
            $encoding
        );

        return $str;
    }

    /**
     * Обрезание текста по длине с поиском последнего полностью вмещающегося слова и удалением лишних крайних знаков пунктуации.
     *
     * @author Agel_Nash <Agel_Nash@xaker.ru>
     * @version 0.1
     *
     * @param string $html HTML текст
     * @param integer $len максимальная длина строки
     * @param string $encoding кодировка
     * @return string
     */
    public static function mb_trim_word($html, $len, $encoding = 'UTF-8')
    {
        $text = trim(preg_replace('|\s+|', ' ', strip_tags($html)));
        $text = mb_substr($text, 0, $len + 1, $encoding);
        if (mb_substr($text, -1, null, $encoding) == ' ') {
            $out = trim($text);
        } else {
            $out = mb_substr($text, 0, mb_strripos($text, ' ', null, $encoding), $encoding);
        }

        return preg_replace("/(([\.,\-:!?;\s])|(&\w+;))+$/ui", "", $out);
    }

    /**
     * Получение значения по ключу из массива, либо возврат значения по умолчанию
     *
     * @param mixed $data массив
     * @param string $key ключ массива
     * @param mixed $default null значение по умолчанию
     * @param Closure $validate null функция дополнительной валидации значения (должна возвращать true или false)
     * @return mixed
     */
    public static function getkey($data, $key, $default = null, $validate = null)
    {
        $out = $default;
        if (is_array($data) && (is_int($key) || is_string($key)) && $key !== '' && array_key_exists($key, $data)) {
            $out = $data[$key];
        }
        if (!empty($validate) && is_callable($validate)) {
            $out = (($validate($out) === true) ? $out : $default);
        }
        return $out;
    }

    /**
     * Email validate
     *
     * @category   validate
     * @version    0.1
     * @license    GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $email проверяемый email
     * @param boolean $dns проверять ли DNS записи
     * @return boolean Результат проверки почтового ящика
     * @author Anton Shevchuk
     */
    public static function emailValidate($email, $dns = true)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list(, $domain) = explode("@", $email, 2);
            if (!$dns || ($dns && checkdnsrr($domain, "MX") && checkdnsrr($domain, "A"))) {
                $error = false;
            } else {
                $error = 'dns';
            }
        } else {
            $error = 'format';
        }

        return $error;
    }

    /**
     * Password generate
     *
     * @category   generate
     * @version   0.1
     * @license    GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $len длина пароля
     * @param string $data правила генерации пароля
     * @return string Строка с паролем
     * @author Agel_Nash <Agel_Nash@xaker.ru>
     *
     * Расшифровка значений $data
     * "A": A-Z буквы
     * "a": a-z буквы
     * "0": цифры
     * ".": все печатные символы
     *
     * @example
     * $this->genPass(10,"Aa"); //nwlTVzFdIt
     * $this->genPass(8,"0"); //71813728
     * $this->genPass(11,"A"); //VOLRTMEFAEV
     * $this->genPass(5,"a0"); //4hqi7
     * $this->genPass(5,"."); //2_Vt}
     * $this->genPass(20,"."); //AMV,>&?J)v55,(^g}Z06
     * $this->genPass(20,"aaa0aaa.A"); //rtvKja5xb0\KpdiRR1if
     */
    public static function genPass($len, $data = '')
    {
        if ($data == '') {
            $data = 'Aa0.';
        }
        $opt = strlen($data);
        $pass = array();

        for ($i = $len; $i > 0; $i--) {
            switch ($data[rand(0, ($opt - 1))]) {
                case 'A':
                    $tmp = rand(65, 90);
                    break;
                case 'a':
                    $tmp = rand(97, 122);
                    break;
                case '0':
                    $tmp = rand(48, 57);
                    break;
                default:
                    $tmp = rand(33, 126);
            }
            $pass[] = chr($tmp);
        }
        $pass = implode("", $pass);

        return $pass;
    }

    /**
     * @param $data
     * @return bool|false|string
     */
    public static function getEnv($data)
    {
        switch (true) {
            case (isset($_SERVER[$data])):
                $out = $_SERVER[$data];
                break;
            case (isset($_ENV[$data])):
                $out = $_ENV[$data];
                break;
            case ($tmp = getenv($data)):
                $out = $tmp;
                break;
            case (function_exists('apache_getenv') && $tmp = apache_getenv($data, true)):
                $out = $tmp;
                break;
            default:
                $out = false;
        }
        unset($tmp);

        return $out;
    }

    /**
     * User IP
     *
     * @category   validate
     * @version   0.1
     * @license    GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $default IP адрес который будет отдан функцией, если больше ничего не обнаружено
     * @return string IP пользователя
     * @author Agel_Nash <Agel_Nash@xaker.ru>
     *
     * @see http://stackoverflow.com/questions/5036443/php-how-to-block-proxies-from-my-site
     */
    public static function getUserIP($default = '127.0.0.1')
    {
        //Порядок условий зависит от приоритетов
        switch (true) {
            case ($tmp = self::getEnv('HTTP_COMING_FROM')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_X_COMING_FROM')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_VIA')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_FORWARDED')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_FORWARDED_FOR')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_X_FORWARDED')):
                $out = $tmp;
                break;
            case ($tmp = self::getEnv('HTTP_X_FORWARDED_FOR')):
                $out = $tmp;
                break;
            case (!empty($_SERVER['REMOTE_ADDR'])):
                $out = $_SERVER['REMOTE_ADDR'];
                break;
            default:
                $out = false;
        }
        unset($tmp);

        return (false !== $out && preg_match('|^(?:[0-9]{1,3}\.){3,3}[0-9]{1,3}$|', $out, $matches)) ? $out : $default;
    }

    /**
     * @param $data
     * @param string $charset
     * @param array $chars
     * @return array|mixed|string
     */
    public static function sanitarTag(
        $data,
        $charset = 'UTF-8',
        $chars = array(
            '['   => '&#91;',
            '%5B' => '&#91;',
            ']'   => '&#93;',
            '%5D' => '&#93;',
            '{'   => '&#123;',
            '%7B' => '&#123;',
            '}'   => '&#125;',
            '%7D' => '&#125;',
            '`'   => '&#96;',
            '%60' => '&#96;'
        )
    ) {
        switch (true) {
            case is_scalar($data):
                $out = str_replace(
                    array_keys($chars),
                    array_values($chars),
                    is_null($charset) ? $data : self::e($data, $charset)
                );
                break;
            case is_array($data):
                $out = $data;
                foreach ($out as $key => &$val) {
                    $val = self::sanitarTag($val, $charset, $chars);
                }
                break;
            default:
                $out = '';
        }

        return $out;
    }

    /**
     * @param $text
     * @param string $charset
     * @return string
     */
    public static function e($text, $charset = 'UTF-8')
    {
        return is_scalar($text) ? htmlspecialchars($text, ENT_QUOTES, $charset, false) : '';
    }

    /**
     * Проверка строки на наличе запрещенных символов
     * Проверка конечно круто, но валидация русских символов в строке порой завершается не удачей по разным причинам
     * (начиная от кривых настроек сервера и заканчивая кривыми настройками кодировки на сайте)
     *
     * @param string $value Проверяемая строка
     * @param int $minLen Минимальная длина строки
     * @param array $alph Разрешенные алфавиты
     * @param array $mixArray Примесь символов, которые так же могут использоваться в строке
     * @return bool
     */
    public static function checkString($value, $minLen = 1, $alph = array(), $mixArray = array())
    {
        $flag = true;
        $len = mb_strlen($value, 'UTF-8');
        $value = trim($value);
        if (mb_strlen($value, 'UTF-8') == $len) {
            $data = is_array($mixArray) ? $mixArray : array();
            $alph = is_array($alph) ? array_unique($alph) : array();
            foreach ($alph as $item) {
                $item = strtolower($item);
                switch ($item) {
                    case 'rus':
                        $data = array_merge($data, array(
                            'А',
                            'Б',
                            'В',
                            'Г',
                            'Д',
                            'Е',
                            'Ё',
                            'Ж',
                            'З',
                            'И',
                            'Й',
                            'К',
                            'Л',
                            'М',
                            'Н',
                            'О',
                            'П',
                            'Р',
                            'С',
                            'Т',
                            'У',
                            'Ф',
                            'Х',
                            'Ц',
                            'Ч',
                            'Ш',
                            'Щ',
                            'Ъ',
                            'Ы',
                            'Ь',
                            'Э',
                            'Ю',
                            'Я'
                        ));
                        break;
                    case 'num':
                        $tmp = range('0', '9');
                        foreach ($tmp as $t) {
                            $data[] = (string)$t;
                        }
                        break;
                    case 'eng':
                        $data = array_merge($data, range('A', 'Z'));
                        break;
                }
            }
            for ($i = 0; $i < $len; $i++) {
                $chr = mb_strtoupper(mb_substr($value, $i, 1, 'UTF-8'), 'UTF-8');
                if (!in_array($chr, $data, true)) {
                    $flag = false;
                    break;
                }
            }
            $flag = ($flag && $len >= $minLen);
        } else {
            $flag = false;
        }

        return $flag;
    }

    /**
     * @param $IDs
     * @param string $sep
     * @param integer[] $ignore
     * @return array
     * @throws Exception
     */
    public static function cleanIDs($IDs, $sep = ',', $ignore = array())
    {
        $out = array();
        if (!is_array($IDs)) {
            if (is_scalar($IDs)) {
                $IDs = explode($sep, $IDs);
            } else {
                $IDs = array();
                throw new Exception('Invalid IDs list <pre>' . print_r($IDs, 1) . '</pre>');
            }
        }
        foreach ($IDs as $item) {
            $item = trim($item);
            if (is_scalar($item) && (int)$item >= 0) { //Fix 0xfffffffff
                if (!empty($ignore) && in_array((int)$item, $ignore, true)) {
                    $this->log[] = 'Ignore id ' . (int)$item;
                } else {
                    $out[] = (int)$item;
                }
            }
        }
        $out = array_unique($out);

        return $out;
    }

    /**
     * Переменовывание элементов массива
     *
     * @param array $data массив с данными
     * @param string $prefix префикс ключей
     * @param string $suffix суффикс ключей
     * @param string $addPS разделитель суффиксов, префиксов и ключей массива
     * @param string $sep разделитель ключей при склейке многомерных массивов
     * @return array массив с переименованными ключами
     */
    public static function renameKeyArr($data, $prefix = '', $suffix = '', $addPS = '.', $sep = '.')
    {
        $out = array();
        if ($prefix == '' && $suffix == '') {
            $out = $data;
        } else {
            $InsertPrefix = ($prefix != '') ? ($prefix . $addPS) : '';
            $InsertSuffix = ($suffix != '') ? ($addPS . $suffix) : '';
            foreach ($data as $key => $item) {
                $key = $InsertPrefix . $key;
                $val = null;
                switch (true) {
                    case is_scalar($item):
                        $val = $item;
                        break;
                    case is_array($item):
                        $val = self::renameKeyArr($item, $key . $sep, $InsertSuffix, '', $sep);
                        $out = array_merge($out, $val);
                        $val = '';
                        break;
                }
                $out[$key . $InsertSuffix] = $val;
            }
        }

        return $out;
    }
}
