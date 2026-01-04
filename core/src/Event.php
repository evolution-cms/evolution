<?php namespace EvolutionCMS;

class Event implements Interfaces\EventInterface
{
    public $name = '';
    public $_propagate = true;
    /**
     * @deprecated use setOutput(), getOutput()
     * @var string
     */
    public $_output;
    public $activated = false;
    public $activePlugin = '';
    public $params = [];
    /**
     * @var null|Event
     */
    private $previousEvent;

    /**
     * @param string $name Name of the event
     */
    public function __construct($name = "")
    {
        $this->_resetEventObject();
        $this->name = $name;
    }

    /**
     * @param Event $event
     */
    public function setPreviousEvent($event)
    {
        $this->previousEvent = $event;
    }

    public function getPreviousEvent()
    {
        return $this->previousEvent;
    }

    /**
     * Display a message to the user
     *
     * @global array $SystemAlertMsgQueque
     * @param string $msg The message
     */
    public function alert($msg)
    {
        global $SystemAlertMsgQueque;
        if ($msg == "") {
            return;
        }
        if (is_array($SystemAlertMsgQueque)) {
            $title = '';
            if ($this->name && $this->activePlugin) {
                $title = '<div><b>'.$this->activePlugin.'</b> - <span style="color:maroon;">'.$this->name.'</span></div>';
            }
            $SystemAlertMsgQueque[] = $title.'<div style="margin-left:10px;margin-top:3px;">'.$msg.'</div>';
        }
    }

    /**
     * Output
     *
     * @param string $msg
     * @deprecated see addOutput
     */
    public function output($msg)
    {
        $this->addOutput($msg);
    }

    /**
     * @param mixed $data
     */
    public function addOutput($data)
    {
        if (\is_scalar($data)) {
            $this->_output .= $data;
        }
    }

    /**
     * @param mixed $data
     */
    public function setOutput($data)
    {
        $this->_output = $data;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Stop event propogation
     */
    public function stopPropagation()
    {
        $this->_propagate = false;
    }

    public function _resetEventObject()
    {
        unset ($this->returnedValues);
        $this->name = '';
        $this->setOutput(null);
        $this->_propagate = true;
        $this->activated = false;
    }
}
