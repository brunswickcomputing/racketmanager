<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class Domain_Architecture_Purity_Test extends TestCase {

	/**
	 * Exact counts of known legacy exceptions in domain files pending extraction
	 * to Presenters / Services. Any deviation (addition or removal) will fail the test.
	 *
	 * @var array<string, array<string, int>>
	 */
	private const array KNOWN_EXCEPTIONS = array();

	/**
	 * Get all PHP files in src/php/Domain/.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function domain_php_files_provider(): array {
		$domain_dir = dirname( __DIR__, 3 ) . '/src/php/Domain';
		if ( ! is_dir( $domain_dir ) ) {
			return array();
		}

		$directory = new RecursiveDirectoryIterator( $domain_dir );
		$iterator  = new RecursiveIteratorIterator( $directory );
		$php_files = new RegexIterator( $iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH );

		$files = array();
		foreach ( $php_files as $file_match ) {
			$file_path = $file_match[0];
			$relative  = str_replace( $domain_dir . '/', '', $file_path );
			$files[ $relative ] = array( $file_path, $relative );
		}

		return $files;
	}

	/**
	 * Scan PHP code for prohibited architectural patterns and return violation counts.
	 *
	 * @param string $code
	 * @return array<string, int>
	 */
	public static function scan_code( string $code, bool $check_wordpress_helpers = false ): array {
		$tokens     = token_get_all( $code );
		$count      = count( $tokens );
		$violations = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( ! is_array( $token ) ) {
				continue;
			}

			list( $id, $text ) = $token;

			if ( T_GLOBAL === $id ) {
				$violations['global'] = ( $violations['global'] ?? 0 ) + 1;
			}

			if ( T_VARIABLE === $id && in_array( $text, array( '$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_SESSION', '$GLOBALS' ), true ) ) {
				$violations[ $text ] = ( $violations[ $text ] ?? 0 ) + 1;
			}

			if ( T_ECHO === $id ) {
				$violations['echo'] = ( $violations['echo'] ?? 0 ) + 1;
			}

			if ( T_PRINT === $id ) {
				$violations['print'] = ( $violations['print'] ?? 0 ) + 1;
			}

			if ( T_OBJECT_OPERATOR === $id || ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) && T_NULLSAFE_OBJECT_OPERATOR === $id ) ) {
				$next = $i + 1;
				while ( $next < $count && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$next++;
				}
				if ( $next < $count && is_array( $tokens[ $next ] ) && 'container' === strtolower( $tokens[ $next ][1] ) ) {
					$violations['->container'] = ( $violations['->container'] ?? 0 ) + 1;
				}
			}

			if ( T_NEW === $id ) {
				$next = $i + 1;
				while ( $next < $count && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$next++;
				}
				if ( $next < $count && is_array( $tokens[ $next ] ) && preg_match( '/Repository$/i', $tokens[ $next ][1] ) ) {
					$violations['new_repository'] = ( $violations['new_repository'] ?? 0 ) + 1;
				}
			}

			$is_name_token = T_STRING === $id
				|| ( defined( 'T_NAME_QUALIFIED' ) && T_NAME_QUALIFIED === $id )
				|| ( defined( 'T_NAME_FULLY_QUALIFIED' ) && T_NAME_FULLY_QUALIFIED === $id );

			if ( $is_name_token ) {
				$last_segment = strtolower( substr( strrchr( '\\' . $text, '\\' ), 1 ) );

				// Check previous token (skipping whitespace and comments)
				$prev = $i - 1;
				while ( $prev >= 0 && is_array( $tokens[ $prev ] ) && in_array( $tokens[ $prev ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$prev--;
				}
				$prev_token = $prev >= 0 ? $tokens[ $prev ] : null;
				$prev_id    = is_array( $prev_token ) ? $prev_token[0] : $prev_token;

				// Check next token (skipping whitespace and comments)
				$next = $i + 1;
				while ( $next < $count && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$next++;
				}
				$next_token = $next < $count ? $tokens[ $next ] : null;
				$next_id    = is_array( $next_token ) ? $next_token[0] : $next_token;

				$is_call = ( '(' === $next_id || ord( '(' ) === $next_id )
					&& ! in_array( $prev_id, array(
						T_OBJECT_OPERATOR,
						defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) ? T_NULLSAFE_OBJECT_OPERATOR : -1,
						T_DOUBLE_COLON,
						T_FUNCTION,
					), true );

				$is_use_func = false;
				if ( T_FUNCTION === $prev_id ) {
					$prev2 = $prev - 1;
					while ( $prev2 >= 0 && is_array( $tokens[ $prev2 ] ) && in_array( $tokens[ $prev2 ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
						$prev2--;
					}
					$prev2_token = $prev2 >= 0 ? $tokens[ $prev2 ] : null;
					$prev2_id    = is_array( $prev2_token ) ? $prev2_token[0] : $prev2_token;
					if ( T_USE === $prev2_id ) {
						$is_use_func = true;
					}
				}

				if ( $is_call || $is_use_func ) {
					if ( preg_match( '/^wp_cache_/i', $last_segment ) ) {
						$violations[ $last_segment ] = ( $violations[ $last_segment ] ?? 0 ) + 1;
					} elseif ( ( $check_wordpress_helpers && in_array( $last_segment, array( '__', 'maybe_unserialize', 'wp_json_encode' ), true ) ) || in_array( $last_segment, array(
						'is_admin',
						'do_shortcode',
						'paginate_links',
						'add_query_arg',
						'get_query_var',
						'show_score',
						'show_standings',
						'get_user',
						'get_userdata',
						'get_user_meta',
						'get_user_by',
						'apply_filters',
						'do_action',
					), true ) ) {
						$violations[ $last_segment ] = ( $violations[ $last_segment ] ?? 0 ) + 1;
					}
				}
			}
		}

		ksort( $violations );
		return $violations;
	}

	/**
	 * Count references from Domain code to outer architectural layers.
	 *
	 * @param string $code
	 * @return int
	 */
	public static function count_outer_layer_references( string $code ): int {
		$tokens = token_get_all( $code );
		$count  = count( $tokens );
		$found  = 0;

		$layers = array( 'Repositories', 'Services', 'Application', 'Infrastructure' );
		$layer_pattern = '(?:' . implode( '|', $layers ) . ')';

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( ! is_array( $token ) ) {
				continue;
			}

			list( $id, $text ) = $token;

			// 1. Inline FQ names
			if ( ( defined( 'T_NAME_QUALIFIED' ) && T_NAME_QUALIFIED === $id ) || ( defined( 'T_NAME_FULLY_QUALIFIED' ) && T_NAME_FULLY_QUALIFIED === $id ) ) {
				$name = ltrim( $text, '\\' );
				if ( preg_match( '/^Racketmanager\\\\' . $layer_pattern . '(?:\\\\|$)/i', $name ) ) {
					++$found;
				}
				continue;
			}

			// 2. Use statements
			if ( T_USE === $id ) {
				$next = self::next_significant_token_index( $tokens, $i + 1 );
				if ( null !== $next && '(' === $tokens[ $next ] ) {
					// Closure use list: not an import; scan the body normally.
					continue;
				}
				$import_tokens = array();
				for ( $j = $i + 1; $j < $count; $j++ ) {
					if ( ';' === $tokens[ $j ] ) {
						break;
					}
					$import_tokens[] = $tokens[ $j ];
				}
				$i = $j; // advance main loop

				// Find prefix (everything before { )
				$prefix = '';
				$brace_index = -1;
				$skip_alias = false;

				foreach ( $import_tokens as $index => $t ) {
					if ( is_array( $t ) ) {
						if ( T_AS === $t[0] ) {
							$skip_alias = true;
							continue;
						}
						if ( $skip_alias ) {
							continue;
						}
						if ( in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_FUNCTION, T_CONST ), true ) ) {
							continue;
						}
						$prefix .= $t[1];
					} else {
						if ( ',' === $t && $skip_alias ) {
							$skip_alias = false;
							$prefix .= $t;
							continue;
						}
						if ( $skip_alias ) {
							continue;
						}
						if ( '{' === $t ) {
							$brace_index = $index;
							break;
						}
						$prefix .= $t;
					}
				}

				if ( -1 !== $brace_index ) {
					// Grouped import
					$member_string = '';
					$skip_alias = false;
					for ( $m = $brace_index + 1; $m < count( $import_tokens ); $m++ ) {
						$t = $import_tokens[ $m ];
						if ( '}' === $t ) {
							break;
						}
						if ( is_array( $t ) ) {
							if ( T_AS === $t[0] ) {
								$skip_alias = true;
								continue;
							}
							if ( $skip_alias ) {
								continue;
							}
							if ( in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_FUNCTION, T_CONST ), true ) ) {
								continue;
							}
							$member_string .= $t[1];
						} else {
							if ( ',' === $t && $skip_alias ) {
								$skip_alias = false;
							}
							if ( $skip_alias ) {
								continue;
							}
							$member_string .= $t;
						}
					}
					$member_list = explode( ',', $member_string );
					foreach ( $member_list as $member ) {
						if ( '' === trim( $member ) ) {
							continue;
						}
						$full_name = ltrim( trim( $prefix ) . trim( $member ), '\\' );
						if ( preg_match( '/^Racketmanager\\\\' . $layer_pattern . '(?:\\\\|$)/i', $full_name ) ) {
							++$found;
						}
					}
				} else {
					// Simple import
					foreach ( explode( ',', $prefix ) as $import ) {
						$full_name = ltrim( trim( $import ), '\\' );
						if ( preg_match( '/^Racketmanager\\\\' . $layer_pattern . '(?:\\\\|$)/i', $full_name ) ) {
							++$found;
						}
					}
				}
			}
		}

		return $found;
	}

	/**
	 * Find the next token that is not whitespace or a comment.
	 *
	 * @param array<int, array{int, string, int}|string> $tokens
	 * @param int                                        $index
	 * @return int|null
	 */
	private static function next_significant_token_index( array $tokens, int $index ): ?int {
		$count = count( $tokens );
		while ( $index < $count ) {
			$token = $tokens[ $index ];
			if ( ! is_array( $token ) || ! in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				return $index;
			}
			++$index;
		}

		return null;
	}

	#[DataProvider( 'domain_php_files_provider' )]
	public function test_domain_files_satisfy_architecture_purity( string $file_path, string $relative_path ): void {
		$content = file_get_contents( $file_path );
		$this->assertNotFalse( $content, "Failed to read $file_path" );

		$core_entities = array( 'Competition/Event.php', 'Competition/Competition.php', 'Fixture/Fixture.php' );
		$violations = self::scan_code( $content, in_array( $relative_path, $core_entities, true ) );

		if ( isset( self::KNOWN_EXCEPTIONS[ $relative_path ] ) ) {
			$expected = self::KNOWN_EXCEPTIONS[ $relative_path ];
			ksort( $expected );
			$this->assertSame(
				$expected,
				$violations,
				"File src/php/Domain/$relative_path exception count mismatch. Update KNOWN_EXCEPTIONS to reflect improvements."
			);
			return;
		}

		$this->assertEmpty(
			$violations,
			"File src/php/Domain/$relative_path contains prohibited architectural calls:\n" . print_r( $violations, true )
		);
	}

	public function test_scanner_detects_prohibited_patterns_including_fully_qualified_calls(): void {
		$this->assertSame( array( 'wp_cache_set' => 1 ), self::scan_code( '<?php \wp_cache_set(1);' ) );
		$this->assertSame( array( 'apply_filters' => 1 ), self::scan_code( '<?php \apply_filters("hook", 1);' ) );
		$this->assertSame( array( 'get_userdata' => 1 ), self::scan_code( '<?php \get_userdata(1);' ) );
		$this->assertSame( array( 'show_score' => 1 ), self::scan_code( '<?php \Racketmanager\show_score($a, $b);' ) );
		$this->assertSame( array( 'wp_cache_delete' => 1 ), self::scan_code( '<?php use function \wp_cache_delete;' ) );
		$this->assertSame( array( 'show_standings' => 1 ), self::scan_code( '<?php use function Racketmanager\show_standings;' ) );
		$this->assertSame( array( '__' => 1, 'maybe_unserialize' => 1, 'wp_json_encode' => 1 ), self::scan_code( '<?php \__("x"); maybe_unserialize($x); wp_json_encode($x);', true ) );
	}

	public function test_scanner_ignores_safe_constructs_such_as_methods_and_strings(): void {
		$this->assertSame( array(), self::scan_code( '<?php $obj->get_user();' ) );
		$this->assertSame( array(), self::scan_code( '<?php $obj?->get_user();' ) );
		$this->assertSame( array(), self::scan_code( '<?php SomeClass::get_user();' ) );
		$this->assertSame( array(), self::scan_code( '<?php class Foo { public function get_user() {} }' ) );
		$this->assertSame( array(), self::scan_code( '<?php $url = "https://example.com/foo//bar";' ) );
		$this->assertSame( array(), self::scan_code( '<?php // \wp_cache_set(1);' ) );
		$this->assertSame( array(), self::scan_code( "<?php \$str = '\$_POST is safe'; // comment" ) );
	}

	public function test_outer_layer_scanner_detects_inline_and_indented_imports(): void {
		$code = <<<'PHP'
<?php
namespace Example {
	use Racketmanager\Application\Use_Case;
	function build( \Racketmanager\Repositories\Interfaces\Repository_Interface $repository ): \Racketmanager\Services\Example_Service {
		return new \Racketmanager\Infrastructure\Adapter();
	}
}
PHP;

		$this->assertSame( 4, self::count_outer_layer_references( $code ) );
	}

	public function test_outer_layer_scanner_detects_grouped_class_function_and_constant_imports(): void {
		$code = <<<'PHP'
<?php
use Racketmanager\{Repositories\One, Services\Two};
use function \Racketmanager\{Application\handle};
use const Racketmanager\{Infrastructure\FEATURE_FLAG};
PHP;

		$this->assertSame( 4, self::count_outer_layer_references( $code ) );
	}

	public function test_outer_layer_scanner_ignores_comments_and_strings(): void {
		$code = <<<'PHP'
<?php
// use Racketmanager\Repositories\Commented;
$value = 'new \\Racketmanager\\Services\\String_Value()';
/* \Racketmanager\Infrastructure\Commented */
PHP;

		$this->assertSame( 0, self::count_outer_layer_references( $code ) );
	}

	/**
	 * Legacy Domain files still importing outer layers, with exact import counts.
	 * The list may only shrink.
	 */
	private const array OUTER_LAYER_IMPORT_EXCEPTIONS = array();

	public function test_domain_does_not_import_outer_layers(): void {
		$actual = array();
		foreach ( self::domain_php_files_provider() as $relative => $info ) {
			$code  = (string) file_get_contents( $info[0] );
			$found = self::count_outer_layer_references( $code );
			if ( $found ) {
				$actual[ $relative ] = $found;
			}
		}
		ksort( $actual );
		$expected = self::OUTER_LAYER_IMPORT_EXCEPTIONS;
		ksort( $expected );
		$this->assertSame( $expected, $actual, 'Domain files must not import Repositories/Services/Application/Infrastructure.' );
	}

	#[DataProvider('provide_scanner_samples')]
	public function test_count_outer_layer_references( string $code, int $expected ): void {
		$this->assertSame( $expected, self::count_outer_layer_references( $code ) );
	}

	public static function provide_scanner_samples(): array {
		return [
			'simple import' => ['<?php use Racketmanager\Repositories\A;', 1],
			'aliased layer import' => ['<?php use Racketmanager\Services as S;', 1],
			'grouped import' => ['<?php use Racketmanager\Repositories\{A, B};', 2],
			'aliased grouped layer import' => ['<?php use Racketmanager\{Services as S};', 1],
			'grouped import with prefix' => ['<?php use Racketmanager\{Repositories\A, Repositories\B};', 2],
			'case insensitive' => ['<?php use RACKETMANAGER\REPOSITORIES\{A, B};', 2],
			'inline qualified' => ['<?php new \Racketmanager\Services\A();', 1],
			'indented import' => ['<?php namespace { use Racketmanager\Repositories\A; }', 1],
			'function import' => ['<?php use function Racketmanager\Services\f;', 1],
			'interfaces group' => ['<?php use Racketmanager\Repositories\Interfaces\{A_Interface, B_Interface};', 2],
			'lowercase inline' => ['<?php new \racketmanager\services\X();', 1],
			'lowercase import' => ['<?php use racketmanager\services\X;', 1],
			'closure body' => ['<?php $f = function() use ($a) { return new \Racketmanager\Services\X(); };', 1],
			'closure without outer layer' => ['<?php $f = function() use ($a) { return 1; };', 0],
			'group function const members' => ['<?php use Racketmanager\{function Services\foo, const Application\BAR};', 2],
			'comma separated mixed' => ['<?php use Racketmanager\Domain\X, Racketmanager\Services\Y;', 1],
			'comma separated both' => ['<?php use Racketmanager\Services\A, Racketmanager\Services\B;', 2],
			'trailing comma group' => ['<?php use Racketmanager\Services\{A, B,};', 2],
			'domain services namespace' => ['<?php use Racketmanager\Domain\Services\X;', 0],
			'ignore comments' => ['<?php // use Racketmanager\Repositories\A;', 0],
			'ignore strings' => ['<?php $s = "use Racketmanager\Repositories\A;";', 0],
		];
	}
}
