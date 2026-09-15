<?php

namespace App\Providers;

use App\Services\Documents\DocumentChunker;
use App\Services\Embeddings\LocalEmbeddingGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentChunker::class, function (): DocumentChunker {
            return new DocumentChunker(
                (int) config('docagent.chunking.target_tokens'),
                (int) config('docagent.chunking.overlap_tokens'),
                (int) config('docagent.chunking.min_tokens'),
            );
        });

        $this->app->bind(LocalEmbeddingGenerator::class, function (): LocalEmbeddingGenerator {
            return new LocalEmbeddingGenerator(
                (int) config('services.embeddings.dimensions', 1536),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Vite::useScriptTagAttributes([
            'crossorigin' => 'anonymous',
        ]);

        if (! $this->app->runningInConsole()) {
            URL::forceRootUrl(request()->root());
        }

        RateLimiter::for('widget-chat', function (Request $request) {
            return Limit::perMinute(40)->by(
                $request->input('kb_id', 'unknown').'|'.$request->ip()
            );
        });

        // Windows env keys are Path/SystemRoot; Laravel only keeps PATH/SYSTEMROOT,
        // which makes `php artisan serve` fail to bind with "reason: ?".
        ServeCommand::$passthroughVariables = array_values(array_unique(array_merge(
            ServeCommand::$passthroughVariables,
            [
                'Path', 'SystemRoot', 'PATHEXT', 'ComSpec', 'COMSPEC', 'windir', 'WINDIR',
                'TMP', 'TEMP', 'TMPDIR', 'USERPROFILE', 'HOMEDRIVE', 'HOMEPATH',
                'APPDATA', 'LOCALAPPDATA',
            ],
        )));
    }
}
