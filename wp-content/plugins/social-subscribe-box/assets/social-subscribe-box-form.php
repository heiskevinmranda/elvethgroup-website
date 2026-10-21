<div id="slide-out-div" class="pt-social-subscribe-box">
    <a class="handle ui-slideouttab-handle-rounded"><?php echo esc_html( ptssbox_get_option('tab_title_closed' ) ) ?></a>

    <div class="pt-social-subscribe-box-header"><!-- subscribe box header -->
        <span class="pt-social-subscribe-box-feedback-message"></span>
		<?php
		$line_1 = ptssbox_get_option( 'line_1' );
		$line_2 = ptssbox_get_option( 'line_2' );
		?>
		<?php if ( $line_1 || $line_2 ) : ?>
            <p class="pt-social-subscribe-box-form-intro"><?php echo $line_1; ?>
                <span><?php echo $line_2 ?></span>
            </p>
		<?php endif; ?>

        <form class="pt-social-subscribe-box-form" action="" method="post">
            <div class="pt-social-subscribe-box-email-row pt-social-subscribe-box-clearfix">
                <input type="text" name="email" class="pt-social-subscribe-box-field-email"
                       placeholder="<?php _e( 'Your email*', 'social-subscribe-box' ) ?>"/>
            </div>

            <div class="pt-social-subscribe-box-name-row pt-social-subscribe-box-clearfix">
                <input type="text" name="first_name"
                       placeholder="<?php _e( 'First Name*', 'social-subscribe-box' ); ?>"
                       class="pt-social-subscribe-box-input-field pt-social-subscribe-box-field-first-name"/>
                <input type="text" name="last_name" placeholder="<?php _e( 'Last Name', 'social-subscribe-box' ); ?>"
                       class="pt-social-subscribe-box-input-field pt-social-subscribe-box-field-last-name"/>

            </div>
			<?php wp_nonce_field( 'ptssbox_subscribe' ); ?>

            <p class="pt-social-subscribe-box-submit-wrapper">
                <button type="submit" id="pt-subscribe-box-submit-btn" class="pt-social-subscribe-box-submit-btn">
					<?php _e( 'Subscribe', 'social-subscribe-box' ); ?>
                </button>
            </p>
        </form>

    </div><!-- end of subscribe header -->
	<?php
	$fb_link          = ptssbox_get_option( 'fb_url' );
	$twitter_link     = ptssbox_get_option( 'twitter_url' );
	$google_plus_link = ptssbox_get_option( 'google_plus_url' );
	$linkedin_link    = ptssbox_get_option( 'linkedin_url' );
	$social_tagline   = ptssbox_get_option( 'social_tagline' );
	?>

    <div class="pt-social-subscribe-box-footer clearfix">
		<?php if ( $social_tagline ): ?>
            <div class="pt-social-subscribe-box-follow-label">
				<?php echo wp_kses_data( $social_tagline ); ?>
            </div>
		<?php endif; ?>

		<?php if ( ! empty( $fb_link ) || ! empty( $twitter_link ) || ! empty( $google_plus_link ) || ! empty( $linkedin_link ) ): ?>

            <div class="pt-social-subscribe-box-social-icons">

				<?php if ( ! empty( $fb_link ) ) : ?>
                    <a class="pt-social-subscribe-box-fb-link" href="<?php echo esc_url( $fb_link ); ?>" target="_blank">
                        <i class="fa fa-facebook" aria-hidden="true"></i><span>Facebook</span>
                    </a>
				<?php endif; ?>

				<?php if ( ! empty( $twitter_link ) ) : ?>
                    <a class="pt-social-subscribe-box-twitter-link" href="<?php echo esc_url( $twitter_link ); ?>" target="_blank">
                        <i class="fa fa-twitter" aria-hidden="true"></i><span>Twitter</span>
                    </a>
				<?php endif; ?>

				<?php if ( ! empty( $google_plus_link ) ) : ?>
                    <a class="pt-social-subscribe-box-google-plus-link" href="<?php echo esc_url( $google_plus_link ); ?>" target="_blank">
                        <i class="fa fa-google-plus" aria-hidden="true"></i><span>Google+</span>
                    </a>
				<?php endif; ?>

				<?php if ( ! empty( $linkedin_link ) ) : ?>
                    <a class="pt-social-subscribe-box-linkedin-link" href="<?php echo esc_url( $linkedin_link ); ?>" target="_blank">
                        <i class="fa fa-linkedin" aria-hidden="true"></i><span>LinkedIn</span>
                    </a>
				<?php endif; ?>

            </div>
		<?php endif; ?>
    </div>

    <div class="pt-social-subscribe-box-images-pre-loader" style="display: none">
        <img class="pt-social-subscribe-box-image-poke-fun" src="<?php echo $this->get_poke_image(); ?>"/>
        <img class="pt-social-subscribe-box-image-thank-you" src="<?php echo $this->get_success_image(); ?>"/>
        <img class="pt-social-subscribe-box-image-error" src="<?php echo $this->get_error_image(); ?>"/>
        <img class="pt-social-subscribe-box-image-loader" src="<?php echo $this->get_loader_image(); ?>"/>
    </div>
</div>
