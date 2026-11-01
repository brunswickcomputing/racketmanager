<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\View\Template_Scope;
use Racketmanager\Tests\Helpers\Typed_Scope_Vm;
use stdClass;

final class Template_Scope_Test extends TestCase {

    public function test_object_vm_is_returned_with_identity_preserved(): void {
        $vm = new Typed_Scope_Vm( 'Final' );

        $resolved = Template_Scope::resolve_vm( [ 'vm' => $vm, 'title' => 'ignored', 'extra' => 'x' ] );

        $this->assertSame( $vm, $resolved );
        $this->assertSame( 'Final', $resolved->title );
    }

    public function test_array_vm_is_flattened_and_vm_keys_win_over_sibling_keys(): void {
        $resolved = Template_Scope::resolve_vm(
            [
                'vm'    => [ 'score' => 10, 'club_presenter' => 'from_vm' ],
                'round' => 3,
                'club_presenter' => 'from_sibling',
            ]
        );

        $this->assertInstanceOf( stdClass::class, $resolved );
        $this->assertSame( 10, $resolved->score );
        $this->assertSame( 3, $resolved->round );
        $this->assertSame( 'from_vm', $resolved->club_presenter );
        $this->assertFalse( isset( $resolved->vm ) );
    }

    public function test_flat_vars_become_an_object(): void {
        $resolved = Template_Scope::resolve_vm( [ 'a' => 1, 'b' => 2 ] );

        $this->assertEquals( (object) [ 'a' => 1, 'b' => 2 ], $resolved );
    }

    public function test_non_object_non_array_vm_is_treated_as_a_plain_variable(): void {
        $resolved = Template_Scope::resolve_vm( [ 'vm' => 'oops', 'a' => 1 ] );

        $this->assertSame( 'oops', $resolved->vm );
        $this->assertSame( 1, $resolved->a );
    }

    public function test_empty_vars_give_an_empty_object(): void {
        $this->assertEquals( new stdClass(), Template_Scope::resolve_vm( [] ) );
    }
}
