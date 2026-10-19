<?php
/**
 * Build match entry for team
 *
 * @package Racketmanager/Templates
 */

namespace RacketManager;

use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;

/** @var Team_Match_Row_View_Model|object|null $match */
/** @var Team_Match_Row_View_Model|null $vm */
if ( isset( $vm ) && $vm instanceof Team_Match_Row_View_Model ) {
    $row_vm = $vm;
} elseif ( isset( $match ) && $match instanceof Team_Match_Row_View_Model ) {
    $row_vm = $match;
} else {
    throw new \UnexpectedValueException( 'Expected Team_Match_Row_View_Model, got ' . ( isset( $vm ) ? get_debug_type( $vm ) : ( isset( $match ) ? get_debug_type( $match ) : 'null' ) ) );
}
?>
<div class="match match--team-match <?php echo $row_vm->is_selected ? 'is-selected' : ''; ?>">
    <?php
    if ( $row_vm->show_header && '' !== $row_vm->league_title ) {
        ?>
        <div class="match__header match__header--up">
            <div class="match__header-title">
                <div class="match__header-title-main">
                    <span><?php echo esc_html( $row_vm->league_title ); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    if ( ! empty( $row_vm->match_link ) ) {
        ?>
        <a class="team-match__wrapper" href="<?php echo esc_url( $row_vm->match_link ); ?>">
        <?php
    } else {
        ?>
        <div class="team-match__wrapper">
        <?php
    }
    ?>
    <div class="match__header">
        <span class="match__header-title">
            <?php
            if ( ! empty( $row_vm->final_name ) ) {
                ?>
                <span><?php echo esc_html( $row_vm->final_name ); ?></span>
                <?php
            } elseif ( ! empty( $row_vm->match_day_text ) ) {
                ?>
                <span><?php echo esc_html( $row_vm->match_day_text ); ?></span>
                <?php
            }
            if ( ! empty( $row_vm->leg_text ) ) {
                ?>
                <span>&nbsp;&#8226&nbsp;<?php echo esc_html( $row_vm->leg_text ); ?></span>
                <?php
            }
            if ( $row_vm->show_date && ! empty( $row_vm->formatted_date ) ) {
                ?>
                &nbsp;&#8226;&nbsp;
                <span>
                    <time datetime="<?php echo esc_attr( (string) $row_vm->date ); ?>"><?php echo esc_html( $row_vm->formatted_date ); ?></time>
                </span>
                <?php
            }
            ?>
        </span>
        <?php
        if ( ! empty( $row_vm->status_message ) ) {
            ?>
            <span class="match__message match-warning"><?php echo esc_html( $row_vm->status_message ); ?></span>
            <?php
        }
        if ( $row_vm->is_highlighted && ! empty( $row_vm->status_text ) ) {
            ?>
            <span class="match__status <?php echo esc_attr( (string) $row_vm->status_class ); ?>"><?php echo esc_attr( $row_vm->status_text ); ?></span>
            <?php
        }
        ?>
    </div>
    <div class="match__body">
        <div class="team-match">
            <div class="team-match__name is-team-1">
                <span class="nav--link">
                    <span class="nav-link__value">
                        <?php
                        if ( $row_vm->home_is_withdrawn ) {
                            ?>
                            <s aria-label="<?php echo esc_attr( (string) $row_vm->home_withdrawn_title ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( (string) $row_vm->home_withdrawn_title ); ?>">
                            <?php
                        }
                        echo esc_html( $row_vm->home_name );
                        if ( $row_vm->home_is_withdrawn ) {
                            ?>
                            </s>
                            <?php
                        }
                        ?>
                    </span>
                </span>
            </div>
            <div class="score <?php echo esc_attr( (string) $row_vm->score_class ); ?>">
                <?php
                if ( $row_vm->is_pending ) {
                    ?>
                    <time datetime="<?php echo esc_attr( (string) $row_vm->date ); ?>"><?php echo esc_html( (string) $row_vm->score_text ); ?></time>
                    <?php
                } else {
                    ?>
                    <span class="is-team-1"><?php echo esc_html( (string) $row_vm->home_points ); ?></span>
                    <span class="score-separator">-</span>
                    <span class="is-team-2"><?php echo esc_html( (string) $row_vm->away_points ); ?></span>
                    <?php
                }
                ?>
            </div>
            <div class="team-match__name is-team-2">
                <span class="nav--link">
                    <span class="nav-link__value">
                        <?php
                        if ( $row_vm->away_is_withdrawn ) {
                            ?>
                            <s aria-label="<?php echo esc_attr( (string) $row_vm->away_withdrawn_title ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( (string) $row_vm->away_withdrawn_title ); ?>">
                            <?php
                        }
                        echo esc_html( $row_vm->away_name );
                        if ( $row_vm->away_is_withdrawn ) {
                            ?>
                            </s>
                            <?php
                        }
                        ?>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <?php
    if ( ! empty( $row_vm->match_link ) ) {
        ?>
        </a>
        <?php
    } else {
        ?>
        </div>
        <?php
    }
    if ( $row_vm->user_can_update && ! empty( $row_vm->update_link ) ) {
        ?>
        <div class="match__button">
            <a href="<?php echo esc_url( $row_vm->update_link ); ?>" class="btn match__btn">
                <svg width="16" height="16" class="icon ">
                    <use xlink:href="<?php echo esc_url( $row_vm->pencil_icon_url ); ?>"></use>
                </svg>
            </a>
        </div>
        <?php
    }
    ?>
</div>
