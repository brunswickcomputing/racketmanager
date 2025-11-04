<?php
/**
 * Entry Form Validation API: Plan validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Validator;

if ( ! class_exists( 'Racketmanager\\Services\\Validator\\Validator_Plan', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/validator/Validator_Plan.php';
    return;
}


