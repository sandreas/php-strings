<?php


namespace Sandreas\Strings\StateMachine;


use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class Scanner implements Countable, IteratorAggregate, ArrayAccess
{
    const CHARSET_UTF_8 = "utf-8";
    /**
     * @var array
     */
    protected $chars = [];

    protected $offset = 0;

    public function __construct($string = "", $charset = self::CHARSET_UTF_8)
    {
        $this->set($string, $charset);
    }

    public function set($string, $charset = self::CHARSET_UTF_8) {
        $this->chars = [];
        $this->offset = 0;
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

        $this->chars = array_merge($this->chars, preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY));
    }

    private static function isUtf8($string)
    {
        return preg_match("//u", $string);
    }


    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->chars);
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->chars);
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->chars[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->chars[$offset];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->chars[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->chars[$offset]);
    }


    public function peek()
    {
        return $this->offset(0);
    }

    public function offset($offset)
    {
        return $this->chars[$this->offset + $offset] ?? null;
    }

    public function poke()
    {
        $returnValue = $this->current();
        $this->next();
        return $returnValue;
    }

    public function next()
    {
        if ($this->offset < count($this->chars)) {
            $this->offset++;
        }

        if ($value = next($this->chars) !== false) {
            return $value;
        }
        return false;
    }

    public function current()
    {
        $value = current($this->chars);
        return $value === false ? null : $value;
    }

    public function key()
    {
        return $this->offset;
    }

    public function endReached()
    {
        return $this->peek() === null;
    }
}
