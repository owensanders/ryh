<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\BudgetRepository;
use App\Repositories\CalendarEventRepository;
use App\Repositories\ChoreRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\HouseholdRepository;
use App\Repositories\MealPlanRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ShoppingRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        BudgetRepositoryInterface::class => BudgetRepository::class,
        CalendarEventRepositoryInterface::class => CalendarEventRepository::class,
        ChoreRepositoryInterface::class => ChoreRepository::class,
        DocumentRepositoryInterface::class => DocumentRepository::class,
        HouseholdRepositoryInterface::class => HouseholdRepository::class,
        MealPlanRepositoryInterface::class => MealPlanRepository::class,
        MembershipRepositoryInterface::class => MembershipRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
        ShoppingRepositoryInterface::class => ShoppingRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
    ];
}
