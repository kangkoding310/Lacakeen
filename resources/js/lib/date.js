export const formatDate = (value, options, fallback = '—') => {
    if (!value) return fallback;

    const text = String(value);
    const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(text) ? `${text}T00:00:00` : text);

    return Number.isNaN(date.getTime())
        ? fallback
        : new Intl.DateTimeFormat('en-US', options).format(date);
};
