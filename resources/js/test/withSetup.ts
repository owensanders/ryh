import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';

/** Runs `setup` inside a mounted component, so provide/inject and watchers work, and returns what it returns. */
export function withSetup<T>(setup: () => T): T {
    let result!: T;

    mount(defineComponent({ setup: () => ((result = setup()), () => null) }));

    return result;
}
