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
class Racketmanager_Competition_Tennis extends Racketmanager_Competition {

	/**
	 * Sports key
	 *
	 * @var string
	 */
	public $sport = 'tennis';

	/**
	 * Default scoring
	 *
	 * @var int
	 */
	public $scoring = 'TB';

	/**
	 * Load specific settings
	 *
	 * @param object $competition competition.
	 * @return void
	 */
	public function __construct( $competition ) {
		parent::__construct( $competition );
		add_filter( 'racketmanager_point_rules_list', array( &$this, 'getPointRuleList' ) );
		add_filter( 'racketmanager_point_rules', array( &$this, 'get_point_rules' ) );
	}
	/**
	 * Get Point Rule list
	 *
	 * @param array $rules rules.
	 * @return array
	 */
	public function getPointRuleList( $rules ) {
		$rules['tennis']       = __( 'Tennis', 'racketmanager' );
		$rules['tennisSummer'] = __( 'Tennis Summer', 'racketmanager' );
		$rules['tennisRubber'] = __( 'Tennis Rubber', 'racketmanager' );

		return $rules;
	}

	/**
	 * Get Point rules
	 *
	 * @param array $rules rules.
	 * @return array
	 */
	public function get_point_rules( $rules ) {
		$rules['tennis']       = array(
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
		);
		$rules['tennisRubber'] = array(
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
		$rules['tennisSummer'] = array(
			'forwin'        => 0,
			'fordraw'       => 0,
			'forloss'       => 0,
			'forwin_split'  => 0,
			'forloss_split' => 0,
			'forshare'      => 0.5,
		);

		return $rules;
	}
}
