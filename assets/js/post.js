/**
 * This file is used only on the details page
 *
 * @since 1.0.0
 * @since 1.4.4 Minified
 * @package GeoDirectory
 */

function geodir_get_popup_forms(e, i, r, o) {
    var s = geodir_params.ajax_url,
        a = i.closest("li");
    i.closest(".geodir-company_info").length > 0 && (a = i.closest(".geodir-company_info"));
    var d = a.find('input[name="geodir_popup_post_id"]').val();

    //WPML
    if (typeof icl_vars !== 'undefined' && icl_vars.current_language) {
        s = s+"&lang="+icl_vars.current_language;
    }

    jQuery.gdmodal('<div id="basic-modal-content" class="clearfix simplemodal-data" style="display: block;"><div class="geodir-modal-loading"><i class="fa fa-refresh fa-spin "></i></div></div>'), jQuery.post(s, {
        popuptype: r,
        post_id: d
    }).done(function(i) {
        a.find(".geodir_display_popup_forms").append(i), e.preventDefault(), jQuery.gdmodal.close(), jQuery("#" + o).gdmodal({
            persist: !0,
            onClose: function() {
                jQuery.gdmodal.close({
                    overlayClose: !0
                }), a.find(".geodir_display_popup_forms").html("")
            }
        })
    })
}

function geodir_popup_validate_field(e) {
    var i = !0;
    switch (erro_msg = "", jQuery(e).attr("field_type")) {
        case "email":
            var r = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w+)+$/;
            "" == e.value && (erro_msg = geodir_params.field_id_required), "" == e.value || r.test(e.value) || (erro_msg = geodir_params.valid_email_address_msg), "" != e.value && r.test(e.value) && (i = !1);
            break;
        case "text":
        case "textarea":
            "" != e.value ? i = !1 : erro_msg = geodir_params.field_id_required
    }
    return i ? (erro_msg && jQuery(e).closest("div").find("span.message_error2").html(erro_msg), jQuery(e).closest("div").find("span.message_error2").fadeIn(), !1) : (jQuery(e).closest("div").find("span.message_error2").html(""), jQuery(e).closest("div").find("span.message_error2").fadeOut(), !0)
}

function geodir_transliterate(e) {
    var i = {},
        r = "";
    i["Ё"] = "YO", i["Й"] = "I", i["Ц"] = "TS", i["У"] = "U", i["К"] = "K", i["Е"] = "E", i["Н"] = "N", i["Г"] = "G", i["Ш"] = "SH", i["Щ"] = "SCH", i["З"] = "Z", i["Х"] = "H", i["Ъ"] = "'", i["ё"] = "yo", i["й"] = "i", i["ц"] = "ts", i["у"] = "u", i["к"] = "k", i["е"] = "e", i["н"] = "n", i["г"] = "g", i["ш"] = "sh", i["щ"] = "sch", i["з"] = "z", i["х"] = "h", i["ъ"] = "'", i["Ф"] = "F", i["Ы"] = "I", i["В"] = "V", i["А"] = "A", i["П"] = "P", i["Р"] = "R", i["О"] = "O", i["Л"] = "L", i["Д"] = "D", i["Ж"] = "ZH", i["Э"] = "E", i["ф"] = "f", i["ы"] = "i", i["в"] = "v", i["а"] = "a", i["п"] = "p", i["р"] = "r", i["о"] = "o", i["л"] = "l", i["д"] = "d", i["ж"] = "zh", i["э"] = "e", i["Я"] = "YA", i["Ч"] = "CH", i["С"] = "S", i["М"] = "M", i["И"] = "I", i["Т"] = "T", i["Ь"] = "'", i["Б"] = "B", i["Ю"] = "YU", i["я"] = "ya", i["ч"] = "ch", i["с"] = "s", i["м"] = "m", i["и"] = "i", i["т"] = "t", i["ь"] = "'", i["б"] = "b", i["ю"] = "yu";
    for (var o = 0; o < e.length; o++) {
        var s = e.charAt(o);
        r += i[s] || s
    }
    return r
}

function geodir_ajax_load_slider(slide){
    // fix the srcset
    if(real_srcset = jQuery(slide).find('img').attr("data-srcset")){
        jQuery(slide).find('img').attr("srcset",real_srcset);
    }
    // fix the src
    if(real_src = jQuery(slide).find('img').attr("data-src")){
        jQuery(slide).find('img').attr("src",real_src);
    }
}

