<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\RagQuery;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $current_team): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'stats' => $this->stats($current_team),
        ]);
    }

    /**
     * Build RAG usage statistics for the current team.
     *
     * @return array<string, mixed>
     */
    protected function stats(Team $team): array
    {
        $documentsByStatus = $team->documents()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'documentsTotal' => (int) $documentsByStatus->sum(),
            'documentsPending' => (int) ($documentsByStatus[DocumentStatus::Pending->value] ?? 0),
            'documentsProcessing' => (int) ($documentsByStatus[DocumentStatus::Processing->value] ?? 0),
            'documentsCompleted' => (int) ($documentsByStatus[DocumentStatus::Completed->value] ?? 0),
            'documentsFailed' => (int) ($documentsByStatus[DocumentStatus::Failed->value] ?? 0),
            'chunksTotal' => $team->documentChunks()->count(),
            'storageBytes' => (int) $team->documents()->sum('size'),
            'queriesTotal' => $team->ragQueries()->count(),
            'queriesLast7Days' => $team->ragQueries()->where('created_at', '>=', now()->subDays(7))->count(),
            'recentDocuments' => $team->documents()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'status' => $document->status->value,
                    'statusLabel' => $document->status->label(),
                    'chunkCount' => $document->chunk_count,
                    'createdAt' => $document->created_at->toISOString(),
                ]),
            'recentQueries' => $team->ragQueries()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (RagQuery $query) => [
                    'id' => $query->id,
                    'question' => $query->question,
                    'askedBy' => $query->user->name,
                    'createdAt' => $query->created_at->toISOString(),
                ]),
        ];
    }
}
