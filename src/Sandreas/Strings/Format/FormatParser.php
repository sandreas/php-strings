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
        while (!$formatRunes->eof()) {
            $formatRune = $formatRunes->poke();
            if ($formatRune === "%" && $formatRunes->current() !== "%") {
                if ($currentSeparator->count() > 0) {
                    $formatRunesParts[$lastPosition] = $currentSeparator;
                    $currentSeparator = new RuneList();
                }

                $placeHolderName = $formatRunes->poke();
                $this->ensureValidPlaceHolderName($placeHolderName);

                $placeHolder = new PlaceHolder($placeHolderName);
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


        $stringRunes = new RuneList($string);

        $formatStringStructure = $this->parseFormatString($formatString);

        print_r($formatStringStructure);
        exit;

        /*
        while (!$formatRunes->eof() && !$stringRunes->eof()) {
            $formatRune = $formatRunes->poke();
            $stringRune = $stringRunes->poke();

            // escaped char
            if ($formatRune === "%" && $formatRunes->current() === "%") {
                continue;
            }

            // non placeholder chars
            if ($formatRune === $stringRune) {
                continue;
            }

            $placeHolderKey = $this->ensureValidPlaceHolderName($formatRunes->current());
            $placeHolder = $this->placeHolderMapping[$placeHolderKey];
            $separator = $this->extractNextFormatSeparator($formatRunes);

            $start = $stringRunes->key() - 1;
            $stringSeparatorPosition = $this->seekSeparatorPosition($stringRunes, $separator);


            $placeHolderValue = $stringRunes->slice($start, $stringSeparatorPosition - $start);


            while ($placeHolderValue->count() > 0) {
                $placeHolderValueAsString = $placeHolderValue->__toString();
                if ($placeHolder->matches($placeHolderValueAsString)) {
                    $placeHolder->value = $placeHolderValueAsString;
                    $stringRunes->seek($stringRunes->key() + $placeHolderValue->count());
                    continue 2;
                }
                $placeHolderValue->pop();
            }

            return false;

        }
        return $formatRunes->eof() && $stringRunes->eof();
        /*
        do {
            $formatRune = $formatRunes->current();
            $stringRune = $stringRunes->current();
            if ($formatRune === "%" && $formatRunes->offset(1) !== "%") {
                $this->parsePlaceHolder($formatRunes, $stringRunes);
                continue;
            }

            if ($formatRune === "%" && $formatRunes->offset(1) === "%") {
                $formatRune = $formatRunes->next();
            }

            if ($formatRunes->next() === false || $formatRune !== $stringRune) {
                return $stringRunes->eof() && $formatRune === $stringRune;
            }
        } while ($stringRunes->next() !== false);
        return $formatRunes->eof();
        */
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

        return $placeHolder;
    }

    /**
     * @param RuneList $formatRunes
     * @return RuneList
     * @throws Exception
     */
    private function extractNextFormatSeparator(RuneList $formatRunes)
    {
        $separator = new RuneList();
        if ($formatRunes->eof()) {
            return $separator;
        }
        $formatRunes->next();
        $index = $formatRunes->key();
        for ($i = $index; $i < $formatRunes->count(); $i++) {
            $current = $formatRunes->position($i);
            if ($current === "%") {
                if ($formatRunes->eof()) {
                    throw new Exception('Invalid format string, empty placeholder');
                }
                $formatRunes->next();
                if ($formatRunes->position($i + 1) !== "%") {
                    break;
                }
                $i++;
            }
            $separator->append($current);

        }

        return $separator;

    }

    private function seekSeparatorPosition(RuneList $stringRunes, RuneList $separator)
    {
        $count = $stringRunes->count();
        $separatorCount = $separator->count();
        if ($separatorCount === 0) {
            return $count;
        }
        $pos = $stringRunes->key();

        for ($i = $pos; $i < $count; $i++) {
            if ($stringRunes->slice($i, $separatorCount)->__toString() === $separator->__toString()) {
                return $i;
            }
        }
        return $count;
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

//    /**
//     * @param RuneList $formatRunes
//     * @param RuneList $stringRunes
//     * @throws Exception
//     */
//    protected function parsePlaceHolder(RuneList $formatRunes, RuneList $stringRunes)
//    {
//
//        $placeHolder = $formatRunes->next();
//        $this->ensureValidPlaceHolder($placeHolder);
//
//        $placeHolderObject = $this->placeHolderMapping[$placeHolder];
//        if (!$formatRunes->next()) {
//            $placeHolderObject->value = $stringRunes->slice($stringRunes->key())->__toString();
//            $stringRunes->end();
//            $formatRunes->end();
//            return;
//        };
//
//        $separator = "";
//        $separatorLength = 0;
//        do {
//            if ($formatRunes->current() === "%" && $formatRunes->offset(1) !== "%") {
//                break;
//            }
//            $separator .= $formatRunes->current();
//            $separatorLength++;
//
//            if ($formatRunes->current() === "%") {
//                $formatRunes->next();
//            }
//
//        } while ($formatRunes->next() !== false);
//
//        if ($formatRunes->key() === null) {
//            $formatRunes->end();
//        }
//
//        do {
//            if ($separatorLength && $separator === $stringRunes->slice($stringRunes->key(), $separatorLength)->__toString()) {
//                break;
//            }
//
//            if (!$placeHolderObject->matchesAfterAppend($stringRunes->current())) {
//                break;
//            }
//            $placeHolderObject->append($stringRunes->current());
//        } while ($stringRunes->next() !== false);
//
//        // handle separator length > 1
//        $separatorLength--;
//        $stringRunes->seek($stringRunes->key() + $separatorLength);
//    }


}