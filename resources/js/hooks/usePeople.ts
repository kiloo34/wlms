import { useQuery } from '@tanstack/react-query';
import axios from '@/lib/axios';

export interface Person {
    id: number;
    name: string;
    email: string;
    employee_id?: string | null;
    org_unit_name?: string | null;
}

/** Direktori user aktif untuk memilih owner project / owner tugas. */
export function usePeople(enabled = true) {
    return useQuery<Person[]>({
        queryKey: ['people'],
        queryFn: async () => {
            const { data } = await axios.get('/api/people');
            return data?.data ?? [];
        },
        enabled,
        staleTime: 5 * 60 * 1000,
    });
}

export function personLabel(person: Person): string {
    return person.org_unit_name
        ? `${person.name} · ${person.org_unit_name}`
        : person.name;
}
