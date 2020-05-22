<?php

namespace Sandreas\Strings\StateMachine;

use PHPUnit\Framework\TestCase;

class ScannerTest extends TestCase
{
    const FIRST_POSSIBLE_OFFSET = 0;
    const TEST_STRING = "this is a test string for a scanner";
    /**
     * @var Scanner
     */
    private $subject;

    public function setUp()
    {
        $this->subject = new Scanner(static::TEST_STRING);
    }


    public function testCursorStartsWith()
    {
        $needle = "test string";
        $this->assertFalse($this->subject->cursorStartsWith($needle));
        $this->subject->seek(10);
        $this->assertTrue($this->subject->cursorStartsWith($needle));
    }

    public function testSeek()
    {
        $this->assertEquals(static::FIRST_POSSIBLE_OFFSET, $this->subject->seek(static::FIRST_POSSIBLE_OFFSET));
        $this->assertEquals(static::FIRST_POSSIBLE_OFFSET, $this->subject->seek(-5));
        $this->assertEquals(mb_strlen(static::TEST_STRING), $this->subject->seek(100));
        $this->assertEquals(5, $this->subject->seek(5));
    }

    public function testSeekEnd()
    {
        $this->assertEquals(mb_strlen(static::TEST_STRING), $this->subject->seekEnd());
        $this->assertTrue($this->subject->endReached());
    }

    public function testSeekString()
    {
        $this->assertEquals(2, $this->subject->seekString("is "));
        $this->subject->seek(3);
        $this->assertEquals(5, $this->subject->seekString("is "));
    }
}
