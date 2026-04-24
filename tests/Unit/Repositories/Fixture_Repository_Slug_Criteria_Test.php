<?php

namespace Racketmanager\Tests\Unit\Repositories {

    use PHPUnit\Framework\TestCase;
    use Racketmanager\Repositories\Fixture_Repository;
    use Racketmanager\Domain\Competition\League;
    use Racketmanager\Domain\Fixture\Fixture;
    use wpdb;
    use stdClass;

    class Fixture_Repository_Slug_Criteria_Test extends TestCase {
        private $wpdb;
        private $original_wpdb;
        private Fixture_Repository $repository;

        protected function setUp(): void {
            parent::setUp();
            $this->wpdb = $this->createStub(wpdb::class);
            $this->wpdb->prefix = 'wp_';

            global $wpdb;
            $this->original_wpdb = $wpdb;
            $wpdb = $this->wpdb;

            $this->repository = new Fixture_Repository();
        }

        protected function tearDown(): void {
            global $wpdb;
            $wpdb = $this->original_wpdb;
            parent::tearDown();
        }

        public function test_find_one_by_slug_criteria_returns_null_if_no_league_id() {
            $result = $this->repository->find_one_by_slug_criteria(['season' => '2023-24']);
            $this->assertNull($result);
        }

        public function test_find_one_by_slug_criteria_returns_null_if_league_not_found() {
            $criteria = [
                'league_id' => 1,
                'season' => '2023-24'
            ];

            global $mock_league;
            $mock_league = null;

            $result = $this->repository->find_one_by_slug_criteria($criteria);
            $this->assertNull($result);
        }

        /*
        #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
        #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
        public function test_find_one_by_slug_criteria_success() {
            // This test is currently disabled because \Racketmanager\get_league is called with an absolute path
            // and cannot be easily mocked in this environment.
        }
        */
    }
}

namespace Racketmanager {
    // Mocking the function if it doesn't exist (it will in real run, but for unit test...)
    if (!function_exists('Racketmanager\get_league')) {
        function get_league($id) {
            global $mock_league;
            return $mock_league;
        }
    }
}
