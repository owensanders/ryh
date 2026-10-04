import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useAddIngredientsToShoppingList() {
    const selectedListId = ref<number | null>(null);

    function addIngredientsToList(recipeId: number): void {
        if (selectedListId.value === null) {
            return;
        }

        router.post(route('recipes.addToShoppingList', { recipe: recipeId }), { shopping_list_id: selectedListId.value }, { preserveScroll: true });
    }

    return { selectedListId, addIngredientsToList };
}
