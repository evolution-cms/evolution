<?php
/**
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author kabachello <kabachnik@hotmail.com>
 */

/**
 * Class filterDocLister
 */
abstract class filterDocLister
{
    /**
     * Объект унаследованный от абстрактоного класса DocLister
     * @var DocLister $DocLister
     * @access protected
     */
    protected $DocLister;

    /**
     * Объект DocumentParser - основной класс MODX
     * @var DocumentParser
     * @access protected
     */
    protected $modx;

    /**
     * Алиас таблицы которая подключается для фильтрации
     * @var string
     * @access protected
     */
    protected $tableAlias = null;

    /**
     * Поле по которому происходит фильтрация
     * @var string
     * @access protected
     */
    protected $field = '';

    /**
     * Вид сопоставления поля со значением
     * @var string
     * @access protected
     */
    protected $operator = '';

    /**
     * Значение которое учавствует в фильтрации
     * @var string
     * @access protected
     */
    protected $value = '';

    /**
     * Номер фильтра в общем списке фильтров
     * @var int
     * @access protected
     */
    protected $totalFilters = 0;

    /**
     * Запуск фильтра
     *
     * @param DocLister $DocLister экземпляр класса DocLister
     * @param string $filter строка с условиями фильтрации
     * @return bool
     */
    public function init(DocLister $DocLister, $filter)
    {
        $this->DocLister = $DocLister;
        $this->modx = $this->DocLister->getMODX();
        $this->totalFilters = $this->DocLister->getCountFilters();

        return $this->parseFilter($filter);
    }

    /**
     * Получение строки для подстановки в секцию WHERE SQL запроса
     *
     * @return string
     */
    abstract public function get_where();

    /**
     * Получение строки для подстановки в SQL запрос после подключения основной таблицы
     *
     * @return string
     */
    abstract public function get_join();

    /**
     * Разбор строки фильтрации
     *
     * @param string $filter строка фильтрации
     * @return bool результат разбора фильтра
     */
    protected function parseFilter($filter)
    {
        // first parse the give filter string
        $parsed = explode(':', $filter, 4);
        $this->field = APIHelpers::getkey($parsed, 1);
        $this->operator = APIHelpers::getkey($parsed, 2);
        $this->value = APIHelpers::getkey($parsed, 3);

        // exit if something is wrong
        return !(empty($this->field) || empty($this->operator) || is_null($this->value));
    }

    /**
     * Установка алиаса таблицы
     * @param string $value алиас
     */
    public function setTableAlias($value)
    {
        $this->tableAlias = $value;
    }

    /**
     * Конструктор условий для WHERE секции
     *
     * @param string $table_alias алиас таблицы
     * @param string $field поле для фильтрации
     * @param string $operator оператор сопоставления
     * @param string $value искомое значение
     * @return string
     */
    protected function build_sql_where($table_alias, $field, $operator, $value)
    {
        $this->DocLister->debug->debug(
            'Build SQL query for filters: ' . $this->DocLister->debug->dumpData(func_get_args()),
            'buildQuery',
            2
        );
        $output = sqlHelper::tildeField($field, $table_alias);

        $delimiter = $this->DocLister->getCFGDef('filter_delimiter', ',');

        switch ($operator) {
            case '=':
            case 'eq':
            case 'is':
                $output .= " = '" . $this->modx->db->escape($value) . "'";
                break;
            case '!=':
            case 'no':
            case 'isnot':
                $output = '(' . $output . " != '" . $this->modx->db->escape($value) . "' OR " . $output . ' IS NULL)';
                break;
            case 'isnull':
                $output .= ' IS NULL';
                break;
            case 'isnotnull':
                $output .= ' IS NOT NULL';
                break;
            case '>':
            case 'gt':
                $output .= ' > ' . str_replace(',', '.', floatval($value));
                break;
            case '<':
            case 'lt':
                $output .= ' < ' . str_replace(',', '.', floatval($value));
                break;
            case '<=':
            case 'elt':
                $output .= ' <= ' . str_replace(',', '.', floatval($value));
                break;
            case '>=':
            case 'egt':
                $output .= ' >= ' . str_replace(',', '.', floatval($value));
                break;
            case '%':
            case 'like':
                $output = $this->DocLister->LikeEscape($output, $value);
                break;
            case 'like-r':
                $output = $this->DocLister->LikeEscape($output, $value, '=', '[+value+]%');
                break;
            case 'like-l':
                $output = $this->DocLister->LikeEscape($output, $value, '=', '%[+value+]');
                break;
            case 'regexp':
                $output .= " REGEXP '" . $this->modx->db->escape($value) . "'";
                break;
            case 'against':
                /** content:pagetitle,description,content,introtext:against:искомая строка */
                if (trim($value) != '') {
                    $field = explode(",", $this->field);
                    $field = implode(",", $this->DocLister->renameKeyArr($field, $this->getTableAlias()));
                    $output = "MATCH ({$field}) AGAINST ('{$this->modx->db->escape($value)}*')";
                }
                break;
            case 'containsOne':
                $containsOneDelimiter = $this->DocLister->getCFGDef('filter_delimiter:containsOne', $delimiter);
                $words = explode($containsOneDelimiter, $value);
                $word_arr = array();
                foreach ($words as $word) {
                    /**
                     * $word оставляю без trim, т.к. мало ли, вдруг важно найти не просто слово, а именно его начало
                     * Т.е. хочется найти не слово содержащее $word, а начинающееся с $word. Для примера:
                     * искомый $word = " когда". С trim найдем "...мне некогда..." и "...тут когда-то...";
                     * Без trim будт обнаружено только "...тут когда-то..."
                     */
                    if (($likeWord = $this->DocLister->LikeEscape($output, $word)) !== '') {
                        $word_arr[] = $likeWord;
                    }
                }
                if (!empty($word_arr)) {
                    $output = '(' . implode(' OR ', $word_arr) . ')';
                } else {
                    $output = '';
                }
                break;
            case 'containsAll':
                $containsAllDelimiter = $this->DocLister->getCFGDef('filter_delimiter:containsAll', $delimiter);
                $words = explode($containsAllDelimiter, $value);
                $word_arr = array();
                foreach ($words as $word) {
                    if (($likeWord = $this->DocLister->LikeEscape($output, $word)) !== '') {
                        $word_arr[] = $likeWord;
                    }
                }
                if (!empty($word_arr)) {
                    $output = '(' . implode(' AND ', $word_arr) . ')';
                } else {
                    $output = '';
                }
            break;
            case 'in':
                $inDelimiter = $this->DocLister->getCFGDef('filter_delimiter:in', $delimiter);
                $output .= ' IN(' . $this->DocLister->sanitarIn($value, $inDelimiter, true) . ')';
                break;
            case 'notin':
                $notinDelimiter = $this->DocLister->getCFGDef('filter_delimiter:notin', $delimiter);
                $output = '(' . $output . ' NOT IN(' . $this->DocLister->sanitarIn($value, $notinDelimiter, true) . ') OR ' . $output . ' IS NULL)';
                break;
            default:
                $output = '';
        }
        $this->DocLister->debug->debugEnd("buildQuery");

        return $output;
    }

    /**
     * Получение алиаса таблицы по которой идет выборка
     * @return string
     */
    public function getTableAlias()
    {
        return $this->tableAlias;
    }

}
