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
     * @param $string
     *
     * @return bool
     * @throws Exception
     */
    public function parseFormat($formatString, $string): bool
    {
        $this->result = [];
        $formatRunes = new RuneList($formatString);
        $stringRunes = new RuneList($string);

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
        return true;
    }

    /**
     * @param RuneList $formatRunes
     * @param RuneList $stringRunes
     * @throws Exception
     */
    protected function parsePlaceHolder(RuneList $formatRunes, RuneList $stringRunes)
    {

        $placeHolder = $formatRunes->next();
        $this->ensureValidPlaceHolder($placeHolder);

        $placeHolderObject = $this->placeHolderMapping[$placeHolder];
        if (!$formatRunes->next()) {
            $placeHolderObject->value = $stringRunes->slice($stringRunes->key())->__toString();
            $stringRunes->end();
            return;
        };

        $separator = "";
        $separatorLength = 0;
        do {
            if ($formatRunes->current() === "%" && $formatRunes->offset(1) !== "%") {
                break;
            }
            $separator .= $formatRunes->current();
            $separatorLength++;

            if ($formatRunes->current() === "%") {
                $formatRunes->next();
            }

        } while ($formatRunes->next() !== false);


        do {
            if ($separatorLength && $separator === $stringRunes->slice($stringRunes->key(), $separatorLength)->__toString()) {
                break;
            }

            if (!$placeHolderObject->matchesAfterAppend($stringRunes->current())) {
                break;
            }
            $placeHolderObject->append($stringRunes->current());
        } while ($stringRunes->next() !== false);

        // handle separator length > 1
        $separatorLength--;
        $stringRunes->seek($stringRunes->key() + $separatorLength);
    }

    /**
     * @param $placeHolder
     *
     * @return array
     * @throws Exception
     */
    protected function ensureValidPlaceHolder($placeHolder)
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