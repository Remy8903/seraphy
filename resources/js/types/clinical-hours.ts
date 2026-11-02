export type SelectOption = {
    value: string;
    label: string;
};

export type ClinicalLogFormOptions = {
    days: SelectOption[];
    placements: SelectOption[];
    clinicModes: SelectOption[];
    practicumTypes: SelectOption[];
    hoursTypes: SelectOption[];
    ageGroups: SelectOption[];
    sessionModes: SelectOption[];
    sessionTypes: SelectOption[];
};

export type ClinicalLogMode = 'log_form' | 'simple';

export type ClinicalLogEntryInput = {
    date: string;
    age_group: string;
    rn_no: string;
    session_mode: string;
    session_type: string;
    diagnosis: string;
    hours: number | string;
    minutes: number | string;
};

export type ClinicalLogFormInput = {
    mode: ClinicalLogMode;
    year: number | string;
    semester: number | string;
    session_start_year: number | string;
    days: string[];
    placement: string;
    clinic_mode: string;
    placement_name: string;
    supervisor_name: string;
    practicum_type: string;
    hours_type: string;
    entries: ClinicalLogEntryInput[];
    hours: number | string;
    minutes: number | string;
};

export type ClinicalHoursCategory = {
    hours_type: string;
    label: string;
    target_minutes: number;
    completed_minutes: number;
    remaining_minutes: number;
    percentage: number;
    is_completed: boolean;
};

export type ClinicalHoursPeriod = {
    key: string;
    label: string;
    minutes: Record<string, number>;
};

export type ClinicalHoursSummary = {
    categories: ClinicalHoursCategory[];
    periods: ClinicalHoursPeriod[];
    totals: Record<string, number>;
};

export type ClinicalLogFormListItem = {
    id: number;
    mode: ClinicalLogMode;
    year: number;
    semester: number;
    session: string;
    placement_name: string | null;
    supervisor_name: string | null;
    practicum_type: string;
    hours_type: string;
    hours_type_label: string;
    entries_count: number;
    total_minutes: number;
};
