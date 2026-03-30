### Database Schematic

The Racketmanager plugin uses a series of custom tables to manage sports leagues, tournaments, clubs, teams, and matches. Below is a Mermaid entity-relationship diagram showing how these tables are linked.

```mermaid
erDiagram
    COMPETITIONS ||--o{ EVENTS : "has"
    COMPETITIONS ||--o{ COMPETITIONS_SEASONS : "belongs to"
    SEASONS ||--o{ COMPETITIONS_SEASONS : "includes"
    EVENTS ||--o{ LEAGUES : "has"
    LEAGUES ||--o{ LEAGUE_TEAMS : "contains"
    LEAGUES ||--o{ MATCHES : "scheduled in"
    TEAMS ||--o{ LEAGUE_TEAMS : "assigned to"
    CLUBS ||--o{ TEAMS : "owns"
    CLUBS ||--o{ CLUB_PLAYERS : "has"
    CLUBS ||--o{ CLUB_ROLES : "managed by"
    PLAYERS ||--o{ CLUB_PLAYERS : "belongs to"
    PLAYERS ||--o{ TEAM_PLAYERS : "plays for"
    TEAMS ||--o{ TEAM_PLAYERS : "consists of"
    MATCHES ||--o{ RUBBERS : "split into"
    RUBBERS ||--o{ RUBBER_PLAYERS : "played by"
    PLAYERS ||--o{ RUBBER_PLAYERS : "identified as"
    MATCHES ||--o{ RESULTS_REPORT : "has"
    MATCHES ||--o{ RESULTS_CHECKER : "validated by"
    COMPETITIONS ||--o{ CHARGES : "generates"
    CHARGES ||--o{ INVOICES : "billed as"
    CLUBS ||--o{ INVOICES : "pays"
    TOURNAMENTS ||--o{ TOURNAMENT_ENTRIES : "has"
    COMPETITIONS ||--o{ TOURNAMENTS : "includes"
    PLAYERS ||--o{ TOURNAMENT_ENTRIES : "enters"

    COMPETITIONS {
        int id PK
        string name
        longtext settings
        longtext seasons
        string type
        string age_group
    }

    EVENTS {
        int id PK
        int competition_id FK
        string name
        string type
        int num_sets
        int num_rubbers
        longtext settings
        longtext seasons
    }

    LEAGUES {
        int id PK
        int event_id FK
        string title
        longtext settings
        longtext seasons
        string sequence
    }

    SEASONS {
        int id PK
        string name
    }

    CLUBS {
        int id PK
        string name
        string website
        string address
        string contactno
        int founded
        string facilities
        string shortcode
    }

    TEAMS {
        int id PK
        int club_id FK
        string title
        string status
        string logo
        string stadium
        tinyint home
        longtext roster
        string type
        string team_type
    }

    MATCHES {
        int id PK
        int league_id FK
        string season
        int home_team FK
        int away_team FK
        datetime date
        string home_points
        string away_points
        int winner_id
        int status
        int post_id
        longtext comments
    }

    RUBBERS {
        int id PK
        int match_id FK
        int rubber_number
        string home_points
        string away_points
        int winner_id
        int loser_id
        int status
    }

    PLAYERS {
        int id PK "WP Users Table"
    }

    LEAGUE_TEAMS {
        int id PK
        int team_id FK
        int league_id FK
        string season
        float points_plus
        float points_minus
        int rank
        int captain FK
    }

    CLUB_PLAYERS {
        int id PK
        int club_id FK
        int player_id FK
        datetime created_date
    }

    TEAM_PLAYERS {
        int id PK
        int team_id FK
        int player_id FK
    }

    RUBBER_PLAYERS {
        int id PK
        int rubber_id FK
        int player_id FK
        int club_player_id FK
        string player_team
    }
```

#### Key Relationships and Structure:
*   **Hierarchy**: A `Competition` contains multiple `Events`, which in turn contain `Leagues`. `Leagues` are the containers for `Matches` (fixtures) for a specific season.
*   **Teams and Clubs**: Every `Team` belongs to a `Club`. A `Team` is entered into a `League` via the `league_teams` table, which also tracks league-specific standings (points, rank).
*   **Matches and Rubbers**: A `Match` (Fixture) is composed of multiple `Rubbers` (individual games). The `rubber_players` table tracks which specific players played in which rubber.
*   **Players**: Players are linked to `Clubs` (via `club_players`) and `Teams` (via `team_players`). Most player IDs refer to the standard WordPress `users` table.
*   **Financials**: `Charges` are generated based on `Competitions`, and `Invoices` are issued to `Clubs` or `Players`.
*   **Tournaments**: Independent of the league structure, `Tournaments` are linked to `Competitions` and track individual player `entries`.

Note: In the codebase, the table name for `LEAGUES` is often referenced as `racketmanager` or `racketmanager_leagues` through the `$wpdb` global.
