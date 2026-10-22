export type AbilitiesData = {
    update?: boolean;
    delete?: boolean;
    toggle?: boolean;
};
export type AgendaEntryData = {
    id: number;
    title: string;
    time: string;
    who: string | null;
    colour: string;
};
export type AgendaGroupData = {
    label: string;
    items: AgendaEntryData[];
};
export type AiMealPlanData = {
    id: number;
    status: AiMealPlanStatusEnum;
    meals: MealSuggestionData[];
    error: string | null;
};
export type AiMealPlanStatusEnum = 'pending' | 'ready' | 'failed';
export type AuthUserData = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
};
export type BillingRequiredScreenData = {
    plan: PlanData;
    isOwner: boolean;
    paymentIssue: boolean;
    billingConfigured: boolean;
};
export type BudgetCategoryData = {
    id: number;
    label: string;
    icon: string;
    colour: string;
    budgeted: string;
    budgetedPence: number;
    shareOfTotal: number;
    isRecurring: boolean;
};
export type BudgetMonthSummaryData = {
    month: string;
    monthLabel: string;
    total: string;
    totalPence: number;
};
export type BudgetScreenData = {
    month: string;
    isCurrentMonth: boolean;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    daysLeftLabel: string;
    leftToSpend: string;
    budgetedLabel: string;
    perDay: string;
    budgetedPence: number;
    incomeTotalPence: number;
    income: IncomeSourceData[];
    incomeTotal: string;
    categories: BudgetCategoryData[];
    history: BudgetMonthSummaryData[];
};
export type BudgetSummaryData = {
    monthLabel: string;
    budgeted: string;
    daysLeft: number;
    categories: BudgetCategoryData[];
};
export type CalendarDayData = {
    date: string;
    dayLabel: string;
    dateLabel: string;
    isToday: boolean;
    isCurrentMonth: boolean;
    events: CalendarEventData[];
};
export type CalendarEventData = {
    id: number;
    title: string;
    date: string;
    time: string;
    who: string;
    isAllDay: boolean;
    location: string | null;
    notes: string | null;
    colour: string;
    colourKey: string;
    attendees: MemberData[];
    isSpanStart: boolean;
    isSpanEnd: boolean;
    startsAt: string;
    endsAt: string | null;
    can: AbilitiesData;
    hasEnded: boolean;
};
export type CalendarMonthData = {
    month: string;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    today: string;
    todayDate: string;
    weekdayLabels: string[];
    days: CalendarDayData[];
    members: MemberData[];
};
export type ChoreBoardScreenData = {
    columns: ChoreColumnData[];
    scores: MemberScoreData[];
    members: MemberData[];
    notifyChoresAvailableAt: string | null;
};
export type ChoreColumnData = {
    status: ChoreStatusEnum;
    title: string;
    count: number;
    items: ChoreData[];
};
export type ChoreData = {
    id: number;
    name: string;
    status: ChoreStatusEnum;
    done: boolean;
    assignee: MemberData | null;
    can: AbilitiesData;
};
export type ChoreProgressData = {
    done: number;
    total: number;
    percentage: number;
};
export type ChoreStatusEnum = 'today' | 'done';
export type CookieSettingsData = {
    sessionCookie: string;
    sessionLifetimeMinutes: number;
    expireOnClose: boolean;
    rememberDays: number;
};
export type CookingConfidenceEnum = 'beginner' | 'comfortable' | 'adventurous';
export type CursorPaginatedDataCollection<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type CursorPaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta: {
        path: string;
        per_page: number;
        next_cursor: string | null;
        next_page_url: string | null;
        prev_cursor: string | null;
        prev_page_url: string | null;
    };
};
export type CursorPaginatorInterface<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type DashboardData = {
    firstName: string;
    summaryLine: string;
    streakDays: number;
    family: MemberData[];
    tonight: PlannedMealData | null;
    shoppingList: ShoppingListData | null;
    chores: ChoreData[];
    choreProgress: ChoreProgressData;
    agenda: AgendaGroupData[];
    budget: BudgetSummaryData;
};
export type DocumentData = {
    id: number;
    name: string;
    extension: string;
    meta: string;
    tags: string[];
    expiryLabel: string | null;
    isUrgent: boolean;
    addedBy: MemberData | null;
};
export type DocumentFolderData = {
    id: number;
    name: string;
    icon: string;
    colour: string;
    colourKey: string;
    count: number;
};
export type DocumentsScreenData = {
    folders: DocumentFolderData[];
    active: DocumentFolderData | null;
    documents: DocumentData[];
    storageLabel: string;
    storagePct: number;
};
export type HouseData = {
    houseName: string;
    houseCreated: string;
    joinCode: string;
    joinCodeEnabled: boolean;
    timezone: string;
    houseStats: StatData[];
    memberCount: number;
    maxMembers: number;
    roleOptions: {
        value: string;
        label: string;
    }[];
    members: HouseMemberData[];
    roles: RoleSummaryData[];
    planStatus: PlanStatusData;
};
export type HouseMemberData = {
    id: number;
    name: string;
    initials: string;
    colour: string;
    email: string;
    role: string;
    roleLabel: string;
    activity: string;
    you: boolean;
    pending: boolean;
    pendingReason: PendingReasonEnum | null;
};
export type HouseholdAbilitiesData = {
    manageHousehold: boolean;
    viewBudget: boolean;
    viewDocuments: boolean;
    createChores: boolean;
    notifyChores: boolean;
    createShoppingLists: boolean;
    createShoppingItems: boolean;
    createCalendarEvents: boolean;
    createPlannedMeals: boolean;
    createRecipes: boolean;
};
export type HouseholdOptionData = {
    id: number;
    name: string;
    role: string;
    roleLabel: string;
    memberCount: number;
    memberAvatars: {
        initials: string;
        colour: string;
    }[];
};
export type HouseholdRoleEnum = 'owner' | 'adult' | 'teen' | 'child';
export type IncomeSourceData = {
    id: number;
    label: string;
    meta: string | null;
    colour: string;
    amount: string;
    amountPence: number;
    initials: string;
    isRecurring: boolean;
};
export type InviteJoinOutcomeEnum = 'joined' | 'household_gone' | 'needs_guardian' | 'household_full';
export type InviteJoinResultData = {
    outcome: InviteJoinOutcomeEnum;
    householdName: string | null;
};
export type KitchenEquipmentEnum =
    | 'Oven'
    | 'Hob'
    | 'Microwave'
    | 'Air fryer'
    | 'Slow cooker'
    | 'Pressure cooker'
    | 'Grill'
    | 'BBQ'
    | 'Rice cooker'
    | 'Food processor';
