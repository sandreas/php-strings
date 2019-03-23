<?php

namespace Sandreas\Strings\Format;

use PHPUnit\Framework\TestCase;

class PlaceHolderTest extends TestCase
{

    public function testSimple()
    {
        $subject = new PlaceHolder("a");
        $this->assertTrue($subject->matchesAfterAppend(""));
        $this->assertTrue($subject->matchesAfterAppend("a"));
        $this->assertTrue($subject->matchesAfterAppend("abcd"));

        $subject->append("a");
        $this->assertEquals("a", $subject->value);
    }

    public function testWithPattern()
    {
        $subject = new PlaceHolder("a", "/^[0-9]{1}$/");
        $this->assertFalse($subject->matchesAfterAppend(""));
        $this->assertTrue($subject->matchesAfterAppend("1"));
        $this->assertTrue($subject->matchesAfterAppend("8"));
        $this->assertFalse($subject->matchesAfterAppend("09"));
        $this->assertFalse($subject->matchesAfterAppend("a"));

        $subject->append("a");
        $subject->append("1");
        $this->assertEquals("a1", $subject->value);

    }
}
