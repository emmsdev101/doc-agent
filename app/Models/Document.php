<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'knowledge_base_id',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size_bytes',
        'status',
        'error_message',
        'chunk_count',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'size_bytes' => 'integer',
            'chunk_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Run a callback with a local filesystem path, downloading from object storage when needed.
     *
     * @template T
     * @param  callable(string): T  $callback
     * @return T
     */
    public function withLocalFile(callable $callback): mixed
    {
        $disk = Storage::disk($this->disk);
        $driver = (string) config("filesystems.disks.{$this->disk}.driver", 'local');

        if ($driver === 'local') {
            return $callback($disk->path($this->path));
        }

        $temporary = tempnam(sys_get_temp_dir(), 'docagent_');
        $localPath = $temporary.'.'.$this->extension;
        rename($temporary, $localPath);

        $source = $disk->readStream($this->path);

        if ($source === false) {
            @unlink($localPath);
            throw new \RuntimeException('Unable to read the stored document.');
        }

        $destination = fopen($localPath, 'wb');

        if ($destination === false) {
            if (is_resource($source)) {
                fclose($source);
            }
            @unlink($localPath);
            throw new \RuntimeException('Unable to create a temporary file for document processing.');
        }

        stream_copy_to_stream($source, $destination);
        fclose($destination);

        if (is_resource($source)) {
            fclose($source);
        }

        try {
            return $callback($localPath);
        } finally {
            @unlink($localPath);
        }
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => DocumentStatus::Processing,
            'error_message' => null,
        ])->save();
    }

    public function markProcessed(int $chunkCount): void
    {
        $this->forceFill([
            'status' => DocumentStatus::Processed,
            'chunk_count' => $chunkCount,
            'error_message' => null,
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => DocumentStatus::Failed,
            'error_message' => Str::limit($message, 1000),
        ])->save();
    }
}
