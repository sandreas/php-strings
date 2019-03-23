<?php


namespace Sandreas\Strings\Format;


class PlaceHolder
{
    public $value;
    public $name;
    protected $pattern;

    public function __construct($name, $pattern = null)
    {
        $this->name = $name;
        $this->value = "";
        $this->pattern = $pattern;
    }

    public function shouldAppend($toAppend)
    {
        return $this->pattern === null || preg_match($this->pattern, $this->value . $toAppend);
    }


    public function append($toAppend)
    {
        if (!$this->shouldAppend($toAppend)) {
            return false;
        }
        $this->value .= $toAppend;
        return true;
    }
}