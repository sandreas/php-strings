<?php

namespace Sandreas\Strings\Format;

use Exception;
use Sandreas\Strings\RuneList;

class FormatParser
{
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
        foreach ($placeHolders as $placeHolder) {
            if (mb_strlen($placeHolder->name) !== 1) {
                throw new Exception("Placeholder names must have a length of 1");
            }
            $this->placeHolderMapping[$placeHolder->name] = $placeHolder;
        }
    }

    /**
     * @param $formatString
     * @return array
     * @throws Exception
     */
    private function parseFormatString($formatString)
    {
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
                $placeHolder = $this->ensureValidPlaceHolderName($placeHolderName);
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

        $formatStringStructure = $this->parseFormatString($formatString);
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
     * @return string
     * @throws Exception
     */
    protected function ensureValidPlaceHolderName($placeHolder)
    {
        if ($placeHolder === null) {
            throw new Exception("Invalid format string (placeHolder <%> is not allowed - please use %% for a % sign)");
        }
        if (!isset($this->placeHolderMapping[$placeHolder])) {
            throw new Exception("Invalid format string (placeHolder <%" . $placeHolder . "> is not allowed)");
        }

        return $this->placeHolderMapping[$placeHolder];
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
}