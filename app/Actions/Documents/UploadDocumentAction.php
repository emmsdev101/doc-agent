<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\KnowledgeBase;
use Illuminate\Http\UploadedFile;

final class UploadDocumentAction
{
    public function execute(KnowledgeBase $knowledgeBase, UploadedFile $file): Document
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->store($knowledgeBase->id, config('docagent.uploads.disk'));

        $document = $knowledgeBase->documents()->create([
            'original_filename' => $file->getClientOriginalName(),
            'disk' => config('docagent.uploads.disk'),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size_bytes' => $file->getSize() ?: 0,
            'status' => DocumentStatus::Pending,
        ]);

        ProcessDocumentJob::dispatch($document);

        return $document;
    }
}
