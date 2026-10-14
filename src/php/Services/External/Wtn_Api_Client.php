<?php
/**
 * External Services: legacy WTN API client alias
 *
 * Deprecated location kept for backward compatibility only. The LTA World Tennis Number
 * integration now lives in the secondary adapter
 * Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter, which implements
 * the Rating_Gateway_Interface port (Domain\Ports). The container resolves both
 * 'rating_gateway' and 'wtn_api_client' to that adapter; new code should depend on the port.
 */

namespace Racketmanager\Services\External;

use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;

/**
 * Legacy alias/subclass for Lta_Wtn_Gateway_Adapter.
 */
class Wtn_Api_Client extends Lta_Wtn_Gateway_Adapter {
}
