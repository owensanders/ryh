import { withSetup } from '@/test/withSetup';
import type { PlannerDayData } from '@/types/generated';
import { describe, expect, it, vi } from 'vitest';
import { provideAiMealPlanForm, type AiMealPlanSettings } from './useAiMealPlanForm';

const day = (date: string, extra: Partial<PlannerDayData> = {}): PlannerDayData => ({
    date,
    dayLabel: date,
    dateLabel: '',
    isToday: false,
    meals: [],
    ...extra,
});

const settings = (overrides: Partial<AiMealPlanSettings> = {}): AiMealPlanSettings => ({
    aiConfigured: true,
    aiPaused: false,
    aiAvailableFrom: null,
    aiSuggestedBudget: null,
    aiOptions: { diets: [], goals: [], confidence: [], equipment: [] },
    days: [day('2026-10-19'), day('2026-10-20', { isToday: true }), day('2026-10-21'), day('2026-10-22')],
    todayDate: '2026-10-20',
    shoppingLists: [],
    ...overrides,
});

describe('useAiMealPlanForm', () => {
    it('preselects the free days from today on, and a suggested budget', () => {
        const dinner = { slotKey: 'dinner' } as PlannerDayData['meals'][number];
        const form = withSetup(() =>
            provideAiMealPlanForm(
                settings({
                    aiSuggestedBudget: 60,
                    days: [day('2026-10-19'), day('2026-10-20', { isToday: true }), day('2026-10-21', { meals: [dinner] }), day('2026-10-22')],
                }),
            ),
        );

        form.reset();

        expect(form.selectedDays.value).toEqual([1, 3]);
        expect(form.budgetAmount.value).toBe(60);
        expect(form.summary.value).toBe('2 dinners · 4 people · Oven, Hob · £60.00');
    });

    it("judges another week by the household's today, not the reader's clock", () => {
        // The reader's clock is a week ahead of the household's.
        vi.useFakeTimers({ now: new Date('2026-10-28T12:00:00') });
        const form = withSetup(() => provideAiMealPlanForm(settings({ days: [day('2026-10-26'), day('2026-10-27')] })));

        form.reset();
        vi.useRealTimers();

        expect(form.selectedDays.value).toEqual([0, 1]);
    });

    it('only asks the questions the library plan needs when the AI can’t be used', () => {
        expect(withSetup(() => provideAiMealPlanForm(settings())).steps.value).toHaveLength(4);

        const paused = withSetup(() => provideAiMealPlanForm(settings({ aiPaused: true })));
        expect(paused.steps.value.map((step) => step.key)).toEqual(['days', 'food']);
        expect(paused.aiUnavailable.value).toContain('taking a short break');

        const used = withSetup(() => provideAiMealPlanForm(settings({ aiAvailableFrom: '30 October' })));
        expect(used.aiUnavailable.value).toBe("You've used this week's AI plan — the next one is available from 30 October.");
    });

    it('needs a day before moving on, and only steps back through answered steps', () => {
        const form = withSetup(() => provideAiMealPlanForm(settings()));

        form.next();
        expect(form.step.value).toBe(0);

        form.toggleDay(2);
        form.next();
        form.next();
        expect(form.step.value).toBe(2);

        form.goTo(3);
        expect(form.step.value).toBe(2);
        form.goTo(0);
        expect(form.step.value).toBe(0);
    });

    it('opens the earliest step with an error, and finds an error on a list item', () => {
        const form = withSetup(() => provideAiMealPlanForm(settings()));
        form.step.value = 3;

        form.goToFirstError({ notes: 'Too long.', 'equipment.0': 'Pick from the list.', avoid: 'Too many.' });
        expect(form.step.value).toBe(1);

        form.form.setError({ 'equipment.1': 'Pick from the list.' });
        expect(form.fieldError('equipment')).toBe('Pick from the list.');
        expect(form.fieldError('diets')).toBeUndefined();
    });

    it('ticks equipment on and off', () => {
        const form = withSetup(() => provideAiMealPlanForm(settings()));

        form.toggleEquipment('Oven');
        form.toggleEquipment('Air fryer');

        expect(form.form.equipment).toEqual(['Hob', 'Air fryer']);
    });
});
