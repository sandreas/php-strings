<?php


namespace Sandreas\Strings\StateMachine;


class Tokenizer
{
    const MAX_FAILED_TOKEN_BUILD_COUNT = 3;
    /**
     * @var Grammar
     */
    protected $grammar;


    public function __construct( Grammar $grammar)
    {
        $this->grammar = $grammar;
    }


    /**
     * @param Scanner $scanner
     * @return Token[]
     * @throws TokenizeException
     */
    public function tokenize(Scanner $scanner)
    {
        /**
         * @var Token[]
         */
        $tokens = [];
        $failedTokenBuildCount = 0;
        while (!$scanner->endReached()) {
            $positionBeforeTokenBuild = $scanner->key();
            $tokens[] = $this->grammar->buildNextToken($scanner);
            $positionAfterTokenBuild = $scanner->key();

            if ($positionAfterTokenBuild <= $positionBeforeTokenBuild) {
                $failedTokenBuildCount++;
            }
            if ($failedTokenBuildCount > static::MAX_FAILED_TOKEN_BUILD_COUNT) {
                throw new TokenizeException(sprintf("Scanner as not moved forward since %s iterations (%s), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped", static::MAX_FAILED_TOKEN_BUILD_COUNT, $scanner->peek()));
            }
        }
        return $tokens;
    }
}
