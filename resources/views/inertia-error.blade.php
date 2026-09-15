<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $status }} · DocAgent</title>
        <style>
            body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #0f172a; color: #e2e8f0; }
            main { min-height: 100vh; display: grid; place-items: center; padding: 32px; }
            .card { width: min(640px, 100%); background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 28px; }
            .status { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #818cf8; }
            h1 { margin: 8px 0 12px; font-size: 28px; color: #fff; }
            p { margin: 0; line-height: 1.6; color: #cbd5e1; white-space: pre-wrap; }
            .meta { margin-top: 16px; font-size: 12px; color: #94a3b8; font-family: ui-monospace, monospace; }
        </style>
    </head>
    <body>
        <main>
            <div class="card">
                <div class="status">HTTP {{ $status }}</div>
                <h1>Request failed</h1>
                <p>{{ $message }}</p>
                @if ($exception)
                    <div class="meta">{{ $exception }}</div>
                @endif
            </div>
        </main>
    </body>
</html>
