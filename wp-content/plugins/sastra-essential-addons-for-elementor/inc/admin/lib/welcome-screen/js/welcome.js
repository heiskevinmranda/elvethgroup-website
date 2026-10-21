var welcomeScreenFunctions = {

    desabledUnusedWidget: function(){

        jQuery('.tmpcoder-btn-unused').click( function() {

            var action = 'tmpcoder_get_elementor_pages';
            var _nonce_key = welcomeScreen.ajax_nonce;
            var loaderTitle = welcomeScreen.disable_unused_widgets_loader_title || '';
            var loaderDesc = welcomeScreen.disable_unused_widgets_loader_desc || '';

            function hideDisableUnusedLoader() {
                if (window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.hide === 'function') {
                    window.tmpcoderCommonLoader.hide('.tmpcoder-widgets-sync-loader');
                } else {
                    jQuery('.tmpcoder-widgets-sync-loader').fadeOut();
                }
                jQuery('.tmpcoder-theme-welcome').css('opacity', '1');
            }

            jQuery.ajax({
                url:welcomeScreen.ajax_url,
                method:'POST',
                data: 
                {
                    action: action,
                    _ajax_nonce: _nonce_key,
                },
                beforeSend: function() {
                    if (window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.show === 'function') {
                        window.tmpcoderCommonLoader.show('.tmpcoder-widgets-sync-loader', loaderTitle, loaderDesc);
                        jQuery('.tmpcoder-theme-welcome').css('opacity', '1');
                    } else {
                        jQuery('.welcome-backend-loader').fadeIn();
                        jQuery('.tmpcoder-theme-welcome').css('opacity','1');
                    }
                },
            })
            .done( function( response ) {

                hideDisableUnusedLoader();
                var currentURL = window.location.href;
                window.location.href = TmpcodersanitizeURL(currentURL);
            })
            .fail( function( error ) {
                hideDisableUnusedLoader();
                console.log('fail');
                console.log(error);
            })
        })
    },
    setGlobalFonts: function() {
        jQuery('.set-global-fonts-btn').click(function(e) {
            e.preventDefault();
            jQuery('.tmpcoder-set-global-fonts-confirm-popup-wrap').fadeIn();
            jQuery('#tmpcoder-set-global-fonts-confirm-popup').fadeIn();
            jQuery('.tmpcoder-admin-popup').fadeIn();

        });
  
        jQuery(document).on('click', '.tmpcoder-set-global-fonts-confirm-popup-wrap .popup-close', function(e) {
            e.preventDefault();
            jQuery('.tmpcoder-set-global-fonts-confirm-popup-wrap').fadeOut();
            jQuery('#tmpcoder-set-global-fonts-confirm-popup').fadeOut();
            jQuery('.tmpcoder-admin-popup').fadeOut();
        });
    
        jQuery(document).on('click', '.tmpcoder-set-global-fonts-confirm-button', function(e) {
            e.preventDefault();
    
            var action = 'tmpcoder_set_global_fonts';
            var _nonce_key = welcomeScreen.ajax_nonce;
    
            jQuery.ajax({
                url: welcomeScreen.ajax_url,
                method: 'POST',
                data: {
                action: action,
                _ajax_nonce: _nonce_key,
                },
                beforeSend: function() {
                    jQuery('.tmpcoder-set-global-fonts-confirm-popup-wrap').fadeOut();
                    jQuery('#tmpcoder-set-global-fonts-confirm-popup').fadeOut();

                    jQuery('.set-global-fonts-popup').fadeIn();
                    jQuery('.tmpcoder-condition-popup-wrap').fadeIn();
                    if (window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.show === 'function') {
                        window.tmpcoderCommonLoader.show('.set-global-loader');
                    } else {
                        jQuery('.set-global-loader').removeAttr('hidden').css('display', 'flex');
                    }
                    jQuery('.set-global-font-success').css('display', 'none');
                }
            })
            .done(function(response) {
                if (response.success == true) {
                    if (window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.hide === 'function') {
                        window.tmpcoderCommonLoader.hide('.set-global-loader');
                    } else {
                        jQuery('.set-global-loader').attr('hidden', true).css('display', 'none');
                    }
                    jQuery('.set-global-font-success').css('display', 'flex');
            
                    setTimeout(function() {
                        jQuery('.tmpcoder-condition-popup-wrap').fadeOut();
                    }, 1700);
                } else {
                    jQuery('.tmpcoder-condition-popup-wrap').fadeOut();
                }
            })
            .fail(function(error) {
                console.log(error);
            });
        });
    },
  
    upgradeProNotice: function(){
        jQuery('.tmpcoder-upgrade-pro-notice .tmpcoder-upgrade-pro-notice-dismiss').click( function(e) {

            $this = jQuery(this);
            $this.parent().slideUp( 700, function() {
              $this.parent().remove();
            });
            
            var action = 'tmpcoder_upgrade_pro_notice_dismiss';
            var _nonce_key = welcomeScreen.ajax_nonce;
            var activate_pro_notice = jQuery(this).hasClass('activate-pro-notice');
            var activate_theme_notice = jQuery(this).hasClass('activate-theme-notice');

            jQuery.ajax({
              url:welcomeScreen.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: _nonce_key,
                    activate_pro_notice: activate_pro_notice,
                    activate_theme_notice: activate_theme_notice,
                },
            })
            .done( function( response ) {

                if (response.success == true)
                {
                  console.log('Notice dismissed');   
                }
                else
                {
                  console.log('Failed to dismiss notice');    
                }
            })
            .fail( function( error ) {
                console.log(error);
            })
        });
    },

    settingsSmoothScroll: function(){
        jQuery('.settings-breadcrumb-nav a').on('click', function(e){
            var targetSelector = jQuery(this).attr('href');
            if (!targetSelector || targetSelector.indexOf('#') === -1) {
                return;
            }

            var hash = targetSelector.substring(targetSelector.indexOf('#'));
            var $target = jQuery(hash);
            if (!$target.length) {
                return;
            }

            e.preventDefault();
            jQuery('html, body').animate({
                scrollTop: $target.offset().top - 80
            }, 400);
        });
    },
    initSidebarAccordion: function () {
        var $wrappers = jQuery('.tmpcoder-theme-welcome .nav-tab-wrapper');
        if (!$wrappers.length) {
            return;
        }

        var expandParentChain = function ($link) {
            if (!$link || !$link.length) {
                return;
            }

            $link.parents('ul').each(function () {
                var $parentLi = jQuery(this).closest('li');
                var $parentLink = $parentLi.children('.nav-tab').first();
                if ($parentLink.length) {
                    $parentLink.attr('aria-expanded', 'true');
                }
                jQuery(this).show();
            });
        };

        var expandActiveChains = function () {
            $wrappers.each(function () {
                var $wrapper = jQuery(this);
                $wrapper.find('.nav-tab.nav-tab-active').each(function () {
                    expandParentChain(jQuery(this));
                });
            });
        };

        $wrappers.each(function () {
            var $wrapper = jQuery(this);

            // Hide all nested tab lists by default.
            $wrapper.find('li > ul').hide();

            // Add arrow indicators for parent tabs.
            $wrapper.find('li').each(function () {
                var $li = jQuery(this);
                var $link = $li.children('.nav-tab').first();
                var $childList = $li.children('ul');
                if (!$link.length || !$childList.length || !$childList.children('li').length) {
                    return;
                }

                $link.attr('aria-expanded', 'false');

                if (!$link.find('[data-subtab-arrow="1"]').length) {
                    $link.append('<span class="dashicons dashicons-arrow-down-alt2" data-subtab-arrow="1" aria-hidden="true"></span>');
                }
            });
        });

        expandActiveChains();
        setTimeout(expandActiveChains, 0);
        setTimeout(expandActiveChains, 200);

        jQuery(document).off('tmpcoder:sidebar-tab-active').on('tmpcoder:sidebar-tab-active', function (_event, $activeLink) {
            expandParentChain($activeLink);
        });

        $wrappers.off('click.sidebarAccordionArrow').on('click.sidebarAccordionArrow', '[data-subtab-arrow="1"]', function (event) {
            event.preventDefault();
            event.stopPropagation();
            jQuery(this).closest('.nav-tab').trigger('click');
        });

        $wrappers.off('click.sidebarAccordion').on('click.sidebarAccordion', '.nav-tab', function (event) {
            var $link = jQuery(this);
            var $li = $link.closest('li');
            var $childList = $li.children('ul');
            if (!$childList.length) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var isOpen = $link.attr('aria-expanded') === 'true';

            $li.siblings('li').each(function () {
                var $sibling = jQuery(this);
                var $siblingList = $sibling.children('ul');
                if ($siblingList.length) {
                    $sibling.children('.nav-tab').attr('aria-expanded', 'false');
                    $siblingList.stop(true, true).slideUp(180);
                }
            });

            if (isOpen) {
                $link.attr('aria-expanded', 'false');
                $childList.stop(true, true).slideUp(180);
            } else {
                $link.attr('aria-expanded', 'true');
                $childList.stop(true, true).slideDown(180);
            }
        });
    },
    mergeGlobalOptionsSidebar: function(attempt){
        var retryAttempt = attempt || 0;
        var maxRetry = 10;
        var retryDelay = 150;
        var $mainNav = jQuery('.tmpcoder-theme-welcome .nav-tab-wrapper').first();
        if (!$mainNav.length) {
            return;
        }

        var $mainNavList = $mainNav.children('ul').first();
        if (!$mainNavList.length) {
            return;
        }

        var $globalTab = $mainNavList.find('> li > .nav-tab').filter(function () {
            var href = jQuery(this).attr('href') || '';
            return href.indexOf('spexo_addons_global_settings') !== -1 || href.indexOf('sastra_addon_global_settings') !== -1;
        }).first();
        var globalHref = $globalTab.attr('href') || '';

        if (!$globalTab.length || $mainNavList.find('.nav-tab[data-global-option-index]').length) {
            return;
        }

        var $reduxItems = jQuery('.redux-sidebar .redux-group-menu > li');

        var $globalItem = $globalTab.closest('li');
        var $globalChildList = $globalItem.children('ul').first();
        if (!$globalChildList.length) {
            $globalChildList = jQuery('<ul></ul>');
            $globalItem.append($globalChildList);
        }

        if ($reduxItems.length) {
            $reduxItems.each(function(index){
                var $item = jQuery(this);
                var $sourceLink = $item.children('a');
                var label = jQuery.trim($sourceLink.text());
                var rel = $sourceLink.attr('data-rel') || '';
                var iconHtml = '';
                var $icon = $sourceLink.children('i, svg, .dashicons').first();
                if (!$icon.length) {
                    $icon = $sourceLink.find('i, svg, .dashicons').first();
                }
                if ($icon.length) {
                    iconHtml = $icon.prop('outerHTML');
                }
                if (!label) {
                    return;
                }

                var $clonedItem = jQuery(
                    '<li><a href="javascript:void(0);" class="nav-tab" data-global-option-index="' + index + '" data-global-option-rel="' + rel + '">' +
                    iconHtml +
                    '<span>' + label + '</span></a></li>'
                );
                $globalChildList.append($clonedItem);

                var $subLinks = $item.find('> .subsection .redux-group-tab-link-li > .redux-group-tab-link-a');
                var $subList = jQuery('<ul></ul>');
                $subLinks.each(function (subIndex) {
                    var $subSourceLink = jQuery(this);
                    var subLabel = jQuery.trim($subSourceLink.text());
                    var subRel = $subSourceLink.attr('data-rel') || '';
                    if (!subLabel) {
                        return;
                    }

                    var $subClonedItem = jQuery('<li><a href="javascript:void(0);" class="nav-tab" data-global-option-parent-index="' + index + '" data-global-option-sub-index="' + subIndex + '" data-global-option-rel="' + subRel + '"><span>' + subLabel + '</span></a></li>');
                    $subList.append($subClonedItem);
                });

                if ($subList.children().length) {
                    $clonedItem.append($subList);
                }
            });
        } else {
            if (retryAttempt < maxRetry) {
                setTimeout(function () {
                    welcomeScreenFunctions.mergeGlobalOptionsSidebar(retryAttempt + 1);
                }, retryDelay);
            }

            var fallbackIconMap = {
                'Global Colors': 'el el-adjust',
                'Buttons': 'el el-cog',
                'Typography': 'el el-font',
                'General Options': 'el el-cogs',
                'Custom CSS': 'el el-css',
                'Custom JS': 'el el-edit',
                'Import / Export': 'el el-refresh'
            };

            var fallbackItems = [
                { label: 'Global Colors' },
                { label: 'Buttons', children: [ 'Desktop', 'Tablet', 'Mobile' ] },
                { label: 'Typography', children: [ 'Desktop', 'Tablet', 'Mobile' ] },
                { label: 'General Options' },
                { label: 'Custom CSS' },
                { label: 'Custom JS' },
                { label: 'Import / Export' }
            ];

            jQuery.each(fallbackItems, function(index, item){
                var iconClass = fallbackIconMap[item.label] || '';
                var iconHtml = iconClass ? '<i class="' + iconClass + '" aria-hidden="true"></i>' : '';
                var $clonedItem = jQuery('<li><a href="javascript:void(0);" class="nav-tab" data-global-option-index="' + index + '">' + iconHtml + '<span>' + item.label + '</span></a></li>');
                $globalChildList.append($clonedItem);

                if (item.children && item.children.length) {
                    var $subList = jQuery('<ul></ul>');
                    jQuery.each(item.children, function(subIndex, subLabel){
                        var $subClonedItem = jQuery('<li><a href="javascript:void(0);" class="nav-tab" data-global-option-parent-index="' + index + '" data-global-option-sub-index="' + subIndex + '"><span>' + subLabel + '</span></a></li>');
                        $subList.append($subClonedItem);
                    });
                    $clonedItem.append($subList);
                }
            });
        }

        var syncActiveGlobalOptionLink = function() {
            if (!$reduxItems.length) {
                return;
            }

            var $activeParent = jQuery('.redux-sidebar .redux-group-menu > li.active').first();
            if (!$activeParent.length) {
                $activeParent = jQuery('.redux-sidebar .redux-group-menu > li.activeChild').first();
            }
            if (!$activeParent.length) {
                $activeParent = jQuery('.redux-sidebar .redux-group-menu > li.activeChild.hasSubSections').first();
            }

            var activeIndex = $activeParent.length ? $activeParent.index() : -1;
            $mainNavList.find('.nav-tab').removeClass('nav-tab-active');

            var activeRel = '';
            var $visibleGroup = jQuery('.redux-main .redux-group-tab:visible').first();
            if ($visibleGroup.length) {
                activeRel = $visibleGroup.attr('data-rel') || $visibleGroup.closest('[data-rel]').attr('data-rel') || '';
            }
            if (!activeRel) {
                var $visibleRel = jQuery('.redux-main [data-rel]:visible').first();
                activeRel = $visibleRel.attr('data-rel') || '';
            }

            if (activeRel) {
                var $activeByRel = $mainNavList.find('.nav-tab[data-global-option-rel="' + activeRel + '"]').first();
                if ($activeByRel.length) {
                    $activeByRel.addClass('nav-tab-active');
                    $activeByRel.parents('ul').each(function () {
                        var $parentLi = jQuery(this).closest('li');
                        var $parentLink = $parentLi.children('.nav-tab').first();
                        if ($parentLink.length) {
                            $parentLink.attr('aria-expanded', 'true');
                        }
                        jQuery(this).show();
                    });
                    return;
                }
            }

            var activeSubIndex = -1;
            if ($activeParent.length) {
                activeSubIndex = $activeParent.find('> .subsection li.active').first().index();
            }
            if (activeIndex >= 0 && activeSubIndex >= 0) {
                var $activeSubTab = $mainNavList.find('.nav-tab[data-global-option-parent-index="' + activeIndex + '"][data-global-option-sub-index="' + activeSubIndex + '"]');
                $activeSubTab.addClass('nav-tab-active');
                $activeSubTab.parents('ul').each(function () {
                    var $parentLi = jQuery(this).closest('li');
                    var $parentLink = $parentLi.children('.nav-tab').first();
                    if ($parentLink.length) {
                        $parentLink.attr('aria-expanded', 'true');
                    }
                    jQuery(this).show();
                });
            } else if (activeIndex >= 0) {
                var $activeMainTab = $mainNavList.find('.nav-tab[data-global-option-index="' + activeIndex + '"]');
                $activeMainTab.addClass('nav-tab-active');
                $activeMainTab.parents('ul').each(function () {
                    var $parentLi = jQuery(this).closest('li');
                    var $parentLink = $parentLi.children('.nav-tab').first();
                    if ($parentLink.length) {
                        $parentLink.attr('aria-expanded', 'true');
                    }
                    jQuery(this).show();
                });
            }
        };

        $mainNavList.on('click', '.nav-tab[data-global-option-index]', function(e){
            e.preventDefault();
            var $link = jQuery(this);
            var hasNestedSubtabs = $link.closest('li').children('ul').children('li').length > 0;
            if (hasNestedSubtabs) {
                return;
            }

            var index = parseInt($link.attr('data-global-option-index'), 10);
            if (isNaN(index)) {
                return;
            }

            var $target = jQuery('.redux-sidebar .redux-group-menu > li').eq(index).children('a');
            if ($target.length) {
                $target.trigger('click');
            } else if (globalHref) {
                window.location.href = globalHref;
            }
            syncActiveGlobalOptionLink();
        });

        $mainNavList.on('click', '.nav-tab[data-global-option-sub-index]', function(e){
            e.preventDefault();
            var parentIndex = parseInt(jQuery(this).attr('data-global-option-parent-index'), 10);
            var subIndex = parseInt(jQuery(this).attr('data-global-option-sub-index'), 10);
            if (isNaN(parentIndex) || isNaN(subIndex)) {
                return;
            }

            var $target = jQuery('.redux-sidebar .redux-group-menu > li').eq(parentIndex).find('> .subsection .redux-group-tab-link-li > .redux-group-tab-link-a').eq(subIndex);
            if ($target.length) {
                $target.trigger('click');
            } else if (globalHref) {
                window.location.href = globalHref;
            }
            syncActiveGlobalOptionLink();
        });

        syncActiveGlobalOptionLink();
        if ($reduxItems.length) {
            jQuery(document).on('click', '.redux-sidebar .redux-group-menu > li > a, .redux-sidebar .redux-group-menu > li > .subsection .redux-group-tab-link-li > .redux-group-tab-link-a', function () {
                setTimeout(syncActiveGlobalOptionLink, 0);
            });
        }
    }
};

