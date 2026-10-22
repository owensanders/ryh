import type { MealPlannerScreenData } from '@/types/generated';

/** Names for shapes the Data classes declare inline. Everything else comes from `generated.ts`. */
export type AiPlannerOptions = MealPlannerScreenData['aiOptions'];

export type TagOption = MealPlannerScreenData['tagOptions'][number];
