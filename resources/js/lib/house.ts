/** Teen and Child are for under-18s, who may only join with a parent or guardian's agreement. */
export function forMinors(role: string): boolean {
    return role === 'teen' || role === 'child';
}
