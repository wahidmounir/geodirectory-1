gd_infowindow = window.gdMaps == 'google' ? new google.maps.InfoWindow() : null;

jQuery(window).load(function() {
    // Chosen selects
    if (jQuery("select.chosen_select").length > 0) {
        jQuery("select.chosen_select").chosen();
        jQuery("select.chosen_select_nostd").chosen({
            allow_single_deselect: 'true'
        });
    }

    // tooltips
    gd_init_tooltips();
    
    // rating click
    jQuery( 'a.gd-rating-link' ).click( function() {
        jQuery.post( ajaxurl, { action: 'geodirectory_rated' } );
        jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
    });

    // image uploads
    jQuery('.gd-upload-img').each(function() {
        var $wrap = jQuery(this);
        var field = $wrap.data('field');
        if (jQuery('[name="' + field + '[id]"]').length && !jQuery('[name="' + field + '[id]"]').val()) {
            jQuery('.gd_remove_image_button', $wrap).hide();
        }
    });

    var media_frame = [];
    jQuery(document).on('click', '.gd_upload_image_button', function(e) {
        e.preventDefault();

        var $this = jQuery(this);
        var $wrap = $this.closest('.gd-upload-img');
        var field = $wrap.data('field');

        if ( !field ) {
            return
        }

        if (media_frame && media_frame[field]) {
            media_frame[field].open();
            return;
        }

        media_frame[field] = wp.media.frames.downloadable_file = wp.media({
            title: geodir_ajax.txt_choose_image,
            button: {
                text: geodir_ajax.txt_use_image
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        media_frame[field].on('select', function() {
            var attachment = media_frame[field].state().get('selection').first().toJSON();

            var thumbnail = attachment.sizes.medium || attachment.sizes.full;
            if (field) {
                if(jQuery('[name="' + field + '[id]"]').length){
                    jQuery('[name="' + field + '[id]"]').val(attachment.id);
                }
                if(jQuery('[name="' + field + '[src]"]').length){
                    jQuery('[name="' + field + '[src]"]').val(attachment.url);
                }
                if(jQuery('[name="' + field + '"]').length){
                    jQuery('[name="' + field + '"]').val(attachment.id);
                }


            }
            $wrap.closest('.form-field.form-invalid').removeClass('form-invalid');
            jQuery('.gd-upload-display', $wrap).find('img').attr('src', thumbnail.url);
            jQuery('.gd_remove_image_button').show();
        });
        // Finally, open the modal.
        media_frame[field].open();
    });

    jQuery(document).on('click', '.gd_remove_image_button', function() {
        var $this = jQuery(this);
        var $wrap = $this.closest('.gd-upload-img');
        var field = $wrap.data('field');
        jQuery('.gd-upload-display', $wrap).find('img').attr('src', geodir_ajax.img_spacer);
        if (field) {
            jQuery('[name="' + field + '[id]"]').val('');
            jQuery('[name="' + field + '[src]"]').val('');
        }
        $this.hide();
        return false;
    });
	
	// Load color picker
	var gdColorPicker = jQuery('.gd-color-picker');
	console.log('gdColorPicker');
	if (gdColorPicker.length) {
		gdColorPicker.wpColorPicker();
	}

});

/**
 * Init the tooltips
 */
function gd_init_tooltips(){
    // Tooltips
    jQuery('.gd-help-tip').tooltip({
        content: function() {
            return jQuery(this).prop('title');
        },
        tooltipClass: 'gd-ui-tooltip',
        position: {
            my: 'center top',
            at: 'center bottom+10',
            collision: 'flipfit',
        },
        hide: {
            duration: 200,
        },
        show: {
            duration: 200,
        },
    });
}

/* Check Uncheck All Related Options Start*/
jQuery(document).ready(function() {
    jQuery('#geodir_add_location_url').click(function() {
        if (jQuery(this).is(':checked')) {
            jQuery(this).closest('td').find('input').attr('checked', true).not(this).prop('disabled', false);
        } else {
            jQuery(this).closest('td').find('input').attr('checked', false).not(this).prop('disabled', true);
        }
    });

    if (jQuery('#geodir_add_location_url').is(':checked')) {
        jQuery('#geodir_add_location_url').closest('td').find('input').not(jQuery('#geodir_add_location_url')).prop('disabled', false);
    } else {
        jQuery('#geodir_add_location_url').closest('td').find('input').not(jQuery('#geodir_add_location_url')).prop('disabled', true);
    }

    jQuery('#submit').click(function() {
        if (jQuery('input[name="ct_cat_icon[src]"]').hasClass('ct_cat_icon[src]')) {
            if (jQuery('input[name="ct_cat_icon[src]"]').val() == '') {
                jQuery('input[name="ct_cat_icon[src]"]').closest('tr').addClass('form-invalid');
                return false;
            } else {
                jQuery('input[name="ct_cat_icon[src]"]').closest('tr').removeClass('form-invalid');
                jQuery('input[name="ct_cat_icon[src]"]').closest('div').removeClass('form-invalid');
            }
        }
    });

    function location_validation(fields) {
        var error = false;

        if (fields.val() == '') {
            jQuery(fields).closest('.gtd-formfeild').find('.gd-location_message_error').show();
            error = true;
        } else {
            jQuery(fields).closest('.gtd-formfeild').find('.gd-location_message_error').hide();
        }

        if (error) {
            return false;
        } else {
            return true;
        }
    }

    jQuery('#location_save').click(function() {
        var is_validate = true;

        jQuery(this).closest('form').find('.required:visible').each(function() {
            var fields = jQuery(this).find('input, select');
            if (!location_validation(fields))
                is_validate = false;
        });

        if (!is_validate) {
            return false;
        }
    });

    jQuery('.default_location_form').find(".required:visible").find('input').blur(function() {
        location_validation(jQuery(this));
    });

    jQuery('.default_location_form').find(".required:visible").find('select').change(function() {
        location_validation(jQuery(this));
    });

    jQuery('.gd-cats-display-checkbox input[type="checkbox"]').click(function() {
        var isChecked = jQuery(this).is(':checked');

        if (!isChecked) {
            var chkVal = jQuery(this).val();
            jQuery(this).closest('.gd-parent-cats-list').find('.gd-cat-row-' + chkVal + ' input[type="checkbox"]').prop("checked", isChecked);
        }
    });

    jQuery('.gd-import-export [data-type="date"]').each(function() {
        jQuery(this).datepicker({changeMonth: true, changeYear: true, dateFormat:'yy-mm-dd'});
    });
    jQuery('#gd-wrapper-main .wp-editor-wrap').each(function() {
        var elH = parseFloat(jQuery(this).find('.wp-editor-container').height());
        if (elH > 30) {
            jQuery(this).find('.wp-editor-container').attr('data-height', elH);
        }
    });
    setTimeout(function() {
        jQuery('#gd-wrapper-main .wp-editor-wrap').each(function() {
            var elH = parseFloat(jQuery(this).find('.wp-editor-container').attr('data-height'));
            if (elH > 30) {
                jQuery(this).find('iframe').css({
                    'height': elH + 'px'
                });
            }
        });
    }, 1000);
});
/* Check Uncheck All Related Options End*/

// WMPL copy function
jQuery(document).ready(function() {
    if (jQuery("#icl_cfo").length == 0) {} else { // it exists let's do stuff.
        jQuery('#icl_cfo').click(function() {
            gd_copy_translation(window.location.protocol + '//' + document.location.hostname + ajaxurl);
        });
    }
});

function gd_copy_translation(url) {
    post_id = jQuery("#icl_translation_of_hidden").val();

    jQuery.ajax({
        url: url,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'gd_copy_original_translation',
            post_id: post_id
        },
        beforeSend: function() {},
        success: function(data, textStatus, xhr) {
            data = JSON.parse(data);

            for (var key in data) {
                jQuery('#' + key).val(data[key]);
            }

            if (data.post_images) {
                plu_show_thumbs('post_images');
            }

            if (data.categories) {
                var a = ["a", "b", "c"];
                data.categories.forEach(function(cat) {
                    show_subcatlist(cat);
                });
            }

            /////////////////
            if (data.post_latitude && data.post_longitude) {
                latlon = new google.maps.LatLng(data.post_latitude, data.post_longitude);
                jQuery.goMap.map.setCenter(latlon);
                updateMarkerPosition(latlon);
                centerMarker();

                if (!data.post_address) {
                    google.maps.event.trigger(baseMarker, 'dragend');
                } // geocode address only if no street name present
            }

            if (data.post_country && jQuery('#post_country').length) {
                jQuery('#post_country').val(data.post_country);
                jQuery("#post_country").trigger("chosen:updated");
            }

            if (data.post_region && jQuery('#post_region').length) {
                jQuery('#post_region').val(data.post_region);
                jQuery("#post_region").trigger("chosen:updated");
            }

            if (data.post_city && jQuery('#post_city').length) {
                jQuery('#post_city').val(data.post_city);
                jQuery("#post_city").trigger("chosen:updated");
            }

            if (data.post_zip && jQuery('#post_zip').length) {
                jQuery('#post_zip').val(data.post_zip);
            }

            if (data.post_country && data.post_region && data.post_city && data.post_zip) {
                gdfi_codeaddress = true;
                gdfi_city = data.post_city;
                gdfi_street = data.post_address;
                gdfi_zip = data.post_zip;
                geodir_codeAddress(true);

                setTimeout(function() {
                    google.maps.event.trigger(baseMarker, 'dragend');
                }, 600);

                //incase the drag marker changes the street and post code we should fix it.
                setTimeout(function() {
                    if (data.post_address && jQuery('#post_address').length) {
                        jQuery('#post_address').val(data.post_address);
                    }
                    if (data.post_zip && jQuery('#post_zip').length) {
                        jQuery('#post_zip').val(data.post_zip);
                    }
                }, 1000);
            }
            //////////////////////
        },
        error: function(xhr, textStatus, errorThrown) {
            alert(textStatus);
        }
    });
}

// Diagnosis related js starts here
/* Check Uncheck All Related Options Start*/
jQuery(document).ready(function() {
    jQuery('.geodir_diagnosis_button').click(function(e) {
        e.preventDefault();
        var diagnose = (jQuery(this).data('diagnose'));
        var step_process = (jQuery(this).data('step'));
        var ptype = (jQuery(this).data('ptype'));
        if (step_process == '1') {
            jQuery('#' + diagnose + '_sub_table').show();
        } else {
            jQuery('.tool-' + diagnose).remove();
            var result_container = jQuery('.geodir_diagnostic_result-' + diagnose);
            if (!result_container.length) {
                if( typeof ptype !== "undefined") {
                    jQuery('<tr class="gd-tool-results tool-' + diagnose + '" ><td colspan="3"><span class="gd-tool-results-remove" onclick="jQuery(this).closest(\'tr\').remove();"></span><div class="geodir_diagnostic_result-' + diagnose + '"></div></td></tr>').insertAfter(jQuery('#' + diagnose +'_'+ ptype));
                } else {
                    jQuery('<tr class="gd-tool-results tool-' + diagnose + '" ><td colspan="3"><span class="gd-tool-results-remove" onclick="jQuery(this).closest(\'tr\').remove();"><i class="fa fa-spinner fa-spin"></i></span><div class="geodir_diagnostic_result-' + diagnose + '"></div></td></tr>').insertAfter(jQuery(this).parents('tr'));
                }
                var result_container = jQuery('.geodir_diagnostic_result-' + diagnose);
            }

            if( typeof ptype !== "undefined") {
                jQuery('<tr>'+
                    '<td colspan="3">' +
                    '<div class="">' +
                    '<div id="gd_progressbar">' +
                    '<div class="gd-progress-label"></div>' +
                    '</div>' +
                    '</div>' +
                    '</td>' +
                    '</tr>').insertAfter(jQuery('#' + diagnose +'_'+ ptype));

                jQuery('#gd_progressbar').progressbar({value: 0});
                jQuery('#gd_progressbar .gd-progress-label').html('<i class="fa fa-refresh fa-spin"></i> Processing...');

            }

            // start the process
            gd_process_diagnose_step( 0, ptype, diagnose, result_container );
        }
        
    });

    geodir_enable_fix_buttons(); // enabel fix buttons
});

function gd_process_diagnose_step(step, ptype, diagnose, result_container) {
    jQuery.ajax({
        url: geodir_all_js_msg.geodir_admin_ajax_url,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'geodir_admin_ajax',
            geodir_admin_ajax_action: 'diagnosis',
            diagnose_this: diagnose,
            step: step,
            ptype: ptype
        },
        beforeSend: function() {},
        success: function(data, textStatus, xhr) {
            if( typeof ptype === "undefined" || 'done' == data ) {
                if( typeof ptype !== "undefined"){
                    jQuery('#' + diagnose +'_'+ ptype).html('<ul class="geodir_noproblem_info"><li>'+data+'</li></ul>');
                    jQuery('#gd_progressbar').remove();
                    jQuery('#' + diagnose + '_sub_table').find('.gd-tool-results').remove();

                } else {
                    jQuery('.tool-' + diagnose + ' .gd-tool-results-remove').html('<i class="fa fa-times"></i>');
                    result_container.html(data);
                }
                geodir_enable_fix_buttons(); //enable new fix buttons
            } else {
                resp = JSON.parse(data);
                jQuery('#gd_progressbar').progressbar({value: resp.percent});
                jQuery('#gd_progressbar .gd-progress-label').html('<i class="fa fa-refresh fa-spin"></i> Processing...');
                gd_process_diagnose_step(parseInt( resp.step ), ptype, diagnose, result_container)
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            alert(textStatus);
        }
    }); // end of ajax
}

function geodir_enable_fix_buttons() {
    jQuery('.geodir_fix_diagnostic_issue').click(function() {
        var diagnose = (jQuery(this).data('diagnostic-issue'))
        var result_container = jQuery(this).parents('td').find("div")

        jQuery.ajax({
            url: geodir_all_js_msg.geodir_admin_ajax_url,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'geodir_admin_ajax',
                geodir_admin_ajax_action: 'diagnosis-fix',
                diagnose_this: diagnose,
                fix: 1
            },
            beforeSend: function() {},
            success: function(data, textStatus, xhr) {
                result_container.html(data);
                geodir_enable_fix_buttons(); //enable new fix buttons
            },
            error: function(xhr, textStatus, errorThrown) {
                alert(textStatus);
            }
        }); // end of ajax
    });
}

function gd_progressbar(el, value, label) {
    var value = parseFloat(value);
    if ( value <= 100 ) {
        jQuery(el).find('#gd_progressbar').progressbar("value",value);
        if (typeof label != 'undefined') {
            jQuery(el).find('#gd_progressbar .gd-progress-label').html(label);
        }
    }
}

function gd_GA_Deauthorize(nonce){
    var result = confirm(geodir_all_js_msg.ga_delete_check);
    if (result) {
        jQuery.ajax({
            url: geodir_all_js_msg.geodir_admin_ajax_url,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'geodir_ga_deauthorize',
                _wpnonce: nonce
            },
            beforeSend: function() {},
            success: function(data, textStatus, xhr) {
                if(data){
                    window.location.assign(data);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                alert(textStatus);
            }
        }); // end of ajax
    }
}