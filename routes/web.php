<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Document;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Embeddings;

use function Laravel\Ai\agent;

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
    $samples = [
        [
            'title' => 'Laravel Routing',
            'content' => 'Laravel routing allows you to define routes for your application in the routes/web.php file. Routes can respond to any HTTP verb.',
        ],
        [
            'title' => 'Laravel Eloquent',
            'content' => 'Eloquent is Laravel\'s ORM that provides a simple ActiveRecord implementation for working with your database.',
        ],
        [
            'title' => 'Laravel Queues',
            'content' => 'Laravel queues allow you to defer time-consuming tasks, such as sending an email, until a later time, speeding up web requests.',
        ],
    ];

    foreach ($samples as $sample) {
        $embedding = Embeddings::for([$sample['content']])->generate(provider: 'gemini');

        Document::create([
            'title' => $sample['title'],
            'content' => $sample['content'],
            'embedding' => $embedding->embeddings[0],
        ]);
    }

    return 'Seeded '.count($samples).' documents!';
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
        'question' => $question,
        'retrieved_documents' => $relevantDocs->pluck('title'),
        'answer' => (string) $response,
    ];
});