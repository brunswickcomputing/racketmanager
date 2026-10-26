<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Racketmanager\Tests\Architecture\Rules\Layer_Rules;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;

/**
 * Guardrails that span layers: legacy/deprecated helper calls, class aliases, strict_types,
 * extract(), composition-root singletons, templates and admin screens, scanner health.
 * Rule catalogue: Layer_Rules (`all.*`, `templates.*`, `admin.*`, `scanner.*`).
 */
final class Cross_Cutting_Test extends Architecture_Test_Case {
    #[DataProvider( 'rules' )]
    public function test_rule( Layer_Rule $rule ): void {
        $this->assert_rule_holds( $rule );
    }

    /**
     * @return iterable<string, array{0: Layer_Rule}>
     */
    public static function rules(): iterable {
        yield from Layer_Rules::provide( 'all.', 'templates.', 'admin.', 'scanner.' );
    }
}
