<?php
/**
 * Template: Rating Tab Content
 *
 * Displays on the listing detail page in the Bewertungen tab
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get rating data
$average = SR_Ratings::get_average( $post_id );
$count = SR_Ratings::get_count( $post_id );
$ratings = SR_Ratings::get_ratings( $post_id );

// Check if user can rate
$user_id = get_current_user_id();
$can_rate_check = SR_Ratings::user_can_rate( $post_id, $user_id );
$can_rate = $can_rate_check['can_rate'];
$rate_reason = $can_rate_check['reason'];
$user_rating = isset( $can_rate_check['rating'] ) ? $can_rate_check['rating'] : null;
?>

<div class="sd-tab-content" data-tab="bewertungen">
    <div class="sd-detail-section sr-reviews-section">
        <h2><?php _e( 'Bewertungen', 'spezialist-ratings' ); ?></h2>

        <!-- Rating Summary (only show if there are ratings) -->
        <?php if ( $count > 0 ) : ?>
        <div class="sr-reviews-summary sr-has-ratings">
            <div class="sr-rating-large">
                <span class="sr-rating-number"><?php echo esc_html( $average ); ?></span>
                <div class="sr-rating-stars-large">
                    <?php echo SR_Ratings::render_stars( $average, 24 ); ?>
                </div>
                <span class="sr-rating-count-text">
                    <?php
                    printf(
                        _n( '%d Bewertung', '%d Bewertungen', $count, 'spezialist-ratings' ),
                        $count
                    );
                    ?>
                </span>
            </div>
        </div>
        <?php else : ?>
        <!-- Call to action banner when no ratings exist -->
        <div class="sr-cta-banner">
            <span class="sr-cta-text"><?php _e( 'Mach mit und füge die erste Bewertung für diesen Eintrag hinzu!', 'spezialist-ratings' ); ?></span>
        </div>
        <?php endif; ?>

        <!-- Rating Form / Messages -->
        <div class="sr-form-section">
            <?php if ( ! is_user_logged_in() ) : ?>
                <!-- Not logged in - show login prompt -->
                <div class="sr-login-prompt">
                    <div class="sr-prompt-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#D1D5DB"/>
                        </svg>
                    </div>
                    <h3><?php _e( 'Bewertung abgeben', 'spezialist-ratings' ); ?></h3>
                    <p><?php _e( 'Melde Dich an, um diesen Spezialisten zu bewerten.', 'spezialist-ratings' ); ?></p>
                    <a href="<?php echo esc_url( SR_Display::get_login_url( $post_id ) ); ?>" class="sd-button sd-button-primary">
                        <?php _e( 'Jetzt anmelden', 'spezialist-ratings' ); ?>
                    </a>
                </div>

            <?php elseif ( $rate_reason === 'is_owner' ) : ?>
                <!-- User is the owner - show message -->
                <div class="sr-owner-notice">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#6B7280"/>
                    </svg>
                    <p><?php _e( 'Du kannst Deinen eigenen Eintrag nicht bewerten.', 'spezialist-ratings' ); ?></p>
                </div>

            <?php elseif ( $rate_reason === 'already_rated' && $user_rating ) : ?>
                <!-- User already rated - show their rating -->
                <div class="sr-your-rating">
                    <h3><?php _e( 'Deine Bewertung', 'spezialist-ratings' ); ?></h3>
                    <div class="sr-your-rating-content">
                        <?php echo SR_Ratings::render_stars( $user_rating->rating, 20 ); ?>
                        <span class="sr-your-rating-value"><?php echo esc_html( $user_rating->rating ); ?>/5</span>
                        <?php if ( $user_rating->status === 'pending' ) : ?>
                            <span class="sr-status-badge sr-status-pending"><?php _e( 'Wartet auf Freigabe', 'spezialist-ratings' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $user_rating->comment ) : ?>
                        <div class="sr-your-comment">
                            <p><?php echo esc_html( $user_rating->comment ); ?></p>
                        </div>
                    <?php endif; ?>
                    <p class="sr-rating-date">
                        <?php
                        printf(
                            __( 'Abgegeben am %s', 'spezialist-ratings' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $user_rating->created_at ) )
                        );
                        ?>
                    </p>
                </div>

            <?php elseif ( $can_rate ) : ?>
                <!-- User can rate - show form -->
                <div class="sr-rating-form-container">
                    <h3><?php _e( 'Deine Bewertung', 'spezialist-ratings' ); ?></h3>
                    <form id="sr-rating-form" class="sr-rating-form" enctype="multipart/form-data">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">

                        <div class="sr-form-group sr-star-input-group">
                            <label><?php _e( 'Bewertung', 'spezialist-ratings' ); ?> <span class="required">*</span></label>
                            <div class="sr-star-input" data-rating="0">
                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <button type="button" class="sr-star-btn" data-value="<?php echo $i; ?>" title="<?php echo $i; ?> <?php echo _n( 'Stern', 'Sterne', $i, 'spezialist-ratings' ); ?>">
                                        <svg width="32" height="32" viewBox="0 0 24 24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="sr-rating-value" required>
                            <span class="sr-rating-text"></span>
                        </div>

                        <div class="sr-form-group">
                            <label for="sr-comment"><?php _e( 'Kommentar (optional)', 'spezialist-ratings' ); ?></label>
                            <textarea id="sr-comment" name="comment" rows="4" placeholder="<?php esc_attr_e( 'Teile Deine Erfahrungen mit diesem Hofladen...', 'spezialist-ratings' ); ?>"></textarea>
                            <p class="sr-field-hint">
                                <?php _e( 'Hinweis: Kommentare werden vor der Veröffentlichung geprüft.', 'spezialist-ratings' ); ?>
                            </p>
                        </div>

                        <div class="sr-form-group">
                            <label for="sr-media"><?php _e( 'Foto oder Video hinzufügen (optional)', 'spezialist-ratings' ); ?></label>
                            <div class="sr-media-upload">
                                <input type="file" id="sr-media" name="media" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime">
                                <p class="sr-field-hint">
                                    <?php _e( 'Max. 10 MB. Bilder: JPG, PNG, WebP. Videos: MP4, MOV.', 'spezialist-ratings' ); ?>
                                </p>
                            </div>
                        </div>

                        <button type="submit" class="sd-button sd-button-primary sr-submit-btn">
                            <span class="sr-btn-text"><?php _e( 'Bewertung abgeben', 'spezialist-ratings' ); ?></span>
                            <span class="sr-btn-loading" style="display: none;">
                                <svg class="sr-spinner" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" transform="rotate(-90 12 12)">
                                        <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <!-- Success message (hidden by default) -->
                    <div id="sr-success-message" class="sr-success-message" style="display: none;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/>
                        </svg>
                        <h4><?php _e( 'Vielen Dank!', 'spezialist-ratings' ); ?></h4>
                        <p class="sr-success-text"></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Ratings List (only shown if ratings exist) -->
        <?php if ( ! empty( $ratings ) ) : ?>
        <div class="sr-ratings-list">
            <h3><?php _e( 'Alle Bewertungen', 'spezialist-ratings' ); ?></h3>

                <?php foreach ( $ratings as $rating ) :
                    $reviewer = get_user_by( 'id', $rating->user_id );
                    if ( ! $reviewer ) continue;
                ?>
                    <div class="sr-rating-item">
                        <div class="sr-rating-header">
                            <?php echo get_avatar( $rating->user_id, 48, '', '', array( 'class' => 'sr-reviewer-avatar' ) ); ?>
                            <div class="sr-rating-meta">
                                <span class="sr-reviewer-name"><?php echo esc_html( $reviewer->display_name ); ?></span>
                                <div class="sr-rating-stars">
                                    <?php echo SR_Ratings::render_stars( $rating->rating, 16 ); ?>
                                </div>
                                <span class="sr-rating-date">
                                    <?php echo esc_html( human_time_diff( strtotime( $rating->created_at ), current_time( 'timestamp' ) ) ); ?>
                                    <?php _e( 'her', 'spezialist-ratings' ); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ( $rating->comment ) : ?>
                            <div class="sr-rating-content">
                                <p><?php echo esc_html( $rating->comment ); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $rating->media_id ) ) :
                            $media_url = wp_get_attachment_url( $rating->media_id );
                            $is_video = wp_attachment_is( 'video', $rating->media_id );
                        ?>
                            <div class="sr-rating-media">
                                <?php if ( $is_video ) : ?>
                                    <video controls preload="metadata" class="sr-review-video">
                                        <source src="<?php echo esc_url( $media_url ); ?>">
                                        <?php _e( 'Dein Browser unterstützt das Video-Format nicht.', 'spezialist-ratings' ); ?>
                                    </video>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $media_url ); ?>" target="_blank" class="sr-review-image-link">
                                        <img src="<?php echo esc_url( wp_get_attachment_image_url( $rating->media_id, 'medium' ) ); ?>"
                                             alt="<?php esc_attr_e( 'Bewertungsfoto', 'spezialist-ratings' ); ?>"
                                             class="sr-review-image">
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $rating->owner_response ) ) : ?>
                            <div class="sr-owner-response">
                                <div class="sr-response-header">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" fill="currentColor"/>
                                    </svg>
                                    <span class="sr-response-label"><?php _e( 'Antwort vom Inhaber', 'spezialist-ratings' ); ?></span>
                                    <?php if ( ! empty( $rating->owner_response_at ) ) : ?>
                                        <span class="sr-response-date">
                                            <?php echo esc_html( human_time_diff( strtotime( $rating->owner_response_at ), current_time( 'timestamp' ) ) ); ?>
                                            <?php _e( 'her', 'spezialist-ratings' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="sr-response-content">
                                    <p><?php echo esc_html( $rating->owner_response ); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
