<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Validator\Player_Details_Validator;
use ReflectionMethod;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Player_Details_Validator_Test extends TestCase {
    private Player_Details_Validator $validator;
    private array $post_backup;

    protected function setUp(): void {
        parent::setUp();
        $this->post_backup = $_POST;
        $_POST             = [];
        $this->validator   = new Player_Details_Validator( $this->createStub( Player_Repository_Interface::class ) );
    }

    protected function tearDown(): void {
        $_POST = $this->post_backup;
        parent::tearDown();
    }

    private function valid_input( array $overrides = [] ): array {
        return array_merge(
            [
                'firstname' => 'Jane',
                'surname'   => 'Doe',
                'gender'    => 'F',
                'email'     => 'jane.doe@example.com',
                'btm'       => '12345678',
            ],
            $overrides
        );
    }

    public function test_repository_is_mandatory(): void {
        $parameter = ( new ReflectionMethod( Player_Details_Validator::class, '__construct' ) )->getParameters()[0];

        $this->assertFalse( $parameter->allowsNull() );
        $this->assertFalse( $parameter->isOptional() );
    }

    public function test_build_player_and_validator_keeps_missing_contactno_null(): void {
        [ $player ] = $this->validator->build_player_and_validator( $this->valid_input() );
        $this->assertNull( $player->contactno );

        [ $player ] = $this->validator->build_player_and_validator( $this->valid_input( [ 'contactno' => '' ] ) );
        $this->assertNull( $player->contactno );
    }

    public function test_validate_player_keeps_contactno_value_and_null_default(): void {
        $with = $this->validator->validate_player( $this->valid_input( [ 'contactno' => '0123456789' ] ) );
        $this->assertInstanceOf( stdClass::class, $with );
        $this->assertSame( '0123456789', $with->contactno );

        $without = $this->validator->validate_player( $this->valid_input() );
        $this->assertInstanceOf( stdClass::class, $without );
        $this->assertNull( $without->contactno );
    }

    public function test_validate_player_details_maps_contactno_to_empty_string(): void {
        $result = $this->validator->validate_player_details( $this->valid_input() );

        $this->assertTrue( $result[0] );
        $this->assertSame( '', $result[1]->contactno );
    }

    public function test_null_input_reads_post_and_explicit_empty_array_does_not(): void {
        $_POST = $this->valid_input();

        $from_post = $this->validator->validate_player( null );
        $this->assertInstanceOf( stdClass::class, $from_post );
        $this->assertSame( 'Jane', $from_post->firstname );

        $explicit = $this->validator->validate_player( [] );
        $this->assertTrue( is_wp_error( $explicit ) );
    }
}
