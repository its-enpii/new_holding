/**
 * Shared currency formatting composable.
 *
 * Usage:
 *   const { format } = useMoney();
 *   format(150000)            // => 'Rp150.000'
 *   format(150000, 'USD')     // => 'US$150'
 */
export function useMoney() {
    const cache = new Map();

    function formatter(currency) {
        if (!cache.has(currency)) {
            cache.set(
                currency,
                new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency,
                    maximumFractionDigits: 0,
                }),
            );
        }
        return cache.get(currency);
    }

    function format(value, currency = 'IDR') {
        return formatter(currency).format(Number(value || 0));
    }

    return { format };
}
