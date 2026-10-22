<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\DocumentFolder;
use App\Models\Household;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class DocumentFolderPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** Whether they get the documents screen at all. */
    public function viewAny(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    /** The documents screen, opened on a folder or (with none) on the first one. */
    public function view(User $user, Household $household, ?DocumentFolder $folder = null): Response
    {
        return $folder === null
            ? $this->viewAny($user, $household)
            : $this->inHouseholdAs($user, $folder->household_id, HouseholdRoleEnum::managers());
    }

    public function create(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
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
