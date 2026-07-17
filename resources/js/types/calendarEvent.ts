export interface CalendarEventItem {
    id: string;
    title: string;
    start: string;
    end?: string;
    allDay: boolean;
    backgroundColor: string;
    extendedProps: { type: 'task' | 'event'; projectId?: string; description?: string | null };
}

export interface CalendarFilters {
    project: string;
    assignee: string;
}

export interface CalendarEventFormPayload {
    title: string;
    description: string;
    project_id: string;
    start_at: string;
    end_at: string;
    all_day: boolean;
}
