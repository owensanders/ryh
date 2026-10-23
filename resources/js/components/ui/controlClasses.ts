/**
 * The classes for an Input or Select: the design's field (`ryh-input`) at one
 * of three sizes, or bare, for a control that sits inside its own styled box
 * (a search pill, a card's menu), whose box shows the focus ring instead.
 */
export type ControlSize = 'sm' | 'md' | 'lg';

const SIZES: Record<ControlSize, string> = {
    // Compact, for a control in a row: a member's role.
    sm: 'ryh-input h-[38px] rounded-[11px] px-2.5 text-[13px] font-semibold',
    md: 'ryh-input',
    // A quick-add bar's main field.
    lg: 'ryh-input h-12 rounded-[14px] px-4 text-[14.5px]',
};

const BARE = 'min-w-0 border-0 bg-transparent text-ryh-ink placeholder:text-ryh-ink3 outline-none';

export function controlClasses(size: ControlSize, bare: boolean): string {
    return bare ? BARE : SIZES[size];
}
