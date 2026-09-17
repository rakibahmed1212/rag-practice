<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $title
 * @property string $original_filename
 * @property string $disk_path
 * @property string $mime_type
 * @property int $size
 * @property DocumentStatus $status
 * @property string|null $error
 * @property int $chunk_count
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read Collection<int, DocumentChunk> $chunks
 */
#[Fillable(['team_id', 'user_id', 'title', 'original_filename', 'disk_path', 'mime_type', 'size', 'status', 'error', 'chunk_count', 'processed_at'])]
class Document extends Model
{
    /**
     * Get the team that owns the document.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who uploaded the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chunks generated from this document.
     *
     * @return HasMany<DocumentChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
