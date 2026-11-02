import type { ClinicalHoursCategory } from './clinical-hours';

export type DashboardWeek = {
    /** Monday of the week, as YYYY-MM-DD. */
    week_start: string;
    sessions: number;
};

export type DashboardRecentSession = {
    id: number;
    client_reference: string;
    activity_title: string;
    game_type_label: string;
    ended_at: string;
    answers: number;
    accuracy: number;
};

export type DashboardGoal = {
    id: number;
    client_id: number;
    client_reference: string;
    description: string;
    target_accuracy: number;
};

export type DashboardProps = {
    hours: {
        categories: ClinicalHoursCategory[];
        forms_count: number;
    };
    counts: {
        active_clients: number;
        activities: number;
        words: number;
        own_words: number;
    };
    sessions: {
        this_week: number;
        total: number;
        answers_last_30_days: number;
        accuracy_last_30_days: number | null;
    };
    weeklySessions: DashboardWeek[];
    recentSessions: DashboardRecentSession[];
    goals: {
        active_count: number;
        achieved_count: number;
        active: DashboardGoal[];
    };
};
