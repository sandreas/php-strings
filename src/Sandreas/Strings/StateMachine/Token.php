<?php


namespace Sandreas\Strings\StateMachine;


class Token
{
    public $type;
    public $value;

    public function __construct($type, $value="")
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function append($string)
    {
        $this->value .= $string;
    }

    public function orNullOnEmptyValue()
    {
        if ((string)$this->value === "") {
            return null;
        }
        return $this;
    }
}