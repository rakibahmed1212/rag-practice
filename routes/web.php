<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Document;
use function Laravel\Ai\agent;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;
use Smalot\PdfParser\Parser;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__ . '/settings.php';

Route::get('/test-ai', function () {

    $response = agent()->prompt('Say hello', provider: 'gemini');

    return (string) $response;

});

Route::get('/rag-seed', function () {
    $path = Storage::disk('local')->path('rag/sample.pdf');

    // ১. PDF থেকে raw text extract করি
    $parser = new Parser();
    $pdf    = $parser->parseFile($path);
    $text   = $pdf->getText();

    // ২. Chunking: fixed-size, একটু overlap সহ (context হারানো এড়াতে)
    $chunks = chunkText($text, chunkSize: 800, overlap: 100);

    $count = 0;

    foreach ($chunks as $index => $chunk) {
        $chunk = trim($chunk);
        if (strlen($chunk) < 50) {
            continue;
        }
        // খুব ছোট/অর্থহীন অংশ বাদ

        $embedding = Embeddings::for([$chunk])->generate(provider: 'gemini');

        Document::create([
            'title'     => 'PDF Chunk #' . ($index + 1),
            'content'   => $chunk,
            'embedding' => $embedding->embeddings[0],
        ]);

        $count++;
    }

    return "Seeded {$count} chunks from PDF!";
});

Route::get('/rag-query', function () {
    $question = 'How does Laravel handle database ORM?';

    // ১. Vector similarity search দিয়ে relevant document খুঁজে বের করি
    $relevantDocs = Document::query()
        ->whereVectorSimilarTo('embedding', $question, minSimilarity: 0.3)
        ->limit(2)
        ->get();

    // ২. Retrieved content দিয়ে context বানাই
    $context = $relevantDocs->map(function ($doc) {
        return "Title: {$doc->title}\nContent: {$doc->content}";
    })->implode("\n\n");

    // ৩. Context + question একসাথে agent কে পাঠাই
    $prompt = "Answer the question based only on the following context:\n\n{$context}\n\nQuestion: {$question}";

    $response = agent()->prompt($prompt, provider: 'gemini');

    return [
        'question'            => $question,
        'retrieved_documents' => $relevantDocs->pluck('title'),
        'answer'              => (string) $response,
    ];
});
function chunkText(string $text, int $chunkSize = 800, int $overlap = 100): array
{
    $text   = preg_replace('/\s+/', ' ', $text); // extra whitespace/newline পরিষ্কার করি
    $chunks = [];
    $length = strlen($text);
    $start  = 0;

    while ($start < $length) {
        $chunk     = substr($text, $start, $chunkSize);
        $chunks[]  = $chunk;
        $start    += ($chunkSize - $overlap); // overlap রেখে পরের chunk শুরু
    }

    return $chunks;
}
