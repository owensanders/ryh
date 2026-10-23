import { computed, inject, type ComputedRef, type InjectionKey } from 'vue';

/** What a Field tells the control inside it: whether it's in error, and what describes it. */
export interface FieldContext {
    invalid: ComputedRef<boolean>;
    describedBy: ComputedRef<string | undefined>;
}

export const fieldKey: InjectionKey<FieldContext> = Symbol('Field');

/** The aria attributes for a control: from its Field when it's in one, otherwise none. */
export function useFieldAria() {
    const field = inject(fieldKey, null);

    return computed(() => ({
        'aria-invalid': field?.invalid.value ? true : undefined,
        'aria-describedby': field?.describedBy.value,
    }));
}
