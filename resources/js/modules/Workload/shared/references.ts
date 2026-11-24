import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from '@/lib/axios';

/**
 * Rujukan "@" ke project WLMS atau dashboard di Katalog Dashboard.
 * Disimpan di teks sebagai token `@[Nama](tipe:uuid)` sehingga tetap bisa dicari
 * dan tidak bentrok dengan mention user (`@namauser`).
 */
export type ReferenceType = 'project' | 'dashboard';

export interface ReferenceSuggestion {
    type: ReferenceType;
    id: string;
    label: string;
    subtitle: string;
    description: string | null;
    owner: string | null;
}

export interface ReferenceDetail {
    type: ReferenceType;
    id: string;
    label: string;
    url: string;
    remaining_days?: number | null;
    dictionary: { key: string; label: string; value: string | number | null }[];
}

export type ReferenceSegment =
    | { kind: 'text'; value: string }
    | { kind: 'reference'; type: ReferenceType; id: string; label: string };

const TOKEN_SOURCE =
    '@\\[([^\\]\\r\\n]{1,200})\\]\\((project|dashboard):([0-9a-fA-F-]{36})\\)';

export function buildReferenceToken(
    type: ReferenceType,
    id: string,
    label: string,
): string {
    // Kurung siku di nama diganti agar token tetap valid.
    const safeLabel = label.replace(/[[\]\r\n]/g, ' ').trim();
    return `@[${safeLabel}](${type}:${id})`;
}

export function parseReferences(text: string): ReferenceSegment[] {
    const regex = new RegExp(TOKEN_SOURCE, 'g');
    const segments: ReferenceSegment[] = [];
    let cursor = 0;
    let match: RegExpExecArray | null;

    while ((match = regex.exec(text)) !== null) {
        if (match.index > cursor) {
            segments.push({
                kind: 'text',
                value: text.slice(cursor, match.index),
            });
        }
        segments.push({
            kind: 'reference',
            label: match[1],
            type: match[2] as ReferenceType,
            id: match[3],
        });
        cursor = match.index + match[0].length;
    }

    if (cursor < text.length) {
        segments.push({ kind: 'text', value: text.slice(cursor) });
    }

    return segments;
}

/** Ubah token menjadi "@Nama" untuk title/tooltip dan teks polos. */
export function referencesToPlainText(text: string): string {
    return text.replace(new RegExp(TOKEN_SOURCE, 'g'), '@$1');
}

export function useReferenceSearch(term: string, enabled: boolean) {
    return useQuery<{
        projects: ReferenceSuggestion[];
        dashboards: ReferenceSuggestion[];
    }>({
        queryKey: ['references', term],
        queryFn: async () => {
            const { data } = await axios.get('/api/references', {
                params: { q: term, limit: 6 },
            });
            return data.data;
        },
        enabled,
        placeholderData: keepPreviousData,
        staleTime: 60 * 1000,
    });
}

export function useReferenceDetail(
    type: ReferenceType,
    id: string,
    enabled: boolean,
) {
    return useQuery<ReferenceDetail>({
        queryKey: ['references', type, id],
        queryFn: async () => {
            const { data } = await axios.get(`/api/references/${type}/${id}`);
            return data.data;
        },
        enabled,
        staleTime: 5 * 60 * 1000,
        retry: false,
    });
}
