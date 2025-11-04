<?php
/**
 * Config Validation API: Config validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Validator;

if ( ! class_exists('Racketmanager\\validator\\Validator_Config', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/validator/Validator_Config.php';
    return;
}

