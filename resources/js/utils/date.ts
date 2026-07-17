export const formatDate = (
    value: string | null | undefined,
    options: Intl.DateTimeFormatOptions,
    fallback = '—'
): string => {
    if (!value) return fallback;

    const text = String(value);
    const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(text) ? `${text}T00:00:00` : text);

    return Number.isNaN(date.getTime())
        ? fallback
        : new Intl.DateTimeFormat('en-US', options).format(date);
};

export const formatShortDate = (value: string | null | undefined, fallback = '—'): string =>
    formatDate(value, { month: 'short', day: '2-digit', year: '2-digit' }, fallback);

export const formatMediumDate = (value: string | null | undefined, fallback = '—'): string =>
    formatDate(value, { month: 'short', day: 'numeric', year: 'numeric' }, fallback);

export const formatLongDate = (value: string | null | undefined, fallback = '—'): string =>
    formatDate(value, { month: 'long', day: 'numeric', year: 'numeric' }, fallback);
