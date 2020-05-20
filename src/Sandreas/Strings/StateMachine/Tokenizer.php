<?php


namespace Sandreas\Strings\StateMachine;


class Tokenizer
{
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
        while($scanner->hasNext()) {
            $tokens[] = $this->grammar->buildNextToken($scanner);
        }
        return $tokens;
    }
}
