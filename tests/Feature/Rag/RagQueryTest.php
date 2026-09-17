<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('rag query page renders with history', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    $team->ragQueries()->create([
        'user_id' => $user->id,
        'question' => 'What is Laravel?',
        'answer' => 'A PHP framework.',
        'retrieved_chunk_ids' => [],
    ]);

    $response = $this->actingAs($user)->get(route('rag-query.index', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rag/query')
        ->has('history', 1)
        ->where('history.0.question', 'What is Laravel?')
    );
});
