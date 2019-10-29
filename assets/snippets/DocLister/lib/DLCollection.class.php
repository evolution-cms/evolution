<?php
include_once(MODX_BASE_PATH . "assets/lib/Helpers/Collection.php");

/**
 * Class DLCollection
 */
class DLCollection extends Helpers\Collection
{
    /**
     * Объект DocumentParser - основной класс MODX
     * @var DocumentParser
     * @access protected
     */
    protected $modx = null;

    /**
     * DLCollection constructor.
     * @param DocumentParser $modx
     * @param mixed $data
     */
    public function __construct(DocumentParser $modx, $data = array())
    {
        $this->modx = $modx;
        switch (true) {
            case is_resource($data):
            case (is_object($data) && ($data instanceof mysqli_result || $data instanceof PDOStatement)):
                $this->fromQuery($data, false);
                break;
            case is_array($data):
                $this->data = $data;
                break;
            case (is_object($data) && $data instanceof IteratorAggregate):
                foreach ($data as $key => $item) {
                    $this->add($item, $key);
                }
                break;
            default:
                $this->add($data);
        }
    }

    /**
     * @param string|resource|mysqli_result $q
     * @param bool $exec
     * @return int
     */
    public function fromQuery($q, $exec = true)
    {
        $i = 0;
        if ($exec) {
            $q = $this->modx->db->query($q);
        }
        while ($row = $this->modx->db->getRow($q)) {
            $data = $this->create($row);
            $this->add($data);
            $i++;
        }

        return $i;
    }

    /**
     * @param array $data
     * @return static
     */
    public function create(array $data = array())
    {
        return new static($this->modx, $data);
    }

}
