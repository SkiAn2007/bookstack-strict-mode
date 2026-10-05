<?php

namespace BookStack\Search;

/**
 * A custom text tokenizer which records & provides insight needed for our search indexing.
 * We used to use basic strtok() but this class does the following which that lacked:
 * - Tracks and provides the current/previous delimiter that we've stopped at.
 * - Returns empty tokens upon parsing a delimiter.
 * - Handles multibyte (UTF-8) delimiters without splitting multibyte characters.
 */
class SearchTextTokenizer
{
    protected int $currentIndex = 0;
    protected int $length;
    protected string $currentDelimiter = '';
    protected string $previousDelimiter = '';

    /**
     * Single-byte (ASCII) delimiters, held as a string for fast lookups.
     */
    protected string $singleByteDelimiters = '';

    /**
     * Multibyte delimiters, each a full UTF-8 encoded character.
     * @var string[]
     */
    protected array $multiByteDelimiters = [];

    /**
     * The set of first bytes (as keys) of the multibyte delimiters, for a quick pre-check.
     * @var array<int, true>
     */
    protected array $multiByteFirstBytes = [];

    public function __construct(
        protected string $text,
        string $delimiters = ' '
    ) {
        $this->length = strlen($this->text);

        foreach (mb_str_split($delimiters) as $delimiter) {
            if (strlen($delimiter) === 1) {
                $this->singleByteDelimiters .= $delimiter;
            } else {
                $this->multiByteDelimiters[] = $delimiter;
                $this->multiByteFirstBytes[ord($delimiter[0])] = true;
            }
        }
    }

    /**
     * Get the current delimiter to be found.
     */
    public function currentDelimiter(): string
    {
        return $this->currentDelimiter;
    }

    /**
     * Get the previous delimiter found.
     */
    public function previousDelimiter(): string
    {
        return $this->previousDelimiter;
    }

    /**
     * Get the next token between delimiters.
     * Returns false if there's no further tokens.
     */
    public function next(): string|false
    {
        $token = '';

        for ($i = $this->currentIndex; $i < $this->length; $i++) {
            $delimiter = $this->getDelimiterAt($i);
            if ($delimiter !== '') {
                $this->previousDelimiter = $this->currentDelimiter;
                $this->currentDelimiter = $delimiter;
                $this->currentIndex = $i + strlen($delimiter);
                return $token;
            }

            $token .= $this->text[$i];
        }

        if ($token) {
            $this->currentIndex = $this->length;
            $this->previousDelimiter = $this->currentDelimiter;
            $this->currentDelimiter = '';
            return $token;
        }

        return false;
    }

    /**
     * Get the delimiter which starts at the given byte index of the text.
     * Returns an empty string if no delimiter starts at that index.
     * Valid UTF-8 never has a character start mid-way through another,
     * so multibyte delimiters can only match on character boundaries.
     */
    protected function getDelimiterAt(int $index): string
    {
        $char = $this->text[$index];
        if (ord($char) < 0x80) {
            return str_contains($this->singleByteDelimiters, $char) ? $char : '';
        }

        if (!isset($this->multiByteFirstBytes[ord($char)])) {
            return '';
        }

        foreach ($this->multiByteDelimiters as $delimiter) {
            if (substr_compare($this->text, $delimiter, $index, strlen($delimiter)) === 0) {
                return $delimiter;
            }
        }

        return '';
    }
}
