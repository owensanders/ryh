/**
 * Mirrors the `App\Data\*` DTOs. Keep in step with them — they are the contract
 * between the use cases and these screens.
 */

export interface Member {
    id: number;
    name: string;
    initials: string;
    /** A `var(--hh-*)` reference, so it follows the active theme. */
    colour: string;
    status: string | null;
}

export interface ShoppingItem {
    id: number;
    name: string;
    quantity: string | null;
    done: boolean;
}

export interface ShoppingList {
    id: number;
    name: string;
    slug: string;
    colour: string;
    /** The `Palette` case name, for the colour picker. */
    colourKey: string;
    remaining: number;
    total: number;
    items: ShoppingItem[];
}

export interface Chore {
    id: number;
    name: string;
    status: ChoreStatus;
    done: boolean;
    assignee: Member | null;
}

export type ChoreStatus = 'today' | 'done';

export interface ChoreColumn {
    status: ChoreStatus;
    title: string;
    count: number;
    items: Chore[];
}

export interface ChoreProgress {
    done: number;
    total: number;
    percentage: number;
}

export interface MemberScore {
    member: Member;
    done: number;
    total: number;
    percentage: number;
}

export interface PlannedMeal {
    id: number;
    recipeId: number;
    name: string;
    slot: string;
    /** Raw enum value ('breakfast' | 'lunch' | 'dinner'), for form <select>s. */
    slotKey: string;
    /** Y-m-d, for form inputs. */
    plannedOn: string;
    duration: string | null;
    difficulty: string | null;
    description: string | null;
    tags: string[];
    /** Index into the `--hh-t1` … `--hh-t5` gradient tokens. */
    tint: number;
    missingIngredients: number;
    missingLabel: string;
    cook: Member | null;
}

export interface PlannerDay {
    date: string;
    dayLabel: string;
    dateLabel: string;
    isToday: boolean;
    meals: PlannedMeal[];
}

export interface RecipeIngredient {
    name: string;
    quantity: string | null;
}

export interface Recipe {
    id: number;
    name: string;
    description: string | null;
    meta: string;
    durationLabel: string | null;
    difficulty: string | null;
    tint: number;
    tags: string[];
    ingredients: RecipeIngredient[];
    isFavourite: boolean;
}

/** A suggested dinner - from the AI, or (with recipe_id) from the household's library. */
export interface AiMealSuggestion {
    name: string;
    description: string | null;
    duration_label: string | null;
    difficulty: string | null;
    estimated_cost: number | null;
    tags: string[];
    ingredients: RecipeIngredient[];
    recipe_id?: number;
    tint?: number;
    is_favourite?: boolean;
}

export interface AiPlannerOptions {
    diets: string[];
    goals: string[];
    confidence: TagOption[];
}

export interface TagOption {
    value: string;
    label: string;
}

export interface AgendaEntry {
    id: number;
    title: string;
    time: string;
    who: string | null;
    colour: string;
}

export interface AgendaGroup {
    label: string;
    items: AgendaEntry[];
}

export interface CalendarEvent {
    id: number;
    title: string;
    date: string;
    /** 'All day', '18:00', or '18:00–19:30'. */
    time: string;
    /** Attendee first names, or 'Everyone' for a whole-house event. */
    who: string;
    isAllDay: boolean;
    location: string | null;
    notes: string | null;
    colour: string;
    /** The `Palette` case name, for the colour picker. */
    colourKey: string;
    attendees: Member[];
    /** True on the day cell where the event actually starts/ends (for spanning-bar corner rounding). */
    isSpanStart: boolean;
    isSpanEnd: boolean;
    /** `YYYY-MM-DDTHH:mm`, for prefilling the edit form. */
    startsAt: string;
    endsAt: string | null;
}

export interface CalendarDay {
    date: string;
    dayLabel: string;
    dateLabel: string;
    isToday: boolean;
    isCurrentMonth: boolean;
    events: CalendarEvent[];
}

export interface CalendarMonth {
    /** `YYYY-MM`. */
    month: string;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    today: string;
    weekdayLabels: string[];
    /** Always 42 — six rows of seven. */
    days: CalendarDay[];
    members: Member[];
}

export interface BudgetCategory {
    id: number;
    label: string;
    icon: string;
    colour: string;
    budgeted: string;
    budgetedPence: number;
    shareOfTotal: number;
    isRecurring: boolean;
}

export interface BudgetMonthSummary {
    month: string;
    monthLabel: string;
    total: string;
    totalPence: number;
}

export interface BudgetSummary {
    monthLabel: string;
    budgeted: string;
    daysLeft: number;
    categories: BudgetCategory[];
}

export interface IncomeSource {
    id: number;
    label: string;
    meta: string | null;
    colour: string;
    amount: string;
    amountPence: number;
    initials: string;
    isRecurring: boolean;
}

export interface HouseStat {
    label: string;
    value: string;
}

export interface RoleOption {
    value: string;
    label: string;
}

export interface HouseMember {
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
    pendingReason: 'invited' | 'requested' | null;
}

export interface HouseRole {
    name: string;
    colour: string;
    body: string;
    count: number;
}

export interface Plan {
    name: string;
    priceMonthly: number;
    priceAnnual: number;
    sub: string;
    cta: string;
    note: string;
    features: string[];
    /** Everyone in a household counts, including pending invites and join requests. */
    maxMembers: number;
}

export interface PlanStatus {
    onTrial: boolean;
    trialEndsLabel: string | null;
    subscribed: boolean;
    interval: 'monthly' | 'annual' | null;
    /** Set once a subscription is cancelled but still running to the end of its paid period. */
    endsLabel: string | null;
    /** A renewal failed — the owner needs to update their card rather than subscribe again. */
    paymentIssue: boolean;
    priceMonthly: number;
    priceAnnual: number;
}

export interface HouseholdOption {
    id: number;
    name: string;
    role: string;
    roleLabel: string;
    memberCount: number;
    memberAvatars: { initials: string; colour: string }[];
}

export interface House {
    houseName: string;
    houseCreated: string;
    joinCode: string;
    joinCodeEnabled: boolean;
    houseStats: HouseStat[];
    /** Includes pending invites and join requests — they hold a place too. */
    memberCount: number;
    maxMembers: number;
    roleOptions: RoleOption[];
    members: HouseMember[];
    roles: HouseRole[];
    planStatus: PlanStatus;
}

export interface DocumentFolder {
    id: number;
    name: string;
    icon: string;
    colour: string;
    /** The `Palette` case name, for the colour picker. */
    colourKey: string;
    count: number;
}

export interface Document {
    id: number;
    name: string;
    extension: string;
    /** e.g. "2.4 MB · 12 Mar 2026". */
    meta: string;
    tags: string[];
    /** e.g. "19 Nov 2027", or null if the document has no expiry set. */
    expiryLabel: string | null;
    /** True when `expiryLabel` is within 60 days. */
    isUrgent: boolean;
    addedBy: Member | null;
}

export interface Dashboard {
    greeting: string;
    dateLine: string;
    summaryLine: string;
    streakDays: number;
    family: Member[];
    tonight: PlannedMeal | null;
    shoppingList: ShoppingList | null;
    chores: Chore[];
    choreProgress: ChoreProgress;
    agenda: AgendaGroup[];
    budget: BudgetSummary;
}
