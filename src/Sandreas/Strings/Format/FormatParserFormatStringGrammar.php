<?php


namespace Sandreas\Strings\Format;


use Sandreas\Strings\StateMachine\Grammar;
use Sandreas\Strings\StateMachine\Scanner;
use Sandreas\Strings\StateMachine\Token;
use Sandreas\Strings\StateMachine\Tokenizer;

class FormatParserFormatStringGrammar extends Grammar
{
    public function __construct(array $callbacks = [])
    {
        parent::__construct(array_merge([
            function (Scanner $scanner) {
                return $this->eatEscapedPercent($scanner);
            },
            function (Scanner $scanner) {
                return $this->eatPlaceHolder($scanner);
            }
        ], $callbacks));
    }

    private function eatEscapedPercent(Scanner $scanner)
    {
        if ($scanner->peek() === FormatParser::PLACEHOLDER_PREFIX && $scanner->offset(1) === FormatParser::PLACEHOLDER_PREFIX) {
            return new Token(Tokenizer::TOKEN_TYPE_DEFAULT, $scanner->poke() . $scanner->poke());
        }
        return null;
    }

    private function eatPlaceHolder(Scanner $scanner)
    {
        if ($scanner->peek() === FormatParser::PLACEHOLDER_PREFIX) {
            return new Token(FormatParser::TOKEN_PLACEHOLDER, $scanner->poke() . $scanner->poke());
        }
        return null;
    }


}