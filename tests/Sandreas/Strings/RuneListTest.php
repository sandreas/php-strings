<?php

namespace Sandreas\Strings;


use PHPUnit\Framework\TestCase;

class RuneListTest extends TestCase
{

    const UNICODE_STRING = "asdf €€€€ ölkj";
    const EURO_SIGN = "€";

    protected $WIN_1252_STRING;

    public function setUp()
    {
        $this->WIN_1252_STRING = mb_convert_encoding(static::UNICODE_STRING, RuneList::CHARSET_WIN_1252, RuneList::CHARSET_UTF_8);

    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Provided string is not encoded in specified charset utf-8
     *
     */
    public function testInvalidCharsetException()
    {
        new RuneList($this->WIN_1252_STRING);
    }

    public function testToString()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertEquals(static::UNICODE_STRING, (string)$subject);
    }

    public function testToStringWithOtherCharset()
    {
        $subject = new RuneList($this->WIN_1252_STRING, RuneList::CHARSET_WIN_1252);
        $this->assertEquals(static::UNICODE_STRING, (string)$subject);
    }

    /**
     * @throws \Exception
     */
    public function testArrayAccess()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertTrue($subject->offsetExists(0));
        $this->assertFalse($subject->offsetExists(mb_strlen(static::UNICODE_STRING)));
        $this->assertEquals(static::EURO_SIGN, $subject->offsetGet(6));

        $subject->offsetSet(6, "a");
        $this->assertEquals("a", $subject->offsetGet(6));
        $subject->offsetUnset(0);
        $this->assertEquals("s", $subject->offsetGet(0));

        $this->assertEquals("s", $subject->rewind());
        $this->assertFalse($subject->prev());
        $this->assertFalse($subject->valid());


    }
}
