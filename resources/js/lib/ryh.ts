/** The five card gradients from the design, cycled by index. */
export function tint(index: number): string {
    return `var(--ryh-t${(index % 5) + 1})`;
}

/** Conic-gradient sweep for the progress dials. */
export function dial(percentage: number, colour = 'var(--ryh-mint)'): string {
    return `conic-gradient(${colour} ${percentage * 3.6}deg, var(--ryh-sunk) 0)`;
}

/** Two-colour conic-gradient ring, e.g. income vs. expenses. */
export function donut(aPct: number, aColour: string, bColour: string): string {
    return `conic-gradient(${aColour} 0 ${aPct * 3.6}deg, ${bColour} 0)`;
}

/** Fill for a tick box — mint when ticked, hairline outline when not. */
export function tickStyle(done: boolean): { borderColor: string; background: string } {
    return {
        borderColor: done ? 'var(--ryh-mint)' : 'var(--ryh-line)',
        background: done ? 'var(--ryh-mint)' : 'transparent',
    };
}

/** A plan price in pounds, e.g. 6.99 -> "£6.99", 0 -> "Free". */
export function formatPlanPrice(amount: number): string {
    return amount === 0 ? 'Free' : `£${amount.toFixed(2).replace(/\.00$/, '')}`;
}

/** `part` as a whole-number percentage of `whole`, capped at 100; 0 when there's no whole. */
export function percentOf(part: number, whole: number): number {
    return whole > 0 ? Math.min(100, Math.round((part / whole) * 100)) : 0;
}

/** An amount in pounds to the penny, e.g. 12.5 -> "£12.50". */
export function pounds(amount: number): string {
    return `£${amount.toFixed(2)}`;
}

/** The Meal Planner props a planned meal can change: planning one can add or edit its recipe too. */
export const PLANNED_MEAL_PROPS = ['days', 'library', 'recipes', 'recipeCount'];

/** The Meal Planner props moving or removing a meal changes: the recipes stay as they are. */
export const PLANNED_DAY_PROPS = ['days'];
