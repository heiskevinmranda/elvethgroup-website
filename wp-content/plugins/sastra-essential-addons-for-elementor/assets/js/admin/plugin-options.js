jQuery(document).ready(function( $ ) {
	"use strict";

	// Track whether the admin menu (sidebar) was folded before opening Popup Builder.
	var tmpcoderAdminMenuWasFolded = null;

	function tmpcoderCollapseAdminMenuForPopup() {
		var $body = $( 'body' );

		// Capture the initial state only once per open.
		if ( tmpcoderAdminMenuWasFolded === null ) {
			tmpcoderAdminMenuWasFolded = $body.hasClass( 'folded' );
		}

		// If it was not folded before, fold it while the popup library is open.
		if ( ! tmpcoderAdminMenuWasFolded ) {
			$body.addClass( 'folded' );
		}
	}

	function tmpcoderRestoreAdminMenuAfterPopup() {
		var $body = $( 'body' );

		// Nothing to restore if we never captured state.
		if ( tmpcoderAdminMenuWasFolded === null ) {
			return;
		}

		// If the menu was not folded before, remove the class we may have added.
		if ( ! tmpcoderAdminMenuWasFolded ) {
			$body.removeClass( 'folded' );
		}

		// Reset so the next open records a fresh state.
		tmpcoderAdminMenuWasFolded = null;
	}

	// Condition Selects
	var globalS  = '.target_rule-condition',
		archiveS = '.archives-condition-select',
		singleS  = '.singles-condition-select',
		inputIDs = '.tmpcoder-condition-input-ids';

	// Condition Popup
	var conditionPupup = $( '.tmpcoder-condition-popup-wrap' );

	// Holds selected prebuilt template info for Popup Builder create flow.
	var tmpcoderPrebuiltSelection = null;

	// Track whether we are in the new Popup Builder combined flow.
	var isPopupBuilderFlow = function() {
		return $('.tmpcoder-popup-builder-page').length && currentTab && currentTab.replace(/\s/g, '_').toLowerCase() === 'type_popup';
	};

	// Current Tab
	var currentTab = $('.tmpcoder-nav-tab-wrapper .nav-tab-active').attr( 'data-title' );
		if ( currentTab ) {
			currentTab = currentTab.trim().toLowerCase(),
			currentTab = currentTab.replace(' ', '_');
		}

	/*
	** Get Active Filter -------------------------
	*/
	function getActiveFilter() {

		var type = currentTab.replace(' ', '_');

		if ( $('.template-filters').length > 0 ) {
			type = $('.template-filters .active-filter').last().attr('data-class');
			type = type.substring( 0, type.length - 1);
		}
		return type;
	}

	/*
	** Render User Template -------------------------
	*/
	function renderUserTemplate( type, title, slug, id ) {
		var html = '';

		html += '<li class="tmpcoder-template-item">';
			html += '<div class="tmpcoder-template-card-inner">';
				html += '<div class="tmpcoder-template-card-text">';
					html += '<div class="tmpcoder-template-title-row">';
						html += '<h3 class="tmpcoder-title">'+ title +'</h3>';
						html += '<span class="tmpcoder-template-draft-tag">Draft</span>';
					html += '</div>';
					html += '<p class="tmpcoder-conditions-note tmpcoder-inactive-label">Inactive Template</p>';
				html += '</div>';
				html += '<div class="tmpcoder-action-buttons">';
					html += '<span class="tmpcoder-template-conditions button-primary" data-slug="'+ slug +'" id="current-layout-'+ id +'">Manage Conditions</span>';
					html += '<a href="post.php?post='+ id +'&action=elementor" class="tmpcoder-edit-template button-primary">Edit Template</a>';
					html += '<span class="tmpcoder-delete-template button-primary" data-slug="'+ slug +'" data-warning="Are you sure you want to delete this template?"><span class="dashicons dashicons-trash"></span></span>';
				html += '</div>';
			html += '</div>';
		html += '</li>';

		// Render
		$( '.tmpcoder-my-templates-list.tmpcoder-'+ getActiveFilter() +'-templates-list' ).prepend( html );

		if ( $('.tmpcoder-empty-templates-message').length ) {
			$('.tmpcoder-empty-templates-message').remove();
		}

		// Run Functions
		changeTemplateConditions();
		deleteTemplate();
	}

	/*
	** Create User Template -------------------------
	*/
	function createUserTemplate() {
		// Get Template Library
		var library = 'type_global_template' === getActiveFilter() ? 'elementor_library' : TmpcoderPluginOptions.post_type;
		// Get Template Title

		var title = $('.tmpcoder-user-template-title').val();
		
		// Get Template Slug
		var slug = 'user-'+ getActiveFilter() +'-'+ title.replace( /\W+/g, '-' ).toLowerCase();

		if ( 'elementor_library' === library ) {
			slug = getActiveFilter() +'-'+ title.replace( /\W+/g, '-' ).toLowerCase();
		}

		add_loader('create_btn');

		// AJAX Data
		var data = {
			action: 'tmpcoder_create_template',
			nonce: TmpcoderPluginOptions.nonce,
			user_template_library: library,
			user_template_title: title,
			user_template_slug: slug,
			user_template_type: getActiveFilter(),
		};

		// Create Template
		$.post(ajaxurl, data, function(response) {
			// Close Popup
			remove_loader('create_btn');

			var id = response.substring( 0, response.length - 1 );
			if (!id) {
				$('.tmpcoder-create-template').before('<p class="tmpcoder-fill-out-the-title"><em>'+TmpcoderPluginOptions.valid_name_msg+'</em></p>');
				$('.tmpcoder-fill-out-the-title').css('margin-top', '4px');
				$('.tmpcoder-fill-out-the-title em').css({'color': '#ff3333', 'font-size': 'smaller'});
				$('.tmpcoder-fill-out-the-title').fadeOut(3000);
				return false;
			}
			
			$('.tmpcoder-user-template-popup-wrap').fadeOut();

			// Open Conditions
			setTimeout(function() {
				// Get Template ID

				if ( 'type_global_template' === currentTab.replace( /\W+/g, '-' ).toLowerCase() ) {
					var url = TmpcoderPluginOptions.admin_url+'post.php?post='+ id +'&action=elementor';
					url = TmpcodersanitizeURL(url);
					window.location.href = url;
					return;
				}

				// Set Template Slug & ID
				$('.tmpcoder-save-conditions').attr('data-slug', slug).attr('data-id', id);

				// Render Template
				renderUserTemplate(getActiveFilter(),$('.tmpcoder-user-template-title').val(), slug, id);

				if ( $('.tmpcoder-no-templates').length ) {
					$('.tmpcoder-no-templates').hide();
				}

				// Open Popup
				// openConditionsPopup( slug );
				openConditionsPopup( id );
				conditionPupup.addClass( 'editor-redirect' );

				// For Popup Builder create flow, attach any selected prebuilt template info to the save button.
				if ( currentTab && currentTab.replace(/\s/g,'_').toLowerCase() === 'type_popup' && $('.tmpcoder-popup-builder-page').length ) {
					$('.tmpcoder-save-conditions').data('prebuilt-template', tmpcoderPrebuiltSelection || null);
					tmpcoderPrebuiltSelection = null;
				}
			}, 500);
		});
	}

	function add_loader(type){
		if (type == 'create_btn')
		{
			var create_btn_text = $('.tmpcoder-create-template').text();
			$('.tmpcoder-create-template').text(create_btn_text + ' . . .');
			$('.tmpcoder-create-template').css('opacity','0.5');
			$('.tmpcoder-create-template').css('pointer-events','none');
		}

		if (type == 'save_btb')
		{
			var save_btn_text = $('.tmpcoder-save-conditions').text();
			$('.tmpcoder-save-conditions').text(save_btn_text+' . . .');
			$('.tmpcoder-save-conditions').css('opacity','0.5');
			$('.tmpcoder-save-conditions').css('pointer-events','none');
		}
	}

	function remove_loader(type){
		if (type == 'create_btn')
		{
			$('.tmpcoder-create-template').text('Create Template');
			$('.tmpcoder-create-template').css('opacity','1');
			$('.tmpcoder-create-template').removeAttr('style');
		}
		if (type == 'save_btb')
		{
			$('.tmpcoder-save-conditions').text('SAVE CONDITIONS');
			$('.tmpcoder-save-conditions').css('opacity','1');
			$('.tmpcoder-save-conditions').removeAttr('style');
		}
	}

	// Open Popup
	$('.tmpcoder-user-template').on( 'click', function() {
		// For Popup Builder: first open prebuilt library instead of name popup.
		if ( $(this).hasClass('tmpcoder-create-popup-btn') && isPopupBuilderFlow() ) {
			// Ensure library wrapper exists; then open Popups tab by default.
			if ( $('#tmpcoder-popup-library-wrap').length ) {
				// Collapse admin menu while the popup library is open.
				tmpcoderCollapseAdminMenuForPopup();

				$('#tmpcoder-popup-library-wrap')
					.attr('data-post-id', '') // not created yet
					.show();
				// if ( typeof tmpcoderPopupLibraryLoadTab === 'function' ) {
					// tmpcoderPopupLibraryLoadTab('popups');
					$('.tmpcoder-popup-library-tab[data-tab="popups"]').click();
				// }
			}
			openConditionsPopup(1);
			$('.tmpcoder-condition-popup .close-popup').trigger('click');
			return;
		}

		if ( $(this).find('div').length ) {
			alert('Please Install/Activate WooCommerce!');
			return;
		}

		$('.tmpcoder-user-template-title').val('');
		$('.tmpcoder-user-template-popup-wrap').fadeIn();
	});

	// Close Popup
	$('.tmpcoder-user-template-popup').find('.close-popup').on( 'click', function() {
		$('.tmpcoder-user-template-popup-wrap').fadeOut();
	});

	// Create - Click
	$('.tmpcoder-create-template').on( 'click', function() {
		if ( '' === $('.tmpcoder-user-template-title').val() ) {
			$('.tmpcoder-user-template-title').css('border-color', 'red');
			if ( $('.tmpcoder-fill-out-the-title').length < 1 ) {
				$('.tmpcoder-create-template').before('<p class="tmpcoder-fill-out-the-title"><em>Please fill the Title field.</em></p>');
				$('.tmpcoder-fill-out-the-title').css('margin-top', '4px');
				$('.tmpcoder-fill-out-the-title em').css({'color': '#ff3333', 'font-size': 'smaller'});
			}
		} else {
			$('.tmpcoder-user-template-title').removeAttr('style');
			$('.tmpcoder-create-template + p').remove();

			// Create Template
			createUserTemplate();
		}
	});

	// Create - Enter Key
	$('.tmpcoder-user-template-title').keypress(function(e) {
		if ( e.which == 13 ) {
			e.preventDefault();
			createUserTemplate();
		}
	});


	/*
	** Reset Template -------------------------
	*/
	function deleteTemplate() {
		$('.tmpcoder-delete-template').on('click', function () {
			var deleteButton = $(this);
			var slug = deleteButton.data('slug');
			var nonce = deleteButton.data('nonce');
	
			// Store data in popup
			$('.tmpcoder-delete-template-confirm-popup-wrap')
				.data('slug', slug)
				.data('nonce', nonce)
				.data('button', deleteButton)
				.fadeIn();
		});
	
		// Cancle delete template
		$('.tmpcoder-delete-template-popup').find('.tmpcoder-delete-template-confirm-popup-close').on( 'click', function() {
			$('.tmpcoder-delete-template-confirm-popup-wrap').fadeOut();
		});
	
		// Confirm delete template
		$('.tmpcoder-delete-template-confirm-button').on('click', function () {
			var popup = $('.tmpcoder-delete-template-confirm-popup-wrap');
			var slug = popup.data('slug');
			var nonce = popup.data('nonce');
			var deleteButton = popup.data('button');
			
			// Get Template Library
			var library = 'type_global_template' === getActiveFilter() ? 'elementor_library' : TmpcoderPluginOptions.post_type;
	
			deleteButton.closest('li').css({
				opacity: '0.5',
				pointerEvents: 'none'
			});
	
			var data = {
				action: 'tmpcoder_delete_template',
				template_slug: slug,
				template_library: library,
				nonce: nonce,
			};
	
			// Delete via AJAX
			$.post(ajaxurl, data, function () {
				deleteButton.closest('li').remove();
	
				setTimeout(function () {
					if ($('.tmpcoder-my-templates-list li').length === 0) {
						$('.tmpcoder-my-templates-list').append('<li class="tmpcoder-no-templates">You don\'t have any templates yet!</li>');
					}
				}, 500);
			});
	
			// Delete associated Conditions
			if ( 'type_global_template' !== getActiveFilter() ) {
				var conditions = JSON.parse($( '#tmpcoder_'+ currentTab +'_conditions' ).val());
				delete conditions[slug];
	
				// Set Conditions
				$('#tmpcoder_'+ currentTab +'_conditions').val( JSON.stringify(conditions) );
	
				// AJAX Data
				var saveData = {
					action: 'tmpcoder_save_template_conditions',
					nonce: TmpcoderPluginOptions.nonce,
				};
				saveData['tmpcoder_' + currentTab + '_conditions'] = JSON.stringify(conditions);
	
				$.post(ajaxurl, saveData);
			}
	
			// Close popup
			popup.fadeOut();
		});
	}

	deleteTemplate();

	/*
	** Condition Popup -------------------------
	*/
	// Open Popup
	function changeTemplateConditions() {

		$( '.tmpcoder-template-conditions' ).on( 'click', function() {
			var template = $(this).attr('data-slug');
			var template_conditions = $(this).attr('data-conditions');

			// Set Template Slug
			$( '.tmpcoder-save-conditions' ).attr( 'data-slug', template );

			// Open Popup
			var current_object = $(this);
			openConditionsPopup( template, template_conditions,current_object );
		});		
	}

	changeTemplateConditions();

	// Close Popup
	conditionPupup.find('.close-popup').on( 'click', function() {
		conditionPupup.fadeOut();
	});

	/*
	** Popup: Clone Conditions -------------------------
	*/
	function popupCloneConditions() {
		// Clone
		$('.tmpcoder-conditions-wrap').append( '<div class="tmpcoder-conditions">'+ $('.tmpcoder-conditions-sample').html() +'</div>' );

		// Add Tab Class
		// why removing and adding again ?
		$('.tmpcoder-target-rule-condition').removeClass( 'tmpcoder-tab-'+ currentTab ).addClass( 'tmpcoder-tab-'+ currentTab );
		var clone = $('.tmpcoder-target-rule-condition').last();

		// Reset Extra
		clone.find('select').not(':first-child').hide();

		// Entrance Animation
		clone.hide().fadeIn();

		// Hide Extra Options
		var currentFilter = $('.template-filters .active-filter').attr('data-class');

		
		if (clone.hasClass('tmpcoder-tab-product_single')) {
			setTimeout(function() {
				clone.find('.tmpcoder-condition-input-ids').each(function() {
					if ( !($(this).val()) ) {
						$(this).val('all').show();
					}
				});
			}, 600);
		}

		if ( 'blog-posts' === currentFilter || 'custom-posts' === currentFilter ) {
			clone.find('.singles-condition-select').children(':nth-child(1),:nth-child(2),:nth-child(3)').remove();
			clone.find('.tmpcoder-condition-input-ids').val('all').show();
		} else if ( 'woocommerce-products' === currentFilter ) {
			clone.find('.singles-condition-select').children().filter(function() {
				return 'product' !== $(this).val()
			}).remove();
			clone.find('.tmpcoder-condition-input-ids').val('all').show();
		} else if ( '404-pages' === currentFilter ) {
			clone.find('.singles-condition-select').children().filter(function() {
				return 'page_404' !== $(this).val()
			}).remove();
		} else if ( 'blog-archives' === currentFilter || 'custom-archives' === currentFilter ) {
			clone.find('.archives-condition-select').children().filter(function() {
				return 'products' == $(this).val() || 'product_cat' == $(this).val() || 'product_tag' == $(this).val();
			}).remove();
		} else if ( 'woocommerce-archives' === currentFilter ) {
			clone.find('.archives-condition-select').children().filter(function() {
				return 'products' !== $(this).val() && 'product_cat' !== $(this).val() && 'product_tag' !== $(this).val();
			}).remove();
		}
	}

	/*
	** Popup: Add Conditions -------------------------
	*/
	function popupAddConditions() {
		$( '.tmpcoder-add-conditions' ).on( 'click', function() {
			// Clone

			popupCloneConditions();

			// Reset
			$('.tmpcoder-conditions').last().find('input').hide();//tmp -maybe remove

			// Show on Canvas
			if ( 'type_header' === currentTab || 'type_footer' === currentTab ) {
				$('.tmpcoder-canvas-condition').show();
			}

			// Run Functions
			popupDeleteConditions();
			popupMainConditionSelect();
			popupSubConditionSelect();
		});
	}

	popupAddConditions();

	/*
	** Popup: Set Conditions -------------------------
	*/
	function popupSetConditions( template ) {
		var conditions = $( '#tmpcoder_'+ currentTab +'_conditions' ).val();

		if (conditions != undefined)
		{
			conditions = '' !== conditions ? JSON.parse(conditions) : {};
		}
		// Reset
		$('.tmpcoder-conditions').remove();

		// Setup Conditions
		if ( conditions[template] != undefined && conditions[template].length > 0 ) {
			// Clone
			for (var i = 0; i < conditions[template].length; i++) {
				popupCloneConditions();
				$( '.tmpcoder-conditions' ).find('select').hide();
			}

			// Set
			if ( $('.tmpcoder-conditions').length ) {
				$('.tmpcoder-conditions').each( function( index ) {
					var path = conditions[template][index].split( '/' );

					for (var s = 0; s < path.length; s++) {
						if ( s === 0 ) {
							$(this).find(globalS).val(path[s]).trigger('change');
							$(this).find('.'+ path[s] +'s-condition-select').show();
						} else if ( s === 1 ) {
							path[s-1] = 'product_archive' === path[s-1] ? 'archive' : path[s-1];
							$(this).find('.'+ path[s-1] +'s-condition-select').val(path[s]).trigger('change');
						} else if ( s === 2 ) {
							$(this).find(inputIDs).val(path[s]).trigger('keyup').show();
						}
					}
				});
			}
		}

		// Set Show on Canvas Switcher value
		var conditionsBtn = $('.tmpcoder-template-conditions[data-slug='+ template +']');

		if ( 'true' === conditionsBtn.attr('data-show-on-canvas') ) {
			$('.tmpcoder-canvas-condition').find('input[type=checkbox]').attr('checked', 'checked');
		} else {
			$('.tmpcoder-canvas-condition').find('input[type=checkbox]').removeAttr('checked');
		}
	}

	/*
	** Popup: Open -------------------------
	*/
	function openConditionsPopup( template, template_conditions, current_object ) {

        if (!current_object){
        	var id = template;
        }
        else
        {
        	var id = current_object.attr('data-id');
        }

        $('.tmpcoder-save-conditions').attr('data-id', id);
        var layout_type = $('.tmpcoder-layout-tabs .nav-tab-active').attr('data-title');
		
		// AJAX Data
		var data = {
			action: 'tmpcoder_select_popup_conditions',
			nonce: TmpcoderPluginOptions.nonce,
			template_id: id,
            layout_type: layout_type,
		};		

		jQuery.ajax({
	        url:ajaxurl,
	        method:'POST',
	        data:data,
	        beforeSend: function() {
				var conditionContent = conditionPupup.find('.tmpcoder-options-row-content');
	       		conditionContent.html($('.popup-loader-html').html());
				var $loader = conditionContent.find('.tmpcoder-template-conditions-loader').first();
				if (window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.show === 'function') {
					window.tmpcoderCommonLoader.show('.tmpcoder-condition-popup-wrap .tmpcoder-options-row-content .tmpcoder-template-conditions-loader');
				} else {
					$loader.removeAttr('hidden').css('display', 'block');
				}
				conditionContent.css('height','100px');
				$('.tmpcoder-save-conditions').css('pointer-events','none');
	        }
	    })
	    .done( function( response ) {
			var conditionContent = conditionPupup.find('.tmpcoder-options-row-content');
				conditionPupup.find('.tmpcoder-template-conditions-loader').attr('hidden', true).css('display', '');
	       		conditionContent.html(response);
				conditionContent.find('.target_rule-add-exclusion-rule').addClass('tmpcoder-hidden');
			window.cloneCondition();
			window.deleteFunction();
			window.targetField();
			$('.tmpcoder-save-conditions').removeAttr('style');
	    })
	    .fail( function( error ) {
			conditionPupup.find('.tmpcoder-template-conditions-loader').attr('hidden', true).css('display', '');
	        console.log(error);
	    })

		// Open Popup
		conditionPupup.fadeIn(); 
	}

	/*
	** Popup: Delete Conditions -------------------------------
	*/
	function popupDeleteConditions() {
		$( '.tmpcoder-delete-template-conditions' ).on( 'click', function() {
			var current = $(this).parent(),
				conditions = $( '#tmpcoder_'+ currentTab +'_conditions' ).val();
				conditions = '' !== conditions ? JSON.parse(conditions) : {};

			// Update Conditions
			$('#tmpcoder_'+ currentTab +'_conditions').val( JSON.stringify( removeConditions( conditions, getConditionsPath(current) ) ) );

			// Remove Conditions
			current.fadeOut( 500, function() {
				$(this).remove();

				// Show on Canvas
				if ( 0 === $('.tmpcoder-conditions').length ) {
					$('.tmpcoder-canvas-condition').hide();
				}
			});
		});
	}


	/*
	** Popup: Condition Selection -------
	*/
	// General Condition Select
	function popupMainConditionSelect() {
		$(globalS).on( 'change', function() {
			var current = $(this).parent();

			// Reset
			// current.find(archiveS).hide();
			// current.find(singleS).hide();
			// current.find(inputIDs).hide();

			// Show
			current.find( '.'+ $(this).val() +'s-condition-select' ).show();

		});
	}

	// Sub Condition Select
	function popupSubConditionSelect() {
		$('.archives-condition-select, .singles-condition-select, .target_rule-condition').on( 'change', function() {
			var current = $(this).parent(),
				selected = $( 'option:selected', this ),
				value = $(this).val();

			// Show Custom ID input
			if ( selected.hasClass('custom-ids') || selected.hasClass('custom-type-ids') ) {
				current.find(inputIDs).val('all').trigger('keyup').show();
			} else {
				current.find(inputIDs).hide();
			}

			console.log(value);

			// Show/Hide Expert Notice
			if ( 0 === value.indexOf('pro-') ) {
				$('.tmpcoder-expert-notice').show();
			} else {
				$('.tmpcoder-expert-notice').hide();
			}
		});
	}

	// Show on Canvas Switcher
	function showOnCanvasSwitcher() {
		$('.tmpcoder-canvas-condition input[type=checkbox]').on('change', function() {
			$('.tmpcoder-template-conditions[data-slug='+ $('.tmpcoder-save-conditions').attr('data-slug') +']').attr('data-show-on-canvas', $(this).prop('checked'));
		});
	}

	/*
	** Remove Conditions --------------------------
	*/
	function removeConditions( conditions, path ) {
		var data = [];

		// Get Templates
		$('.tmpcoder-template-conditions').each(function() {
			data.push($(this).attr('data-slug'))
		});

		// Loop
		for ( var key in conditions ) {
			if ( conditions.hasOwnProperty(key) ) {
				// Remove Duplicate
				for (var i = 0; i < conditions[key].length; i++) {
					if ( path == conditions[key][i] ) {
						if ( 'popup' !== getActiveFilter() ) {
							conditions[key].splice(i, 1);
						}
					}
				};

				// Clear Database
				if ( data.indexOf(key) === -1 ) {
					delete conditions[key];
				}
			}
		}

		return conditions;
	}

	/*
	** Get Conditions Path -------------------------
	*/
	function getConditionsPath( current ) {
		var path = '';

		// Selects
		var global = 'none' !== current.find(globalS).css('display') ?  current.find(globalS).val() : currentTab,
			archive = current.find(archiveS).val(),
			single = current.find(singleS).val(),
			customIds = current.find(inputIDs);

		if ( 'archive' === global || 'product_archive' === global ) {
			if ( 'none' !== customIds.css('display') ) {
				path = global +'/'+ archive +'/'+ customIds.val();
			} else {
				path = global +'/'+ archive;
			}
		} else if ( 'single' === global || 'product_single' === global ) {
			if ( 'none' !== customIds.css('display') ) {
				path = global +'/'+ single +'/'+ customIds.val();
			} else {
				path = global +'/'+ single;
			}
		} else {
			path = 'global';
		}

		return path;
	}

	/*
	** Get Conditions -------------------------
	*/

	function getConditions( template, conditions ) {
		// Conditions
		conditions = ('' === conditions || '[]' === conditions) ? {} : JSON.parse(conditions);
		conditions[template] = [];
		var includeArr = [];

		$('.target_rule-condition').each( function(index) {
			includeArr.push($(this).val());
		});

		return includeArr;
	}

	/*
	** Save Conditions -------------------------
	*/

	function saveConditions() {
		$( '.tmpcoder-save-conditions' ).on( 'click', function() {
			var proActive = (1 === $('.tmpcoder-my-templates-list').data('pro')) ? true : false;

			// Current Template
			var template = $(this).attr('data-slug'),
				TemplateID = $(this).attr('data-id');

			// Get Conditions
			var conditions = getConditions( template, $( '#tmpcoder_'+ currentTab +'_conditions' ).val() );

			// Don't save if not active
			if (conditions != '') {

				if ( !proActive ) {
					
					if ( "basic-global" != conditions || 'undefined' == typeof conditions) {
						alert('Please select "Entire Site" to continue! Mutiple and custom conditions are fully supported in the Pro version.');
						return;
					}
				}
			}


			// Set Conditions
			$('#tmpcoder_'+ currentTab +'_conditions').val( JSON.stringify(conditions) );

            var specific_condition = [];
            $('select.target_rule-specific-page').each(function(){
                specific_condition.push( $(this).val() );
            });

			// AJAX Data
			var data = {
				action: 'tmpcoder_save_template_conditions',
				nonce: TmpcoderPluginOptions.nonce,
				template: template
			};
			
			// data['bsf-target-rules-location[rule]'] = conditions;
            data['bsf-target-rules-location'] = {};
            data['bsf-target-rules-location']['rule'] = [];
            data['bsf-target-rules-location']['rule'] = conditions;
            data['bsf-target-rules-location']['specific'] = [];
            data['bsf-target-rules-location']['specific'] = specific_condition;
            data['bsf-target-rules-location'] = JSON.stringify(data['bsf-target-rules-location']);

			data['tmpcoder_'+ currentTab +'_conditions'] = conditions;

			add_loader('save_btb');

			$.post(ajaxurl, data, function(response) {
				if ( typeof response === 'string' ) {
					try { response = JSON.parse(response); } catch (e) { response = {}; }
				}
				conditionPupup.fadeOut();
				remove_loader('save_btb');
				$('#current-layout-'+TemplateID).attr('data-conditions', JSON.stringify(conditions));
				$('#current-layout-'+TemplateID).attr('data-specific', JSON.stringify(specific_condition));

				var $li = $('#current-layout-'+TemplateID).closest('li.tmpcoder-template-item');
				if ( $li.length && response && response.success && response.data ) {
					var d = response.data;
					$li.toggleClass('tmpcoder-template-active', !!d.is_active);

					var $text = $li.find('.tmpcoder-template-card-text');
					if ( $text.length ) {
						var $titleRow = $text.find('.tmpcoder-template-title-row');
						if ( $titleRow.length ) {
							$titleRow.find('.tmpcoder-template-draft-tag').remove();
							if ( ! d.is_active ) {
								$titleRow.append('<span class="tmpcoder-template-draft-tag">Draft</span>');
							}
						}

						$text.find('.tmpcoder-template-conditions-summary, .tmpcoder-conditions-note, .tmpcoder-inactive-label').remove();

						var toArr = function (x) { return Array.isArray(x) ? x : (x && typeof x === 'object' ? Object.values(x) : []); };
						var inc = toArr(d.conditions_summary && d.conditions_summary.include);
						var exc = toArr(d.conditions_summary && d.conditions_summary.exclude);
						var lbl = d.labels || { include: 'Active Conditions:', exclude: 'Condition Excluded:' };
						var esc = function (s) { return (s && typeof s === 'string') ? String(s).replace(/</g,'&lt;').replace(/>/g,'&gt;') : ''; };

						if ( inc.length ) {
							$text.append('<p class="tmpcoder-conditions-note">' + (lbl.include || 'Active Conditions:') + ' <strong>' + inc.map(esc).join(', ') + '</strong></p>');
						}

						if ( exc.length ) {
							$text.append('<p class="tmpcoder-conditions-note">' + (lbl.exclude || 'Condition Excluded:') + ' <strong>' + exc.map(esc).join(', ') + '</strong></p>');
						}

						if ( ! d.is_active ) {
							$text.append('<p class="tmpcoder-conditions-note tmpcoder-inactive-label">Inactive Template</p>');
						}
					}
				}

				// After create + conditions: for popup builder optionally import selected prebuilt, others redirect to editor
				if ( conditionPupup.hasClass('editor-redirect') ) {
					conditionPupup.removeClass('editor-redirect');
					var isPopup = ( currentTab && currentTab.replace(/\s/g,'_').toLowerCase() === 'type_popup' );
					if ( isPopup && $('.tmpcoder-popup-builder-page').length ) {
						// If a prebuilt template was selected in Step 1, import it now, then go to Elementor.
						var prebuilt = $('.tmpcoder-save-conditions').data('prebuilt-template') || null;
						var redirectUrl = TmpcoderPluginOptions.admin_url+'post.php?post='+ TemplateID +'&action=elementor';
						redirectUrl = TmpcodersanitizeURL ? TmpcodersanitizeURL(redirectUrl) : redirectUrl;

						if ( prebuilt && prebuilt.module && prebuilt.slug ) {
							$.post(ajaxurl, {
								action:        'tmpcoder_import_prebuilt_into_popup',
								nonce:         TmpcoderPluginOptions.nonce,
								post_id:       TemplateID,
								template_slug: prebuilt.fullSlug || (prebuilt.module + '/' + prebuilt.slug),
								kit:           prebuilt.kit || '',
								section:       prebuilt.section || ''
							}).always(function() {
								window.location.href = redirectUrl;
							});
						} else {
							window.location.href = redirectUrl;
						}
					} else {
						var url = TmpcoderPluginOptions.admin_url+'post.php?post='+ TemplateID +'&action=elementor';
						url = TmpcodersanitizeURL(url);
						window.location.href = url;
					}
					return;
				}
			});
		});		
	}
	
	saveConditions();

	/*
	** Popup Builder: Prebuilt Library modal (after Save Conditions)
	*/
	if ( $('.tmpcoder-popup-builder-page').length && $('#tmpcoder-popup-library-wrap').length ) {

		var tmpcoderPopupMacy = null;
		var tmpcoderPopupMacyRecalcTimer = null;
		var tmpcoderPopupMacyRevealTimer = null;

		function tmpcoderSchedulePopupMacyReflow( onStable ) {
			if ( ! tmpcoderPopupMacy ) {
				if ( typeof onStable === 'function' ) {
					onStable();
				}
				return;
			}

			if ( tmpcoderPopupMacyRecalcTimer ) {
				clearTimeout( tmpcoderPopupMacyRecalcTimer );
			}
			tmpcoderPopupMacyRecalcTimer = setTimeout( function() {
				tmpcoderPopupMacyRecalcTimer = null;
				try {
					tmpcoderPopupMacy.recalculate( true );
				} catch ( e ) {}
			}, 60 );

			if ( typeof onStable === 'function' ) {
				if ( tmpcoderPopupMacyRevealTimer ) {
					clearTimeout( tmpcoderPopupMacyRevealTimer );
				}
				tmpcoderPopupMacyRevealTimer = setTimeout( function() {
					tmpcoderPopupMacyRevealTimer = null;
					onStable();
				}, 180 );
			}
		}

		// Ensure overlay is attached directly to <body> so it is not clipped by admin layout.
		(function() {
			var $wrap = $('#tmpcoder-popup-library-wrap');
			if ( $wrap.parent()[0] !== document.body ) {
				$wrap.appendTo('body');
			}
		})();

		// Expose loader so it can be used from the create-popup button handler.
		var tmpcoderPopupLibraryLoadTabInProgress = false;
		var tmpcoderPopupLibraryLoadTabPending = null;

		var tmpcoderPopupLibraryLoadTab = function(tab) {
			var $wrap    = $('#tmpcoder-popup-library-wrap');
			var $content = $wrap.find('.tmpcoder-popup-library-content');
			var $loading = $wrap.find('.tmpcoder-popup-library-loading');

			tmpcoderPopupLibraryLoadTabInProgress = true;

			$content.find('.tmpcoder-tplib-sidebar, .tmpcoder-tplib-template-gird, .tmpcoder-tplib-template-gird-inner').remove();
			$loading.show();

			var isBlocks = ( tab === 'blocks' );
			var action   = isBlocks ? 'tmpcoder_render_library_templates_blocks' : 'tmpcoder_render_library_templates_popups';

			$.post( ajaxurl, { action: action }, function( html ) {

				// Inject the full sidebar + grid markup exactly as returned (contains built-in search & category filters).
				$content.append( html );

				// Hide sidebar until loader is finished and grid is ready,
				// so it doesn't flash in before blocks are laid out.
				var $sidebar  = $content.find('.tmpcoder-tplib-sidebar');
				$sidebar.css({ opacity: 0, visibility: 'hidden' });

				var $gridInner = $content.find('.tmpcoder-tplib-template-gird-inner').first();
				var $blocksLibraryWrap = $gridInner.closest('.tmpcoder-prebuild-blocks-library-page');
				var gridRevealed = false;
				var revealGridWhenReady = function() {
					if ( gridRevealed ) {
						return;
					}
					gridRevealed = true;
					if ( isBlocks && $blocksLibraryWrap.length ) {
						$blocksLibraryWrap.addClass('tmpcoder-blocks-layout-ready');
					} else {
						$gridInner.css('opacity', '');
					}
					$sidebar.css({ opacity: '', visibility: '' });
					$loading.hide();
				};

				// Ensure only one "Create from Scratch" card (remove any existing in this grid).
				$gridInner.find('.tmpcoder-popup-library-scratch-card').remove();

                // Add "Create from Scratch" card into the grid.
                var $scratchCard = $(
					'<div class="tmpcoder-popup-library-scratch-card">' +
						'<div class="tmpcoder-tplib-template tmpcoder-tplib-template-scratch" data-create-scratch="1">' +
							'<div class="tmpcoder-tplib-template-media">' +
								'<span class="dashicons dashicons-plus-alt2"></span>' +
								'<span>' + ( TmpcoderPluginOptions.create_from_scratch_label || 'Create from Scratch' ) + '</span>' +
								'<p>' + ( TmpcoderPluginOptions.create_from_scratch_desc || 'Start with a blank canvas.' ) + '</p>' +
							'</div>' +
						'</div>' +
					'</div>'
				);
				if ( $gridInner.length ) {
					$gridInner.prepend( $scratchCard );
				}

				// Lazy-load thumbnails: keep spinner/placeholder as initial src,
				// then swap in the real preview image once it has fully loaded.
				$content.find('.tmpcoder-lazyload-image').each(function() {
					var $img    = $(this);
					var realSrc = $img.attr('data-src');
					if ( ! realSrc ) {
						return;
					}

					var loaderSrc = $img.attr('src') || '';

					var preloader = new Image();
					preloader.onload = function() {
						$img.attr('src', realSrc);
						tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
					};
					preloader.onerror = function() {
						// Keep the loader/placeholder on error.
						if ( loaderSrc ) {
							$img.attr('src', loaderSrc );
						}
						// Even on broken image URLs, keep layout progressing.
						tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
					};
					preloader.src = realSrc;
				});

				// Filters
				$('.tmpcoder-tplib-filters').on('click', function(){
					if ( '0' == $('.tmpcoder-tplib-filters-list').css('opacity') ) {
						$('.tmpcoder-tplib-filters-list').css({
							'opacity' : '1',
							'visibility' : 'visible'
						});
					} else {
						$('.tmpcoder-tplib-filters-list').css({
							'opacity' : '0',
							'visibility' : 'hidden'
						});
					}
				});
				
				// Bind category filters (existing UI inside .tmpcoder-tplib-sidebar).
				$content.find('.tmpcoder-tplib-filters-list ul li').off('click.tmpcoderFilter').on('click.tmpcoderFilter', function() {
					var current = $(this).attr('data-filter');

					if ( 'all' === current ) {
						$content.find('.tmpcoder-tplib-template').parent().show();
					} else {
						$content.find('.tmpcoder-tplib-template').parent().hide();
						$content.find('.tmpcoder-tplib-template[data-filter="'+ current +'"]').parent().show();
					}

					$content.find('.tmpcoder-tplib-filters h3 span').attr('data-filter', current).text( $(this).text() );

					if ( tmpcoderPopupMacy ) {
						tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
					}
				});

				// Bind search (existing input inside .tmpcoder-tplib-search).
				var searchTimeout = null;
				$content.find('.tmpcoder-tplib-search input').off('keyup.tmpcoderSearch').on('keyup.tmpcoderSearch', function() {
					var val = $(this).val().toLowerCase();

					if ( searchTimeout ) {
						clearTimeout( searchTimeout );
					}

					searchTimeout = setTimeout( function() {
						searchTimeout = null;
						$content.find('.tmpcoder-tplib-template-wrap').each(function() {
							var title = ($(this).data('title') || '').toString();
							$(this).toggle( ! val || title.indexOf( val ) !== -1 );
						});
						if ( tmpcoderPopupMacy ) {
							tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
						}
					}, 200 );
				});

				// Initialize Macy for grid layout if available.
				if ( tmpcoderPopupMacy ) {
					try { tmpcoderPopupMacy.remove(); } catch ( e ) {}
					tmpcoderPopupMacy = null;
				}
				if ( typeof Macy !== 'undefined' && $gridInner.length ) {
					// For Blocks, keep grid hidden until Macy has done its first layout
					// so the user never sees the pre-Masonry "stuck together" state.
					if ( isBlocks ) {
						$gridInner.css('opacity', 0);
					}

					tmpcoderPopupMacy = Macy({
						container: $gridInner[0],
						waitForImages: true,
						margin: 30,
						columns: 4,
						breakAt: {
							1370: 4,
							1100: 3,
							768: 2,
							480: 1
						}
					});

					if ( typeof tmpcoderPopupMacy.runOnImageLoad === 'function' ) {
						tmpcoderPopupMacy.runOnImageLoad( function() {
							tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
						}, true );
					}
					tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
					setTimeout( function() {
						tmpcoderSchedulePopupMacyReflow( revealGridWhenReady );
					}, 250 );
					// Safety fallback: reveal even if Macy/image callbacks are missed.
					setTimeout( function() {
						revealGridWhenReady();
					}, 1500 );
				} else {
					// Macy unavailable – fall back to simple grid, reveal sidebar, and hide loader.
					revealGridWhenReady();
				}

				tmpcoderPopupLibraryLoadTabInProgress = false;
				if ( tmpcoderPopupLibraryLoadTabPending !== null && tmpcoderPopupLibraryLoadTabPending !== tab ) {
					var nextTab = tmpcoderPopupLibraryLoadTabPending;
					tmpcoderPopupLibraryLoadTabPending = null;
					tmpcoderPopupLibraryLoadTab( nextTab );
				} else {
					tmpcoderPopupLibraryLoadTabPending = null;
				}

            }).fail(function() {
				$loading.hide();
				$content.find('.tmpcoder-library-error').remove();
				$content.append('<p class="tmpcoder-library-error">Failed to load templates.</p>');
				tmpcoderPopupLibraryLoadTabInProgress = false;
				if ( tmpcoderPopupLibraryLoadTabPending !== null ) {
					var nextTab = tmpcoderPopupLibraryLoadTabPending;
					tmpcoderPopupLibraryLoadTabPending = null;
					tmpcoderPopupLibraryLoadTab( nextTab );
				} else {
					tmpcoderPopupLibraryLoadTabPending = null;
				}
			});
		};

		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-popup-library-tab', function() {
			var tab = $(this).data('tab');
			$(this).addClass('active').siblings().removeClass('active');
			// Prevent multiple AJAX calls on rapid tab clicks (lock + pending tab).
			if ( tmpcoderPopupLibraryLoadTabInProgress ) {
				tmpcoderPopupLibraryLoadTabPending = tab;
				return;
			}
			tmpcoderPopupLibraryLoadTab(tab);
		});

		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-popup-library-create-scratch, .tmpcoder-tplib-template-scratch, [data-create-scratch="1"], .tmpcoder-tplib-template-scratch .tmpcoder-tplib-template-media', function(e) {
			e.preventDefault();
			// Create from scratch: clear selection and open combined setup popup (name + conditions).
			tmpcoderPrebuiltSelection = null;
			$('#tmpcoder-popup-library-wrap').hide().attr('data-post-id','');
			// Restore admin menu after leaving the popup library.
			tmpcoderRestoreAdminMenuAfterPopup();
			$('#tmpcoder_popup_setup_name').val('');
			$('#tmpcoder-popup-setup-wrap').fadeIn();
			if ( typeof tmpcoderLoadPopupSetupConditions === 'function' ) {
				tmpcoderLoadPopupSetupConditions();
			}
		});

		// Helper: switch popup library header between default and preview modes.
		function tmpcoderPopupLibraryShowHeaderPreview( previewCard ) {
			var $header = $('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-header');
			if ( ! $header.length ) {
				return;
			}
			var $logo      = $header.find('.tmpcoder-popup-library-logo');
			var $tabs      = $header.find('.tmpcoder-popup-library-tabs');
			var $createBtn = $header.find('.tmpcoder-popup-library-create-scratch');
			var $previewBar = $header.find('.tmpcoder-popup-library-preview-bar');

			if ( ! $previewBar.length ) {
				$previewBar = $(
					'<div class="tmpcoder-popup-library-preview-bar">' +
						'<button type="button" class="button-link tmpcoder-popup-library-back">' +
							'<span class="eicon-chevron-left"></span>' +
							'<span>' + ( TmpcoderPluginOptions.back_to_library_label || 'Back to Library' ) + '</span>' +
						'</button>' +
						'<button type="button" class="button button-primary tmpcoder-popup-library-insert-preview"></button>' +
					'</div>'
				);
				// $header.append( $previewBar );
				$header.prepend( $previewBar );
			}

			$logo.hide();
			$tabs.hide();
			$createBtn.hide();
			$previewBar.show();

			// Remember which card is being previewed so Insert can reuse its handler.
			var cardEl = ( previewCard && previewCard.length ) ? previewCard[0] : null;
			$header.data( 'tmpcoderPreviewCard', cardEl );

			// Configure the preview header action button based on template type (Free vs Pro).
			var $actionBtn = $previewBar.find('.tmpcoder-popup-library-insert-preview');
			var $card      = cardEl ? $( cardEl ) : $();
			var isLockedPro = false;
			var templateSlug = '';

			if ( $card.length ) {
				var $tpl = $card.find('.tmpcoder-tplib-template').first();
				if ( ! $tpl.length ) {
					$tpl = $card;
				}
				templateSlug = $tpl.data('slug') || $card.data('slug') || '';
				var $proWrap = $card.closest('.tmpcoder-tplib-pro-wrap');
				isLockedPro  = $proWrap.length && ! $proWrap.hasClass('tmpcoder-tplib-pro-active');
			}

			if ( isLockedPro ) {
				$actionBtn
					.addClass('tmpcoder-popup-library-insert-pro-upgrade')
					.data('upgradeSlug', templateSlug)
					.html(
						'<i class="eicon-flash"></i> ' +
						( TmpcoderPluginOptions.go_pro_label || 'Go PRO' )
					);
			} else {
				$actionBtn
					.removeClass('tmpcoder-popup-library-insert-pro-upgrade')
					.removeData('upgradeSlug')
					.html(
						'<i class="eicon-file-download"></i> ' +
						( TmpcoderPluginOptions.insert_label || 'Insert' )
					);
			}
		}

		function tmpcoderPopupLibraryShowHeaderDefault() {
			var $header = $('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-header');
			if ( ! $header.length ) {
				return;
			}
			$header.find('.tmpcoder-popup-library-preview-bar').hide();
			$header.find('.tmpcoder-popup-library-logo, .tmpcoder-popup-library-tabs, .tmpcoder-popup-library-create-scratch').show();
			$header.removeData('tmpcoderPreviewCard');
		}

		// Insert selected template into created popup post (mirrors editor behaviour).
		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-tplib-insert-template:not(.tmpcoder-tplib-insert-pro)', function(e) {
			e.preventDefault();
			var $btn  = $(this);
			var $card = $btn.closest('.tmpcoder-tplib-template-wrap, .tmpcoder-tplib-template');

			var $tpl      = $card.find('.tmpcoder-tplib-template');
			var module    = $tpl.data('filter') || $card.data('filter') || '';
			var template  = $tpl.data('slug') || $card.data('slug') || '';
			var kitID     = $tpl.data('kit') || $card.data('kit') || '';

			if ( ! template || ! module ) {
				return;
			}

			// Match editor logic: strip -zzz and prefix with module/<slug>
			if ( template.indexOf('-zzz') !== -1 ) {
				template = template.replace('-zzz', '');
			}
			var fullSlug   = module + '/' + template;

			tmpcoderPrebuiltSelection = {
				module:   module,
				slug:     template,
				fullSlug: fullSlug,
				kit:      kitID || '',
				section:  ''
			};

			// Close library and open combined setup popup (name + conditions).
			$('#tmpcoder-popup-library-wrap').hide().attr('data-post-id','');
			$('#tmpcoder_popup_setup_name').val('');
			$('#tmpcoder-popup-setup-wrap').fadeIn();
			if ( typeof tmpcoderLoadPopupSetupConditions === 'function' ) {
				tmpcoderLoadPopupSetupConditions();
			}
		});

		// Template thumbnail preview – image for popups, iframe for blocks.
		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-tplib-template-media', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var $tpl  = $(this).closest('.tmpcoder-tplib-template');
			if ( ! $tpl.length ) {
				return;
			}
			var $card = $tpl.closest('.tmpcoder-tplib-template-wrap, .tmpcoder-tplib-template');

			// Switch header into preview mode (Back + Insert buttons).
			tmpcoderPopupLibraryShowHeaderPreview( $card );

			var isPopup   = $tpl.attr('data-template-type') === 'popup';
			var $wrap     = $('#tmpcoder-popup-library-wrap');
			var $content  = $wrap.find('.tmpcoder-popup-library-content');
			var $overlay  = $('#tmpcoder-popup-library-preview');
			if ( ! $overlay.length ) {
				$overlay = $(
					'<div id="tmpcoder-popup-library-preview" class="tmpcoder-popup-library-preview-overlay">' +
						'<div class="tmpcoder-popup-library-preview-inner">' +
							'<span class="tmpcoder-popup-library-preview-close dashicons dashicons-no-alt"></span>' +
							'<img class="tmpcoder-popup-library-preview-img" src="" alt="" />' +
							'<iframe class="tmpcoder-popup-library-preview-iframe" src="about:blank" title=""></iframe>' +
						'</div>' +
					'</div>'
				);
				$('.tmpcoder-popup-library').append($overlay);
			}

			var $loading = $wrap.find('.tmpcoder-popup-library-loading');

			if ( isPopup ) {
				var previewUrl = $tpl.data('preview-url') || $tpl.find('img[data-src]').attr('data-src') || $tpl.find('.tmpcoder-lazyload-image').attr('src') || $tpl.find('img').attr('src');
				if ( ! previewUrl ) {
					return;
				}

				$overlay.removeClass('tmpcoder-preview-mode-iframe').addClass('tmpcoder-preview-mode-image');
				$overlay.find('.tmpcoder-popup-library-preview-img').attr('src', previewUrl).show();
				$overlay.find('.tmpcoder-popup-library-preview-iframe').attr('src', 'about:blank').hide();
				$loading.hide();
			} else {
				var blockSlug = $tpl.attr('data-preview-url') || $tpl.data('preview-url');
				if ( ! blockSlug ) {
					return;
				}
				var demosUrl = ( typeof TmpcoderLibFrontJs !== 'undefined' && TmpcoderLibFrontJs.demos_url ) ? TmpcoderLibFrontJs.demos_url : '';
				var iframeUrl = demosUrl + ( String( blockSlug ).indexOf('/') === 0 ? blockSlug.slice(1) : blockSlug );
				iframeUrl = iframeUrl + '?ref=real-plugin-library-preview';
				$overlay.removeClass('tmpcoder-preview-mode-image').addClass('tmpcoder-preview-mode-iframe');
				$overlay.find('.tmpcoder-popup-library-preview-img').attr('src', '').hide();
				var $iframe = $overlay.find('.tmpcoder-popup-library-preview-iframe');
				var $inner = $overlay.find('.tmpcoder-popup-library-preview-inner');
				$loading.appendTo( $inner ).addClass('tmpcoder-popup-library-loading-in-preview').show();
				$iframe.css('visibility', 'hidden').show();
				$iframe.off('load.tmpcoderPreview');
				$iframe.on('load.tmpcoderPreview', function() {
					$loading.removeClass('tmpcoder-popup-library-loading-in-preview').hide().prependTo( $content );
					$iframe.css('visibility', '');
				});
				$iframe.attr('src', iframeUrl);
			}

			// Show preview, hide grid content (mirrors editor library flow).
			$content.hide();
			$overlay.show().addClass('is-visible');
		});

		$('body').on('click', '.tmpcoder-popup-library-preview-close, #tmpcoder-popup-library-preview.is-visible', function(e) {
			if ( e.target !== this && ! $(e.target).hasClass('tmpcoder-popup-library-preview-close') ) {
				return;
			}
			var $wrap    = $('#tmpcoder-popup-library-wrap');
			var $content = $wrap.find('.tmpcoder-popup-library-content');
			var $preview = $('#tmpcoder-popup-library-preview');
			var $loading = $wrap.find('.tmpcoder-popup-library-loading');
			$preview.removeClass('is-visible');
			$preview.find('.tmpcoder-popup-library-preview-iframe').attr('src', 'about:blank');
			$loading.removeClass('tmpcoder-popup-library-loading-in-preview').hide().prependTo( $content );
			$preview.hide();
			$content.show();
			tmpcoderPopupLibraryShowHeaderDefault();
		});

		// Header Back button: close preview overlay and restore default header.
		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-popup-library-back', function(e) {
			e.preventDefault();
			var $wrap    = $('#tmpcoder-popup-library-wrap');
			var $content = $wrap.find('.tmpcoder-popup-library-content');
			var $preview = $('#tmpcoder-popup-library-preview');
			var $loading = $wrap.find('.tmpcoder-popup-library-loading');
			if ( $preview.length ) {
				$preview.removeClass('is-visible');
				$preview.find('.tmpcoder-popup-library-preview-iframe').attr('src', 'about:blank');
				$loading.removeClass('tmpcoder-popup-library-loading-in-preview').hide().prependTo( $content );
			}
			$preview.hide();
			$content.show();
			tmpcoderPopupLibraryShowHeaderDefault();
		});

		// Header Insert button: reuse the same insert handler as the card's Insert button.
		$('#tmpcoder-popup-library-wrap').on('click', '.tmpcoder-popup-library-insert-preview', function(e) {
			e.preventDefault();
			var $header = $('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-header');
			var cardEl  = $header.data('tmpcoderPreviewCard');
			if ( cardEl ) {
				var $card = $( cardEl );
				var $tpl  = $card.find('.tmpcoder-tplib-template').first();
				if ( ! $tpl.length ) {
					$tpl = $card;
				}

				var $proWrap   = $card.closest('.tmpcoder-tplib-pro-wrap');
				var isLockedPro = $proWrap.length && ! $proWrap.hasClass('tmpcoder-tplib-pro-active');

				if ( isLockedPro ) {
					var templateSlug = $tpl.data('slug') || $card.data('slug') || '';
					if ( templateSlug ) {
						var baseUrl = ( typeof TmpcoderLibFrontJs !== 'undefined' && TmpcoderLibFrontJs.TMPCODER_PURCHASE_PRO_URL )
							? TmpcoderLibFrontJs.TMPCODER_PURCHASE_PRO_URL
							: 'https://spexoaddons.com/';
						var upgradeUrl = baseUrl + '?ref=rea-plugin-library-' + encodeURIComponent( templateSlug ) + '-upgrade-pro#purchasepro';
						window.open( upgradeUrl, '_blank' );
					}
				} else {
					var $insertBtn = $card.find('.tmpcoder-tplib-insert-template:not(.tmpcoder-tplib-insert-pro)').first();
					if ( $insertBtn.length ) {
						$insertBtn.trigger('click');
					}
				}
			}

			var $wrap    = $('#tmpcoder-popup-library-wrap');
			var $content = $wrap.find('.tmpcoder-popup-library-content');
			var $preview = $('#tmpcoder-popup-library-preview');
			var $loading = $wrap.find('.tmpcoder-popup-library-loading');
			if ( $preview.length ) {
				$preview.removeClass('is-visible');
				$preview.find('.tmpcoder-popup-library-preview-iframe').attr('src', 'about:blank');
				$loading.removeClass('tmpcoder-popup-library-loading-in-preview').hide().prependTo( $content );
			}
			$preview.hide();
			$content.show();
			tmpcoderPopupLibraryShowHeaderDefault();
		});

		$('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-close').on('click', function() {
			$('#tmpcoder-popup-library-wrap').hide().removeAttr('data-post-id');
			// Restore admin menu when the popup library is closed without creating a popup.
			tmpcoderRestoreAdminMenuAfterPopup();
		});

		$('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-search').on('input', function() {
			var q = $(this).val().toLowerCase();
			$('#tmpcoder-popup-library-wrap .tmpcoder-tplib-template-wrap').each(function() {
				var title = $(this).data('title') || $(this).find('[data-title]').attr('data-title') || '';
				$(this).toggle( ! q || title.indexOf(q) !== -1 );
			});
			$('#tmpcoder-popup-library-wrap .tmpcoder-popup-library-scratch-card').show();
		});
		
	}

	/*
	** Popup Builder: load Display On conditions into Popup Setup modal (same as Manage Conditions, avoids conflicts).
	*/
	function tmpcoderLoadPopupSetupConditions( onComplete ) {
		var $cell = $('#tmpcoder-popup-setup-wrap .tmpcoder-popup-setup-conditions-cell');
		if ( ! $cell.length || ! $cell.find('.tmpcoder-popup-setup-conditions-placeholder').length ) {
			if ( typeof onComplete === 'function' ) {
				onComplete();
			}
			return;
		}
		$.post( ajaxurl, {
			action: 'tmpcoder_get_popup_setup_conditions',
			nonce:  TmpcoderPluginOptions.nonce,
			layout_type:'type_popup'
		}, function( response ) {
			$cell.html( response );

			$cell.find('.target_rule-add-exclusion-rule').addClass('tmpcoder-hidden');
			if ( typeof window.cloneCondition === 'function' ) {
				window.cloneCondition();
			}
			if ( typeof window.deleteFunction === 'function' ) {
				window.deleteFunction();
			}
			if ( typeof window.targetField === 'function' ) {
				window.targetField();
			}
			if ( typeof onComplete === 'function' ) {
				onComplete();
			}
			// popupAddConditions();
		}).fail(function() {
			$cell.html('<p class="tmpcoder-library-error">' + (TmpcoderPluginOptions.load_error || 'Failed to load display rules.') + '</p>');
			if ( typeof onComplete === 'function' ) {
				onComplete();
			}
		});
	}

	/*
	** Popup Builder: combined Popup Setup (name + conditions)
	*/
	function tmpcoderCollectPopupSetupConditions( templateSlug ) {
		// Build conditions object in the same way as getConditions(), but scoped to setup popup.
		var conditions = {};
		conditions[ templateSlug ] = [];
		var includeArr = [];

		$('#tmpcoder-popup-setup-wrap select.target_rule-condition').each( function() {
			includeArr.push( $(this).val() );
		});

		conditions[ templateSlug ] = includeArr;

		// Specific pages
		var specific_condition = [];
		$('#tmpcoder-popup-setup-wrap select.target_rule-specific-page').each(function(){
			specific_condition.push( $(this).val() );
		});

		return {
			conditions: conditions,
			specific:   specific_condition
		};
	}

	function tmpcoderPopupSetupCreateAndSave() {
		if ( ! isPopupBuilderFlow() ) {
			return;
		}

		var name = $('#tmpcoder_popup_setup_name').val().trim();
		if ( '' === name ) {
			$('#tmpcoder_popup_setup_name').css('border-color', 'red').focus();
			return;
		}
		$('#tmpcoder_popup_setup_name').removeAttr('style');

		// Build slug in the same way as createUserTemplate().
		var library = TmpcoderPluginOptions.post_type;
		var type    = getActiveFilter(); // should be type_popup.
		var slug    = 'user-'+ type +'-'+ name.replace( /\W+/g, '-' ).toLowerCase();

		// If using elementor_library this is adjusted in original code, but for popups we use our post type.

		// Collect conditions in the same format as saveConditions (tmpcoder_save_template_conditions).
		var condObj   = tmpcoderCollectPopupSetupConditions( slug );
		var ruleArr   = condObj.conditions[ slug ] || [];
		var specific  = condObj.specific || [];
		var specificFlat = [];
		specific.forEach( function( val ) {
			if ( Array.isArray( val ) ) {
				specificFlat = specificFlat.concat( val );
			} else {
				specificFlat.push( val );
			}
		} );
		specificFlat = specificFlat.filter( Boolean );

		// For free users, only allow "Entire Site" (basic-global) condition.
		var proActive = ( 1 === $('.tmpcoder-my-templates-list').data('pro') ) ? true : false;
		if ( ! proActive && ruleArr && ruleArr.length ) {
			if ( ruleArr.length !== 1 || ruleArr[0] !== 'basic-global' ) {
				alert( 'Please select \"Entire Site\" to continue! Mutiple and custom conditions are fully supported in the Pro version.' );
				return;
			}
		}

		var bsfTargetRules = {
			rule:     ruleArr,
			specific: specificFlat
		};

		var dataCreate = {
			action:                   'tmpcoder_create_popup_with_conditions',
			nonce:                    TmpcoderPluginOptions.nonce,
			popup_name:               name,
			popup_slug:               slug,
			'bsf-target-rules-location': JSON.stringify( bsfTargetRules ),
			prebuilt_full_slug:       ( tmpcoderPrebuiltSelection && tmpcoderPrebuiltSelection.fullSlug ) ? tmpcoderPrebuiltSelection.fullSlug : '',
			prebuilt_kit:             ( tmpcoderPrebuiltSelection && tmpcoderPrebuiltSelection.kit ) ? tmpcoderPrebuiltSelection.kit : '',
			prebuilt_section:         ( tmpcoderPrebuiltSelection && tmpcoderPrebuiltSelection.section ) ? tmpcoderPrebuiltSelection.section : ''
		};

		// Disable button to prevent double submit.
		var $saveBtn = $('.tmpcoder-popup-setup-save');
		$saveBtn.prop('disabled', true).css('opacity', '0.7');

		$.post( ajaxurl, dataCreate, function( response ) {
			if ( typeof response === 'string' ) {
				try { response = JSON.parse( response ); } catch (e) { response = {}; }
			}

			if ( ! response || ! response.success || ! response.data ) {
				$saveBtn.prop('disabled', false).css('opacity', '1');
				alert( TmpcoderPluginOptions.valid_name_msg || 'Enter valid template name.' );
				return;
			}

			var redirectUrl = response.data.redirect || '';
			if ( redirectUrl ) {
				window.location.href = redirectUrl;
			} else {
				$saveBtn.prop('disabled', false).css('opacity', '1');
			}
		}).always(function() {
			// Close setup popup.
			$('#tmpcoder-popup-setup-wrap').fadeOut();
		});
	}

	// Setup popup footer buttons.
	$('.tmpcoder-popup-setup-save').on('click', function() {
		tmpcoderPopupSetupCreateAndSave();
	});

	$('.tmpcoder-popup-setup-back').on('click', function() {
		$('#tmpcoder-popup-setup-wrap').hide();
		$('.tmpcoder-popup-library-preview-bar').hide();
		$('.tmpcoder-popup-library-logo').show();
		$('.tmpcoder-popup-library-tabs').show();
		$('.tmpcoder-popup-library-create-scratch').show();

		// Go back to library with popups tab.
		if ( $('#tmpcoder-popup-library-wrap').length ) {
			$('#tmpcoder-popup-library-wrap').show();
		}
	});

	$('.tmpcoder-popup-setup-close').on('click', function() {
		$('#tmpcoder-popup-setup-wrap').fadeOut();
	});

	/*
	** Highlight Templates with Active Conditions --------
	*/

	if ( $('body').hasClass('sastra-elementor-addon_page_tmpcoder-theme-builder') || $('body').hasClass('sastra-elementor-addon_page_tmpcoder-popup') ) {
		if ( currentTab && 'my_templates' !== currentTab ) {
			var conditions = $( '#tmpcoder_'+ currentTab +'_conditions' ).val(),
				conditions = ('' === conditions || '[]' === conditions) ? {} : JSON.parse(conditions);

			for ( var key in conditions ) {
				$('.tmpcoder-delete-template[data-slug="'+ key +'"]').closest('li').addClass('tmpcoder-active-conditions-template');
			}
		}
	}

	/*
	** Save Options with Ajax -------------------------
	*/
	$('.tmpcoder-settings-page form, .spexo-settings-page form, .spexo-settings-form').submit(function () {
		var $form = $(this);
		var settings =  $form.serialize();
		var $loader = $form.find('.welcome-backend-loader').first();
		var loaderSelector = '';

		if ($loader.hasClass('tmpcoder-widgets-sync-loader')) {
			loaderSelector = '.tmpcoder-widgets-sync-loader';
		} else if ($loader.hasClass('tmpcoder-settings-sync-loader')) {
			loaderSelector = '.tmpcoder-settings-sync-loader';
		} else if ($loader.hasClass('tmpcoder-tools-sync-loader')) {
			loaderSelector = '.tmpcoder-tools-sync-loader';
		}

		if (loaderSelector && window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.show === 'function') {
			window.tmpcoderCommonLoader.show(loaderSelector);
		} else {
			$loader.fadeIn();
		}
		$('.tmpcoder-theme-welcome').css('opacity','1');

		$.post( 'options.php', settings ).error(function() {
			// alert('error');
			if (loaderSelector && window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.hide === 'function') {
				window.tmpcoderCommonLoader.hide(loaderSelector);
			} else {
				$loader.fadeOut();
			}
			$('.tmpcoder-theme-welcome').css('opacity','1');
		}).success(function() {
			if (loaderSelector && window.tmpcoderCommonLoader && typeof window.tmpcoderCommonLoader.hide === 'function') {
				window.tmpcoderCommonLoader.hide(loaderSelector);
			} else {
				$loader.fadeOut();
			}
			$('.tmpcoder-theme-welcome').css('opacity','1');
			$('.tmpcoder-settings-saved').stop().fadeIn(500).delay(1000).fadeOut(1000); 
		});

		return false;    
	});

	$('.tmpcoder-element').find('input').on( 'change', function() {
		$('.tmpcoder-settings-page form, .spexo-settings-page form, .spexo-settings-form').submit();
	});

	/*
	** Elements Toggle -------------------------
	*/
	$(".tmpcoder-btn-group").on( "click", '.tmpcoder-btn', function () {

		$('.tmpcoder-btn-group input').trigger('click');

        var $btn = $(this), isChecked = $btn.hasClass("tmpcoder-btn-enable");

        if (!$btn.hasClass("active")) {
            $(".tmpcoder-btn-group .tmpcoder-btn").removeClass("active");
            $btn.addClass("active");
        }

        if (isChecked) {
            $(".tmpcoder-btn-group .tmpcoder-btn-unused").removeClass("dimmed");
            $('.tmpcoder-element').find('input').prop( 'checked', true );
        } else {
            $(".tmpcoder-btn-group .tmpcoder-btn-unused").addClass("dimmed");
            $('.tmpcoder-element').find('input').prop( 'checked', false );
        }
        $('.tmpcoder-settings-page form').submit();
    });

	
	/*
	** Image Upload Option -----------------------
	*/
	$('body').on( 'click', '.tmpcoder-setting-custom-img-upload button', function(e){
		e.preventDefault();

		var button = $(this);

		if ( ! button.find('img').length ) {
			var custom_uploader = wp.media({
				title: 'Insert image',
				library : {
					uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
					type : 'image'
				},
				button: {
					text: 'Use this image' // button label text
				},
				multiple: false
			}).on('select', function() {
				var attachment = custom_uploader.state().get('selection').first().toJSON();

				button.find('i').remove();
				button.prepend('<img src="' + attachment.url + '">');
				button.find('span').text('Remove Image');

				$('#tmpcoder_wl_plugin_logo').val(attachment.id);
			}).open();
		} else {
			button.find('img').remove();
			button.prepend('<i class="dashicons dashicons-cloud-upload"></i>');
			button.find('span').text('Upload Image');

			$('#tmpcoder_wl_plugin_logo').val('');
		}
	
	});

	/*
	** Elements Search --------------------------
	*/
	var searchTimeout = null;  
	$('.tmpcoder-widgets-search').find('input').keyup(function(e) {
		if ( e.which === 13 ) {
			return false;
		}

		var val = $(this).val().toLowerCase();

		if (searchTimeout != null) {
			clearTimeout(searchTimeout);
		}

		searchTimeout = setTimeout(function() {
			searchTimeout = null;
			let visibleElements = 'none';
			
			// Reset
			$('.tmpcoder-widgets-not-found').hide();
			$('.submit').show();

			if ( '' !== val ) {
				$('.tmpcoder-elements, .tmpcoder-element-box-inner, .tmpcoder-elements-heading').hide();
				$('.tmpcoder-widgets-not-found').hide();
			} else {
				$('.tmpcoder-elements, .tmpcoder-element, .tmpcoder-elements-heading').show();
				$('.tmpcoder-elements-filters li').first().trigger('click');
			}

			$('.tmpcoder-element-box-inner').each(function(){
				let title = $(this).find('h3').text().toLowerCase();

				if ( -1 !== title.indexOf(val) ) {
					$(this).show();
					$(this).parent().show();
					visibleElements = 'visible';
				}
			});

			if ( 'none' === visibleElements ) {
				$('.tmpcoder-widgets-not-found').css('display', 'flex');
				$('.submit').hide();
			}

			jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'tmpcoder_backend_search_query_results',
                    search_query: val,
                    type:1
                },
                success: function( response ) {}
            });

		}, 1000);  
	});

	/*
	** Elements Filters -----------------------------------
	*/
	$('.tmpcoder-elements-filters li').on('click', function() {
		let filter = $(this).data('filter');

		$('.tmpcoder-elements-toggle').hide();
		$('.tmpcoder-elements-filters li').removeClass('tmpcoder-active-filter');
		$(this).addClass('tmpcoder-active-filter');

		if ( 'all' === filter ) {
			$('.tmpcoder-elements, .tmpcoder-elements-heading').show();
			$('.tmpcoder-elements-toggle').show();
		} else if ( 'creative' === filter ) {
			$('.tmpcoder-elements, .tmpcoder-elements-heading').hide();
			$('.tmpcoder-elements-general').show();
			$('.tmpcoder-elements-general').prev('.tmpcoder-elements-heading').show();
		} else if ( 'theme' === filter ) {
			$('.tmpcoder-elements, .tmpcoder-elements-heading').hide();
			$('.tmpcoder-elements-theme').show();
			$('.tmpcoder-elements-theme').prev('.tmpcoder-elements-heading').show();
		} else if ( 'extensions' === filter ) {
			$('.tmpcoder-elements, .tmpcoder-elements-heading').hide();
			$('.tmpcoder-elements-extensions').show();
			$('.tmpcoder-elements-extensions').prev('.tmpcoder-elements-heading').show();
		} 
		else {
			$('.tmpcoder-elements, .tmpcoder-elements-heading').hide();
			$('.tmpcoder-elements-woo').show();
			$('.tmpcoder-elements-woo').prev('.tmpcoder-elements-heading').show();
		}
	});


}); // end dom ready