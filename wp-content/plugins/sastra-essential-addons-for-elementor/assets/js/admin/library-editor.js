( function( $ ) {

	"use strict";

	// Elementor Editor Popup
	var TmpcoderElementorEditorPopup = {

		loaded: false,

		init: function() {
			window.elementor.on( 'preview:loaded', TmpcoderElementorEditorPopup.loadPreview );
		},

		loadPreview: function() {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/shortcode.default', function( $scope ) {
				$scope.find( '.tmpcoder-template-edit-btn' ).on( 'click', TmpcoderElementorEditorPopup.renderPopup );
			} );

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/tmpcoder-advanced-slider.default', function( $scope ) {
				$scope.find( '.tmpcoder-template-edit-btn' ).on( 'click', TmpcoderElementorEditorPopup.renderPopup );
			} );

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/tmpcoder-tabs.default', function( $scope ) {
				$scope.find( '.tmpcoder-template-edit-btn' ).on( 'click', TmpcoderElementorEditorPopup.renderPopup );
			} );

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/tmpcoder-elementor-template.default', function( $scope ) {
				$scope.find( '.tmpcoder-template-edit-btn' ).on( 'click', TmpcoderElementorEditorPopup.renderPopup );
			} );

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/tmpcoder-content-toggle.default', function( $scope ) {
				$scope.find( '.tmpcoder-template-edit-btn' ).on( 'click', TmpcoderElementorEditorPopup.renderPopup );
			} );
		},

		renderPopup: function( link ) {
			// Open Editor
			TmpcoderElementorEditorPopup.getPopup().show();

			// Render Iframe
			$( '#tmpcoder-template-editor-popup .dialog-message').html( '<iframe src="' + $( this ).data( 'permalink' ) + '&elementor' + '" id="tmpcoder-template-edit-frame" width="100%" height="100%"></iframe>' );
			
			// Preloading
			$( '#tmpcoder-template-editor-popup .dialog-message').append( '<div id="tmpcoder-template-editor-loading"><div class="elementor-loader-wrapper"><div class="elementor-loader"><div class="elementor-loader-boxes"><div class="elementor-loader-box"></div><div class="elementor-loader-box"></div><div class="elementor-loader-box"></div><div class="elementor-loader-box"></div></div></div><div class="elementor-loading-title">Loading</div></div></div>' );

			// Loaded
			$( '#tmpcoder-template-edit-frame').on( 'load', function() {
				$( '#tmpcoder-template-editor-loading').fadeOut( 300 );
			} );

			// Close
			$( '#tmpcoder-template-editor-popup .dialog-close-button' ).css({
				'right' : '30px',
				'width' : '35px',
				'height' : '35px',
				'line-height' : '30px',
				'border-radius' : '50%',
				'text-align' : 'center',
				'opacity' : '1',
				'background-color' : '#333',
				'box-shadow' : '1px 1px 3px 0 #000',
			}).html( '<i class="eicon-close"></i>');

			$( '#tmpcoder-template-editor-popup .dialog-close-button i' ).css({
				'font-size' : '15px',
				'color' : '#fff',
			})

			$( '#tmpcoder-template-editor-popup .dialog-close-button' ).on( 'click', function() {
				elementor.reloadPreview();
			});
		},

		getPopup: function() {

			if ( ! TmpcoderElementorEditorPopup.loaded ) {
				this.loaded = elementor.dialogsManager.createWidget( 'lightbox', {
					id: 'tmpcoder-template-editor-popup',
					closeButton: true,
					hide: { onBackgroundClick: false }
				} );
			}

			return TmpcoderElementorEditorPopup.loaded;
		}

	};

	$( window ).on( 'elementor:init', TmpcoderElementorEditorPopup.init );


	// Modal Popups (only for popup template editor: body.elementor-editor-tmpcoder-popup)
	var TmpcoderModalPopups = {

		init: function() {
			if ( ! $( 'body' ).hasClass( 'elementor-editor-tmpcoder-popup' ) ) {
				return;
			}

			// Add "Popup Settings" button to top toolbar (same way as Spexo AI Page Translator)
			TmpcoderModalPopups.addPopupSettingsToolbarButton();
			if ( typeof elementor !== 'undefined' && elementor.hooks ) {
				elementor.hooks.addAction( 'panel/open_editor/widget', function() {
					setTimeout( TmpcoderModalPopups.addPopupSettingsToolbarButton, 100 );
				} );
				elementor.hooks.addAction( 'navigator/init', function() {
					setTimeout( TmpcoderModalPopups.addPopupSettingsToolbarButton, 100 );
				} );
			}
			setTimeout( TmpcoderModalPopups.addPopupSettingsToolbarButton, 500 );
			setTimeout( TmpcoderModalPopups.addPopupSettingsToolbarButton, 1500 );

			// Hide "Popup Settings" tooltip when user opens document settings (footer gear or toolbar button).
			$( document ).on( 'click.tmpcoderHidePopupNotification', '#elementor-panel-footer-settings, .tmpcoder-popup-settings-toolbar-btn', TmpcoderModalPopups.hideSettingsNotification );

			// Also show tooltip when user clicks any element/widget in the editor panel (fallback
			// in case the Elementor panel hook does not fire in some contexts).
			$( document ).on(
				'click.tmpcoderShowPopupNotification',
				'#elementor-panel .elementor-element, #elementor-editor-wrapper-v2 .elementor-panel .elementor-element',
				function() {
					TmpcoderModalPopups.showSettingsNotification();
				}
			);

			// Load Preview
			window.elementor.on( 'preview:loaded', TmpcoderModalPopups.onPreviewLoad );

			// Change Preview
			window.elementor.on( 'preview:loaded', TmpcoderModalPopups.onPreviewChange );

			// Change Controls
			elementor.settings.page.model.on( 'change', TmpcoderModalPopups.onControlChange );
		},

		/**
		 * Add "Popup Settings" button to top toolbar (mirrors Spexo AI Page Translator approach).
		 * Only runs when body has elementor-editor-tmpcoder-popup (popup template editor only).
		 */
		addPopupSettingsToolbarButton: function() {
			if ( ! $( 'body' ).hasClass( 'elementor-editor-tmpcoder-popup' ) ) {
				return;
			}
			if ( $( '.tmpcoder-popup-settings-toolbar-btn' ).length > 0 ) {
				$('#elementor-editor-wrapper-v2 [value="Spexo Popup Settings"] svg').hide();
				return;
			}

			var locations = [
				{ selector: '#elementor-editor-wrapper-v2 [value="Spexo Popup Settings"]', priority: 1 },
				{ selector: '#elementor-editor-wrapper-v2 .elementor-editor-panel-tools', priority: 2 },
				{ selector: '.elementor-editor-panel-tools', priority: 3 },
				{ selector: '#elementor-panel-header', priority: 4 },
				{ selector: '.elementor-panel-header', priority: 5 }
			];
			locations.sort( function( a, b ) { return a.priority - b.priority; } );

			var buttonAdded = false;
			locations.forEach( function( loc ) {
				var $target = $( loc.selector );
				if ( ! $target.length || $target.find( '.tmpcoder-popup-settings-toolbar-btn' ).length || buttonAdded ) {
					return;
				}
				if ( $target.closest( '.elementor-panel-navigation' ).length ||
					$target.closest( '.elementor-panel-navigation-tabs' ).length ||
					$target.closest( '#elementor-panel' ).length ) {
					return;
				}

				var $btn = $( '<button class="tmpcoder-popup-settings-toolbar-btn" type="button">' +
					'<i class="eicon-cog"></i><span>Popup Settings</span></button>' );
				$btn.on( 'click', function( e ) {
					e.preventDefault();
					$( '#elementor-panel-footer-settings' ).trigger( 'click' );
				} );

				var $addButton = $target.find( '[data-tooltip="Add Element"], .elementor-panel-header-add-button, .MuiToolbar-root button:nth-child(2)' );
				if ( $addButton.length ) {
					$addButton.before( $btn );
				} else {
					$target.prepend( $btn );
				}
				buttonAdded = true;
			} );
		},

		onPreviewLoad: function() {
			// Ensure Popup Settings button in toolbar (toolbar may render after preview)
			TmpcoderModalPopups.addPopupSettingsToolbarButton();
			// Open Popup Settings (do not show tooltip here – panel is already open)
			setTimeout(function() {
				$( '#elementor-panel-footer-settings' ).trigger( 'click' );
			}, 2000);

			// Prepare notification DOM (hidden). Show only when user leaves Popup Settings (e.g. adds/edits element).
			TmpcoderModalPopups.ensureSettingsNotificationReady();
			TmpcoderModalPopups.hideSettingsNotification();
			if ( elementor.hooks ) {
				elementor.hooks.addAction( 'panel/open_editor/widget', TmpcoderModalPopups.showSettingsNotification );
			}

			// Fix Popup Layout
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function( $scope ) {
				var popup = $scope.closest( '.tmpcoder-template-popup' );

				TmpcoderModalPopups.fixPopupLayout( popup );
			} );
		},

		onPreviewChange: function() {
			// preview change code goes here
		},

		onControlChange: function( model ) {
			var iframe = document.getElementById( 'elementor-preview-iframe' ),
				iframeContent = iframe.contentDocument || iframe.contentWindow.document;

			// Popup
			var popup = $( '.tmpcoder-template-popup', iframeContent );

			// Scrollbar
			if ( model.changed.hasOwnProperty( 'popup_height' ) ) {
				// elementor.reloadPreview();
			}

			// Display As
			if ( model.changed.hasOwnProperty( 'popup_display_as' ) ) {
				if ( 'notification' === model.changed['popup_display_as'] ) {
					popup.addClass( 'tmpcoder-popup-notification' );
				} else {
					popup.removeClass( 'tmpcoder-popup-notification' );
				}
			}

			if ( model.changed.hasOwnProperty( 'popup_display_as' ) ) {

			}

			// Entrance Animation
			if ( model.changed.hasOwnProperty( 'popup_animation' ) ) {
				var popupContainer = popup.find('.tmpcoder-popup-container');

				popupContainer.removeAttr( 'class');
				popupContainer.addClass( 'tmpcoder-popup-container animated '+ model.changed['popup_animation'] );
			}
		},

		fixPopupLayout: function( popup ) {
			var settings = TmpcoderModalPopups.getDocumentSettings();

			// Add Scrollbar
			if ( ! popup.find('.tmpcoder-popup-container-inner').hasClass('ps') ) {
				const ps = new PerfectScrollbar(popup.find('.tmpcoder-popup-container-inner')[0], {
					suppressScrollX: true
				});
			}

			if ( 'notification' === settings.popup_display_as ) {
				popup.addClass( 'tmpcoder-popup-notification' );
			}
		},

		getDocumentSettings: function() {
			var documentSettings = {},
				settings = elementor.settings.page.model;

			jQuery.each(settings.getActiveControls(), function (controlKey) {
				documentSettings[controlKey] = settings.attributes[controlKey];
			});

			return documentSettings;
		},

		ensureSettingsNotificationReady: function() {
			if ( $( '#tmpcoder-template-settings-notification' ).length ) {
				return;
			}
			var nHTML = '\
				<div id="tmpcoder-template-settings-notification" style="display: none;">\
					<h4><i class="eicon-info-circle"></i><span>Please Note</span></h4>\
					<p>Click here to access <a href="#" id="tmpcoder-popup-settings-notification-link">Popup Settings</a>.</p>\
					<i class="eicon-close"></i>\
				</div>\
			';
			$( 'body' ).append( nHTML );
			$( '#tmpcoder-template-settings-notification .eicon-close' ).on( 'click', function() {
				TmpcoderModalPopups.hideSettingsNotification();
			});
			$( '#tmpcoder-popup-settings-notification-link' ).on( 'click', function( e ) {
				e.preventDefault();
				$( '#elementor-panel-footer-settings' ).trigger( 'click' );
				TmpcoderModalPopups.hideSettingsNotification();
			});
		},

		showSettingsNotification: function() {
			TmpcoderModalPopups.ensureSettingsNotificationReady();
			// $( '#tmpcoder-template-settings-notification' ).stop().hide().fadeIn();
			$( '#tmpcoder-template-settings-notification' ).stop().fadeIn();
			console.log( 'showSettingsNotification' );
		},

		hideSettingsNotification: function() {
			$( '#tmpcoder-template-settings-notification' ).stop().fadeOut();
		},

		settingsNotification: function() {
			// Legacy: show notification (used only if called elsewhere). Prefer showSettingsNotification.
			TmpcoderModalPopups.ensureSettingsNotificationReady();
			TmpcoderModalPopups.showSettingsNotification();
		},
	};

	$( window ).on( 'elementor:init', TmpcoderModalPopups.init );


	// Theme Builder
	var TmpcoderTemplateEditor = {

		init: function() {
			if ( ! $( 'body' ).hasClass( 'elementor-editor-tmpcoder-theme-builder' ) ) {
				return;
			}

			// Load Preview
			window.elementor.on( 'preview:loaded', TmpcoderTemplateEditor.onPreviewLoad );
		},

		onPreviewLoad: function() {

			// Open Popup Settings
			setTimeout(function() {
				$( '#elementor-panel-footer-settings' ).trigger( 'click' );
				// $( '#tmpcoder-template-settings-notification .eicon-close' ).trigger( 'click' );
			}, 500 );

			// Popup Settings Notification
			TmpcoderTemplateEditor.settingsNotification();

			// Submit Preview Changes
			$( '#elementor-panel-footer-settings' ).on( 'click', function() {
				setTimeout(function() {

					$( '.elementor-control-submit_preview_changes' ).on( 'click', function() {
						$( '#elementor-panel-saver-button-publish' ).trigger( 'click' );
						$( '#elementor-preview-loading' ).show();


						var saveChanges = setInterval(function() {
							if ( ! $( 'html' ).hasClass( 'nprogress-busy' ) ) {
								location.reload();
								clearInterval(saveChanges);
							}
						}, 500 );
					});
				});
			});

		},

		settingsNotification: function() {
			// Get Close Time
			var closeTime = JSON.parse( localStorage.getItem( 'TmpcoderTemplateEditorNotification') ) || {};

			if ( closeTime + 604800000 >= Date.now() ) {
				return;
			}

			// Notification HTML
			var nHTML = '\
				<div id="tmpcoder-template-settings-notification">\
					<h4><i class="eicon-info-circle"></i><span>Please Note</span></h4>\
					<p>You can change <strong>Preview Settings</strong> here.</p>\
					<i class="eicon-close"></i>\
				</div>\
			';

			setTimeout(function() {
				// Render Notification
				$( 'body' ).append( nHTML ).hide().fadeIn();

				// Set Close Time
				$( document ).on( 'click', function() {
					$( '#tmpcoder-template-settings-notification' ).fadeOut();
				});

				// Hide on Click
				$( '#tmpcoder-template-settings-notification .eicon-close' ).on( 'click', function() {
					$( '#tmpcoder-template-settings-notification' ).fadeOut();

					// Save Close Time in Browser
					localStorage.setItem( 'TmpcoderTemplateEditorNotification', Date.now() );
				});

			}, 1000 );
		},
	};

	$( window ).on( 'elementor:init', TmpcoderTemplateEditor.init );

}( jQuery ) );
