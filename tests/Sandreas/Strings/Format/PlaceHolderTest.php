<?php

namespace Sandreas\Strings\Format;

use PHPUnit\Framework\TestCase;

class PlaceHolderTest extends TestCase
{

    public function testSimple()
    {
        $subject = new PlaceHolder("a");
        $this->assertTrue($subject->shouldAppend(""));
        $this->assertTrue($subject->shouldAppend("a"));
        $this->assertTrue($subject->shouldAppend("abcd"));

        $this->assertTrue($subject->append("a"));
        $this->assertEquals("a", $subject->value);
    }

    public function testWithPattern()
    {
        $subject = new PlaceHolder("a", "/^[0-9]{1}$/");
        $this->assertFalse($subject->shouldAppend(""));
        $this->assertTrue($subject->shouldAppend("1"));
        $this->assertTrue($subject->shouldAppend("8"));
        $this->assertFalse($subject->shouldAppend("09"));
        $this->assertFalse($subject->shouldAppend("a"));

        $this->assertFalse($subject->append("a"));
        $this->assertTrue($subject->append("1"));
        $this->assertEquals("1", $subject->value);

    }
}
