<?php

namespace App\Policies;

use App\Models\KnowledgeBase;
use App\Models\User;

class KnowledgeBasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->organization_id === $knowledgeBase->organization_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $this->view($user, $knowledgeBase);
    }

    public function delete(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $this->view($user, $knowledgeBase);
    }
}
