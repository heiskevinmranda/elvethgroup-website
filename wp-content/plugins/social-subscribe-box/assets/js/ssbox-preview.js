//For Live Preview
;(function ($) {
    var api = wp.customize;

    /**
     * Asynchronous updating
     */
    apply_background_color('ptssbox_tab_bg_color', '.pt-social-subscribe-box .ui-slideouttab-handle');
    apply_color_change('ptssbox_tab_text_color', '.pt-social-subscribe-box .ui-slideouttab-handle');

    apply_background_color('ptssbox_header_bg_color', '.pt-social-subscribe-box-header');
    apply_color_change('ptssbox_header_text_color', '.pt-social-subscribe-box-header');


    apply_background_color('ptssbox_footer_bg_color', '.pt-social-subscribe-box-footer');
    apply_color_change('ptssbox_footer_text_color', '.pt-label-subscribe-box-header');


    apply_background_hover_style('ptssbox_subscribe_btn_bg_color', 'ptssbox_subscribe_btn_hover_color', '.pt-social-subscribe-box-submit-btn');
    apply_text_hover_style('ptssbox_subscribe_btn_text_color', 'ptssbox_subscribe_btn_hover_text_color', '.pt-social-subscribe-box-submit-btn');


    /**
     * Apply color change.
     *
     * @param setting
     * @param selector
     */
    function apply_color_change(setting, selector) {
        api(setting, function (value) {

            value.bind(function (to) {
                generate_internal_css(setting, selector, {color: to});
            });
        });

    }

    /**
     * Apply background change to a selector live
     *
     * @param setting setting name
     * @param selector selector.
     */
    function apply_background_color(setting, selector) {

        api(setting, function (value) {
            value.bind(function (to) {
                generate_internal_css(setting, selector, {'background-color': to});
            });
        });
    }

    /**
     * Apply hover styles to color properties
     *
     * @param element
     * @param selector
     */
    function apply_text_hover_style(setting, setting_hover, selector, selector_hover) {

        if (!selector_hover) {
            selector_hover = selector + ':hover';
        }

        apply_hover_style(setting + '-text-hover', 'color', setting, setting_hover, selector, selector_hover);

    }

    /**
     * Apply background hover style
     * @param setting
     * @param selector
     * @param selector_hover
     */
    function apply_background_hover_style(setting, setting_hover, selector, selector_hover) {


        if (selector_hover == undefined) {
            selector_hover = selector + ':hover';
        }

        apply_hover_style(setting + '-background-hover', 'background-color', setting, setting_hover, selector, selector_hover);
    }

    /**
     * Apply hover style based on the two settings
     *
     * @param string id used for uniquely identifyinf the css block in the current page
     * @param string property valid css property used for hover
     * @param string setting_general name of the setting controlling normal state
     * @param string setting_hover name of setting controlling hover state
     * @param string selector normal state selector
     * @param string selector_hover hover state selector
     */
    function apply_hover_style(id, property, setting_general, setting_hover, selector, selector_hover) {
        //return ;

        var props = [setting_general, setting_hover];

        api.when.apply(api, props).done(function (color, hover_color) {

            var head = $('head'),
                style_id = 'custom-hover-css-' + id;

            var style = $('#' + style_id);

            var update = function () {
                var css = '', hover_css = '';
                //color change?
                if (color()) {
                    css = property + ':' + color() + ';';
                }

                if (hover_color()) {
                    hover_css = property + ':' + hover_color() + ';';
                }

                // Refresh the stylesheet by removing and recreating it.
                style.remove();

                style = $('<style type="text/css" id="' + style_id + '">\r\n' +
                    selector + '{ ' + css + ' }\r\n' +
                    selector_hover + '{' + hover_css + '}' +
                    '</style>'
                ).appendTo(head);


            };

            $.each(arguments, function () {

                this.bind(update);
            });

        });

    }

    var head = $('head');

    /**
     *
     * @param setting unique setting id, must be unique for each supplied css object, else will override
     * @param selector the css seletor to which the styles should be spplied
     * @param css_object
     */
    function generate_internal_css(setting, selector, css_object) {
        var style_id = 'ssbox-custom-css-style-setting-' + setting;
        var style = $('#' + style_id);

        style.remove();

        if ($.isEmptyObject(css_object)) {
            return;
        }

        var css = '';

        for (var key in css_object) {
            css += key + ':' + css_object[key] + ';';
        }

        $('<style type="text/css" id="' + style_id + '">\r\n' +
            selector + '{' + css + '}' +
            '</style>'
        ).appendTo(head);

    }

})(jQuery);
