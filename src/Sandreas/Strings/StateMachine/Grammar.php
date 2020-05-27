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
     */
    public function buildNextToken(Scanner $scanner) {
        foreach ($this->callbacks as $tokenGeneratorCallback) {
            $token = $tokenGeneratorCallback($scanner);
            if($token instanceof Token) {
                return $token;
            }
        }
        return null;
    }


}
