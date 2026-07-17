export interface AnalyticsMemberStat {
    name: string;
    avatar: string | null;
    tasks: number;
    completed: number;
}

export interface Analytics {
    byStatus: Record<string, number>;
    byPriority: Record<string, number>;
    completion: Record<string, number>;
    members: AnalyticsMemberStat[];
}

export interface AnalyticsFilters {
    project: string;
    from: string;
    to: string;
}