export type LegalData = {
    tradingName: string;
    entityName: string | null;
    entityType: 'sole_trader' | 'limited_company' | null;
    companyNumber: string | null;
    address: string | null;
    icoRegistration: string | null;
    supportEmail: string | null;
    privacyEmail: string | null;
    hostingProvider: string | null;
    mailProvider: string | null;
    errorTracker: string | null;
    backupRetentionDays: number;
    supportResponse: string;
    termsVersion: string;
    termsUpdatedLabel: string;
    privacyVersion: string;
    privacyUpdatedLabel: string;
    cookiesVersion: string;
    cookiesUpdatedLabel: string;
};
export type LengthAwarePaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta: {
        total: number;
        current_page: number;
        first_page_url: string;
        from: number | null;
        last_page: number;
        last_page_url: string;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number | null;
    };
};
export type LengthAwarePaginatorInterface<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type MealDietEnum = 'Vegetarian' | 'Vegan' | 'Pescatarian' | 'Gluten free' | 'Dairy free' | 'Nut free';
export type MealGoalEnum = 'Eat healthier' | 'Spend less' | 'Eat more veg' | 'Quick weeknights' | 'Batch cook';
export type MealPlannerScreenData = {
    weekOf: string;
    days: PlannerDayData[];
    library: RecipeData[];
    recipeCount: number;
    recipes: RecipeData[];
    members: MemberData[];
    aiConfigured: boolean;
    aiPaused: boolean;
    aiAvailableFrom: string | null;
    aiSuggestedBudget: number | null;
    aiPlan: AiMealPlanData | null;
    shoppingLists: ShoppingListData[];
    tagOptions: {
        value: string;
        label: string;
    }[];
    aiOptions: {
        diets: string[];
        goals: string[];
        confidence: {
            value: string;
            label: string;
        }[];
        equipment: string[];
    };
};
export type MealSlotEnum = 'breakfast' | 'lunch' | 'dinner';
export type MealSuggestionData = {
    name: string;
    description: string | null;
    duration_label: string | null;
    difficulty: string | null;
    estimated_cost: number | null;
    tags: string[];
    ingredients: {
        name: string;
        quantity: string | null;
    }[];
    recipe_id?: number;
    tint?: number;
    is_favourite?: boolean;
};
export type MemberData = {
    id: number;
    name: string;
    initials: string;
    colour: string;
    status: string | null;
};
export type MemberScoreData = {
    member: MemberData;
    done: number;
    total: number;
    percentage: number;
};
export type NotificationCategoryEnum = 'chores' | 'calendar' | 'meals';
export type NotificationData = {
    id: string;
    data: Record<string, any>;
    read_at: string | null;
};
export type NotificationSettingsData = {
    categories: PushCategoryData[];
    vapidPublicKey: string | null;
};
export type PaginatedDataCollection<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type PaletteEnum = 'coral' | 'mint' | 'lilac' | 'sun' | 'sky';
export type PendingReasonEnum = 'invited' | 'requested';
export type PlanData = {
    name: string;
    priceMonthly: number;
    priceAnnual: number;
    sub: string;
    cta: string;
    note: string;
    features: string[];
    maxMembers: number;
};
export type PlanStatusData = {
    onTrial: boolean;
    trialEndsLabel: string | null;
    subscribed: boolean;
    interval: 'monthly' | 'annual' | null;
    endsLabel: string | null;
    paymentIssue: boolean;
    priceMonthly: number;
    priceAnnual: number;
};
export type PlannedMealData = {
    id: number;
    recipeId: number;
    name: string;
    slot: string;
    slotKey: string;
    plannedOn: string;
    duration: string | null;
    difficulty: string | null;
    description: string | null;
    tags: string[];
    tint: number;
    missingIngredients: number;
    missingLabel: string;
    cook: MemberData | null;
    can: AbilitiesData;
};
export type PlannerDayData = {
    date: string;
    dayLabel: string;
    dateLabel: string;
    isToday: boolean;
    meals: PlannedMealData[];
};
export type PlatformStatusData = {
    overall: ServiceStateEnum;
    services: ServiceStatusData[];
    checkedAt: string;
};
export type PushCategoryData = {
    value: NotificationCategoryEnum;
    label: string;
    description: string;
    enabled: boolean;
};
export type RecipeData = {
    id: number;
    name: string;
    description: string | null;
    meta: string;
    durationLabel: string | null;
    difficulty: string | null;
    tint: number;
    tags: string[];
    ingredients: {
        name: string;
        quantity: string | null;
    }[];
    isFavourite: boolean;
    can: AbilitiesData;
};
export type RecipeLibraryScreenData = {
    recipes: RecipeData[];
    tagOptions: {
        value: string;
        label: string;
    }[];
    shoppingLists: ShoppingListData[];
};
export type RecipeTagEnum =
    | 'healthy'
    | 'vegetarian'
    | 'vegan'
    | 'quick'
    | 'kid-friendly'
    | 'comfort-food'
    | 'spicy'
    | 'batch-cook'
    | 'budget'
    | 'one-pot';
export type RoleSummaryData = {
    name: string;
    colour: string;
    body: string;
    count: number;
};
export type ServiceStateEnum = 'operational' | 'degraded' | 'down';
export type ServiceStatusData = {
    name: string;
    description: string;
    state: ServiceStateEnum;
    stateLabel: string;
};
export type ShoppingItemData = {
    id: number;
    name: string;
    quantity: string | null;
    done: boolean;
    can: AbilitiesData;
};
export type ShoppingListData = {
    id: number;
    name: string;
    slug: string;
    colour: string;
    colourKey: string;
    remaining: number;
    total: number;
    items: ShoppingItemData[];
    can: AbilitiesData;
};
export type ShoppingScreenData = {
    lists: ShoppingListData[];
    active: ShoppingListData | null;
};
export type StatData = {
    label: string;
    value: string;
};
