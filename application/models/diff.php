<?php

final class DiffModel {
    public const OLD = 0;
    public const ADDED = 1;
    public const REMOVED = 2;
    public const DIFF_LINE_END_WARNING = 3;
    public const NO_LINE_END_EOF_WARNING = 4;

    function calculateDiff($from, $to) {
        if (!file_exists($from))
            return [false, []];

        if ($from == $to || !($to && file_exists($to))) {
            return [false, file_get_contents($from)];
        }

        $gitDiff = @`git diff --no-index -p --diff-algorithm=minimal --unified=1000000 {$from} {$to}`;

        if (!$gitDiff) {
            return [false, file_get_contents($from)];
        }

        $lines = $this->splitStringByLines($gitDiff);

        $start = 0;
        while ($start < count($lines)) {
            if (!preg_match('/^diff --git|^index|^---|^\+\+\+|^@@/', $lines[$start]))
                break;
            $start++;
        }

        return [true, array_slice($lines, $start)];
    }

    /**
     * Returns the diff between two arrays or strings as array.
     *
     * Each array element contains two elements:
     *   - [0] => mixed $token
     *   - [1] => 2|1|0
     *
     * - 2: REMOVED: $token was removed from $from
     * - 1: ADDED: $token was added to $from
     * - 0: OLD: $token is not changed in $to
     *
     * @param array|string $from
     * @param array|string $to
     */
    public function diffToArray($from, $to): array {
        if (is_string($from)) {
            $from = $this->splitStringByLines($from);
        } elseif (!is_array($from)) {
            throw new InvalidArgumentException('"from" must be an array or string.');
        }

        if (is_string($to)) {
            $to = $this->splitStringByLines($to);
        } elseif (!is_array($to)) {
            throw new InvalidArgumentException('"to" must be an array or string.');
        }

        [$from, $to, $start, $end] = self::getArrayDiffParted($from, $to);

        $common = $this->calculate(array_values($from), array_values($to));
        $diff = [];

        foreach ($start as $token) {
            $diff[] = [$token, self::OLD];
        }

        reset($from);
        reset($to);
        foreach ($common as $token) {
            while (($fromToken = reset($from)) !== $token) {
                $diff[] = [array_shift($from), self::REMOVED];
            }

            while (($toToken = reset($to)) !== $token) {
                $diff[] = [array_shift($to), self::ADDED];
            }

            $diff[] = [$token, self::OLD];

            array_shift($from);
            array_shift($to);
        }

        while (($token = array_shift($from)) !== null) {
            $diff[] = [$token, self::REMOVED];
        }

        while (($token = array_shift($to)) !== null) {
            $diff[] = [$token, self::ADDED];
        }

        foreach ($end as $token) {
            $diff[] = [$token, self::OLD];
        }

        if ($this->detectUnmatchedLineEndings($diff)) {
            array_unshift($diff, ["#Warning: Strings contain different line endings!\n", self::DIFF_LINE_END_WARNING]);
        }

        return $diff;
    }

    /**
     * Checks if input is string, if so it will split it line-by-line.
     */
    private function splitStringByLines(string $input): array {
        return preg_split('/(.*\R)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns true if line ends don't match in a diff.
     */
    private function detectUnmatchedLineEndings(array $diff): bool {
        $newLineBreaks = ['' => true];
        $oldLineBreaks = ['' => true];

        foreach ($diff as $entry) {
            if (self::OLD === $entry[1]) {
                $ln = $this->getLinebreak($entry[0]);
                $oldLineBreaks[$ln] = true;
                $newLineBreaks[$ln] = true;
            } elseif (self::ADDED === $entry[1]) {
                $newLineBreaks[$this->getLinebreak($entry[0])] = true;
            } elseif (self::REMOVED === $entry[1]) {
                $oldLineBreaks[$this->getLinebreak($entry[0])] = true;
            }
        }

        // if either input or output is a single line without breaks than no warning should be raised
        if (['' => true] === $newLineBreaks || ['' => true] === $oldLineBreaks) {
            return false;
        }

        // two way compare
        foreach ($newLineBreaks as $break => $set) {
            if (!isset($oldLineBreaks[$break])) {
                return true;
            }
        }

        foreach ($oldLineBreaks as $break => $set) {
            if (!isset($newLineBreaks[$break])) {
                return true;
            }
        }

        return false;
    }

    private function getLinebreak($line): string {
        if (!is_string($line)) {
            return '';
        }

        $lc = substr($line, -1);

        if ("\r" === $lc) {
            return "\r";
        }

        if ("\n" !== $lc) {
            return '';
        }

        if ("\r\n" === substr($line, -2)) {
            return "\r\n";
        }

        return "\n";
    }

    private static function getArrayDiffParted(array &$from, array &$to): array {
        $start = [];
        $end = [];

        reset($to);

        foreach ($from as $k => $v) {
            $toK = key($to);

            if ($toK === $k && $v === $to[$k]) {
                $start[$k] = $v;

                unset($from[$k], $to[$k]);
            } else {
                break;
            }
        }

        end($from);
        end($to);

        do {
            $fromK = key($from);
            $toK = key($to);

            if (null === $fromK || null === $toK || current($from) !== current($to)) {
                break;
            }

            prev($from);
            prev($to);

            $end = [$fromK => $from[$fromK]] + $end;
            unset($from[$fromK], $to[$toK]);
        } while (true);

        return [$from, $to, $start, $end];
    }

    public function calculate(array $from, array $to): array {
        $common = [];
        $fromLength = count($from);
        $toLength = count($to);
        $width = $fromLength + 1;
        $matrix = array($width * ($toLength + 1));//new SplFixedArray($width * ($toLength + 1));

        for ($i = 0; $i <= $fromLength; ++$i) {
            $matrix[$i] = 0;
        }

        for ($j = 0; $j <= $toLength; ++$j) {
            $matrix[$j * $width] = 0;
        }

        for ($i = 1; $i <= $fromLength; ++$i) {
            $fi_1 = $from[$i - 1];
            for ($j = 1; $j <= $toLength; ++$j) {
                $o = ($j * $width) + $i;

                // don't use max() to avoid function call overhead
                $firstOrLast = $fi_1 === $to[$j - 1] ? $matrix[$o - $width - 1] + 1 : 0;

                $m_o_w = $matrix[$o - $width];

                if ($matrix[$o - 1] > $m_o_w) {
                    $matrix[$o] = $firstOrLast > $matrix[$o - 1]
                        ? $firstOrLast
                        : $matrix[$o - 1];
                } else {
                    $matrix[$o] = $firstOrLast > $m_o_w
                        ? $firstOrLast
                        : $m_o_w;
                }
            }
        }

        $i = $fromLength;
        $j = $toLength;

        while ($i > 0 && $j > 0) {
            if ($from[$i - 1] === $to[$j - 1]) {
                $common[] = $from[$i - 1];
                --$i;
                --$j;
            } else {
                $o = ($j * $width) + $i;

                if ($matrix[$o - $width] > $matrix[$o - 1]) {
                    --$j;
                } else {
                    --$i;
                }
            }
        }

        return array_reverse($common);
    }
}

