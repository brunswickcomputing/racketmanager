<?php
/**
 * Template for match score
 *
 * @package Racketmanager/Templates/Match
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

/** @var Fixture_Details_DTO $dto */
/** @var Fixture $match */
/** @var string $score_class */
/** @var string $link_title */
/** @var float  $score_team_1 */
/** @var float  $score_team_2 */
/** @var bool   $home_away */
$dto          = $dto ?? $vars['dto'] ?? $vm->dto ?? null;
if ( ! $dto ) {
    return;
}
$match        = $match ?? $vars['match'] ?? $vm->match ?? $dto->fixture ?? null;
$score_class  = $score_class ?? $vars['score_class'] ?? $vm->score_class ?? '';
$link_title   = $link_title ?? $vars['link_title'] ?? $vm->link_title ?? '';
$score_team_1 = $score_team_1 ?? $vars['score_team_1'] ?? $vm->score_team_1 ?? 0;
$score_team_2 = $score_team_2 ?? $vars['score_team_2'] ?? $vm->score_team_2 ?? 0;
$home_away    = $home_away ?? $vars['home_away'] ?? $vm->home_away ?? false;
?>
<a href="<?php echo esc_html( $dto->link ); ?>">
    <span class="score <?php echo esc_attr( $score_class ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $link_title ); ?>">
        <span class="is-team-1"><?php echo esc_html( sprintf( '%g', $score_team_1 ) ); ?></span>
        <?php
        if ( $home_away ) {
            ?>
            <span class="score-separator">-</span>
            <span class="is-team-2"><?php echo esc_html( sprintf( '%g', $score_team_2 ) ); ?></span>
            <?php
        }
        ?>
    </span>
</a>
