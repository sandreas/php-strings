<?php


namespace Sandreas\Strings\StateMachine;


class Grammar
{
    /**
     * @var array
     */
    protected $callbacks = [];

    public function __construct(array $callbacks = []) {
        $this->callbacks = $callbacks;
    }

    /**
     * @param Scanner $scanner
     * @return Token
     * @throws TokenizeException
     */
    public function buildNextToken(Scanner $scanner) {
        foreach ($this->callbacks as $tokenGeneratorCallback) {
            $token = $tokenGeneratorCallback($scanner);
            if($token instanceof Token) {
                return $token;
            }
        }
        throw new TokenizeException("Could not build next token - no matching token generator found in grammar or no token returned");
    }


}
