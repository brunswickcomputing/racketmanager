<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Presentation\View_Models\Tournament_Date_View_Model;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Tournament_Email_Template_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_renders_tournament_entry_open_template_with_tournament_dates(): void {
        $tournament = new Tournament( (object) [
            'id'   => 1,
            'name' => 'Spring Open',
        ] );

        $tournament_dates = new Tournament_Date_View_Model(
            date_display: '2026-08-30',
            date_closing_display: '2026-08-01',
            date_withdrawal_display: '2026-08-05',
            date_open_display: '2026-07-01',
            date_start_display: '2026-08-20'
        );

        $addressee      = 'Jane Doe';
        $type           = 'open';
        $days_remaining = null;
        $from_email     = 'admin@test.local';
        $action_url     = 'https://example.com/entry';
        $organisation   = 'Test Club';
        $account_link   = '<a href="https://example.com/account">link</a>';

        $salutation = __DIR__ . '/../../../templates/email/components/salutation.php';
        $paragraph  = __DIR__ . '/../../../templates/email/components/paragraph.php';
        $contact    = __DIR__ . '/../../../templates/email/components/contact.php';
        $closing    = __DIR__ . '/../../../templates/email/components/closing.php';

        ob_start();
        require __DIR__ . '/../../../templates/email/tournament-entry-open.php';
        $html = ob_get_clean();

        $this->assertStringContainsString( 'Spring Open', $html );
        $this->assertStringContainsString( '2026-08-01', $html );
        $this->assertStringContainsString( '2026-08-20', $html );
        $this->assertStringContainsString( '2026-08-30', $html );
    }

    public function test_renders_tournament_withdrawal_template_with_tournament_dates(): void {
        $tournament = new Tournament( (object) [
            'id'   => 1,
            'name' => 'Autumn Open',
        ] );
        $tournament->is_open = true;

        $player = Player::from_array( [
            'ID'           => 10,
            'display_name' => 'John Smith',
            'first_name'   => 'John',
            'last_name'    => 'Smith',
        ] );

        $tournament_dates = new Tournament_Date_View_Model(
            date_display: '2026-10-30',
            date_closing_display: '2026-10-01',
            date_withdrawal_display: '2026-10-05',
            date_open_display: '2026-09-01',
            date_start_display: '2026-10-20'
        );

        $tournament_name = 'Autumn Open';
        $tournament_link = '<a href="https://example.com/autumn">Autumn Open</a>';
        $contact_email   = 'admin@test.local';
        $action_url      = 'https://example.com/entry';

        $salutation = __DIR__ . '/../../../templates/email/components/salutation.php';
        $paragraph  = __DIR__ . '/../../../templates/email/components/paragraph.php';
        $contact    = __DIR__ . '/../../../templates/email/components/contact.php';
        $closing    = __DIR__ . '/../../../templates/email/components/closing.php';
        $title      = __DIR__ . '/../../../templates/email/components/title.php';

        ob_start();
        require __DIR__ . '/../../../templates/email/tournament-withdrawal.php';
        $html = ob_get_clean();

        $this->assertStringContainsString( 'Withdrawal confirmation', $html );
        $this->assertStringContainsString( 'Autumn Open', $html );
        $this->assertStringContainsString( '2026-10-01', $html );
    }
}
