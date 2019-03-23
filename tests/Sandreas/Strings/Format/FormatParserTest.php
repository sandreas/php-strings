<?php

namespace Sandreas\Strings\Format;


use PHPUnit\Framework\TestCase;

class FormatParserTest extends TestCase
{
    const PLACEHOLDER_AUTHOR = "a";
    const PLACEHOLDER_SERIES = "s";
    const PLACEHOLDER_SERIES_PART = "p";
    const PLACEHOLDER_TITLE = "t";

    /** @var FormatParser */
    protected $subject;

    public function setUp()
    {
        $this->subject = new FormatParser(
            new PlaceHolder(static::PLACEHOLDER_AUTHOR),
            new PlaceHolder(static::PLACEHOLDER_SERIES),
            new PlaceHolder(static::PLACEHOLDER_SERIES_PART),
            new PlaceHolder(static::PLACEHOLDER_TITLE)
        );
    }

    /**
     * @throws \Exception
     */
    public function testParseFormatSimple()
    {
        $this->subject->parseFormat("--- %a/%s/%p - %t/", "--- Patrick Rothfuss/The Kingkiller Chronicles/1 - The name of the wind/");
        $this->assertEquals("Patrick Rothfuss", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("The Kingkiller Chronicles", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES));
        $this->assertEquals("1", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES_PART));
        $this->assertEquals("The name of the wind", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_TITLE));
    }

    /**
     * @throws \Exception
     */
    public function testParseFormatWithStringPercentSign()
    {
        $this->subject->parseFormat("%a/%t", "John Doe/100% Dirt Bike");
        $this->assertEquals("John Doe", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("100% Dirt Bike", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_TITLE));
    }

    /**
     * @throws \Exception
     */
    public function testParseFormatWithFormatPercentSign()
    {
        $this->subject->parseFormat("%a/%p%% %s", "John Doe/100% Dirt Bike");
        $this->assertEquals("John Doe", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("100", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES_PART));
        $this->assertEquals("Dirt Bike", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES));
    }

}
