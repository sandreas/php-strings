<?php

namespace Sandreas\Strings;


use PHPUnit\Framework\TestCase;

class RuneListTest extends TestCase
{

    const UNICODE_STRING = "asdf €€€€ ölkj";
    const EURO_SIGN = "€";

    const QUOTE_MAPPING = [
        "=" => "\\",
        ";" => "\\",
        "#" => "\\",
        "\n" => "\\",
    ];
    const QUOTED_STRING = "when a\\=b\\; then \\# with \\\\\nand a new line";
    const UNQUOTED_STRING = "when a=b; then # with \\\nand a new line";

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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage only runes of length 1 are allowed
     */
    public function testArrayAccessException()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $subject->offsetSet(6, "abcd");
    }

    /**
     * @throws \Exception
     */
    public function testSeekKeyCurrentEnd()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $subject->prev();
        $subject->seek(1);
        $this->assertEquals(1, $subject->key());
        $subject->seek(1);
        $this->assertEquals(1, $subject->key());
        $subject->seek(3);
        $this->assertEquals(3, $subject->key());
        $subject->seek(0);
        $this->assertEquals(0, $subject->key());

        $subject->seek(0);
        $this->assertEquals(mb_substr(static::UNICODE_STRING, 0, 1), $subject->current());
        $subject->end();
        $this->assertEquals(mb_strlen($subject) - 1, $subject->key());
    }

    /**
     * @throws \Exception
     */
    public function testCount()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertEquals(mb_strlen(static::UNICODE_STRING), $subject->count());
    }


    /**
     * @throws \Exception
     */
    public function testOffset()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertEquals(0, $subject->key());
        $this->assertEquals("f", $subject->offset(3));
        $this->assertEquals(0, $subject->key());
        $subject->seek(4);
        $this->assertEquals("f", $subject->offset(-1));
    }

    /**
     * @throws \Exception
     */
    public function testPosition()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertEquals(0, $subject->key());
        $this->assertEquals("f", $subject->position(3));
        $this->assertEquals(0, $subject->key());
        $this->assertNull($subject->position(-1));
    }

    /**
     * @throws \Exception
     */
    public function testSlice()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $slice = $subject->slice(3, 5);

        $this->assertEquals(0, $slice->key());
        $this->assertEquals(5, $slice->count());
        $this->assertEquals(mb_substr(static::UNICODE_STRING, 3, 5), (string)$slice);
    }

    public function testAppend()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $subject->append("");
        $this->assertEquals(static::UNICODE_STRING, (string)$subject);
        $subject->append("€");
        $this->assertEquals(static::UNICODE_STRING . "€", (string)$subject);
    }

    public function testEof()
    {
        $subject = new RuneList(static::UNICODE_STRING);
        $this->assertFalse($subject->eof());
        $subject->end();
        $this->assertTrue($subject->eof());
    }

    public function testQuote()
    {
        $subject = new RuneList(static::UNQUOTED_STRING);
        $this->assertEquals(static::QUOTED_STRING, $subject->quote(static::QUOTE_MAPPING)->__toString());
    }

    public function testUnquote()
    {
        $subject = new RuneList(static::QUOTED_STRING);
        $this->assertEquals(static::UNQUOTED_STRING, $subject->unquote(static::QUOTE_MAPPING)->__toString());
    }

    public function testUnquoteEdgeCase()
    {
        $subject = new RuneList("\\\\\\\\\\\\\;test");
        $this->assertEquals("\\\\\\;test", $subject->unquote([
            "\\" => "\\",
            ";" => "\\"
        ])->__toString());
    }

    public function testSeek()
    {
        $subject = new RuneList("abc");
        $subject->seek(5);
        $this->assertEquals(2, $subject->key());
        $subject->seek(1);
        $this->assertEquals(1, $subject->key());
    }

    public function testShift()
    {
        $subject = new RuneList("abc");
        $this->assertEquals("a", $subject->shift());
        $this->assertEquals("b", $subject->shift());

    }

    public function testPeek()
    {
        $subject = new RuneList("abc");
        $this->assertEquals("b", $subject->peek());
    }

    public function testNextEnd()
    {
        $subject = new RuneList("abc");
        $subject->end();
        $this->assertFalse($subject->next());
    }

    public function testPop()
    {
        $subject = new RuneList("ab");
        $this->assertEquals("b", $subject->pop());
        $this->assertEquals("a", $subject->pop());
        $this->assertNull($subject->pop());
    }

    public function testPoke()
    {
        $subject = new RuneList("ab");
        $this->assertEquals("a", $subject->poke());
        $this->assertEquals(1, $subject->key());
    }


}

