<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function delete(User $user, Document $document): bool
    {
        return $user->organization_id === $document->knowledgeBase->organization_id;
    }
}
