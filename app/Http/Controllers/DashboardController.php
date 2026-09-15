<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $organization = $request->user()->organization()->withCount('knowledgeBases')->first();

        $knowledgeBases = KnowledgeBase::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount([
                'documents',
                'documents as ready_documents_count' => fn ($query) => $query->where('status', DocumentStatus::Processed),
                'documents as pending_documents_count' => fn ($query) => $query
                    ->whereIn('status', [DocumentStatus::Pending, DocumentStatus::Processing]),
            ])
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'organization' => $organization,
            'knowledgeBases' => $knowledgeBases,
            'stats' => [
                'knowledge_bases' => $knowledgeBases->count(),
                'ready_documents' => (int) $knowledgeBases->sum('ready_documents_count'),
                'pending_documents' => (int) $knowledgeBases->sum('pending_documents_count'),
            ],
        ]);
    }
}
