<?php

namespace vektah\parser_combinator\parser;

use vektah\parser_combinator\exception\GrammarException;
use vektah\parser_combinator\Input;
use vektah\parser_combinator\Result;

class CharRangeParser extends Parser
{
    private $min;
    private $max;

    private $chars = [];
    private $raw_chars = '';

    private $ranges;

    public function __construct(array $ranges, $min = null, $max = null, $capture = true)
    {
        if (!is_numeric($min) && !is_null($min)) {
            throw new GrammarException('Min must be numeric');
        }

        if (!is_numeric($max) && !is_null($max)) {
            throw new GrammarException('Max must be numeric');
        }

        if (!is_bool($capture)) {
            throw new GrammarException('Capture must be a boolean');
        }

        $this->min = $min;
        $this->max = $max;
        $this->capture = $capture;

        foreach ($ranges as $first => $last) {
            // If the value is an array with one value then treat it as a sequence of chars instead of a range.
            if (is_array($last)) {
                if (!is_string($last[0])) {
                    throw new GrammarException('CharRangeParsers may contain array values containing only strings!');
                }
                $this->chars = array_flip(str_split($last[0]));
                $this->raw_chars = $last[0];
            } else {
                $this->ranges[ord($first)] = ord($last);
            }
        }
    }

    private function inRanges($char) {
        $ord = ord($char);

        foreach ($this->ranges as $start => $end) {
            if ($start <= $ord && $ord <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse the given input
     *
     * @param Input $input
     *
     * @return mixed result
     */
    public function parse(Input $input)
    {
        $offset = 0;

        while ($offset < $input->strlen()) {
            file_put_contents('/tmp/log', $input->getOffset(), FILE_APPEND);
            if ($this->max !== null && $offset >= $this->max) {
                break;
            }

            $char = $input->peek($offset);

            if (!isset($this->chars[$char]) && !$this->inRanges($char)) {
                break;
            }

            $offset++;
            continue;
        }
        $result = $input->get($offset);
        $input->consume($offset);

        if ($offset < $this->min) {
            return Result::error("At {$input->getPositionDescription()}: Did not capture enough chars, expected {$this->min} of [{$this->raw_chars}], found {$offset}.");
        }

        if ($this->capture) {
            return Result::match($result);
        }

        return Result::nonCapturingMatch();
    }
}
