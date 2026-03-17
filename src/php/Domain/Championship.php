<?php
/**
 * Championship API: Championship class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

class Championship {
    /**
     * League ID.
     *
     * @var int
     */
    private int $league_id;

    /**
     * Is consolation draw?
     *
     * @var bool
     */
    private bool $is_consolation;

    /**
     * Championship settings.
     *
     * @var Championship_Settings
     */
    private Championship_Settings $settings;

    /**
     * Number of teams advancing to finals.
     *
     * @var int
     */
    private int $num_advance;

    /**
     * Number of final rounds.
     *
     * @var int
     */
    private int $num_rounds;

    /**
     * Number of teams in the league.
     *
     * @var int
     */
    private int $num_teams;

    /**
     * Number of teams in the first round.
     *
     * @var int
     */
    private int $num_teams_first_round;

    /**
     * Number of seeds.
     *
     * @var int
     */
    private int $num_seeds;

    /**
     * Finals indexed by round.
     *
     * @var array
     */
    private array $keys_by_round;

    /**
     * Finals indexed by key.
     *
     * @var array
     */
    private array $finals_by_key;

    /**
     * Placeholder teams indexed by a final key.
     *
     * @var array
     */
    private array $final_teams_by_round;

    /**
     * Current/default final key.
     *
     * @var string
     */
    private string $current_final;

    /**
     * Constructor.
     *
     * @param int $league_id league id.
     * @param bool $is_consolation consolation flag.
     * @param Championship_Settings $settings settings.
     * @param int $num_advance number advancing.
     * @param int $num_rounds number of rounds.
     * @param int $num_teams number of teams.
     * @param int $num_teams_first_round teams in first round.
     * @param int $num_seeds number of seeds.
     * @param array $keys_by_round keys by round.
     * @param array $finals_by_key finals by key.
     * @param array $final_teams_by_round final teams by round.
     * @param string $current_final current final key.
     */
    public function __construct(
        int $league_id,
        bool $is_consolation,
        Championship_Settings $settings,
        int $num_advance,
        int $num_rounds,
        int $num_teams,
        int $num_teams_first_round,
        int $num_seeds,
        array $keys_by_round,
        array $finals_by_key,
        array $final_teams_by_round,
        string $current_final,
    ) {
        $this->league_id             = $league_id;
        $this->is_consolation        = $is_consolation;
        $this->settings              = $settings;
        $this->num_advance           = $num_advance;
        $this->num_rounds            = $num_rounds;
        $this->num_teams             = $num_teams;
        $this->num_teams_first_round = $num_teams_first_round;
        $this->num_seeds             = $num_seeds;
        $this->keys_by_round         = $keys_by_round;
        $this->finals_by_key         = $finals_by_key;
        $this->final_teams_by_round  = $final_teams_by_round;
        $this->current_final         = $current_final;
    }

    /**
     * Magic property access for backwards compatibility.
     *
     * @param string $name property name.
     *
     * @return mixed
     */
    public function __get( string $name ): mixed {
        return match ( $name ) {
            'league_id' => $this->league_id,
            'is_consolation' => $this->is_consolation,
            'groups' => $this->settings->groups,
            'teams_per_group' => $this->settings->teams_per_group,
            'num_groups' => $this->settings->num_groups(),
            'num_advance' => $this->num_advance,
            'num_rounds' => $this->num_rounds,
            'num_teams' => $this->num_teams,
            'num_teams_first_round' => $this->num_teams_first_round,
            'num_seeds' => $this->num_seeds,
            'finals' => $this->finals_by_key,
            'current_final' => $this->current_final,
            'final_teams' => $this->final_teams_by_round,
            default => null,
        };
    }

    /**
     * Magic isset support for backwards compatibility.
     *
     * @param string $name property name.
     *
     * @return bool
     */
    public function __isset( string $name ): bool {
        return in_array(
            $name,
            array(
                'league_id',
                'is_consolation',
                'groups',
                'teams_per_group',
                'num_groups',
                'num_advance',
                'num_rounds',
                'num_teams',
                'num_teams_first_round',
                'num_seeds',
                'finals',
                'current_final',
                'final_teams',
            ),
            true
        );
    }

    /**
     * Get league id.
     *
     * @return int
     */
    public function league_id(): int {
        return $this->league_id;
    }

    /**
     * Check if it is a consolation championship.
     *
     * @return bool
     */
    public function is_consolation(): bool {
        return $this->is_consolation;
    }

    /**
     * Get settings.
     *
     * @return Championship_Settings
     */
    public function settings(): Championship_Settings {
        return $this->settings;
    }

    /**
     * Get groups.
     *
     * @return array
     */
    public function groups(): array {
        return $this->settings->groups;
    }

    /**
     * Get number advancing.
     *
     * @return int
     */
    public function num_advance(): int {
        return $this->num_advance;
    }

    /**
     * Get the number of rounds.
     *
     * @return int
     */
    public function num_rounds(): int {
        return $this->num_rounds;
    }

    /**
     * Get the number of teams.
     *
     * @return int
     */
    public function num_teams(): int {
        return $this->num_teams;
    }

    /**
     * Get teams in the first round.
     *
     * @return int
     */
    public function num_teams_first_round(): int {
        return $this->num_teams_first_round;
    }

    /**
     * Get the number of seeds.
     *
     * @return int
     */
    public function num_seeds(): int {
        return $this->num_seeds;
    }

    /**
     * Get all finals.
     *
     * @return array
     */
    public function finals(): array {
        return $this->finals_by_key;
    }

    /**
     * Get a specific final.
     *
     * @param string $key final key.
     *
     * @return array|null
     */
    public function final( string $key ): ?array {
        return $this->finals_by_key[ $key ] ?? null;
    }

    /**
     * Get all final keys by round.
     *
     * @return array
     */
    public function final_keys(): array {
        return $this->keys_by_round;
    }

    /**
     * Get the final key for the round.
     *
     * @param int $round round number.
     *
     * @return string|false
     */
    public function final_key_for_round( int $round ): string|false {
        return $this->keys_by_round[ $round ] ?? false;
    }

    /**
     * Get the default / current final key.
     *
     * @return string|false
     */
    public function default_final_key(): string|false {
        if ( '' !== $this->current_final ) {
            return $this->current_final;
        }

        return $this->final_key_for_round( 1 );
    }

    /**
     * Get final teams for the round.
     *
     * @param string $final_round final round key.
     *
     * @return array|null
     */
    public function final_teams( string $final_round ): ?array {
        return $this->final_teams_by_round[ $final_round ] ?? null;
    }

    /**
     * Get the current final key.
     *
     * @return string
     */
    public function current_final(): string {
        return $this->current_final;
    }

    /**
     * Backwards-compatible getter for groups.
     *
     * @return array
     */
    public function get_groups(): array {
        return $this->groups();
    }

    /**
     * Backwards-compatible getter for final keys.
     *
     * @param false|int $round round number.
     *
     * @return array|string|false
     */
    public function get_final_keys( false|int $round = false ): array|string|false {
        if ( false === $round ) {
            return $this->final_keys();
        }

        return $this->final_key_for_round( $round );
    }

    /**
     * Backwards-compatible getter for finals.
     *
     * @param false|int|string $key final key.
     *
     * @return array|null
     */
    public function get_finals( false|int|string $key = false ): ?array {
        if ( false === $key ) {
            return $this->finals();
        }

        if ( 'current' === $key ) {
            $key = $this->current_final();
        }

        return is_string( $key ) ? $this->final( $key ) : null;
    }

    /**
     * Backwards-compatible getter for the current final key.
     *
     * @return string
     */
    public function get_current_final_key(): string {
        return $this->current_final();
    }

    /**
     * Backwards-compatible getter for final teams.
     *
     * @param string $final_round final round.
     *
     * @return array|null
     */
    public function get_final_teams( string $final_round ): ?array {
        return $this->final_teams( $final_round );
    }

    public function get_finals_by_key(): array {
        return $this->finals_by_key;
    }

    /**
     * Resolve the final key from team count.
     *
     * @param int $num_teams teams in round.
     *
     * @return string
     */
    public static function resolve_final_key( int $num_teams ): string {
        return match ( $num_teams ) {
            2 => 'final',
            4 => 'semi',
            8 => 'quarter',
            default => 'last-' . $num_teams,
        };
    }
}
