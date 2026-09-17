<?php

namespace App\Http\Controllers\Documents;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Jobs\ProcessDocumentEmbeddings;
use App\Models\Document;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display a listing of the team's documents.
     */
    public function index(Team $current_team): Response
    {
        $documents = $current_team->documents()
            ->with('user:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'originalFilename' => $document->original_filename,
                'status' => $document->status->value,
                'statusLabel' => $document->status->label(),
                'error' => $document->error,
                'chunkCount' => $document->chunk_count,
                'size' => $document->size,
                'uploadedBy' => $document->user->name,
                'createdAt' => $document->created_at->toISOString(),
            ]);

        return Inertia::render('documents/index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Store a newly uploaded document and dispatch it for processing.
     */
    public function store(StoreDocumentRequest $request, Team $current_team): RedirectResponse
    {
        $file = $request->file('file');

        $diskPath = $file->storeAs(
            "rag/{$current_team->id}",
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $document = $current_team->documents()->create([
            'user_id' => $request->user()->id,
            'title' => $file->getClientOriginalName(),
            'original_filename' => $file->getClientOriginalName(),
            'disk_path' => $diskPath,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => DocumentStatus::Pending,
        ]);

        ProcessDocumentEmbeddings::dispatch($document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document uploaded, embedding in progress.')]);

        return back();
    }

    /**
     * Display the chunks generated for a single document.
     */
    public function show(Team $current_team, Document $document): Response
    {
        abort_unless($document->team_id === $current_team->id, 404);

        $chunks = $document->chunks()
            ->orderBy('chunk_index')
            ->paginate(20)
            ->through(fn ($chunk) => [
                'id' => $chunk->id,
                'chunkIndex' => $chunk->chunk_index,
                'content' => $chunk->content,
            ]);

        return Inertia::render('documents/show', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'status' => $document->status->value,
                'statusLabel' => $document->status->label(),
                'error' => $document->error,
                'chunkCount' => $document->chunk_count,
            ],
            'chunks' => $chunks,
        ]);
    }

    /**
     * Remove the specified document and its file from disk.
     */
    public function destroy(Team $current_team, Document $document): RedirectResponse
    {
        abort_unless($document->team_id === $current_team->id, 404);

        if (! Storage::disk('local')->delete($document->disk_path)) {
            logger()->warning('Failed to delete document file from disk.', [
                'document_id' => $document->id,
                'disk_path' => $document->disk_path,
            ]);
        }

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document deleted.')]);

        return back();
    }
}
