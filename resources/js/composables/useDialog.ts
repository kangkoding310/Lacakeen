import { reactive } from 'vue';

type DialogVariant = 'info' | 'confirm' | 'prompt';

interface DialogOptions {
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    /** Style the confirm button as destructive (red) instead of primary (blue). */
    danger?: boolean;
}

interface PromptOptions extends DialogOptions {
    placeholder?: string;
    defaultValue?: string;
    /** Return an error message to block submission, or nothing to allow it. */
    validate?: (value: string) => string | void;
}

interface DialogState {
    open: boolean;
    variant: DialogVariant;
    title: string;
    description?: string;
    confirmText: string;
    cancelText: string;
    danger: boolean;
    placeholder?: string;
    value: string;
    error?: string;
}

const state = reactive<DialogState>({
    open: false,
    variant: 'info',
    title: '',
    description: undefined,
    confirmText: 'OK',
    cancelText: 'Cancel',
    danger: false,
    placeholder: undefined,
    value: '',
    error: undefined,
});

let validator: ((value: string) => string | void) | undefined;
let resolvePromise: ((value: never) => void) | null = null;

function open<T>(config: Partial<DialogState>): Promise<T> {
    return new Promise((resolve) => {
        resolvePromise = resolve as (value: unknown) => void;
        Object.assign(state, {
            description: undefined,
            danger: false,
            placeholder: undefined,
            value: '',
            error: undefined,
            ...config,
            open: true,
        });
    });
}

function settle(value: unknown) {
    state.open = false;
    validator = undefined;
    resolvePromise?.(value as never);
    resolvePromise = null;
}

/** Info/alert dialog — single acknowledgement button. Resolves when dismissed. */
export function alertDialog(options: DialogOptions): Promise<void> {
    return open<void>({
        variant: 'info',
        title: options.title,
        description: options.description,
        confirmText: options.confirmText ?? 'OK',
        cancelText: options.cancelText ?? 'Cancel',
    });
}

/** Confirm dialog for dangerous/irreversible actions. Resolves true/false. */
export function confirmDialog(options: DialogOptions): Promise<boolean> {
    return open<boolean>({
        variant: 'confirm',
        title: options.title,
        description: options.description,
        confirmText: options.confirmText ?? 'Confirm',
        cancelText: options.cancelText ?? 'Cancel',
        danger: options.danger ?? false,
    });
}

/** Prompt dialog for free-text input. Resolves the entered string, or null if canceled. */
export function promptDialog(options: PromptOptions): Promise<string | null> {
    validator = options.validate;
    return open<string | null>({
        variant: 'prompt',
        title: options.title,
        description: options.description,
        confirmText: options.confirmText ?? 'OK',
        cancelText: options.cancelText ?? 'Cancel',
        placeholder: options.placeholder,
        value: options.defaultValue ?? '',
    });
}

function confirmAction() {
    if (state.variant === 'prompt') {
        const message = validator?.(state.value);
        if (message) {
            state.error = message;
            return;
        }
        settle(state.value);
        return;
    }
    settle(state.variant === 'confirm' ? true : undefined);
}

function cancelAction() {
    settle(state.variant === 'confirm' ? false : state.variant === 'prompt' ? null : undefined);
}

/** Renders the app-wide dialog UI (mounted once in AppLayout); use the exported functions above to open it. */
export function useDialog() {
    return { state, confirmAction, cancelAction };
}
