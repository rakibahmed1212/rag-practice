<?php

namespace App\Support;

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
        $text = preg_replace('/\s+/', ' ', $text);
        $chunks = [];
        $length = strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunks[] = substr($text, $start, $chunkSize);
            $start += ($chunkSize - $overlap);
        }

        return $chunks;
    }
}
