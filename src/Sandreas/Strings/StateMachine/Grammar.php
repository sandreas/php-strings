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
     * @param $existingTokens
     * @return Token
     */
    public function buildNextToken(Scanner $scanner, array &$existingTokens = null)
    {
        foreach ($this->callbacks as $tokenGeneratorCallback) {
            $token = $tokenGeneratorCallback($scanner, $existingTokens);
            if ($token instanceof Token) {
                return $token;
            }
        }
        return null;
    }


}
