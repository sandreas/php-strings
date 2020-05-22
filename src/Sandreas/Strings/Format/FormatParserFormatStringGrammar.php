<?php


namespace Sandreas\Strings\Format;


use Sandreas\Strings\StateMachine\Grammar;
use Sandreas\Strings\StateMachine\Scanner;
use Sandreas\Strings\StateMachine\Token;

class FormatParserFormatStringGrammar extends Grammar
{
    public function __construct(array $callbacks = [])
    {
        parent::__construct(array_merge([
            function (Scanner $scanner) {
                return $this->eatPlaceHolder($scanner);
            },
            function (Scanner $scanner) {
                return $this->eatNonPlaceholder($scanner);
            }
        ], $callbacks));
    }

    private function eatPlaceHolder(Scanner $scanner)
    {
        if ($scanner->peek() === FormatParser::PLACEHOLDER_PREFIX && $scanner->offset(1) !== FormatParser::PLACEHOLDER_PREFIX) {
            return new Token(FormatParser::TOKEN_PLACEHOLDER, $scanner->poke() . $scanner->poke());
        }
        return null;
    }

    private function eatNonPlaceholder(Scanner $scanner)
    {
        $token = new Token(FormatParser::TOKEN_NO_PLACEHOLDER);
        while (!$scanner->endReached()) {
            if ($scanner->peek() === FormatParser::PLACEHOLDER_PREFIX && $scanner->offset(1) !== FormatParser::PLACEHOLDER_PREFIX) {
                return $token->orNullOnEmptyValue();
            }
            $char = $scanner->poke();
            if ($char === FormatParser::PLACEHOLDER_PREFIX && $scanner->peek() === FormatParser::PLACEHOLDER_PREFIX) {
                $token->append($char . $scanner->poke());
            } else {
                $token->append($char);
            }
        }
        return $token->orNullOnEmptyValue();
    }

}