export function formatMoney(value) {
    const amount = Number(value ?? 0);
    return new Intl.NumberFormat('nl-NL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function sanitizeMoneyInput(rawValue, { allowEmpty = true } = {}) {
    const raw = String(rawValue ?? '').trim();
    if (raw === '') {
        return allowEmpty ? '' : '0.00';
    }

    const cleaned = raw
        .replace(/\s+/g, '')
        .replace(/[^0-9,.-]/g, '');

    const lastComma = cleaned.lastIndexOf(',');
    const lastDot = cleaned.lastIndexOf('.');
    const separatorIndex = Math.max(lastComma, lastDot);

    const intPartRaw = (separatorIndex >= 0 ? cleaned.slice(0, separatorIndex) : cleaned).replace(/\D/g, '');
    const decimalPart = (separatorIndex >= 0 ? cleaned.slice(separatorIndex + 1) : '')
        .replace(/\D/g, '')
        .slice(0, 2);
    const intPart = intPartRaw === '' ? '0' : intPartRaw.replace(/^0+(?=\d)/, '');

    return decimalPart.length > 0 ? `${intPart}.${decimalPart}` : intPart;
}

export function moneyDisplayValue(value, { fallback = '' } = {}) {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }
    return formatMoney(value);
}
