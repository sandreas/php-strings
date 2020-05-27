<?php


namespace Sandreas\Strings\StateMachine;


class Tokenizer
{
    const TOKEN_TYPE_DEFAULT = 0;
    /**
     * @var Grammar
     */
    protected $grammar;

    protected $maxFailedTokenBuildCount = 1;


    public function __construct(Grammar $grammar)
    {
        $this->grammar = $grammar;
    }

    /**
     * Prevents endless loops, when scanner does not move forward for x iterations
     * set to 0 do disable this check
     *
     * @param $maxFailedTokenBuildCount
     */
    public function setMaxFailedTokenBuildCount($maxFailedTokenBuildCount)
    {
        $this->maxFailedTokenBuildCount = $maxFailedTokenBuildCount;
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
        $fallbackToken = $this->createEmptyFallbackToken();
        $maxFailedCount = $this->maxFailedTokenBuildCount;
        while (!$scanner->endReached()) {
            $positionBeforeTokenBuild = (int)$scanner->key();
            $token = $this->grammar->buildNextToken($scanner);

            if ($token === null) {
                $fallbackToken->append($scanner->poke());
                continue;
            }

            if ($this->maxFailedTokenBuildCount > 0 && (int)$scanner->key() <= $positionBeforeTokenBuild && --$maxFailedCount < 1) {
                throw new TokenizeException(sprintf("Scanner as not moved forward since %s iterations (at position %s), so there seems to be something wrong with your grammar - to prevent endless loops, the tokenizer has been stopped", $this->maxFailedTokenBuildCount, $scanner->key()));
            }

            if ($token->type === $fallbackToken->type) {
                $fallbackToken->append($token->value);
                continue;
            }
            $this->appendNonEmptyTokens($tokens, $fallbackToken, $token);
            $fallbackToken = $this->createEmptyFallbackToken();
        }
        $this->appendNonEmptyTokens($tokens, $fallbackToken);

        return $tokens;
    }

    private function appendNonEmptyTokens(&$tokens, Token ...$tokensToAppend)
    {
        foreach ($tokensToAppend as $token) {
            if ($token->orNullOnEmptyValue() !== null) {
                $tokens[] = $token;
            }
        }
    }


    private function createEmptyFallbackToken()
    {
        return new Token(static::TOKEN_TYPE_DEFAULT);
    }
}
