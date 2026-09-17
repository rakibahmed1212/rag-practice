<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Support\TextChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;
use Smalot\PdfParser\Parser;
use Throwable;

class ProcessDocumentEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $this->document->update(['status' => DocumentStatus::Processing]);

        try {
            $path = Storage::disk('local')->path($this->document->disk_path);

            $text = (new Parser)->parseFile($path)->getText();

            $chunks = collect(TextChunker::chunk($text))
                ->map(fn (string $chunk): string => trim($chunk))
                ->filter(fn (string $chunk): bool => strlen($chunk) >= 50)
                ->values();

            if ($chunks->isEmpty()) {
                $this->document->update([
                    'status' => DocumentStatus::Failed,
                    'error' => 'No extractable text was found in this PDF.',
                ]);

                return;
            }

            $embeddings = Embeddings::for($chunks->all())->generate(provider: 'gemini');

            $chunks->each(function (string $chunk, int $index) use ($embeddings) {
                $this->document->chunks()->create([
                    'team_id' => $this->document->team_id,
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => $embeddings->embeddings[$index],
                ]);
            });

            $this->document->update([
                'status' => DocumentStatus::Completed,
                'chunk_count' => $chunks->count(),
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $this->document->update([
                'status' => DocumentStatus::Failed,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
