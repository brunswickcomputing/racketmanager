<?php
/**
 * Tennis Competition class
 *
 * @package Racketmanager/Classes/Sports/Tennis
 */

namespace Racketmanager;

/**
 * Tennis competition clas
 */
class Competition_Tennis extends Competition {

	/**
	 * Sports key
	 *
	 * @var string
	 */
	public string $sport = 'tennis';

	/**
	 * Default scoring
	 *
	 * @var string|null
	 */
	public ?string $scoring = 'TB';

	/**
	 * Load specific settings
	 *
	 * @param object $competition competition.
	 *
	 * @return void
	 */
	public function __construct( object $competition ) {
		parent::__construct( $competition );
		add_filter( 'racketmanager_point_rules_list', array( &$this, 'get_point_rule_list' ) );
		add_filter( 'racketmanager_point_rules', array( &$this, 'get_point_rules' ) );
	}
	/**
	 * Get Point Rule list
	 *
	 * @param array $rules rules.
	 *
	 * @return array
	 */
	public function get_point_rule_list( array $rules ): array {
		$rules['tennis']                = __( 'Tennis', 'racketmanager' );
		$rules['tennisNoPenalty']       = __( 'Tennis No Penalty', 'racketmanager' );
		$rules['tennisSummer']          = __( 'Tennis Summer', 'racketmanager' );
		$rules['tennisSummerNoPenalty'] = __( 'Tennis Summer No Penalty', 'racketmanager' );
		$rules['tennisRubber']          = __( 'Tennis Rubber', 'racketmanager' );

		return $rules;
	}

	/**
	 * Get Point rules
	 *
	 * @param array $rules rules.
	 *
	 * @return array
	 */
	public function get_point_rules( array $rules ): array {
		$rules['tennis']                = array(
			'forwin'             => 1,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 0.5,
			'rubber_win'         => 0,
			'rubber_draw'        => 0,
			'shared_match'       => 0.5,
			'match_result'       => null,
			'forwalkover_rubber' => 1,
			'forwalkover_match'  => 1,
			'result_late'        => 1,
			'confirmation_late'  => 1,
		);
		$rules['tennisNoPenalty']       = array(
			'forwin'             => 1,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 0.5,
			'rubber_win'         => 0,
			'rubber_draw'        => 0,
			'shared_match'       => 0.5,
			'match_result'       => null,
			'forwalkover_rubber' => 0,
			'forwalkover_match'  => 0,
		);
		$rules['tennisRubber']          = array(
			'forwin'             => 0,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 0.5,
			'rubber_win'         => 2,
			'rubber_draw'        => 1,
			'shared_match'       => 0.5,
			'match_result'       => 'rubber_count',
			'forwalkover_rubber' => 2,
			'forwalkover_match'  => 0,
		);
		$rules['tennisSummer']          = array(
			'forwin'             => 1,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 0.5,
			'matches_win'        => 3,
			'matches_draw'       => 1.5,
			'forwalkover_rubber' => 1,
			'forwalkover_match'  => 1,
			'result_late'        => 1,
			'confirmation_late'  => 1,
		);
		$rules['tennisSummerNoPenalty'] = array(
			'forwin'             => 1,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 0.5,
			'matches_win'        => 3,
			'matches_draw'       => 1.5,
			'forwalkover_rubber' => 0,
			'forwalkover_match'  => 0,
		);
		$rules['score']                 = array(
			'forwin'             => 0,
			'fordraw'            => 0,
			'forloss'            => 0,
			'forwin_split'       => 0,
			'forloss_split'      => 0,
			'forshare'           => 1,
			'matches_win'        => 0,
			'matches_draw'       => 0,
			'match_result'       => 'games',
			'forwalkover_rubber' => 1,
			'forwalkover_match'  => 1,
		);

		return $rules;
	}
}
