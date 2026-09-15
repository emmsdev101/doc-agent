<?php

namespace App\Http\Middleware;

use App\Models\KnowledgeBase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWidgetOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('widgetToken') ?? $request->input('kb_id');

        $knowledgeBase = KnowledgeBase::query()
            ->where('widget_token', $token)
            ->where('is_active', true)
            ->first();

        if ($knowledgeBase === null) {
            abort(404, 'Knowledge base not found.');
        }

        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        if (is_string($origin) && $origin !== '') {
            $parts = parse_url($origin);
            $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

            if (! empty($parts['port'])) {
                $origin .= ':'.$parts['port'];
            }
        } else {
            $origin = null;
        }

        if (! $knowledgeBase->allowsOrigin($origin)) {
            abort(403, 'This origin is not allowed to use the widget.');
        }

        $request->attributes->set('knowledgeBase', $knowledgeBase);

        return $next($request);
    }
}
