<?php


namespace Sandreas\Strings\Format;


use Exception;
use Sandreas\Strings\RuneList;

class FormatParser
{
    /** @var PlaceHolder[] */
    protected $placeholderMapping;
    protected $result = [];

    public function __construct(PlaceHolder ...$placeholders)
    {
        foreach ($placeholders as $placeHolder) {
            $this->placeholderMapping[$placeHolder->name] = $placeHolder;
        }
    }

    /**
     * @param $formatString
     * @param $string
     *
     * @throws Exception
     */
    public function parseFormat($formatString, $string)
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
                return;
            }
        } while ($stringRunes->next() !== false);
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
            throw new Exception("Invalid format string (placeholder <%> is not allowed - please use %% for a % sign)");
        }
        if (!isset($this->placeholderMapping[$placeHolder])) {
            throw new Exception("Invalid format string (placeholder <%" . $placeHolder . "> is not allowed)");
        }

        return $placeHolder;
    }

    /**
     * @param RuneList $formatRunes
     * @param RuneList $stringRunes
     * @throws Exception
     */
    private function parsePlaceHolder(RuneList $formatRunes, RuneList $stringRunes)
    {

        $placeHolder = $formatRunes->next();
        $this->ensureValidPlaceHolder($placeHolder);

        $placeHolderObject = $this->placeholderMapping[$placeHolder];
        if (!$formatRunes->next()) {
            $placeHolderObject->value = $stringRunes->slice($stringRunes->key())->__toString();
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

            if (!$placeHolderObject->append($stringRunes->current())) {
                return;
            }
        } while ($stringRunes->next() !== false);

        // handle separator length > 1
        $separatorLength--;
        if ($separatorLength > 0) {
            $stringRunes->seek($stringRunes->key() + $separatorLength);
        }
    }

    public function getPlaceHolderValue($placeHolder)
    {
        if (!isset($this->placeholderMapping[$placeHolder])) {
            return "";
        }
        return $this->placeholderMapping[$placeHolder]->value;
    }


}