<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Domain\DTO\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Exceptions\Fixture_Validation_Exception;

class Fixture_Status_Update_Request_Test extends TestCase {
    public function test_validate_throws_exception_on_missing_fixture_id(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 0,
            match_status: 'none',
            modal: 'test-modal'
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_throws_exception_on_missing_modal_when_not_rubber(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'none',
            modal: null,
            rubber_number: null
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_throws_exception_on_missing_match_status(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: null,
            modal: 'test-modal'
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_throws_exception_on_invalid_walkover_status(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'walkover', // Missing player_ref
            modal: 'test-modal'
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_throws_exception_on_invalid_status_value(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'something_invalid',
            modal: 'test-modal'
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_passes_on_valid_data(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'walkover_player1',
            modal: 'test-modal'
        );

        $request->validate();
        $this->assertTrue(true); // If no exception, it passes
    }

    public function test_validate_passes_on_valid_none_status(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'none',
            modal: 'test-modal'
        );

        $request->validate();
        $this->assertTrue(true);
    }

    public function test_validate_throws_exception_on_invalid_status_for_fixture(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'invalid',
            modal: 'test-modal',
            rubber_number: null
        );

        $this->expectException(Fixture_Validation_Exception::class);
        $request->validate();
    }

    public function test_validate_passes_on_invalid_status_for_rubber(): void {
        $request = new Fixture_Status_Update_Request(
            fixture_id: 1,
            match_status: 'invalid',
            modal: null,
            rubber_number: 1
        );

        $request->validate();
        $this->assertTrue(true);
    }
}
