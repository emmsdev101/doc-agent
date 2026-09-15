<?php

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

final class DeleteDocumentAction
{
    public function execute(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }
}
