<?php

namespace App\Http\Controllers;

use App\Actions\Documents\DeleteDocumentAction;
use App\Actions\Documents\UploadDocumentAction;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;

class DocumentController extends Controller
{
    public function store(
        StoreDocumentRequest $request,
        KnowledgeBase $knowledgeBase,
        UploadDocumentAction $action,
    ): RedirectResponse {
        $this->authorize('update', $knowledgeBase);

        $action->execute($knowledgeBase, $request->file('file'));

        return back()->with('success', 'Document uploaded and queued for processing.');
    }

    public function reprocess(KnowledgeBase $knowledgeBase, Document $document): RedirectResponse
    {
        $this->authorize('update', $knowledgeBase);
        abort_unless($document->knowledge_base_id === $knowledgeBase->id, 404);

        ProcessDocumentJob::dispatch($document);

        return back()->with('success', 'Document re-queued for processing.');
    }

    public function destroy(
        KnowledgeBase $knowledgeBase,
        Document $document,
        DeleteDocumentAction $action,
    ): RedirectResponse {
        $this->authorize('update', $knowledgeBase);
        abort_unless($document->knowledge_base_id === $knowledgeBase->id, 404);

        $action->execute($document);

        return back()->with('success', 'Document deleted.');
    }
}
