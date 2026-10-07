export type TourStop = {
    id: string;
    name: string;
    address: string;
    remark: string | null;
};
export type Tour = {
    id: string;
    name: string;
    scheduled_date: string;
    departure_address: string | null;
    arrival_address: string | null;
    stops: TourStop[];
    started_at: string | null;
    finished_at: string | null;
    optimized_at: string | null;
    worker?: { id: string; name: string } | null;
};
export type TourPagination = {
    data: Tour[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
