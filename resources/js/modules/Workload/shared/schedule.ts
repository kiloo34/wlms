/**
 * Util jadwal: durasi dalam hari kalender dan inklusif.
 * Mulai 1 Des + durasi 1 hari = selesai 1 Des; durasi 5 hari = selesai 5 Des.
 * Semua tanggal berformat YYYY-MM-DD dan dihitung di zona waktu lokal browser.
 */

function parseDate(value?: string | null): Date | null {
    if (!value) return null;
    const [y, m, d] = value.slice(0, 10).split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

function formatDate(date: Date): string {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

const DAY_MS = 24 * 60 * 60 * 1000;

function diffDays(from: Date, to: Date): number {
    // Math.round menetralkan pergeseran jam saat DST.
    return Math.round((to.getTime() - from.getTime()) / DAY_MS);
}

export function todayString(): string {
    return formatDate(new Date());
}

export function endDateFromDuration(
    startDate?: string | null,
    durationDays?: number | null,
): string | null {
    const start = parseDate(startDate);
    if (!start || !durationDays || durationDays < 1) return null;
    start.setDate(start.getDate() + durationDays - 1);
    return formatDate(start);
}

export function durationBetween(
    startDate?: string | null,
    endDate?: string | null,
): number | null {
    const start = parseDate(startDate);
    const end = parseDate(endDate);
    if (!start || !end || end < start) return null;
    return diffDays(start, end) + 1;
}

/** Sisa hari ke tanggal selesai: negatif = terlambat, 0 = hari ini. */
export function remainingDays(endDate?: string | null): number | null {
    const end = parseDate(endDate);
    if (!end) return null;
    const today = parseDate(todayString()) as Date;
    return diffDays(today, end);
}

export function formatDisplayDate(
    value?: string | null,
    locale: string = 'id',
): string {
    const date = parseDate(value);
    if (!date) return '-';
    return date.toLocaleDateString(locale === 'id' ? 'id-ID' : 'en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
