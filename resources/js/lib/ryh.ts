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

/** The Meal Planner props a planned meal can change: planning one can add or edit its recipe too. */
export const PLANNED_MEAL_PROPS = ['days', 'library', 'recipes', 'recipeCount'];
