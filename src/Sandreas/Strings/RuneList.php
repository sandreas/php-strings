<?php


namespace Sandreas\Strings;


use ArrayAccess;
use Countable;
use \Exception;
use InvalidArgumentException;
use SeekableIterator;

class RuneList implements ArrayAccess, SeekableIterator, Countable
{
    const CARRIAGE_RETURN = "\r";
    const LINE_FEED = "\n";
    const CHARSET_UTF_8 = "utf-8";
    const CHARSET_WIN_1252 = "windows-1252";

    protected $position = 0;
    /** @var string[] */
    protected $runes = [];

    public function __construct($string = "", $charset = self::CHARSET_UTF_8)
    {
        $this->append($string, $charset);
    }

    public function append($string, $charset = self::CHARSET_UTF_8)
    {
        if ($string === "") {
            return;
        }
        if ($charset !== static::CHARSET_UTF_8) {
            $string = mb_convert_encoding($string, static::CHARSET_UTF_8, $charset);
        }

        if (!static::isUtf8($string)) {
            throw new InvalidArgumentException(sprintf("Provided string is not encoded in specified charset %s", $charset));
        }

        $this->runes = array_merge($this->runes, preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY));
    }

    private static function isUtf8($string)
    {
        return preg_match("//u", $string);
    }

    public function __toString()
    {
        return implode($this->runes);
    }

    public function offsetExists($offset)
    {
        return isset($this->runes[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->runes[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        if (mb_strlen($value) !== 1) {
            throw new Exception("only runes of length 1 are allowed");
        }
        $this->runes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        array_splice($this->runes, $offset, 1);
    }

    /**
     * @param int $position
     * @return mixed|void
     */
    public function seek($position)
    {
        if (!$this->valid() || $position <= 0) {
            $this->rewind();
        }
        $offset = $this->key();
        if ($position === $offset) {
            return;
        }

        $count = $this->count();
        if ($position > $count) {
            $this->end();
            return;
        }

        while ($position < $offset) {
            $this->prev();
            $position++;
        }

        while ($offset < $position) {
            $this->next();
            $offset++;
        }
    }

    public function valid()
    {
        return current($this->runes) !== false;
    }

    public function rewind()
    {
        $this->position = 0;
        return reset($this->runes);
    }

    public function key()
    {
        return key($this->runes);
    }

    public function prev()
    {
        if ($value = prev($this->runes)) {
            $this->position--;
            return $value;
        }
        return false;
    }

    public function next()
    {
        if ($value = next($this->runes)) {
            $this->position++;
            return $value;
        }
        return false;
    }

    public function current()
    {
        return current($this->runes);
    }

    public function end()
    {
        $this->position = $this->count() - 1;
        return end($this->runes);
    }

    public function count()
    {
        return count($this->runes);
    }

    public function offset($offset)
    {
        return $this->runes[$this->key() + $offset] ?? null;
    }

    public function position($position)
    {
        return $this->runes[$position] ?? null;
    }

    public function eof()
    {
        return $this->position >= $this->count() - 1;
    }

    /**
     * @param $offset
     * @param null $length
     * @return RuneList
     */
    public function slice($offset, $length = null)
    {
        return static::fromRunes(array_slice($this->runes, $offset, $length));
    }

    public function shift()
    {
        $this->position--;
        return array_shift($this->runes);
    }

    public function pop()
    {
        $this->position--;
        return array_pop($this->runes);
    }

    public function peek()
    {
        return $this->offset(1);
    }

    public function poke()
    {
        $returnValue = $this->current();
        $this->next();
        return $returnValue;
    }


    private static function fromRunes(array $runes)
    {
        $instance = new static;
        $instance->runes = $runes;
        $instance->rewind();
        return $instance;
    }


}