<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Documents\DocumentController;
use App\Http\Controllers\Rag\RagQueryController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

use function Laravel\Ai\agent;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('rag-query', [RagQueryController::class, 'index'])->name('rag-query.index');
        Route::post('rag-query', [RagQueryController::class, 'store'])->name('rag-query.store');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

Route::get('/test-ai', function () {

    $response = agent()->prompt('Say hello', provider: 'gemini');

    return (string) $response;

});
