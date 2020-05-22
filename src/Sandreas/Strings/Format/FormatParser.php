<?php

namespace Sandreas\Strings\Format;

use Exception;
use Sandreas\Strings\RuneList;
use Sandreas\Strings\StateMachine\Scanner;
use Sandreas\Strings\StateMachine\Token;
use Sandreas\Strings\StateMachine\TokenizeException;
use Sandreas\Strings\StateMachine\Tokenizer;

class FormatParser
{

    const PLACEHOLDER_PREFIX = "%";

    const TOKEN_PLACEHOLDER = 0;
    const TOKEN_NO_PLACEHOLDER = 1;

    /** @var PlaceHolder[] */
    protected $placeHolderMapping;
    protected $result = [];

    /**
     * FormatParser constructor.
     * @param PlaceHolder ...$placeHolders
     * @throws Exception
     */
    public function __construct(PlaceHolder ...$placeHolders)
    {
        $this->placeHolderMapping = $this->buildPlaceHolderMapping($placeHolders);
    }

    /**
     * @param $placeHolders
     * @return array
     * @throws Exception
     */
    private function buildPlaceHolderMapping($placeHolders)
    {
        $mapping = [];
        foreach ($placeHolders as $placeHolder) {
            if (mb_strlen($placeHolder->name) !== 1) {
                throw new Exception("Placeholder names must have a length of 1");
            }
            $mapping[$placeHolder->name] = $placeHolder;
        }
        return $mapping;
    }

    /**
     * @param $formatString
     * @param $placeHolderMapping
     * @return array
     * @throws Exception
     */
    private function parseFormatString($formatString, $placeHolderMapping)
    {
        $tokens = $this->tokenizeFormatString($formatString);
        $formatRunesParts = [];
        $position = 0;
        foreach ($tokens as $token) {
            $runeList = new RuneList($token->value);
            if ($token->type === static::TOKEN_PLACEHOLDER) {
                $placeHolder = ltrim($token->value, static::PLACEHOLDER_PREFIX);
                $formatRunesParts[$position] = $this->ensureValidPlaceHolderName($placeHolder, $placeHolderMapping);
                $position += $runeList->count();
                continue;
            } else {
                $runeList = $runeList->unquote([
                    static::PLACEHOLDER_PREFIX => static::PLACEHOLDER_PREFIX
                ]);
            }

            $formatRunesParts[$position] = $runeList;
            $position += $runeList->count();
        }
        return $formatRunesParts;
    }

    /**
     * @param $formatString
     * @param $string
     *
     * @return bool
     * @throws Exception
     */
    public function parseFormat($formatString, $string): bool
    {
        $this->result = [];

        $formatStringStructure = $this->parseFormatString($formatString, $this->placeHolderMapping);

        $stringRunes = new RuneList($string);
        while ($element = current($formatStringStructure)) {
            $nextElement = next($formatStringStructure);
            if ($element instanceof PlaceHolder) {
                // placeholder till the end -> slice end and check match
                if (!$nextElement) {
                    $value = $stringRunes->slice($stringRunes->key());
                    if (!$element->matches((string)$value)) {
                        return false;
                    }
                    $element->value = $value;
                    return true;
                }

                // placeholder with unclear length -> try to match longest possible candidate
                if ($nextElement instanceof PlaceHolder) {
                    $longestPossibleCandidate = $stringRunes->slice($stringRunes->key());
                    $stringRunes->end();
                    while (!$element->matches((string)$longestPossibleCandidate)) {
                        $stringRunes->prev();
                        $longestPossibleCandidate->pop();
                    }
                    $stringRunes->next();
                    if ($longestPossibleCandidate->count() === 0) {
                        return false;
                    }

                    $element->value = (string)$longestPossibleCandidate;
                    continue;
                }

                // nextElement is a separator  -> try to find it in string
                $nextElementString = (string)$nextElement;
                $placeHolderValue = "";
                while (true) {
                    $sliced = (string)$stringRunes->slice($stringRunes->key(), $nextElement->count());
                    if ($sliced === $nextElementString) {
                        break;
                    }
                    $placeHolderValue .= $stringRunes->current();
                    if (!$stringRunes->next()) {
                        return false;
                    }
                }
                if (!$element->matches($placeHolderValue)) {
                    return false;
                }
                $element->value = $placeHolderValue;
            } else {
                $sliced = (string)$stringRunes->slice($stringRunes->key(), $element->count());
                if ($sliced !== (string)$element) {
                    return false;
                }
                for ($i = 0; $i < $element->count(); $i++) {
                    $stringRunes->next();
                }
            }
        }
        return true;
    }


    /**
     * @param $placeHolder
     *
     * @param $mapping
     * @return string
     * @throws Exception
     */
    protected function ensureValidPlaceHolderName($placeHolder, $mapping)
    {
        if ($placeHolder === null) {
            throw new Exception("Invalid format string (placeHolder <%> is not allowed - please use %% for a % sign)");
        }
        if (!isset($mapping[$placeHolder])) {
            throw new Exception("Invalid format string (placeHolder <%" . $placeHolder . "> is not allowed)");
        }

        return $mapping[$placeHolder];
    }


    /**
     * @param $placeHolder
     * @return string
     */
    public function getPlaceHolderValue($placeHolder): string
    {
        if (!isset($this->placeHolderMapping[$placeHolder])) {
            return "";
        }
        return $this->placeHolderMapping[$placeHolder]->value;
    }

    /**
     * @param $formatString
     * @return string
     * @throws Exception
     */
    public function trimSeparatorPrefix($formatString)
    {
        if ($formatString === "") {
            return $formatString;
        }

        $tokens = $this->tokenizeFormatString($formatString);
        $firstToken = reset($tokens);
        if (!($firstToken instanceof Token) || $firstToken->type === static::TOKEN_PLACEHOLDER) {
            return $formatString;
        }
        return mb_substr($formatString, mb_strlen($firstToken->value));
    }

    /**
     * @param $formatString
     * @return Token[]
     * @throws TokenizeException
     */
    private function tokenizeFormatString($formatString)
    {
        $subject = new Tokenizer(new FormatParserFormatStringGrammar());
        return $subject->tokenize(new Scanner($formatString));
    }


    /**
     * @param $formatString
     * @param array $placeHolders
     * @return string
     * @throws Exception
     */
    public function format($formatString, $placeHolders = [])
    {
        $placeHolderMapping = count($placeHolders) === 0 ? $this->placeHolderMapping : $this->buildPlaceHolderMapping($placeHolders);
        $formatStructure = $this->parseFormatString($formatString, $placeHolderMapping);
        $returnValue = "";
        foreach ($formatStructure as $element) {
            if ($element instanceof RuneList) {
                $returnValue .= (string)$element;
            } else {
                $returnValue .= $element->value;
            }
        }
        return $returnValue;
    }
}