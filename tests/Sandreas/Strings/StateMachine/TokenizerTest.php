<?php

namespace Sandreas\Strings\StateMachine;

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
     * @var m\MockInterface|m\LegacyMockInterface|Grammar
     */
    private $mockGrammar;
    /**
     * @var m\MockInterface|m\LegacyMockInterface|Scanner
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

    public function testDefaultMaxFailedTokenBuildCount()
    {
        $this->expectExceptionObject(new TokenizeException("Scanner as not moved forward since 1 iterations (at position 0), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped"));
        $this->mockScanner->shouldReceive("endReached")->andReturn(false);
        $this->mockScanner->shouldReceive("key")->andReturn(0);
        $this->mockGrammar->shouldReceive("buildNextToken")->andReturn("fake-token");
        $this->subject->tokenize($this->mockScanner);
    }

    public function testSetMaxFailedTokenBuildCount()
    {
        $this->expectExceptionObject(new TokenizeException("Scanner as not moved forward since 3 iterations (at position 0), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped"));
        $this->mockScanner->shouldReceive("endReached")->andReturn(false);
        $this->mockScanner->shouldReceive("key")->andReturn(0);
        $this->mockGrammar->shouldReceive("buildNextToken")->andReturn("fake-token");

        $this->subject->setMaxFailedTokenBuildCount(3);
        $this->subject->tokenize($this->mockScanner);
    }

    /*
    public function testDisableMaxFailedTokenBuildCount()  {
        $this->mockScanner->shouldReceive("endReached")->andReturnUsing(function() {
            static $iterations = 0;
            $iterations++;
            return $iterations < 3;
        });
        $this->mockScanner->shouldReceive("key")->andReturn(function() {
            static $iterations = 0;
            $iterations++;
            return $iterations < 2 ? 0 : 1;
        });
        $this->mockGrammar->shouldReceive("buildNextToken")->andReturn("fake-token");

        $this->subject->setMaxFailedTokenBuildCount(0);
        $tokens = $this->subject->tokenize($this->mockScanner);
        $this->assertCount(3, $tokens);
    }
    */

}
