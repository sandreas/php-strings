<?php

namespace Sandreas\Strings\Format;

use Exception;
use Sandreas\Strings\RuneList;
use Sandreas\Strings\StateMachine\Grammar;
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
        /*
        // very strange: although tokens end up in exactly the same formatRunesParts,
        // tests do not pass - problem seems to be the RuneLists with % in it
        // but this is HARD to debug


        $arrayKeys = array_keys($formatRunesParts);
        keys: 0,2,4,5,7,10
        $keys = array(0,7); // 0 and 7 contain % RuneLists

        // all  runelists with % in it produce some kind of problem
        // copying from original array works
        foreach($formatRunesParts as $key => $value) {
            if(in_array($key, $keys)) {
                $formatRunesParts2[$key] = $formatRunesParts[$key];
            }
        }

        $tokens = $this->tokenizeFormatString($formatString);
        $formatRunesParts2 = [];
        $position = 0;
        foreach ($tokens as $token) {
            $runeList = new RuneList($token->value);
            if ($token->type === static::TOKEN_PLACEHOLDER) {
                $placeHolder = ltrim($token->value, static::PLACEHOLDER_PREFIX);
                $formatRunesParts2[$position] = $this->ensureValidPlaceHolderName($placeHolder, $placeHolderMapping);
                $position += $runeList->count();
                continue;
            }

            $formatRunesParts2[$position] = $runeList;
            $position += $runeList->count();
        }
        // return $formatRunesParts2;
*/

        $formatRunes = new RuneList($formatString);
        $formatRunesParts = [];
        $lastPosition = 0;
        $currentSeparator = new RuneList();
        while ($formatRune = $formatRunes->poke()) {
            if ($formatRune === "%" && $formatRunes->current() !== "%") {
                if ($currentSeparator->count() > 0) {
                    $formatRunesParts[$lastPosition] = $currentSeparator;
                    $currentSeparator = new RuneList();
                }

                $placeHolderName = $formatRunes->poke();
                $placeHolder = $this->ensureValidPlaceHolderName($placeHolderName, $placeHolderMapping);
                $placeHolderPosition = $formatRunes->key() ? $formatRunes->key() - 2 : $formatRunes->count() - 2;
                $formatRunesParts[$placeHolderPosition] = $placeHolder;
                $lastPosition = $formatRunes->key();
                continue;
            }
            $currentSeparator->append($formatRune);

            // escaped char, ignore and proceed
            if ($formatRune === "%" && $formatRunes->current() === "%") {
                $formatRunes->next();
                continue;
            }
        }
        if ($currentSeparator->count() > 0) {
            $formatRunesParts[$lastPosition] = $currentSeparator;
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
                while (1) {
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
        $grammar = new Grammar([
            function (Scanner $scanner) {
                return $this->eatPlaceHolder($scanner);
            },
            function (Scanner $scanner) {
                return $this->eatNonPlaceholder($scanner);
            }
        ]);
        $subject = new Tokenizer($grammar);
        return $subject->tokenize(new Scanner($formatString));
    }

    private function eatPlaceHolder(Scanner $scanner)
    {
        if ($scanner->peek() === static::PLACEHOLDER_PREFIX && $scanner->offset(1) !== static::PLACEHOLDER_PREFIX) {
            return new Token(static::TOKEN_PLACEHOLDER, $scanner->poke() . $scanner->poke());
        }
        return null;
    }

    private function eatNonPlaceholder(Scanner $scanner)
    {
        $token = new Token(static::TOKEN_NO_PLACEHOLDER);
        while (!$scanner->endReached()) {
            if ($scanner->peek() === static::PLACEHOLDER_PREFIX && $scanner->offset(1) !== static::PLACEHOLDER_PREFIX) {
                return $token->orNullOnEmptyValue();
            }
            $char = $scanner->poke();
            if ($char === static::PLACEHOLDER_PREFIX && $scanner->peek() === static::PLACEHOLDER_PREFIX) {
                $token->append($char . $scanner->poke());
            } else {
                $token->append($char);
            }
        }
        return $token->orNullOnEmptyValue();
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