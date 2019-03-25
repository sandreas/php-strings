<?php

namespace Sandreas\Strings\Format;

use PHPUnit\Framework\TestCase;

class PlaceHolderTest extends TestCase
{

    public function testSimple()
    {
        $subject = new PlaceHolder("a");
        $this->assertTrue($subject->matches(""));
        $this->assertTrue($subject->matches("a"));
        $this->assertTrue($subject->matches("abcd"));

        $subject->setValue("aaa");
        $this->assertEquals("aaa", $subject->value);
    }

    public function testWithPattern()
    {
        $subject = new PlaceHolder("a", "/^[0-9]{1}$/");
        $this->assertFalse($subject->matches(""));
        $this->assertTrue($subject->matches("1"));
        $this->assertTrue($subject->matches("8"));
        $this->assertFalse($subject->matches("09"));
        $this->assertFalse($subject->matches("a"));

        $subject->setValue("1");
        $this->assertEquals("1", $subject->value);

    }
}
