<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class DocumentFolderPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The documents screen, opened on a folder or (with none) on the first one. */
    public function view(User $user, ?DocumentFolder $folder = null): Response
    {
        return $folder === null
            ? $this->as($user, HouseholdRoleEnum::managers())
            : $this->inHouseholdAs($user, $folder->household_id, HouseholdRoleEnum::managers());
    }

    public function create(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, DocumentFolder $folder): Response
    {
        return $this->inHouseholdAs($user, $folder->household_id, HouseholdRoleEnum::managers());
    }

    /** Uploading into the folder, or moving a document into it. */
    public function addDocument(User $user, DocumentFolder $folder): Response
    {
        return $this->delete($user, $folder);
    }
}
