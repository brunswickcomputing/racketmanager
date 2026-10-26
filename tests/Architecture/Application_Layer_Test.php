<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Racketmanager\Tests\Architecture\Rules\Layer_Rules;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;

/**
 * Guardrails for src/php/Application: depends on Domain and repository ports only, no `$wpdb`,
 * no global service locator, no HTTP input, no Presentation. Rule catalogue: Layer_Rules (`application.*`).
 */
final class Application_Layer_Test extends Architecture_Test_Case {
    #[DataProvider( 'rules' )]
    public function test_rule( Layer_Rule $rule ): void {
        $this->assert_rule_holds( $rule );
    }

    /**
     * @return iterable<string, array{0: Layer_Rule}>
     */
    public static function rules(): iterable {
        yield from Layer_Rules::provide( 'application.' );
    }
}
