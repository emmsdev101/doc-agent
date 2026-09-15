<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_chunks') || ! Schema::hasColumn('document_chunks', 'embedding')) {
            return;
        }

        $dimensions = (int) config('services.embeddings.dimensions', 1024);

        try {
            $type = Schema::getColumnType('document_chunks', 'embedding');
        } catch (\Throwable) {
            return;
        }

        if (in_array($type, ['json', 'jsonb'], true)) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS document_chunks_embedding_idx');
        DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding');
        DB::statement("ALTER TABLE document_chunks ADD COLUMN embedding vector({$dimensions})");
        DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_chunks') || ! Schema::hasColumn('document_chunks', 'embedding')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS document_chunks_embedding_idx');
        DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding');
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING hnsw (embedding vector_cosine_ops)');
    }
};
