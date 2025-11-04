<?php
/**
 * Tournament Validation API: Tournament validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Validator;

if ( ! class_exists( 'Racketmanager\\Services\\Validator\\Validator_Tournament', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/validator/Validator_Tournament.php';
    return;
}

