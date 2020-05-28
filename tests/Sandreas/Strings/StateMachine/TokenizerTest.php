<?php

namespace Sandreas\Strings\StateMachine;

use PHPUnit\Framework\TestCase;
use Mockery as m;

class TokenizerTest extends TestCase
{
    const TOKEN_PLACEHOLDER = 1;
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
    /**
     * @var m\LegacyMockInterface|m\MockInterface|Token
     */
    private $mockToken;


    public function setUp()
    {
        $this->mockToken = m::mock(Token::class);
        $this->mockToken->makePartial();
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

    /**
     * @throws TokenizeException
     */
    public function testDefaultMaxFailedTokenBuildCount()
    {
        $this->expectExceptionObject(new TokenizeException("Scanner as not moved forward since 1 iterations (at position 0), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped"));

        $this->mockScanner->shouldReceive("endReached")->andReturn(false);
        $this->mockScanner->shouldReceive("key")->andReturn(0);
        $this->mockGrammar->shouldReceive("buildNextToken")->andReturn($this->mockToken);
        $this->subject->tokenize($this->mockScanner);
    }

    /**
     * @throws TokenizeException
     */
    public function testSetMaxFailedTokenBuildCount()
    {
        $this->expectExceptionObject(new TokenizeException("Scanner as not moved forward since 3 iterations (at position 0), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped"));
        $this->mockScanner->shouldReceive("endReached")->andReturn(false);
        $this->mockScanner->shouldReceive("key")->andReturn(0);
        $this->mockGrammar->shouldReceive("buildNextToken")->andReturn($this->mockToken);

        $this->subject->setMaxFailedTokenBuildCount(3);
        $this->subject->tokenize($this->mockScanner);
    }
}
