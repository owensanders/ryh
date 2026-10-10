<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Document;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** Downloading it. */
    public function view(User $user, Document $document): Response
    {
        return $this->inHouseholdAs($user, $document->household_id, HouseholdRoleEnum::managers());
    }

    /** Moving it to another folder; the destination is checked with DocumentFolderPolicy::addDocument. */
    public function update(User $user, Document $document): Response
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): Response
    {
        return $this->view($user, $document);
    }
}
