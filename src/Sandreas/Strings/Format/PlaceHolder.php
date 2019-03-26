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

    public function matches($value)
    {
        return $this->pattern === null || preg_match($this->pattern, $value);
    }

}