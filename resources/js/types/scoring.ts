export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

/** A team as shown next to a blader name. */
export type TeamSummary = {
    name: string;
    acronym: string | null;
    logo_url: string | null;
};

export type Player = {
    id: number;
    /** Real name (optional). */
    name: string | null;
    blader_name: string;
    team: TeamSummary | null;
    date_started: string | null;
    scores_count: number;
    scores_total: number;
};

/** The one-time DBBL roster seed (Settings > Profile). */
export type PlayerDataStatus = {
    teams: number;
    players: number;
    seeded_at: string | null;
    seeded_by: string | null;
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

// On standings and the dashboard, `player_name` is the blader name.
export type AwardWinner = {
    player_id: number;
    player_name: string;
    value: number;
    team: TeamSummary | null;
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
    team: TeamSummary | null;
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
    team: TeamSummary | null;
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
    /** The player's current team (dashboard activity only). */
    team?: TeamSummary | null;
    created_at: string | null;
};