function geodir_init_slider($id){

   // console.log($id);
    //return;
    // chrome 53 introduced a bug, so we need to repaint the slider when shown.
    jQuery('.geodir-slides').addClass('flexslider-fix-rtl');

    jQuery("#"+$id+"_carousel").flexslider({
        animation: "slide",
        namespace: "geodir-",
        selector: ".geodir-slides > li",
        controlNav: !1,
        directionNav: !1,
        animationLoop: !1,
        slideshow: !1,
        itemWidth: 75,
        itemMargin: 5,
        asNavFor: "#"+$id,
        rtl: 1 == parseInt(geodir_params.is_rtl) ? !0 : !1
    }), jQuery("#"+$id).flexslider({
        animation: jQuery("#"+$id).attr("data-animation")=='fade' ? "fade" : "slide",
        selector: ".geodir-slides > li",
        namespace: "geodir-",
        // controlNav: !0,
        controlNav: parseInt(jQuery("#"+$id).attr("data-controlnav")),
        directionNav: 1,
        prevText: "",
        nextText: "",
        animationLoop: !0,
        slideshow: parseInt(jQuery("#"+$id).attr("data-slideshow")),
        sync: "#"+$id+"_carousel",
        start: function(slider) {

            // chrome 53 introduced a bug, so we need to repaint the slider when shown.
            jQuery('.geodir-slides').removeClass('flexslider-fix-rtl');
            jQuery("#"+$id).removeClass('geodir-slider-loading');

            jQuery(".geodir_flex-loader").hide(), jQuery("#"+$id).css({
                visibility: "visible"
            }), jQuery("#"+$id+"_carousel").css({
                visibility: "visible"
            });


            // Ajaxify the slider if needed.
            next = slider.slides.eq(slider.currentSlide + 1);
            // fix the srcset
            if(real_srcset = jQuery(next).find('img').attr("data-srcset")){
                jQuery(next).find('img').attr("srcset",real_srcset);
            }
            // fix the src
            if(real_src = jQuery(next).find('img').attr("data-src")){
                jQuery(next).find('img').attr("src",real_src);
            }
        },
        before: function(slider){
            // Ajaxify the slider if needed.
            animatingTo = slider.slides.eq(slider.animatingTo);
            next_next = slider.slides.eq(slider.currentSlide + 2);
            geodir_ajax_load_slider(next_next);// load the next-next slide via ajax so its always loaded early
            geodir_ajax_load_slider(animatingTo); // double check the current slide is loaded (in-case user goes backwards)
        },
        rtl: 1 == parseInt(geodir_params.is_rtl) ? !0 : !1
    });

}


jQuery(document).ready(function() {


    jQuery('.geodir-slider').each(function(i, obj) {
        // init the sliders
        geodir_init_slider(obj.id);
    });


    



    // let the popups open via url param
    if(gdUrlParam('gd_popup')=='send_friend' && jQuery('a.b_sendtofriend').length){
        jQuery('.b_sendtofriend').trigger("click");
    }else if(gdUrlParam('gd_popup')=='send_enquiry' && jQuery('a.b_send_inquiry').length){
        jQuery('.b_send_inquiry').trigger("click");
    }

    /**
     * Rating script for ratings inputs.
     * @info This is shared in both post.js and admin.js any changes shoudl be made to both.
     */
    jQuery(".gd-rating-input").each(function () {

        if (geodir_params.rating_type =='font-awesome') { // font awesome rating
            $type = 'i'
        }else{// image
            $type = 'img'
        }

        $total = jQuery(this).find('.gd-rating-foreground > ' + $type).length;
        $parent = this;

        // set the current star value and text
        $value = jQuery($parent).find('input').val();
        if($value > 0){
            jQuery($parent).find('.gd-rating-foreground').width( $value / $total * 100 + '%');
            jQuery($parent).find('.gd-rating-text').text( jQuery($parent).find($type+':eq('+ ($value - 1) +')').attr("title"));
        }

        // loop all rating stars
        jQuery(this).find($type).each(function (index) {
            $original_rating = jQuery($parent).find('input').val();

            $original_percent = $original_rating / $total * 100;
            $rating_set = false;

            jQuery(this).hover(
                function () {
                    $percent = 0;
                    $rating = index + 1;
                    $rating_text = jQuery(this).attr("title");
                    $original_rating_text = jQuery($parent).find('.gd-rating-text').text();
                    if ($rating > $total) {
                        $rating = $rating - $total;
                    }
                    $percent = $rating / $total * 100;
                    jQuery($parent).find('.gd-rating-foreground').width($percent + '%');
                    jQuery($parent).find('.gd-rating-text').text($rating_text);
                },
                function () {
                    if (!$rating_set) {
                        jQuery($parent).find('.gd-rating-foreground').width($original_percent + '%');
                        jQuery($parent).find('.gd-rating-text').text($original_rating_text);
                    } else {
                        $rating_set = false;
                    }
                }
            );

            jQuery(this).click(function () {
                $original_percent = $percent;
                $original_rating = $rating;
                jQuery($parent).find('input').val($rating);
                jQuery($parent).find('.gd-rating-text').text($rating_text);
                $rating_set = true;
            });

        });

    });




});