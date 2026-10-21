import type { AiMealSuggestion } from '@/types/ryh';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

/** Raw `HouseholdRoleEnum` value. */
export type HouseholdRole = 'owner' | 'adult' | 'teen' | 'child';

export interface SharedData {
    name: string;
    auth: Auth;
    /** The household in the URL; on pages outside one (settings), the one they were last in. */
    household: { id: number; name: string; slug: string; role: HouseholdRole | null } | null;
    flash: { toast: string | null; toastError: string | null; aiMeals: AiMealSuggestion[] | null; libraryMeals: AiMealSuggestion[] | null };
    // Inertia's PageProps requires an index signature to be used with usePage<T>().
    [key: string]: unknown;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
