<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Racketmanager\Tests\Architecture\Rules\Layer_Rules;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;

/**
 * Guardrails for src/php/Services (and the other outer layers sharing the `services.*` rules) and
 * src/php/Repositories: no raw SQL in services, no global service locator, no Presentation imports,
 * repositories do not depend on services. Rule catalogue: Layer_Rules (`services.*`, `repositories.*`).
 */
final class Services_And_Repositories_Layer_Test extends Architecture_Test_Case {
    #[DataProvider( 'rules' )]
    public function test_rule( Layer_Rule $rule ): void {
        $this->assert_rule_holds( $rule );
    }

    /**
     * @return iterable<string, array{0: Layer_Rule}>
     */
    public static function rules(): iterable {
        yield from Layer_Rules::provide( 'services.', 'repositories.' );
    }
}
