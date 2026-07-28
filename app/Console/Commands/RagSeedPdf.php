<?php
namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;
use Smalot\PdfParser\Parser;

use function Laravel\Ai\agent;

class RagSeedPdf extends Command
{
    protected $signature   = 'rag:seed-pdf {--pages-per-batch=8}';
    protected $description = 'Extract, topic-chunk, embed, and store a PDF into the documents table';

    public function handle(): int
    {
        $path          = Storage::disk('local')->path('rag/software_engineering_notes.pdf');
        // dd($path);
//         dd(
//     Storage::disk('local')->path('rag/software_engineering_notes.pdf'),
//     file_exists(Storage::disk('local')->path('rag/software_engineering_notes.pdf'))
// );
        $pagesPerBatch = (int) $this->option('pages-per-batch');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info('Parsing PDF...');
        $parser = new Parser();
        $pdf    = $parser->parseFile($path);
        $pages  = $pdf->getPages();

        $totalPages = count($pages);
        $this->info("Total pages: {$totalPages}");

        $pageBatches = array_chunk($pages, $pagesPerBatch);

        $carryOver   = ''; // আগের batch এর শেষ chunk, যদি topic continue করে
        $totalChunks = 0;

        $bar = $this->output->createProgressBar(count($pageBatches));
        $bar->start();

        foreach ($pageBatches as $batchIndex => $pageGroup) {
            $batchText = collect($pageGroup)
                ->map(fn($page) => $page->getText())
                ->implode("\n\n");

            $batchText = preg_replace('/\s+/', ' ', $batchText);

            // আগের batch থেকে অসম্পূর্ণ topic থাকলে সেটা এই batch এর শুরুতে জুড়ে দিই
            $fullText = $carryOver ? "{$carryOver}\n\n{$batchText}" : $batchText;

            $response = agent(
                instructions: 'You split documents into topic-based chunks. Each chunk covers one coherent topic and can be any length — a paragraph or many pages. '
                . 'If the text starts mid-topic (continuing from a previous batch), merge it into the first chunk rather than creating a new one. '
                . 'If the LAST chunk appears cut off / incomplete (topic likely continues beyond this text), mark it with "incomplete": true so it can be merged with the next batch. '
                . 'Do not summarize or alter the original wording — only split it.',
                schema: fn(JsonSchema $schema) => [
                    'chunks' => $schema->array()->items(
                        $schema->object(fn($schema) => [
                            'topic'      => $schema->string()->required(),
                            'content'    => $schema->string()->required(),
                            'incomplete' => $schema->boolean()->required(),
                        ])
                    )->required(),
                ],
            )->prompt("Split the following text into topic-based chunks:\n\n{$fullText}", provider: 'gemini');

            $chunks = $response['chunks'];

            // শেষ chunk incomplete হলে, সেটা carry over হিসেবে রেখে দিই, ডেটাবেজে সেভ করি না এখনই
            $lastChunk = end($chunks);
            if ($lastChunk && $lastChunk['incomplete']) {
                array_pop($chunks);
                $carryOver = $lastChunk['content'];
            } else {
                $carryOver = '';
            }

            foreach ($chunks as $chunk) {
                if (strlen(trim($chunk['content'])) < 30) {
                    continue;
                }

                $embedding = Embeddings::for([$chunk['content']])->generate(provider: 'gemini');

                Document::create([
                    'title'     => $chunk['topic'],
                    'content'   => $chunk['content'],
                    'embedding' => $embedding->embeddings[0],
                ]);

                $totalChunks++;
            }

            $bar->advance();
        }

        // শেষ batch এর পর যদি carryOver থেকে যায়, সেটাও সেভ করে দিই
        if ($carryOver && strlen(trim($carryOver)) >= 30) {
            $embedding = Embeddings::for([$carryOver])->generate(provider: 'gemini');
            Document::create([
                'title'     => 'Final chunk',
                'content'   => $carryOver,
                'embedding' => $embedding->embeddings[0],
            ]);
            $totalChunks++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Seeded {$totalChunks} topic-wise chunks from {$totalPages} pages.");

        return self::SUCCESS;
    }
}
