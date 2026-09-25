export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type Player = {
    id: number;
    name: string;
    date_started: string | null;
    scores_count: number;
    scores_total: number;
};

export type Score = {
    id: number;
    player_id: number;
    player_name: string;
    score: number;
    is_burst: boolean;
    created_at: string | null;
};

export type ScoreEntry = {
    score: number;
    is_burst: boolean;
};

export type AwardWinner = {
    player_id: number;
    player_name: string;
    value: number;
};

export type AwardLeaderboard = {
    key: string;
    name: string;
    description: string;
    metric: string;
    leaders: AwardWinner[];
};

export type LeaderboardRow = {
    player_id: number;
    player_name: string;
    rank: number;
    battles: number;
    points: number;
    spin: number;
    over: number;
    burst: number;
    extreme: number;
};

export type PlayerPlacement = {
    key: string;
    name: string;
    position: number;
    value: number;
    metric: string;
};

export type PlayerProfile = {
    player_id: number;
    player_name: string;
    date_started: string | null;
    rank: number | null;
    total_players: number;
    battles: number;
    points: number;
    average: number;
    breakdown: {
        spin: number;
        over: number;
        burst: number;
        extreme: number;
    };
    placements: PlayerPlacement[];
    recent: {
        id: number;
        score: number;
        is_burst: boolean;
        created_at: string | null;
    }[];
};

export type Award = {
    key: string;
    name: string;
    description: string;
    metric: string;
    winner: AwardWinner | null;
};

export type AuditLogChange = {
    action: 'created' | 'updated' | 'deleted';
    subject: string;
    score_id: number;
    player_id: number;
    player_name: string | null;
    old: ScoreEntry | null;
    new: ScoreEntry | null;
};

export type AuditLog = {
    id: number;
    user_name: string;
    changes: AuditLogChange;
    created_at: string | null;
};
