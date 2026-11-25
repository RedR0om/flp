export type FlpLevelOption = {
    value: string;
    title: string;
    description: string;
    label: string;
};

export type ClusterOption = {
    id: number;
    number: number;
    supervisor_name: string;
    supervisor_title: string;
};

export type SchoolSectionSummary = {
    id: number;
    name: string;
    learners: number;
    internet_percent: number;
};

export type SchoolRow = {
    id: number;
    name: string;
    head_name: string | null;
    cluster_id: number;
    cluster_number: number;
    cluster_label: string;
    supervisor_name: string;
    supervisor_title: string;
    learners_total: number | null;
    learners_reached: number | null;
    flp_level: string;
    flp_label: string;
    ta_needed: boolean;
    reach_rate: number | null;
    status: string;
    status_label: string;
    sections?: SchoolSectionSummary[];
};

export type EnrollmentRow = {
    cluster: number;
    label: string;
    schools: number;
    enrollment: number;
    with_enrollment: number;
    share: number;
};

export type EnrollmentSummary = {
    total: number;
    with_records: number;
    schools: number;
    rows: EnrollmentRow[];
};

export const statusActions: Record<string, string> = {
    ex: 'Sustain monitoring',
    ok: 'Routine monitoring',
    na: 'Follow-up required',
    hp: 'Immediate school/cluster intervention',
    cr: 'Immediate intervention and follow-up',
    none: '',
};

export type StatusSegment = {
    key: string;
    label: string;
    count: number;
    color: string;
};

export type MonitoringFilters = {
    cluster: string;
    q: string;
};

export const levelColors: Record<string, string> = {
    HAYO: '#1f9d55',
    HINAY: '#f2663f',
    HINGA: '#ee9422',
    HINTO: '#e0413a',
};

export function formatNumber(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US').format(value);
}

export function formatRate(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return `${value.toFixed(1)}%`;
}
