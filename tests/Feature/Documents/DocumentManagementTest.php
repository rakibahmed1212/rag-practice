<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('documents index shows uploaded documents', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    $team->documents()->create([
        'user_id' => $user->id,
        'title' => 'sample.pdf',
        'original_filename' => 'sample.pdf',
        'disk_path' => 'rag/x/sample.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1000,
        'status' => 'completed',
        'chunk_count' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('documents.index', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('documents/index')
        ->has('documents.data', 1)
        ->where('documents.data.0.title', 'sample.pdf')
        ->where('documents.data.0.status', 'completed')
    );
});

test('document deletion removes the document and its file', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    Storage::disk('local')->put('rag/x/a.pdf', 'fake-pdf-contents');

    $document = $team->documents()->create([
        'user_id' => $user->id,
        'title' => 'a.pdf',
        'original_filename' => 'a.pdf',
        'disk_path' => 'rag/x/a.pdf',
        'mime_type' => 'application/pdf',
        'size' => 500,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($user)->delete(route('documents.destroy', [$team, $document]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('local')->assertMissing('rag/x/a.pdf');
});

test('a team cannot view another teams document', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    $otherDocument = $otherTeam->documents()->create([
        'user_id' => $user->id,
        'title' => 'secret.pdf',
        'original_filename' => 'secret.pdf',
        'disk_path' => 'rag/y/secret.pdf',
        'mime_type' => 'application/pdf',
        'size' => 500,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($user)->get(route('documents.show', [$team, $otherDocument]));

    $response->assertNotFound();
});
