<?php

namespace App\Support;

use InvalidArgumentException;

class TextChunker
{
    /**
     * Split the given text into fixed-size, overlapping chunks so context
     * is not lost at chunk boundaries.
     *
     * @return array<int, string>
     */
    public static function chunk(string $text, int $chunkSize = 800, int $overlap = 100): array
    {
        if ($overlap >= $chunkSize) {
            throw new InvalidArgumentException('The overlap must be smaller than the chunk size.');
        }

        $text = preg_replace('/\s+/', ' ', $text);
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;
        $step = $chunkSize - $overlap;

        while ($start < $length) {
            $chunks[] = mb_substr($text, $start, $chunkSize);
            $start += $step;
        }

        return $chunks;
    }
}
