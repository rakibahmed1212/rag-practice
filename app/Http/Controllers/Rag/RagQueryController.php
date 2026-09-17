<?php

namespace App\Http\Controllers\Rag;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rag\RagQueryRequest;
use App\Models\DocumentChunk;
use App\Models\RagQuery;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

use function Laravel\Ai\agent;

class RagQueryController extends Controller
{
    /**
     * Display the RAG query page with recent history.
     */
    public function index(Team $current_team): Response
    {
        return Inertia::render('rag/query', [
            'history' => $this->history($current_team),
        ]);
    }

    /**
     * Run a RAG query against the team's embedded documents.
     */
    public function store(RagQueryRequest $request, Team $current_team): RedirectResponse
    {
        $question = $request->validated('question');

        $relevantChunks = DocumentChunk::query()
            ->where('team_id', $current_team->id)
            ->whereHas('document', fn ($query) => $query->where('status', DocumentStatus::Completed))
            ->whereVectorSimilarTo('embedding', $question, minSimilarity: 0.3)
            ->with('document:id,title')
            ->limit(4)
            ->get();

        $context = $relevantChunks
            ->map(fn (DocumentChunk $chunk) => "Title: {$chunk->document->title}\nContent: {$chunk->content}")
            ->implode("\n\n");

        $prompt = $context === ''
            ? "Answer the question, noting that no relevant context was found:\n\nQuestion: {$question}"
            : "Answer the question based only on the following context:\n\n{$context}\n\nQuestion: {$question}";

        $answer = (string) agent()->prompt($prompt, provider: 'gemini');

        $current_team->ragQueries()->create([
            'user_id' => $request->user()->id,
            'question' => $question,
            'answer' => $answer,
            'retrieved_chunk_ids' => $relevantChunks->pluck('id')->all(),
        ]);

        return back();
    }

    /**
     * Get the team's recent query history for display.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function history(Team $team): array
    {
        return $team->ragQueries()
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (RagQuery $query) => [
                'id' => $query->id,
                'question' => $query->question,
                'answer' => $query->answer,
                'askedBy' => $query->user->name,
                'createdAt' => $query->created_at->toISOString(),
            ])
            ->all();
    }
}
