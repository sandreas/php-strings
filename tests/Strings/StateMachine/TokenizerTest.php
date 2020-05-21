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
    const PLACEHOLDER_PREFIX = "%";
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
        $this->mockScanner->shouldReceive("endReached")->andReturnTrue();
        $this->assertEquals([], $this->subject->tokenize($this->mockScanner));
    }

    /**
     * @throws TokenizeException
     */
    public function testExtensiveTokenizeWithIntegrationOfGrammarAndScanner()
    {
        $formatString = "--- %a/%s/%p - %t/";

        $grammar = new Grammar([
            function (Scanner $scanner) {
                return $this->eatPlaceHolder($scanner);
            },
            function (Scanner $scanner) {
                return $this->eatNonPlaceholder($scanner);
            }
        ]);
        $subject = new Tokenizer($grammar);
        $tokens = $subject->tokenize(new Scanner($formatString));

        $this->assertCount(9, $tokens);
    }


    private function eatPlaceHolder(Scanner $scanner)
    {
        if ($scanner->peek() === static::PLACEHOLDER_PREFIX && $scanner->offset(1) !== static::PLACEHOLDER_PREFIX) {
            return new Token(static::TOKEN_PLACEHOLDER, $scanner->poke() . $scanner->poke());
        }
        return null;
    }

    private function eatNonPlaceholder(Scanner $scanner)
    {
        $token = new Token(static::TOKEN_NO_PLACEHOLDER);
        while (!$scanner->endReached()) {
            if ($scanner->peek() === static::PLACEHOLDER_PREFIX && $scanner->offset(1) !== static::PLACEHOLDER_PREFIX) {
                return $token->orNullOnEmptyValue();
            }
            $char = $scanner->poke();
            if ($char === static::PLACEHOLDER_PREFIX && $scanner->peek() === static::PLACEHOLDER_PREFIX) {
                $token->append($char . $scanner->poke());
            } else {
                $token->append($char);
            }
        }
        return $token->orNullOnEmptyValue();
    }
}
