jQuery(document).ready(function ($) {
    var $win = $(window);
    var $box = $('.pt-social-subscribe-box');

    // Preloader.
    var $preloader_container = $box.find('.pt-social-subscribe-box-images-pre-loader');

    var $success_image = $preloader_container.find('.pt-social-subscribe-box-image-thank-you');
    var $err_image     = $preloader_container.find('.pt-social-subscribe-box-image-error');
    var $poke_image    = $preloader_container.find('.pt-social-subscribe-box-image-poke-fun');
    var $loader_image  =  $preloader_container.find('.pt-social-subscribe-box-image-loader');

    var $feedback = $box.find('.pt-social-subscribe-box-feedback-message');

    var $form       = $box.find('.pt-social-subscribe-box-form');
    var $email      = $form.find('.pt-social-subscribe-box-field-email');
    var $first_name = $form.find('.pt-social-subscribe-box-field-first-name');

    var isMobile     = PTSocailSubscribeBox.is_mobile;
    var isOpen       = isMobile ? false : true;
    var isBoxVisible = false;


    var tabOffset     = PTSocailSubscribeBox.tab_offset;
    var tabOffsetFrom = PTSocailSubscribeBox.tab_offset_from;
    var tabLocation   = PTSocailSubscribeBox.tab_location;

    var offsetReverse = ( tabOffsetFrom === 'top' || tabOffsetFrom === 'left') ? false : true;

    // Configure the box.
    $box.tabSlideOut({
        tabLocation: tabLocation,
        offset: tabOffset + 'px',
        offsetReverse: offsetReverse,
        onOpen: function () {
            isOpen = true;
            $box.find('.ui-slideouttab-handle').text(PTSocailSubscribeBox.tab_title_open);
        },
        onClose: function () {
            isOpen = false;
            $box.find('.ui-slideouttab-handle').text(PTSocailSubscribeBox.tab_title_closed);
        }
    });
    // on scroll.
    $win.scroll(function () {
        var top = $win.scrollTop();

        if (top > 200 && !isMobile) {
            if (!isBoxVisible) {
                openBox();
            }
        } else if (isBoxVisible) {
            closeBox();
        }
    });

    if (!isMobile) {
        setTimeout(openBox, 2000);
    }

    $box.on('click', '.ui-slideouttab-handle', function (evt) {

        if (typeof(evt.isTrigger) !== 'undefined') {
            return;
        }

        if (isOpen) {
            saveUserClosedPreference();
        }
    });

    // On form submission.
    $form.submit(function (event) {
        event.preventDefault();
        $feedback.empty();

        var $this = $(this);

        var email = $email.val();
        var fname = $first_name.val();
        // first name and email is required.
        if (!email.length || !fname.length) {
            $feedback.html($poke_image.clone());
            return false;
        }
        $feedback.html($loader_image.clone());
        var data = $this.serialize();
        data += '&action=ptssbox_subscribe';

        $.post(PTSocailSubscribeBox.ajax_url, data, function (resp) {

            if (resp.success) {
                $feedback.removeClass('pt-feedback-error').addClass('pt-feedback-success');
                $feedback.html(resp.data.message);
                $feedback.prepend($success_image.clone());
                saveSubscribedPreference();
            } else {
                $feedback.removeClass('pt-feedback-success').addClass('pt-feedback-error');
                $feedback.html(resp.data.message);
                $feedback.prepend($err_image.clone());
            }
        });
    });

    /**
     * Open the subscribe box.
     */
    function openBox() {

        if (!isBoxVisible && !(hasAlreadySubscribed() || hasUserClosed())) {
            $box.tabSlideOut('open');
            isBoxVisible = true;
        }
    }

    /**
     * Close subscribe box.
     */
    function closeBox() {
        if (isBoxVisible) {
            $box.tabSlideOut('close');
            isBoxVisible = false;
        }
    }

    /**
     * Check if user already subscribed.
     *
     * @returns {boolean}
     */
    function hasAlreadySubscribed() {
        if (typeof $.cookie === 'undefined') {
            return false;
        }
        return $.cookie('pt-social-subscribe-box-subscribed');
    }

    /**
     * Has user manually closed the tab.
     *
     * @returns {boolean}
     */
    function hasUserClosed() {
        if (typeof $.cookie === 'undefined') {
            return false;
        }
        return $.cookie('pt-social-subscribe-box-closed');

    }

    /**
     * Save a cookie for a month if the user subscribed. it allows us to not show the form again.
     */
    function saveSubscribedPreference() {
        if (typeof $.cookie === 'undefined') {
            return;
        }

        $.cookie('pt-social-subscribe-box-subscribed', 1, {expires: 30, path: '/'});
    }

    /**
     * Save the preference that user Closed.
     */
    function saveUserClosedPreference() {
        if (typeof $.cookie === 'undefined') {
            return;
        }
        // for 7 days
        $.cookie('pt-social-subscribe-box-closed', 1, {expires: 7, path: '/'});

    }

    function is_user_click(evt) {
        if (typeof(evt.isTrigger) == 'undefined') {
            return true;
        }
        return false;
    }

});