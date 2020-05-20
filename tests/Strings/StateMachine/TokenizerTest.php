<?php

namespace Strings\StateMachine;

use Sandreas\Strings\StateMachine\Grammar;
use Sandreas\Strings\StateMachine\Scanner;
use Sandreas\Strings\StateMachine\Token;
use Sandreas\Strings\StateMachine\TokenizeException;
use Sandreas\Strings\StateMachine\Tokenizer;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class TokenizerTest extends TestCase
{
    const TOKEN_PLACEHOLDER = 0;
    const TOKEN_NO_PLACEHOLDER = 1;
    /**
     * @var Tokenizer
     */
    private $subject;
    /**
     * @var m\LegacyMockInterface|m\MockInterface|Grammar
     */
    private $mockGrammar;
    /**
     * @var m\LegacyMockInterface|m\MockInterface|Scanner
     */
    private $mockScanner;


    public function setUp()
    {
        $this->mockGrammar = m::mock(Grammar::class);
        $this->mockScanner = m::mock(Scanner::class);
        $this->subject = new Tokenizer($this->mockGrammar);
    }

    /**
     * @throws TokenizeException
     */
    public function testEmptyScanner()
    {
        $this->mockGrammar->shouldNotReceive("buildNextToken");
        $this->mockScanner->shouldReceive("hasNext")->andReturnFalse();
        $this->assertEquals([], $this->subject->tokenize($this->mockScanner));
    }

    /**
     * @throws TokenizeException
     */
    public function testFullSpec()
    {
        $template = "a/testing/%a/with/100%%/value%b";
        $grammar = new Grammar([
            function (Scanner $scanner) {
                $token = new Token(static::TOKEN_NO_PLACEHOLDER);
                while ($scanner->peek() !== null) {
                    if ($scanner->peek() === "%") {
                        if ($scanner->offset(1) === "%") {
                            $token->append($scanner->poke() . $scanner->poke());
                        }
                        return $token->orNullOnEmptyValue();
                    } else {
                        $token->append($scanner->poke());
                    }
                }
                return $token->orNullOnEmptyValue();
            },
            function (Scanner $scanner) {
                if ($scanner->peek() === "%" && $scanner->offset(1) !== "%") {
                    return new Token(static::TOKEN_PLACEHOLDER, $scanner->poke() . $scanner->poke());
                }
                return null;
            }
        ]);
        $subject = new Tokenizer($grammar);
        $tokens = $subject->tokenize(new Scanner($template));

        print_r($tokens);

    }
}
