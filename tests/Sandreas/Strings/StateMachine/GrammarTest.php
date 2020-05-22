<?php

namespace Sandreas\Strings\StateMachine;

use Mockery as m;
use PHPUnit\Framework\TestCase;


class GrammarTest extends TestCase
{
    const TOKEN_SPACE = 0;
    const TOKEN_NONE_SPACE = 1;
    /**
     * @var m\LegacyMockInterface|m\MockInterface|Scanner
     */
    private $mockScanner;

    public function setUp()
    {
        $this->mockScanner = m::mock(Scanner::class);

    }

    /**
     * @throws TokenizeException
     */
    public function testEmptyGrammar()
    {
        $this->expectExceptionObject(new TokenizeException("Could not build next token - no matching token generator found in grammar or no token returned"));
        $subject = new Grammar();
        $subject->buildNextToken($this->mockScanner);
    }

    /**
     * @throws TokenizeException
     */
    public function testSimpleGrammar()
    {
        $subject = new Grammar([
            function (Scanner $scanner) {
                $tokenValue = "";
                while ($scanner->peek() === " ") {
                    $tokenValue .= $scanner->poke();
                }
                return new Token(static::TOKEN_SPACE, $tokenValue);
            },
            function (Scanner $scanner) {
                $tokenValue = "";
                while ($scanner->peek() !== " " && $scanner->peek() !== null) {
                    $tokenValue .= $scanner->poke();
                }
                return new Token(static::TOKEN_NONE_SPACE, $tokenValue);
            },
        ]);

        $scanner = new Scanner("   x");
        $token = $subject->buildNextToken($scanner);
        $this->assertEquals("   ", $token->value);
        $this->assertEquals("x", $scanner->peek());
    }
}
