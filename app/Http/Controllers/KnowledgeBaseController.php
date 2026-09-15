<?php

namespace App\Http\Controllers;

use App\Actions\KnowledgeBases\CreateKnowledgeBaseAction;
use App\Actions\KnowledgeBases\UpdateKnowledgeBaseAction;
use App\Http\Requests\KnowledgeBases\StoreKnowledgeBaseRequest;
use App\Http\Requests\KnowledgeBases\UpdateKnowledgeBaseRequest;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KnowledgeBase::class);

        $knowledgeBases = KnowledgeBase::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('documents')
            ->latest()
            ->get();

        return Inertia::render('KnowledgeBases/Index', [
            'knowledgeBases' => $knowledgeBases,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', KnowledgeBase::class);

        return Inertia::render('KnowledgeBases/Create');
    }

    public function store(StoreKnowledgeBaseRequest $request, CreateKnowledgeBaseAction $action): RedirectResponse
    {
        $this->authorize('create', KnowledgeBase::class);

        $knowledgeBase = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('knowledge-bases.show', $knowledgeBase)
            ->with('success', 'Knowledge base created.');
    }

    public function show(Request $request, KnowledgeBase $knowledgeBase): Response
    {
        $this->authorize('view', $knowledgeBase);

        $knowledgeBase->load(['documents' => fn ($query) => $query->latest()]);

        $appUrl = rtrim(request()->root(), '/');

        return Inertia::render('KnowledgeBases/Show', [
            'knowledgeBase' => $knowledgeBase,
            'embedSnippet' => $knowledgeBase->embedSnippet($appUrl),
            'appUrl' => $appUrl,
        ]);
    }

    public function edit(KnowledgeBase $knowledgeBase): Response
    {
        $this->authorize('update', $knowledgeBase);

        return Inertia::render('KnowledgeBases/Settings', [
            'knowledgeBase' => $knowledgeBase,
            'embedSnippet' => $knowledgeBase->embedSnippet(rtrim(request()->root(), '/')),
            'allowedOriginsText' => implode("\n", $knowledgeBase->allowed_origins ?? ['*']),
        ]);
    }

    public function update(
        UpdateKnowledgeBaseRequest $request,
        KnowledgeBase $knowledgeBase,
        UpdateKnowledgeBaseAction $action,
    ): RedirectResponse {
        $this->authorize('update', $knowledgeBase);

        $action->execute($knowledgeBase, $request->validated());

        return redirect()
            ->route('knowledge-bases.edit', $knowledgeBase)
            ->with('success', 'Settings saved.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $this->authorize('delete', $knowledgeBase);

        $knowledgeBase->delete();

        return redirect()
            ->route('knowledge-bases.index')
            ->with('success', 'Knowledge base deleted.');
    }
}
