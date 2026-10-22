import type { AuthUserData, HouseholdAbilitiesData, MealSuggestionData } from '@/types/generated';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: AuthUserData | null;
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

export interface SharedData {
    name: string;
    auth: Auth;
    /** The household in the URL; on pages outside one (settings), the one they were last in. */
    household: { id: number; name: string; slug: string; can: HouseholdAbilitiesData } | null;
    flash: { toast: string | null; toastError: string | null; aiMeals: MealSuggestionData[] | null; libraryMeals: MealSuggestionData[] | null };
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

export type BreadcrumbItemType = BreadcrumbItem;