jQuery( document ).ready( function() {
    welcomeScreenFunctions.desabledUnusedWidget();
    welcomeScreenFunctions.setGlobalFonts();
    welcomeScreenFunctions.upgradeProNotice();
    welcomeScreenFunctions.settingsSmoothScroll();
    welcomeScreenFunctions.mergeGlobalOptionsSidebar();
    welcomeScreenFunctions.initSidebarAccordion();

    // var pluginMenuRef = jQuery('#adminmenuwrap #toplevel_page_spexo-welcome');
    // console.log('pluginMenuRef', pluginMenuRef);
    // pluginMenuRef.find('.wp-submenu-wrap li').each(function(){
    //     console.log( jQuery(this).find('a').attr('href') );
    //     if ( jQuery(this).find('a').attr('href') == welcomeScreen.global_options_link ){
    //         jQuery(this).addClass('tmpcoder-global-options-menu');
    //     }
    //     else if( jQuery(this).find('a').attr('href') == welcomeScreen.widget_settings_link ){
    //         jQuery(this).addClass('tmpcoder-widgets-settings-menu');
    //     }
    //     else if( jQuery(this).find('a').attr('href') == welcomeScreen.global_settings_link ){
    //         jQuery(this).addClass('tmpcoder-intigration-settings-menu');
    //     }
    // });

    // const $elementToMove = jQuery('.tmpcoder-global-options-menu');
    // const $siblingElement = jQuery('.tmpcoder-intigration-settings-menu');
    // if ( $siblingElement.length != 0 ){
    //     $elementToMove.insertBefore($siblingElement);
    // }

});


jQuery(document).ready(function () {
    const $header = jQuery('.tmpcoder-import-demo-page > header');

    if ($header.length === 0) return;

    const checkSticky = () => {
        const rect = $header[0].getBoundingClientRect();
        if (rect.top <= 32) {
        $header.addClass('tmpcoder-prebuilt-websites-header-sticky');
        } else {
        $header.removeClass('tmpcoder-prebuilt-websites-header-sticky');
        }
    };

    jQuery(window).on('scroll', checkSticky);
    checkSticky(); // initial check

    var searchTimeout = null;  
    jQuery('.tmpcoder-search-tracking').keyup(function(e) {

        console.log('tmpcoder-search-tracking');

        if ( e.which === 13 ) {
            return false;
        }

        var val = jQuery(this).val().toLowerCase();

        if (searchTimeout != null) {
            clearTimeout(searchTimeout);
        }

        var type = jQuery(this).data('type');

        searchTimeout = setTimeout(function() {
            searchTimeout = null;
            jQuery.ajax({
                type: 'POST',
                url:welcomeScreen.ajax_url,
                data: {
                    action: 'tmpcoder_backend_search_query_results',
                    search_query: val,
                    type:type
                },
                success: function( response ) {}
            });
        }, 1000);  
    });

});