<?php
/**
 * Business Hours Helper Class
 *
 * Handles business hours display and status calculations
 *
 * @package Spezialist_Directory
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Business_Hours Class
 */
class SD_Business_Hours {

    /**
     * Day names in German
     *
     * @var array
     */
    private static $day_names = array(
        'monday'    => 'Montag',
        'tuesday'   => 'Dienstag',
        'wednesday' => 'Mittwoch',
        'thursday'  => 'Donnerstag',
        'friday'    => 'Freitag',
        'saturday'  => 'Samstag',
        'sunday'    => 'Sonntag',
    );

    /**
     * PHP day number to day key mapping
     *
     * @var array
     */
    private static $day_map = array(
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        0 => 'sunday',
    );

    /**
     * Get business hours for a post
     *
     * @param int $post_id
     * @return array
     */
    public static function get_hours( $post_id ) {
        $hours = get_post_meta( $post_id, '_sd_business_hours', true );
        return is_array( $hours ) ? $hours : array();
    }

    /**
     * Check if a listing has business hours set
     *
     * @param int $post_id
     * @return bool
     */
    public static function has_hours( $post_id ) {
        $hours = self::get_hours( $post_id );
        if ( empty( $hours ) ) {
            return false;
        }

        // Check if at least one day is marked as open
        foreach ( $hours as $day_data ) {
            if ( ! empty( $day_data['open'] ) && ! empty( $day_data['from'] ) && ! empty( $day_data['to'] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if listing is currently open
     *
     * @param int $post_id
     * @return array [ 'is_open' => bool, 'status' => string, 'next_change' => string ]
     */
    public static function get_open_status( $post_id ) {
        $hours = self::get_hours( $post_id );

        if ( empty( $hours ) ) {
            return array(
                'is_open'     => null,
                'status'      => '',
                'next_change' => '',
            );
        }

        // Get current time in WordPress timezone
        $current_time = current_time( 'timestamp' );
        $current_day_num = (int) date( 'w', $current_time );
        $current_day = self::$day_map[ $current_day_num ];
        $current_time_str = date( 'H:i', $current_time );

        // Check if today has hours set
        if ( ! isset( $hours[ $current_day ] ) || empty( $hours[ $current_day ]['open'] ) ) {
            return array(
                'is_open'     => false,
                'status'      => __( 'Geschlossen', 'spezialist-directory' ),
                'next_change' => self::get_next_opening( $hours, $current_day_num ),
            );
        }

        $today = $hours[ $current_day ];
        $open_from = $today['from'];
        $open_to = $today['to'];
        $break_from = ! empty( $today['break_from'] ) ? $today['break_from'] : null;
        $break_to = ! empty( $today['break_to'] ) ? $today['break_to'] : null;

        // Check if currently open
        $is_open = false;
        $status = __( 'Geschlossen', 'spezialist-directory' );
        $next_change = '';

        if ( $current_time_str >= $open_from && $current_time_str < $open_to ) {
            // Within opening hours
            if ( $break_from && $break_to && $current_time_str >= $break_from && $current_time_str < $break_to ) {
                // Currently on break
                $is_open = false;
                $status = __( 'Mittagspause', 'spezialist-directory' );
                $next_change = sprintf( __( 'Öffnet wieder um %s', 'spezialist-directory' ), $break_to );
            } else {
                // Open
                $is_open = true;
                $status = __( 'Geöffnet', 'spezialist-directory' );

                // Determine next closing time
                if ( $break_from && $current_time_str < $break_from ) {
                    $next_change = sprintf( __( 'Schließt um %s', 'spezialist-directory' ), $break_from );
                } else {
                    $next_change = sprintf( __( 'Schließt um %s', 'spezialist-directory' ), $open_to );
                }
            }
        } else if ( $current_time_str < $open_from ) {
            // Before opening
            $is_open = false;
            $status = __( 'Geschlossen', 'spezialist-directory' );
            $next_change = sprintf( __( 'Öffnet um %s', 'spezialist-directory' ), $open_from );
        } else {
            // After closing
            $is_open = false;
            $status = __( 'Geschlossen', 'spezialist-directory' );
            $next_change = self::get_next_opening( $hours, $current_day_num );
        }

        return array(
            'is_open'     => $is_open,
            'status'      => $status,
            'next_change' => $next_change,
        );
    }

    /**
     * Get next opening time/day
     *
     * @param array $hours
     * @param int $current_day_num
     * @return string
     */
    private static function get_next_opening( $hours, $current_day_num ) {
        // Check next 7 days
        for ( $i = 1; $i <= 7; $i++ ) {
            $check_day_num = ( $current_day_num + $i ) % 7;
            $check_day = self::$day_map[ $check_day_num ];

            if ( isset( $hours[ $check_day ] ) && ! empty( $hours[ $check_day ]['open'] ) && ! empty( $hours[ $check_day ]['from'] ) ) {
                $day_name = self::$day_names[ $check_day ];
                $open_time = $hours[ $check_day ]['from'];

                if ( $i === 1 ) {
                    return sprintf( __( 'Öffnet morgen um %s', 'spezialist-directory' ), $open_time );
                } else {
                    return sprintf( __( 'Öffnet %s um %s', 'spezialist-directory' ), $day_name, $open_time );
                }
            }
        }

        return '';
    }

    /**
     * Render business hours table HTML
     *
     * @param int $post_id
     * @return string
     */
    public static function render_hours_table( $post_id ) {
        $hours = self::get_hours( $post_id );

        if ( empty( $hours ) ) {
            return '';
        }

        $has_any_hours = false;
        foreach ( $hours as $day_data ) {
            if ( ! empty( $day_data['open'] ) ) {
                $has_any_hours = true;
                break;
            }
        }

        if ( ! $has_any_hours ) {
            return '';
        }

        $current_day_num = (int) date( 'w', current_time( 'timestamp' ) );
        $current_day = self::$day_map[ $current_day_num ];

        ob_start();
        ?>
        <div class="sd-business-hours">
            <table class="sd-hours-table">
                <tbody>
                    <?php foreach ( self::$day_names as $day_key => $day_name ) :
                        $day_data = isset( $hours[ $day_key ] ) ? $hours[ $day_key ] : array();
                        $is_open = ! empty( $day_data['open'] );
                        $is_today = ( $day_key === $current_day );
                    ?>
                        <tr class="<?php echo $is_today ? 'sd-hours-today' : ''; ?>">
                            <td class="sd-hours-day">
                                <?php echo esc_html( $day_name ); ?>
                                <?php if ( $is_today ) : ?>
                                    <span class="sd-today-marker"><?php _e( '(Heute)', 'spezialist-directory' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="sd-hours-time">
                                <?php if ( $is_open && ! empty( $day_data['from'] ) && ! empty( $day_data['to'] ) ) : ?>
                                    <?php echo esc_html( $day_data['from'] ); ?> - <?php echo esc_html( $day_data['to'] ); ?>
                                    <?php if ( ! empty( $day_data['break_from'] ) && ! empty( $day_data['break_to'] ) ) : ?>
                                        <span class="sd-hours-break">
                                            (<?php printf( __( 'Pause: %s - %s', 'spezialist-directory' ), esc_html( $day_data['break_from'] ), esc_html( $day_data['break_to'] ) ); ?>)
                                        </span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="sd-hours-closed"><?php _e( 'Geschlossen', 'spezialist-directory' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render open/closed badge
     *
     * @param int $post_id
     * @param bool $show_next_change Whether to show next change time
     * @return string
     */
    public static function render_status_badge( $post_id, $show_next_change = true ) {
        if ( ! self::has_hours( $post_id ) ) {
            return '';
        }

        $status = self::get_open_status( $post_id );

        if ( $status['is_open'] === null ) {
            return '';
        }

        $badge_class = $status['is_open'] ? 'sd-status-open' : 'sd-status-closed';

        ob_start();
        ?>
        <div class="sd-open-status <?php echo esc_attr( $badge_class ); ?>">
            <span class="sd-status-indicator"></span>
            <span class="sd-status-text"><?php echo esc_html( $status['status'] ); ?></span>
            <?php if ( $show_next_change && ! empty( $status['next_change'] ) ) : ?>
                <span class="sd-status-next"><?php echo esc_html( $status['next_change'] ); ?></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
