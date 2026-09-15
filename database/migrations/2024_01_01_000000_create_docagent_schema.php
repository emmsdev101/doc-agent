<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('SAVEPOINT before_vector_ext');
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::unprepared('RELEASE SAVEPOINT before_vector_ext');
        } catch (\Throwable) {
            DB::unprepared('ROLLBACK TO SAVEPOINT before_vector_ext');
        }

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('widget_token')->unique();
            $table->string('welcome_message')->default('Hi! Ask me anything about our docs.');
            $table->text('system_prompt')->nullable();
            $table->json('allowed_origins')->nullable();
            $table->string('primary_color', 16)->default('#4f46e5');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('disk')->default('documents');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('token_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['knowledge_base_id', 'document_id']);
        });

        $dimensions = (int) env('EMBEDDING_DIMENSIONS', 1536);
        DB::unprepared('SAVEPOINT before_vector_col');
        try {
            DB::statement("ALTER TABLE document_chunks ADD COLUMN embedding vector({$dimensions})");
            DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING hnsw (embedding vector_cosine_ops)');
            DB::unprepared('RELEASE SAVEPOINT before_vector_col');
        } catch (\Throwable) {
            DB::unprepared('ROLLBACK TO SAVEPOINT before_vector_col');
            DB::statement('ALTER TABLE document_chunks ADD COLUMN IF NOT EXISTS embedding jsonb');
        }

        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->string('origin')->nullable();
            $table->string('visitor_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_session_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->json('citations')->nullable();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('knowledge_bases');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('organizations');
    }
};
