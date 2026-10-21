( function( $, elementor ) {

	"use strict";

	var TmpcoderPopups = {

		init: function() {
			$(document).ready(function() {
				if ( ! $( '.tmpcoder-template-popup' ).length || TmpcoderPopups.editorCheck() ) {
					return;
				}

				TmpcoderPopups.openPopupInit();
				TmpcoderPopups.closePopupInit();
			});
		},

		openPopupInit: function() {
			$( '.tmpcoder-template-popup' ).each( function() {
				var popup = $(this),
					popupID = TmpcoderPopups.getID( popup );

				if ( ! TmpcoderPopups.checkAvailability( popupID ) ) {
					return;
				}

				if ( ! TmpcoderPopups.checkStopShowingAfterDate( popup ) ) {
					return;
				}

				// Set Local Storage
				TmpcoderPopups.setLocalStorage( popup, 'show' );

				// Get Settings
				var getLocalStorage = JSON.parse( localStorage.getItem( 'TmpcoderPopupSettings' ) ),
					settings = getLocalStorage[ popupID ];

				if ( ! TmpcoderPopups.checkAvailableDevice( popup, settings ) ) {
					return false;
				}

				// Trigger Button Init
				TmpcoderPopups.popupTriggerInit( popup );

				// Page Load


				if ('load' === settings.popup_trigger) {

				    var loadDelay = settings.popup_load_delay * 1000;

				    function tmpcoderTriggerPopup() {
				        if (popup.length) {
				            setTimeout(function () {
				                TmpcoderPopups.openPopup(popup, settings);
				            }, loadDelay);
				        }
				    }

				    if (document.readyState === "complete") {
				        tmpcoderTriggerPopup();
				    } else {
				        $(window).on("load", tmpcoderTriggerPopup);
				    }
				}

				// if ( 'load' === settings.popup_trigger ) {
				// 	var loadDelay = settings.popup_load_delay * 1000;

				// 	$(window).on( 'load', function() {
				// 		setTimeout( function() {
				// 			TmpcoderPopups.openPopup( popup, settings );
				// 		}, loadDelay );
				// 	});

				// } 
				// Page Scroll
				else if ( 'scroll' === settings.popup_trigger ) {
					$(window).on( 'scroll', function() {
						var scrollPercent = $(window).scrollTop() / ($(document).height() - $(window).height()),
							scrollPercent = Math.round( scrollPercent * 100 );

						if ( scrollPercent >= settings.popup_scroll_progress && ! popup.hasClass( 'tmpcoder-popup-open' ) ) {
							TmpcoderPopups.openPopup( popup, settings );
						}
					});

				// Scroll to Element
				} else if ( 'element-scroll' === settings.popup_trigger ) {
					$(window).on( 'scroll', function() {
						var element = $( settings.popup_element_scroll ),
							ScrollBottom = $(window).scrollTop() + $(window).height();

						if ( ! element.length ) {
							return;
						}

						if ( element.offset().top < ScrollBottom && ! popup.hasClass( 'tmpcoder-popup-open' ) ) {
							TmpcoderPopups.openPopup( popup, settings );
						}
					});

				// Specific Date
				} else if ( 'date' === settings.popup_trigger ) {
					var nowDate   = Date.now(),
						startDate = Date.parse( settings.popup_specific_date );

					if ( startDate < nowDate ) {

						setTimeout( function() {
							TmpcoderPopups.openPopup( popup, settings );
						}, 1000 );
					}

				// User Inactivity
				} else if ( 'inactivity' === settings.popup_trigger ) {
					var idleTimer = null,
						inactivityTime = settings.popup_inactivity_time * 1000;

					$( '*' ).bind( 'mousemove click keyup scroll resize', function () {
						if ( popup.hasClass( 'tmpcoder-popup-open' ) ) {
							return;
						}

						// Reset Timer
						clearTimeout( idleTimer );

						// Open if Inactive
						idleTimer = setTimeout( function() { 
							TmpcoderPopups.openPopup( popup, settings );
						}, inactivityTime );
					});

					$( 'body' ).trigger( 'mousemove' );

				// User Exit Intent
				} else if ( 'exit' === settings.popup_trigger ) {
					$(document).on( 'mouseleave', 'body', function( event ) {
						if ( ! popup.hasClass( 'tmpcoder-popup-open' ) ) {
							TmpcoderPopups.openPopup( popup, settings );
						}
					} );

				// Custom Trigger
				} else if ( 'custom' === settings.popup_trigger ) {
					$( settings.popup_custom_trigger ).on( 'click', function() {
						TmpcoderPopups.openPopup( popup, settings );
					});

					$( settings.popup_custom_trigger ).css( 'cursor', 'pointer' );
				}

				// Enable Scrollbar
				if ( '0px' !== popup.find('.tmpcoder-popup-container-inner').css('height') ) {
					if ( typeof PerfectScrollbar !== 'undefined' ) {
						const ps = new PerfectScrollbar(popup.find('.tmpcoder-popup-container-inner')[0], {
							suppressScrollX: true
						});
					}
				}
			});
		}, // End openPopup

		openPopup: function( popup, settings ) {
			if ( 'notification' === settings.popup_display_as ) {
				popup.addClass( 'tmpcoder-popup-notification' );

				setTimeout(function() {
					$( 'body' ).animate({
						'padding-top' : popup.find( '.tmpcoder-popup-container' ).outerHeight() +'px'
					}, settings.popup_animation_duration * 1000, 'linear' );
				}, 10 );
			}

			// Disable Page Scroll
			if ( settings.popup_disable_page_scroll && 'modal' === settings.popup_display_as ) {
				$( 'body' ).css( 'overflow', 'hidden' );
			}

			// Open Popup
			popup.addClass( 'tmpcoder-popup-open' ).show();
			popup.find( '.tmpcoder-popup-container' ).addClass( 'animated '+ settings.popup_animation );

            // Trigger resize
            $(window).trigger('resize');

			// Overlay Fade In
			$( '.tmpcoder-popup-overlay' ).hide().fadeIn();

			// Close Button Show Up Delay
			popup.find( '.tmpcoder-popup-close-btn' ).css( 'opacity', '0' );
			setTimeout(function() {
				popup.find( '.tmpcoder-popup-close-btn' ).animate({
					'opacity' : '1'
				}, 500 );
			}, settings.popup_close_button_display_delay * 1000 );


			// Close Automatically
			if ( false !== settings.popup_automatic_close_switch ) {
				setTimeout(function() {
					TmpcoderPopups.closePopup( popup );
				}, settings.popup_automatic_close_delay * 1000 );
			}
		}, // End openPopup

		closePopupInit: function() {
			// Close Button
			$( '.tmpcoder-popup-close-btn' ).on( 'click', function() {
				TmpcoderPopups.closePopup( $(this).closest( '.tmpcoder-template-popup' ) );
			});

			// Overlay Click
			$( '.tmpcoder-popup-overlay' ).on( 'click', function() {
				var popup = $(this).closest( '.tmpcoder-template-popup' ),
					popupID = TmpcoderPopups.getID( popup ),
					settings = TmpcoderPopups.getLocalStorage( popupID );

				if ( false == settings.popup_overlay_disable_close ) {
					TmpcoderPopups.closePopup( popup );
				}
			});

			// ESC Key Press
			$(document).on( 'keyup', function( event ) {
				var popup = $( '.tmpcoder-popup-open' );

				if ( popup.length ) {
					var	popupID = TmpcoderPopups.getID( popup ),
						settings = TmpcoderPopups.getLocalStorage( popupID );

					if ( 27 == event.keyCode && false == settings.popup_disable_esc_key ) {
						TmpcoderPopups.closePopup( popup );
					}
				}
			});
		},

		closePopup: function( popup ) {
			var popupID = TmpcoderPopups.getID( popup ),
				settings = TmpcoderPopups.getLocalStorage( popupID );

			// Notification
			if ( 'notification' === settings.popup_display_as ) {
				$( 'body' ).css( 'padding-top', 0 );
			}

			// Update Local Storage
			TmpcoderPopups.setLocalStorage( popup, 'hide' );

			// Close Popup
			if ( 'modal' === settings.popup_display_as ) {
				popup.fadeOut();
			} else {
				popup.hide();
			}

			// Enable Page Scrolling
			$( 'body' ).css( 'overflow', 'visible' );
			
            // Trigger resize
            $(window).trigger('resize');
		},

		popupTriggerInit: function( popup ) {
			var popupTrigger = popup.find( '.tmpcoder-popup-trigger-button' );

			if ( ! popupTrigger.length ) {
				return;
			}

			popupTrigger.on( 'click', function() {
				// Get Settings
				var settings = JSON.parse( localStorage.getItem( 'TmpcoderPopupSettings') ) || {};

				var popupTriggerType = $(this).attr( 'data-trigger' ),
					popupShowDelay = $(this).attr( 'data-show-delay'),
					popupRedirect = $(this).attr( 'data-redirect'),
					popupRedirectURL = $(this).attr( 'data-redirect-url'),
					popupID = TmpcoderPopups.getID( popup );

				if ( 'close' === popupTriggerType ) {
					settings[popupID].popup_show_again_delay = parseInt( popupShowDelay, 10 );
					settings[popupID].popup_close_time = Date.now();
				} else if ( 'close-permanently' === popupTriggerType ) {
					settings[popupID].popup_show_again_delay = parseInt( popupShowDelay, 10 );
					settings[popupID].popup_close_time = Date.now();
				} else if ( 'back' === popupTriggerType ) {
					window.history.back();
				}

				TmpcoderPopups.closePopup( popup );

				// Save Settings in Browser
				localStorage.setItem( 'TmpcoderPopupSettings', JSON.stringify( settings ) );

				if ( 'back' !== popupTriggerType && 'yes' === popupRedirect ) {
					setTimeout(function() {
						window.location.href = popupRedirectURL;
					}, 100);
				}
			});

		}, // End popupTriggerInit

		getLocalStorage: function( id ) {
			var getLocalStorage = JSON.parse( localStorage.getItem( 'TmpcoderPopupSettings' ) );

			if ( null == getLocalStorage ) {
				return false;
			}

			// Get Settings
			var settings = getLocalStorage[ id ];

			if ( null == settings ) {
				return false;
			}

			return settings;
		},

		setLocalStorage: function( popup, display ) {
			var popupID = TmpcoderPopups.getID( popup );

			// Parse Settings
			var dataSettings = JSON.parse( popup.attr( 'data-settings' ) ),
				settings = JSON.parse( localStorage.getItem( 'TmpcoderPopupSettings') ) || {};

			// Merge With Defaults
			settings[popupID] = dataSettings;

			// Set Close Time
			if ( 'hide' === display ) {
				settings[popupID].popup_close_time = Date.now();
			} else {
				settings[popupID].popup_close_time = false;
			}

			// Save Settings in Browser
			localStorage.setItem( 'TmpcoderPopupSettings', JSON.stringify( settings ) );
		},

		checkStopShowingAfterDate: function( popup ) {
			var settings = JSON.parse( popup.attr( 'data-settings' ) );

			// Current Date
			var currentDate = Date.now();

			// Stop Showing after Date
			if ( 'yes' === settings.popup_stop_after_date ) {
				if ( currentDate >= Date.parse( settings.popup_stop_after_date_select ) ) {
					return false;
				}
			}

			return true;
		},

		checkAvailability: function( id ) {
			var popup = $( '#tmpcoder-popup-id-'+ id ),
				dataSettings = JSON.parse( popup.attr( 'data-settings' ) ),
				currentURL = window.location.href;

			if ( 'yes' === dataSettings.popup_show_via_referral && -1 === currentURL.indexOf('tmpcoder_templates=user-popup') ) {
				if ( currentURL.indexOf( dataSettings.popup_referral_keyword ) == -1 ) {
					return;
				}
			}

			// If Storage not set, continue
			if ( false === TmpcoderPopups.getLocalStorage( id ) ) {
				return true;
			}

			// Popup Trigger
			var trigger = popup.find( '.tmpcoder-popup-trigger-button' ),
				triggerShowDelay = trigger.attr( 'data-show-delay' );

			// Current Date
			var currentDate = Date.now();

			// Get Settings
			var settings = TmpcoderPopups.getLocalStorage( id );

			// If delay has been changed
			if ( triggerShowDelay ) {

				var permanent = true;

				trigger.each(function() {
					var delay = $(this).attr( 'data-show-delay' );

					if ( settings.popup_show_again_delay == parseInt( delay, 10 ) ) {
						permanent = false;
					}
				});

				if ( true === permanent ) {
					return true;
				}
			} else {
				if ( settings.popup_show_again_delay != dataSettings.popup_show_again_delay ) {
					return true;
				}
			}

			// Get Dates
			var closeDate = settings.popup_close_time || 0,
				showDelay = parseInt( settings.popup_show_again_delay, 10 );

			if ( closeDate + showDelay >= currentDate ) {
				return false;
			} else {
				return true;
			}
		},

		checkAvailableDevice: function( popup, settings ) {
			var viewport = $( 'body' ).prop( 'clientWidth' );

			if ( viewport > 1024 ) {
				return Boolean(settings.popup_show_on_device);
			} else if ( viewport > 768 ) {
				return Boolean(settings.popup_show_on_device_tablet);
			} else {
				return Boolean(settings.popup_show_on_device_mobile);
			}
		},

		getID: function( popup ) {
			var id = popup.attr( 'id' );

			return id.replace( 'tmpcoder-popup-id-', '' );
		},

		// Editor Check
		editorCheck: function() {
			return $( 'body' ).hasClass( 'elementor-editor-active' ) ? true : false;
		}
	} // End TmpcoderPopups

	// Init
	TmpcoderPopups.init();

}( jQuery, window.elementorFrontend ) );
