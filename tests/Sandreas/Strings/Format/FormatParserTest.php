<?php

namespace Sandreas\Strings\Format;


use Exception;
use PHPUnit\Framework\TestCase;

class FormatParserTest extends TestCase
{
    const PLACEHOLDER_AUTHOR = "a";
    const PLACEHOLDER_SERIES = "s";
    const PLACEHOLDER_SERIES_PART = "p";
    const PLACEHOLDER_TITLE = "t";

    const PLACEHOLDER_DAY = "d";
    const PLACEHOLDER_MONTH = "m";
    const PLACEHOLDER_YEAR = "y";
    const PLACEHOLDER_GENRE = "g";

    const FORMAT_SIMPLE = "%a/%t";
    const FORMAT_WITH_SEPARATOR_PREFIX = "--- %a/%s/%p - %t/";
    const FORMAT_WITH_ESCAPE = "%%%a/%p%% %s";
    const FORMAT_WITH_DOTTED_PREFIX = "../data/batch/%a/%s/%p - %t";
    const FORMAT_WITHOUT_SEPARATORS = "%d%m%y";

    /** @var FormatParser */
    protected $subject;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        $this->subject = new FormatParser(
            new PlaceHolder(static::PLACEHOLDER_GENRE),
            new PlaceHolder(static::PLACEHOLDER_AUTHOR),
            new PlaceHolder(static::PLACEHOLDER_SERIES),
            new PlaceHolder(static::PLACEHOLDER_SERIES_PART),
            new PlaceHolder(static::PLACEHOLDER_TITLE),
            new PlaceHolder(static::PLACEHOLDER_DAY),
            new PlaceHolder(static::PLACEHOLDER_MONTH),
            new PlaceHolder(static::PLACEHOLDER_YEAR)
        );
    }

    /**
     * @throws Exception
     */
    public function testParseFormatSimple()
    {
        $this->assertTrue($this->subject->parseFormat(static::FORMAT_WITH_SEPARATOR_PREFIX, "--- Patrick Rothfuss/The Kingkiller Chronicles/1 - The name of the wind/"));
        $this->assertEquals("Patrick Rothfuss", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("The Kingkiller Chronicles", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES));
        $this->assertEquals("1", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES_PART));
        $this->assertEquals("The name of the wind", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_TITLE));
    }

    /**
     * @throws Exception
     */
    public function testParseFormatWithStringPercentSign()
    {
        $this->assertTrue($this->subject->parseFormat(static::FORMAT_SIMPLE, "John Doe/100% Dirt Bike"));
        $this->assertEquals("John Doe", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("100% Dirt Bike", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_TITLE));
    }

    /**
     * @throws Exception
     */
    public function testParseFormatWithFormatPercentSign()
    {
        $this->assertTrue($this->subject->parseFormat(static::FORMAT_WITH_ESCAPE, "%John Doe/100% Dirt Bike"));
        $this->assertEquals("John Doe", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_AUTHOR));
        $this->assertEquals("100", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES_PART));
        $this->assertEquals("Dirt Bike", $this->subject->getPlaceHolderValue(static::PLACEHOLDER_SERIES));
    }

    /**
     * @throws Exception
     */
    public function testImproperFormatString()
    {
        $this->assertFalse($this->subject->parseFormat(static::FORMAT_WITH_DOTTED_PREFIX, "../data/batch/An author/a book"));
    }

    /**
     * @throws Exception
     */
    public function testParseWithRegex()
    {
        $subject = new FormatParser(
            new PlaceHolder("d", "/^[0-9]{2}$/"),
            new PlaceHolder("m", "/^[0-9]{2}$/"),
            new PlaceHolder("y", "/^[0-9]{4}$/")
        );
        $this->assertTrue($subject->parseFormat(static::FORMAT_WITHOUT_SEPARATORS, "11122018"));
        $this->assertEquals("11", $subject->getPlaceHolderValue(static::PLACEHOLDER_DAY));
        $this->assertEquals("12", $subject->getPlaceHolderValue(static::PLACEHOLDER_MONTH));
        $this->assertEquals("2018", $subject->getPlaceHolderValue(static::PLACEHOLDER_YEAR));
    }

    /**
     * @throws Exception
     */
    public function testTrimSeparatorPrefix()
    {

        $this->assertEquals(static::FORMAT_SIMPLE, $this->subject->trimSeparatorPrefix(static::FORMAT_SIMPLE));
        $this->assertEquals("%a/%s/%p - %t/", $this->subject->trimSeparatorPrefix(static::FORMAT_WITH_SEPARATOR_PREFIX));
        $this->assertEquals("%a/%s/%p - %t", $this->subject->trimSeparatorPrefix(static::FORMAT_WITH_DOTTED_PREFIX));
        $this->assertEquals("%a/%p%% %s", $this->subject->trimSeparatorPrefix(static::FORMAT_WITH_ESCAPE));
        $this->assertEquals(static::FORMAT_WITHOUT_SEPARATORS, $this->subject->trimSeparatorPrefix(static::FORMAT_WITHOUT_SEPARATORS));
        $this->assertEquals("%g/%a/%s/%p - %t/", $this->subject->trimSeparatorPrefix("../data/issue_60/%g/%a/%s/%p - %t/"));

    }

    /**
     * @throws Exception
     */
    public function testFormat()
    {
        $stringWithSeparatorPrefix = "--- Patrick Rothfuss/The Kingkiller Chronicles/1 - The name of the wind/";
        $this->subject->parseFormat(static::FORMAT_WITH_SEPARATOR_PREFIX, $stringWithSeparatorPrefix);
        $this->assertEquals($stringWithSeparatorPrefix, $this->subject->format(static::FORMAT_WITH_SEPARATOR_PREFIX));
    }


    /**
     * @throws Exception
     */
    public function testSpecificIssue()
    {
        $formatString = "../data/issue_60/%g/%a/%s/%p - %t/";
        $string = "../data/issue_60/Fantasy/Joanne/Harry Potter/0 - Zero Test/";
        $this->assertEquals("%g/%a/%s/%p - %t/", $this->subject->trimSeparatorPrefix($formatString));
        $this->assertTrue($this->subject->parseFormat($formatString, $string));

    }
}


