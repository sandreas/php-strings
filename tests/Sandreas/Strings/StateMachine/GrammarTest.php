<?php

namespace Sandreas\Strings\StateMachine;

use Mockery as m;
use PHPUnit\Framework\TestCase;


class GrammarTest extends TestCase
{
    const TOKEN_SPACE = 1;

    /**
     * @var m\LegacyMockInterface|m\MockInterface|Scanner
     */
    private $mockScanner;

    public function setUp()
    {
        $this->mockScanner = m::mock(Scanner::class);

    }
    public function testEmptyGrammar()
    {
        $subject = new Grammar();
        $this->assertNull($subject->buildNextToken($this->mockScanner));
    }

    public function testSimpleGrammar()
    {
        $subject = new Grammar([
            function (Scanner $scanner) {
                $tokenValue = "";
                while ($scanner->peek() === " ") {
                    $tokenValue .= $scanner->poke();
                }
                return new Token(static::TOKEN_SPACE, $tokenValue);
            }
        ]);

        $scanner = new Scanner("   x");
        $token = $subject->buildNextToken($scanner);
        $this->assertEquals("   ", $token->value);
        $this->assertEquals("x", $scanner->peek());
    }
}
