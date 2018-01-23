<?php
/**
 * Admin template tag functions.
 *
 * @since 1.0.0
 * @package GeoDirectory
 */

if (!function_exists('geodir_admin_panel')) {
    /**
     * GeoDirectory Backend Admin Panel.
     *
     * Handles the display of the main GeoDirectory admin panel.
     *
     * @since 1.0.0
     * @package GeoDirectory
     * @global string $current_tab Current tab in geodirectory settings.
     * @global object $geodirectory GeoDirectory plugin object.
     */
    function geodir_admin_panel()
    {
        global $geodirectory;
        global $current_tab;


        ?>

        <div id="gd-wrapper-main" class="wrap geodirectory">
            <?php
            /**
             * Called just after the opening wrapper div for the GD settings page in the admin area.
             *
             * @since 1.0.0
             */
            do_action('geodir_before_admin_panel');
            ?>

            <div class="gd-wrapper gd-wrapper-vr clearfix">

                <div class="gd-left-nav">
                    <img src="<?php echo geodir_plugin_url(); ?>/assets/images/geo-logo.png" alt="geo-logo"
                         class="geo-logo"/>
                    <img src="<?php echo geodir_plugin_url(); ?>/assets/images/geo-logoalter.png"
                         alt="geo-logo" class="geo-logoalter"/>
                    <ul>
                        <?php
                        $tabs = geodir_get_settings_tabs();
                        geodir_update_option('geodir_tabs', $tabs);// Important to show settings menu dropdown

                        foreach ($tabs as $name => $args) :
                            $label = $args['label'];


                            $query_string = '';
                            if (isset($args['subtabs']) && !empty($args['subtabs'])):

                                $subtabs = $args['subtabs'];

                                $query_string = '&subtab=' . $subtabs[0]['subtab'];

                            endif;


                            $tab_link = admin_url('admin.php?page=geodirectory&tab=' . $name . $query_string);

                            if (isset($args['url']) && $args['url'] != '') {
                                $tab_link = $args['url'];
                            }

                            if (!empty($args['request']))
                                $tab_link = geodir_getlink($tab_link, $args['request']);

                            if (isset($args['target']) && $args['target'] != '') {
                                $tab_target = " target='" . sanitize_text_field($args['target']) . "' ";
                            } else
                                $tab_target = '';

                            $tab_active = '';
                            if ($current_tab == $name)
                                $tab_active = ' class="tab-active" ';
                            /**
                             * Called before the individual settings tabs are output.
                             *
                             * @since 1.0.0
                             * @param string $name The name of the settings tab.
                             * @see 'geodir_after_settings_tabs'
                             */
                            do_action('geodir_before_settings_tabs', $name);
                            echo '<li ' . $tab_active . ' ><a href="' . esc_url($tab_link) . '"  ' . $tab_target . ' >' . $label . '</a></li>';
                            /**
                             * Called after the individual settings tabs are output.
                             *
                             * @since 1.0.0
                             * @param string $name The name of the settings tab.
                             * @see 'geodir_before_settings_tabs'
                             */
                            do_action('geodir_after_settings_tabs', $name);
                        endforeach;

                        /**
                         * Called after the GD settings tabs have been output.
                         *
                         * Called before the closing `ul` so can be used to add new settings tab links.
                         *
                         * @since 1.0.0
                         */
                        do_action('geodir_settings_tabs');
                        ?>
                    </ul>
                </div>
                <!--gd-left-nav ends here-->

                <div class="gd-content-wrapper">
                    <div class="gd-tabs-main">

                        <?php
                        unset($subtabs);
                        if (isset($tabs[$current_tab]['subtabs']))
                            $subtabs = $tabs[$current_tab]['subtabs'];
                        $form_action = '';

                        if (!empty($subtabs)):
                        ?>
                            <dl class="gd-tab-head">
                                <?php
                                foreach ($subtabs as $sub) {
                                    $subtab_active = '';
                                    if (isset($_REQUEST['subtab']) && $sub['subtab'] == $_REQUEST['subtab']) {
                                        $subtab_active = 'class="gd-tab-active"';
                                        $form_action = isset($sub['form_action']) ? $sub['form_action'] : '';
                                    }

                                    $sub_tabs_link = admin_url() . 'admin.php?page=geodirectory&tab=' . $current_tab . '&subtab=' . $sub['subtab'];
                                    if (isset($sub['request']) && is_array($sub['request']) && !empty($sub['request'])) {
                                        $sub_tabs_link = geodir_getlink($sub_tabs_link, $sub['request']);
                                    }
                                    echo '<dd ' . $subtab_active . ' id="claim_listing"><a href="' . esc_url($sub_tabs_link) . '" >' . sanitize_text_field($sub['label']) . '</a></dd>';
                                }
                                ?>
                            </dl>

                        <?php endif; ?>
                        <div class="gd-tab-content <?php if (empty($subtabs)) {
                            echo "inner_contet_tabs";
                        } ?>">
                            <form method="post" id="mainform"
                                  class="geodir_optionform <?php echo $current_tab . ' '; ?><?php if (isset($sub['subtab'])) {
                                      echo sanitize_text_field($sub['subtab']);
                                  } ?>" action="<?php echo $form_action; ?>" enctype="multipart/form-data">
                                <input type="hidden" class="active_tab" name="active_tab"
                                       value="<?php if (isset($_REQUEST['active_tab'])) {
                                           echo sanitize_text_field($_REQUEST['active_tab']);
                                       } ?>"/>
                                <?php wp_nonce_field('geodir-settings', '_wpnonce', true, true); ?>
                                <?php wp_nonce_field('geodir-settings-' . $current_tab, '_wpnonce-' . $current_tab, true, true); ?>
                                <?php
                                /**
                                 * Used to call the content of each GD settings tab page.
                                 *
                                 * @since 1.0.0
                                 */
                                do_action('geodir_admin_option_form', $current_tab); ?>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <script type="text/javascript">
            jQuery(window).load(function () {
                // Subsubsub tabs
                jQuery('ul.subsubsub li a:eq(0)').addClass('current');
                jQuery('.subsubsub_section .section:gt(0)').hide();

                jQuery('ul.subsubsub li a').click(function () {
                    /*jQuery('a', jQuery(this).closest('ul.subsubsub')).removeClass('current');
                     jQuery(this).addClass('current');
                     jQuery('.section', jQuery(this).closest('.subsubsub_section')).hide();
                     jQuery( jQuery(this).attr('href') ).show();
                     jQuery('#last_tab').val( jQuery(this).attr('href') );
                     return false;*/
                });
                <?php if (isset($_GET['subtab']) && $_GET['subtab']) echo 'jQuery(\'ul.subsubsub li a[href="#' . sanitize_text_field($_GET['subtab']) . '"]\').click();'; ?>
                // Countries
                jQuery('select#geodirectory_allowed_countries').change(function () {
                    if (jQuery(this).val() == "specific") {
                        jQuery(this).parent().parent().next('tr').show();
                    } else {
                        jQuery(this).parent().parent().next('tr').hide();
                    }
                }).change();

                // Color picker
                jQuery('.colorpick').each(function () {
                    jQuery('.colorpickdiv', jQuery(this).parent()).farbtastic(this);
                    jQuery(this).click(function () {
                        if (jQuery(this).val() == "") jQuery(this).val('#');
                        jQuery('.colorpickdiv', jQuery(this).parent()).show();
                    });
                });
                jQuery(document).mousedown(function () {
                    jQuery('.colorpickdiv').hide();
                });

                // Edit prompt
                jQuery(function () {
                    var changed = false;

                    jQuery('input, textarea, select, checkbox').change(function () {
                        changed = true;
                    });

                    jQuery('.geodirectory-nav-tab-wrapper a').click(function () {
                        if (changed) {
                            window.onbeforeunload = function () {
                                return '<?php echo __( 'The changes you made will be lost if you navigate away from this page.', 'geodirectory'); ?>';
                            }
                        } else {
                            window.onbeforeunload = '';
                        }
                    });

                    jQuery('.submit input').click(function () {
                        window.onbeforeunload = '';
                    });
                });

                // Sorting
                jQuery('table.wd_gateways tbody').sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    handle: 'td',
                    scrollSensitivity: 40,
                    helper: function (e, ui) {
                        ui.children().each(function () {
                            jQuery(this).width(jQuery(this).width());
                        });
                        ui.css('left', '0');
                        return ui;
                    },
                    start: function (event, ui) {
                        ui.item.css('background-color', '#f6f6f6');
                    },
                    stop: function (event, ui) {
                        ui.item.removeAttr('style');
                    }
                });
                
                jQuery("select.geodir-select").trigger('geodir-select-init');
                jQuery("select.geodir-select-nostd").trigger('geodir-select-init');
            });
        </script>
    <?php

    }
}



/*
function gd_compat_read_write_code($code,$theme){

$url = wp_nonce_url('admin.php?page=geodirectory&tab=compatibility_settings','gd-compat-theme-options');
if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
	return; // stop processing here
}

if ( ! WP_Filesystem($creds) ) {
	request_filesystem_credentials($url, '', true, false, null);
	return;
}

global $wp_filesystem;

$wp_filesystem->put_contents(
  plugin_dir_path( __FILE__ ).'/geodirectory/includes/templates/compatibility/'.$theme.'php',
  'Example contents of a file',
  FS_CHMOD_FILE // predefined mode settings for WP files
);
 
 
 }
*/


/**
 * Updates theme compatibility settings.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 */
function geodir_update_options_compatibility_settings()
{

    global $wpdb;


    $theme_settings = array();

    $theme_settings['geodir_wrapper_open_id'] = $_POST['geodir_wrapper_open_id'];
    $theme_settings['geodir_wrapper_open_class'] = $_POST['geodir_wrapper_open_class'];
    $theme_settings['geodir_wrapper_open_replace'] = stripslashes($_POST['geodir_wrapper_open_replace']);

    $theme_settings['geodir_wrapper_close_replace'] = stripslashes($_POST['geodir_wrapper_close_replace']);

    $theme_settings['geodir_wrapper_content_open_id'] = $_POST['geodir_wrapper_content_open_id'];
    $theme_settings['geodir_wrapper_content_open_class'] = $_POST['geodir_wrapper_content_open_class'];
    $theme_settings['geodir_wrapper_content_open_replace'] = stripslashes($_POST['geodir_wrapper_content_open_replace']);

    $theme_settings['geodir_wrapper_content_close_replace'] = stripslashes($_POST['geodir_wrapper_content_close_replace']);

    $theme_settings['geodir_article_open_id'] = $_POST['geodir_article_open_id'];
    $theme_settings['geodir_article_open_class'] = $_POST['geodir_article_open_class'];
    $theme_settings['geodir_article_open_replace'] = stripslashes($_POST['geodir_article_open_replace']);

    $theme_settings['geodir_article_close_replace'] = stripslashes($_POST['geodir_article_close_replace']);

    $theme_settings['geodir_sidebar_right_open_id'] = $_POST['geodir_sidebar_right_open_id'];
    $theme_settings['geodir_sidebar_right_open_class'] = $_POST['geodir_sidebar_right_open_class'];
    $theme_settings['geodir_sidebar_right_open_replace'] = stripslashes($_POST['geodir_sidebar_right_open_replace']);

    $theme_settings['geodir_sidebar_right_close_replace'] = stripslashes($_POST['geodir_sidebar_right_close_replace']);

    $theme_settings['geodir_sidebar_left_open_id'] = $_POST['geodir_sidebar_left_open_id'];
    $theme_settings['geodir_sidebar_left_open_class'] = $_POST['geodir_sidebar_left_open_class'];
    $theme_settings['geodir_sidebar_left_open_replace'] = stripslashes($_POST['geodir_sidebar_left_open_replace']);

    $theme_settings['geodir_sidebar_left_close_replace'] = stripslashes($_POST['geodir_sidebar_left_close_replace']);

    $theme_settings['geodir_main_content_open_id'] = $_POST['geodir_main_content_open_id'];
    $theme_settings['geodir_main_content_open_class'] = $_POST['geodir_main_content_open_class'];
    $theme_settings['geodir_main_content_open_replace'] = stripslashes($_POST['geodir_main_content_open_replace']);

    $theme_settings['geodir_main_content_close_replace'] = stripslashes($_POST['geodir_main_content_close_replace']);

// Other Actions
    $theme_settings['geodir_top_content_add'] = stripslashes($_POST['geodir_top_content_add']);
    $theme_settings['geodir_before_main_content_add'] = stripslashes($_POST['geodir_before_main_content_add']);

// Filters
    $theme_settings['geodir_full_page_class_filter'] = stripslashes($_POST['geodir_full_page_class_filter']);
    $theme_settings['geodir_before_widget_filter'] = stripslashes($_POST['geodir_before_widget_filter']);
    $theme_settings['geodir_after_widget_filter'] = stripslashes($_POST['geodir_after_widget_filter']);
    $theme_settings['geodir_before_title_filter'] = stripslashes($_POST['geodir_before_title_filter']);
    $theme_settings['geodir_after_title_filter'] = stripslashes($_POST['geodir_after_title_filter']);
    $theme_settings['geodir_menu_li_class_filter'] = stripslashes($_POST['geodir_menu_li_class_filter']);
    $theme_settings['geodir_sub_menu_ul_class_filter'] = stripslashes($_POST['geodir_sub_menu_ul_class_filter']);
    $theme_settings['geodir_sub_menu_li_class_filter'] = stripslashes($_POST['geodir_sub_menu_li_class_filter']);
    $theme_settings['geodir_menu_a_class_filter'] = stripslashes($_POST['geodir_menu_a_class_filter']);
    $theme_settings['geodir_sub_menu_a_class_filter'] = stripslashes($_POST['geodir_sub_menu_a_class_filter']);
//location manager filters
    $theme_settings['geodir_location_switcher_menu_li_class_filter'] = stripslashes($_POST['geodir_location_switcher_menu_li_class_filter']);
    $theme_settings['geodir_location_switcher_menu_a_class_filter'] = stripslashes($_POST['geodir_location_switcher_menu_a_class_filter']);
    $theme_settings['geodir_location_switcher_menu_sub_ul_class_filter'] = stripslashes($_POST['geodir_location_switcher_menu_sub_ul_class_filter']);
    $theme_settings['geodir_location_switcher_menu_sub_li_class_filter'] = stripslashes($_POST['geodir_location_switcher_menu_sub_li_class_filter']);


// theme required css
    $theme_settings['geodir_theme_compat_css'] = stripslashes($_POST['geodir_theme_compat_css']);

// theme required js
    $theme_settings['geodir_theme_compat_js'] = stripslashes($_POST['geodir_theme_compat_js']);

// theme compat name
    $theme_settings['gd_theme_compat'] = $_POST['gd_theme_compat'];
    if ($theme_settings['gd_theme_compat'] == '') {
        geodir_update_option('gd_theme_compat', '');
        geodir_update_option('theme_compatibility_setting', '');
        return;
    }

// theme default options
    $theme_settings['geodir_theme_compat_default_options'] = '';


//supported theme code
    $theme_settings['geodir_theme_compat_code'] = false;

    $theme = wp_get_theme();

    if ($theme->parent()) {
        $theme_name = str_replace(" ", "_", $theme->parent()->get('Name'));
    } else {
        $theme_name = str_replace(" ", "_", $theme->get('Name'));
    }

    if (in_array($theme_name, array('Avada', 'Enfold', 'X', 'Divi', 'Genesis', 'Jupiter', 'Multi_News','Kleo','Twenty_Seventeen'))) {// list of themes that have php files
        $theme_settings['geodir_theme_compat_code'] = $theme_name;
    }


    $theme_name = $theme_name . "_custom";
    $theme_arr = geodir_get_option('gd_theme_compats');
    geodir_update_option('gd_theme_compat', $theme_name);
    /**
     * Called before the theme compatibility settings are saved to the DB.
     *
     * @since 1.4.0
     * @param array $theme_settings {
     *    Attributes of the theme compatibility settings array.
     *
     *    @type string $geodir_wrapper_open_id Geodir wrapper open html id.
     *    @type string $geodir_wrapper_open_class Geodir wrapper open html class.
     *    @type string $geodir_wrapper_open_replace Geodir wrapper open content replace.
     *    @type string $geodir_wrapper_close_replace Geodir wrapper close content replace.
     *    @type string $geodir_wrapper_content_open_id Geodir wrapper content open html id.
     *    @type string $geodir_wrapper_content_open_class Geodir wrapper content open html class.
     *    @type string $geodir_wrapper_content_open_replace Geodir wrapper content open content replace.
     *    @type string $geodir_wrapper_content_close_replace Geodir wrapper content close content replace.
     *    @type string $geodir_article_open_id Geodir article open html id.
     *    @type string $geodir_article_open_class Geodir article open html class.
     *    @type string $geodir_article_open_replace Geodir article open content replace.
     *    @type string $geodir_article_close_replace Geodir article close content replace.
     *    @type string $geodir_sidebar_right_open_id Geodir sidebar right open html id.
     *    @type string $geodir_sidebar_right_open_class Geodir sidebar right open html class.
     *    @type string $geodir_sidebar_right_open_replace Geodir sidebar right open content replace.
     *    @type string $geodir_sidebar_right_close_replace Geodir sidebar right close content replace.
     *    @type string $geodir_sidebar_left_open_id Geodir sidebar left open html id.
     *    @type string $geodir_sidebar_left_open_class Geodir sidebar left open html class.
     *    @type string $geodir_sidebar_left_open_replace Geodir sidebar left open content replace.
     *    @type string $geodir_sidebar_left_close_replace Geodir sidebar left close content replace.
     *    @type string $geodir_main_content_open_id Geodir main content open html id.
     *    @type string $geodir_main_content_open_class Geodir main content open html class.
     *    @type string $geodir_main_content_open_replace Geodir main content open content replace.
     *    @type string $geodir_main_content_close_replace Geodir main content close content replace.
     *    @type string $geodir_top_content_add Geodir top content add.
     *    @type string $geodir_before_main_content_add Geodir before main content add.
     *    @type string $geodir_full_page_class_filter Geodir full page class filter.
     *    @type string $geodir_before_widget_filter Geodir before widget filter.
     *    @type string $geodir_after_widget_filter Geodir after widget filter.
     *    @type string $geodir_before_title_filter Geodir before title filter.
     *    @type string $geodir_after_title_filter Geodir after title filter.
     *    @type string $geodir_menu_li_class_filter Geodir menu li class filter.
     *    @type string $geodir_sub_menu_ul_class_filter Geodir sub menu ul class filter.
     *    @type string $geodir_sub_menu_li_class_filter Geodir sub menu li class filter.
     *    @type string $geodir_menu_a_class_filter Geodir menu a class filter.
     *    @type string $geodir_sub_menu_a_class_filter Geodir sub menu a class filter.
     *    @type string $geodir_location_switcher_menu_li_class_filter Geodir location switcher menu li class filter.
     *    @type string $geodir_location_switcher_menu_a_class_filter Geodir location switcher menu a class filter.
     *    @type string $geodir_location_switcher_menu_sub_ul_class_filter Geodir location switcher menu sub ul class filter.
     *    @type string $geodir_location_switcher_menu_sub_li_class_filter Geodir location switcher menu sub li class filter.
     *    @type string $geodir_theme_compat_css Geodir theme compatibility css.
     *    @type string $geodir_theme_compat_js Geodir theme compatibility js.
     *    @type string $gd_theme_compat Gd theme compatibility.
     *    @type string $geodir_theme_compat_default_options Geodir theme compatibility default options.
     *    @type bool $geodir_theme_compat_code Geodir theme compatibility code Ex: 'Avada.
     *
     * }
     */
    do_action('gd_compat_save_settings', $theme_settings);

    $theme_arr[$theme_name] = $theme_settings;
    
    geodir_update_option( 'gd_theme_compats', $theme_arr );
    geodir_update_option( 'theme_compatibility_setting', $theme_settings );
}

/**
 * Displays theme compatibility settings.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 */
function geodir_theme_compatibility_setting_page()
{
    global $wpdb;
    $tc = geodir_get_option( 'theme_compatibility_setting' );
    //print_r($tc);
    //print_r(wp_get_theme());

    ?>
    <div class="inner_content_tab_main">
        <div class="gd-content-heading">


            <h3><?php _e('Theme Compatibility Settings', 'geodirectory');?></h3>
            <style>
                .gd-theme-compat-table {
                    width: 100%;
                    border: 1px solid #666;
                }

                #gd-import-export-theme-comp, .gd-theme-compat-table textarea {
                    width: 100%;
                }

                .gd-theme-comp-out {
                    border-bottom: #000000 solid 1px;
                }

                .gd-comp-import-export {
                    display: none;
                }

                #gd-compat-warnings h3 {
                    background-color: #FFA07A;
                }

            </style>

            <?php if (str_replace("_custom", "", geodir_get_option('gd_theme_compat')) == 'Avada') { ?>
                <div id="gd-compat-warnings">
                    <h3><?php _e('Since Avada 3.8+ they have added hooks for compatibility for GeoDirectory so the header.php modification is no longer required. <a href="http://docs.wpgeodirectory.com/avada-compatibility-header-php/" target="_blank">See here</a>', 'geodirectory'); ?></h3>
                </div>
            <?php }?>

            <h4><?php _e('Select Theme Compatibility Pack', 'geodirectory');?></h4>

            <select name="gd_theme_compat" id="gd_theme_compat">
                <option value=""><?php _e('Select Theme', 'geodirectory');?></option>
                <option value="custom"><?php _e('Custom', 'geodirectory');?></option>
                <?php
                $theme_arr = geodir_get_option('gd_theme_compats');
                $theme_active = geodir_get_option('gd_theme_compat');
                if (is_array($theme_arr)) {
                    foreach ($theme_arr as $key => $theme) {
                        $sel = '';
                        if ($theme_active == $key) {
                            $sel = "selected";
                        }
                        echo "<option $sel>$key</option>";
                    }


                }

                ?>
            </select>
            <button onclick="gd_comp_export();" type="button"
                    class="button-primary"><?php _e('Export', 'geodirectory');?></button>
            <button onclick="gd_comp_import();" type="button"
                    class="button-primary"><?php _e('Import', 'geodirectory');?></button>

            <div class="gd-comp-import-export">
                <textarea id="gd-import-export-theme-comp"
                          placeholder="<?php _e('Paste the JSON code here and then click import again', 'geodirectory');?>"></textarea>
            </div>
            <script>

                function gd_comp_export() {
                    theme = jQuery('#gd_theme_compat').val();
                    if (theme == '' || theme == 'custom') {
                        alert("<?php _e('Please select a theme to export','geodirectory');?>");
                        return false;
                    }
                    jQuery('.gd-comp-import-export').show();
                    var data = {
                        'action': 'get_gd_theme_compat_callback',
                        'theme': theme,
                        'export': true
                    };
                    jQuery.post(ajaxurl, data, function (response) {
                        jQuery('#gd-import-export-theme-comp').val(response);
                    });
                    return false;
                }

                function gd_comp_import() {
                    if (jQuery('.gd-comp-import-export').css('display') == 'none') {
                        jQuery('#gd-import-export-theme-comp').val('');
                        jQuery('.gd-comp-import-export').show();
                        return false;
                    }

                    json = jQuery('#gd-import-export-theme-comp').val();
                    if (json == '') {
                        return false;
                    }

                    var data = {
                        'action': 'get_gd_theme_compat_import_callback',
                        'theme': json
                    };

                    jQuery.post(ajaxurl, data, function (response) {
                        if (response == '0') {
                            alert("<?php _e('Something went wrong','geodirectory');?>");
                        } else {
                            alert("<?php _e('Theme Compatibility Imported','geodirectory');?>");
                            jQuery('#gd-import-export-theme-comp').val('');
                            jQuery('.gd-comp-import-export').hide();
                            jQuery('#gd_theme_compat').append(new Option(response, response));
                        }
                    });
                    return false;
                }

                jQuery("#gd_theme_compat").change(function () {
                    var data = {
                        'action': 'get_gd_theme_compat_callback',
                        'theme': jQuery(this).val()
                    };

                    if (jQuery(this).val() == 'custom') {
                        return;
                    }
                    if (jQuery(this).val() != '') {
                        jQuery.post(ajaxurl, data, function (response) {
                            var obj = jQuery.parseJSON(response);
                            console.log(obj);
                            gd_fill_compat_fields(obj);
                        });
                    } else {
                        jQuery(this).closest('form').find("input[type=text], textarea").val("");

                    }

                });

                function gd_fill_compat_fields(obj) {

                    jQuery.each(obj, function (i, item) {
                        jQuery('[name="' + i + '"]').val(item);
                    });

                }

            </script>

            <h4><?php _e('Main Wrapper Actions', 'geodirectory');?></h4>

            <table class="form-table gd-theme-compat-table">
                <tbody>
                <tr>
                    <td><strong><?php _e('Hook', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('ID', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('Class', 'geodirectory');?></strong></td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_wrapper_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_wrapper_open_id'])) {
                            echo $tc['geodir_wrapper_open_id'];
                        }?>" type="text" name="geodir_wrapper_open_id" placeholder="geodir-wrapper"/></td>
                    <td><input value="<?php if (isset($tc['geodir_wrapper_open_class'])) {
                            echo $tc['geodir_wrapper_open_class'];
                        }?>" type="text" name="geodir_wrapper_open_class" placeholder=""/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_wrapper_open_replace"
                                  placeholder='<div id="[id]" class="[class]">'><?php if (isset($tc['geodir_wrapper_open_replace'])) {
                                echo $tc['geodir_wrapper_open_replace'];
                            }?></textarea>
                    </td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_wrapper_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_wrapper_open_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_wrapper_open_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_wrapper_close_replace"
                                  placeholder='</div><!-- wrapper ends here-->'><?php if (isset($tc['geodir_wrapper_close_replace'])) {
                                echo $tc['geodir_wrapper_close_replace'];
                            }?></textarea>
                    </td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_wrapper_content_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_wrapper_content_open_id'])) {
                            echo $tc['geodir_wrapper_content_open_id'];
                        }?>" type="text" name="geodir_wrapper_content_open_id" placeholder="geodir-wrapper-content"/>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_wrapper_content_open_class'])) {
                            echo $tc['geodir_wrapper_content_open_class'];
                        }?>" type="text" name="geodir_wrapper_content_open_class" placeholder=""/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_wrapper_content_open_replace"
                                  placeholder='<div id="[id]" class="[class]" role="main" [width_css]>'><?php if (isset($tc['geodir_wrapper_content_open_replace'])) {
                                echo $tc['geodir_wrapper_content_open_replace'];
                            }?></textarea>
                    </td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_wrapper_content_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_wrapper_content_close_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_wrapper_content_close_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_wrapper_content_close_replace"
                                  placeholder='</div><!-- content ends here-->'><?php if (isset($tc['geodir_wrapper_content_close_replace'])) {
                                echo $tc['geodir_wrapper_content_close_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_article_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_article_open_id'])) {
                            echo $tc['geodir_article_open_id'];
                        }?>" type="text" name="geodir_article_open_id" placeholder="geodir-wrapper-content"/></td>
                    <td><input value="<?php if (isset($tc['geodir_article_open_class'])) {
                            echo $tc['geodir_article_open_class'];
                        }?>" type="text" name="geodir_article_open_class" placeholder=""/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_article_open_replace"
                                  placeholder='<article  id="[id]" class="[class]" itemscope itemtype="[itemtype]">'><?php if (isset($tc['geodir_article_open_replace'])) {
                                echo $tc['geodir_article_open_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_article_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_article_close_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_article_close_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_article_close_replace"
                                  placeholder='</article><!-- article ends here-->'><?php if (isset($tc['geodir_article_close_replace'])) {
                                echo $tc['geodir_article_close_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sidebar_right_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_sidebar_right_open_id'])) {
                            echo $tc['geodir_sidebar_right_open_id'];
                        }?>" type="text" name="geodir_sidebar_right_open_id" placeholder="geodir-sidebar-right"/></td>
                    <td><input value="<?php if (isset($tc['geodir_sidebar_right_open_class'])) {
                            echo $tc['geodir_sidebar_right_open_class'];
                        }?>" type="text" name="geodir_sidebar_right_open_class"
                               placeholder="geodir-sidebar-right geodir-listings-sidebar-right"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_sidebar_right_open_replace"
                                  placeholder='<aside  id="[id]" class="[class]" role="complementary" itemscope itemtype="[itemtype]" [width_css]>'><?php if (isset($tc['geodir_sidebar_right_open_replace'])) {
                                echo $tc['geodir_sidebar_right_open_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sidebar_right_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_sidebar_right_close_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_sidebar_right_close_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_sidebar_right_close_replace"
                                  placeholder='</aside><!-- sidebar ends here-->'><?php if (isset($tc['geodir_sidebar_right_close_replace'])) {
                                echo $tc['geodir_sidebar_right_close_replace'];
                            }?></textarea>
                    </td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_sidebar_left_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_sidebar_left_open_id'])) {
                            echo $tc['geodir_sidebar_left_open_id'];
                        }?>" type="text" name="geodir_sidebar_left_open_id" placeholder="geodir-sidebar-left"/></td>
                    <td><input value="<?php if (isset($tc['geodir_sidebar_left_open_class'])) {
                            echo $tc['geodir_sidebar_left_open_class'];
                        }?>" type="text" name="geodir_sidebar_left_open_class"
                               placeholder="geodir-sidebar-left geodir-listings-sidebar-left"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_sidebar_left_open_replace"
                                  placeholder='<aside  id="[id]" class="[class]" role="complementary" itemscope itemtype="[itemtype]" [width_css]>'><?php if (isset($tc['geodir_sidebar_left_open_replace'])) {
                                echo $tc['geodir_sidebar_left_open_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sidebar_left_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_sidebar_left_close_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_sidebar_left_close_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_sidebar_left_close_replace"
                                  placeholder='</aside><!-- sidebar ends here-->'><?php if (isset($tc['geodir_sidebar_left_close_replace'])) {
                                echo $tc['geodir_sidebar_left_close_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_main_content_open</small>
                    </td>
                    <td><input value="<?php if (isset($tc['geodir_main_content_open_id'])) {
                            echo $tc['geodir_main_content_open_id'];
                        }?>" type="text" name="geodir_main_content_open_id" placeholder="geodir-main-content"/></td>
                    <td><input value="<?php if (isset($tc['geodir_main_content_open_class'])) {
                            echo $tc['geodir_main_content_open_class'];
                        }?>" type="text" name="geodir_main_content_open_class" placeholder="CURRENT-PAGE-page"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_main_content_open_replace"
                                  placeholder='<main  id="[id]" class="[class]"  role="main">'><?php if (isset($tc['geodir_main_content_open_replace'])) {
                                echo $tc['geodir_main_content_open_replace'];
                            }?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_main_content_close</small>
                    </td>
                    <td><input disabled="disabled" type="text" name="geodir_main_content_close_id"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                    <td><input disabled="disabled" type="text" name="geodir_main_content_close_class"
                               placeholder="<?php _e('Not used', 'geodirectory');?>"/></td>
                </tr>

                <tr class="gd-theme-comp-out">
                    <td colspan="3">
                        <span><?php _e('Output:', 'geodirectory');?></span>
                        <textarea name="geodir_main_content_close_replace"
                                  placeholder='</main><!-- main ends here-->'><?php if (isset($tc['geodir_main_content_close_replace'])) {
                                echo $tc['geodir_main_content_close_replace'];
                            }?></textarea>
                    </td>
                </tr>


                </tbody>
            </table>

            <h4><?php _e('Other Actions', 'geodirectory');?></h4>

            <table class="form-table gd-theme-compat-table">
                <tbody>
                <tr>
                    <td><strong><?php _e('Hook', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('Content', 'geodirectory');?></strong></td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_top_content</small>
                    </td>
                    <td><textarea name="geodir_top_content_add"
                                  placeholder=''><?php if (isset($tc['geodir_top_content_add'])) {
                                echo $tc['geodir_top_content_add'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_before_main_content</small>
                    </td>
                    <td><textarea name="geodir_before_main_content_add"
                                  placeholder=''><?php if (isset($tc['geodir_before_main_content_add'])) {
                                echo $tc['geodir_before_main_content_add'];
                            }?></textarea></td>
                </tr>


                </tbody>
            </table>


            <h4><?php _e('Other Filters', 'geodirectory');?></h4>

            <table class="form-table gd-theme-compat-table">
                <tbody>
                <tr>
                    <td><strong><?php _e('Filter', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('Content', 'geodirectory');?></strong></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_full_page_class</small>
                    </td>
                    <td><textarea name="geodir_full_page_class_filter"
                                  placeholder='geodir_full_page clearfix'><?php if (isset($tc['geodir_full_page_class_filter'])) {
                                echo $tc['geodir_full_page_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_before_widget</small>
                    </td>
                    <td><textarea name="geodir_before_widget_filter"
                                  placeholder='<section id="%1$s" class="widget geodir-widget %2$s">'><?php if (isset($tc['geodir_before_widget_filter'])) {
                                echo $tc['geodir_before_widget_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_after_widget</small>
                    </td>
                    <td><textarea name="geodir_after_widget_filter"
                                  placeholder='</section>'><?php if (isset($tc['geodir_after_widget_filter'])) {
                                echo $tc['geodir_after_widget_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_before_title</small>
                    </td>
                    <td><textarea name="geodir_before_title_filter"
                                  placeholder='<h3 class="widget-title">'><?php if (isset($tc['geodir_before_title_filter'])) {
                                echo $tc['geodir_before_title_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_after_title</small>
                    </td>
                    <td><textarea name="geodir_after_title_filter"
                                  placeholder='</h3>'><?php if (isset($tc['geodir_after_title_filter'])) {
                                echo $tc['geodir_after_title_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_menu_li_class</small>
                    </td>
                    <td><textarea name="geodir_menu_li_class_filter"
                                  placeholder='menu-item'><?php if (isset($tc['geodir_menu_li_class_filter'])) {
                                echo $tc['geodir_menu_li_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sub_menu_ul_class</small>
                    </td>
                    <td><textarea name="geodir_sub_menu_ul_class_filter"
                                  placeholder='sub-menu'><?php if (isset($tc['geodir_sub_menu_ul_class_filter'])) {
                                echo $tc['geodir_sub_menu_ul_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sub_menu_li_class</small>
                    </td>
                    <td><textarea name="geodir_sub_menu_li_class_filter"
                                  placeholder='menu-item'><?php if (isset($tc['geodir_sub_menu_li_class_filter'])) {
                                echo $tc['geodir_sub_menu_li_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_menu_a_class</small>
                    </td>
                    <td><textarea name="geodir_menu_a_class_filter"
                                  placeholder=''><?php if (isset($tc['geodir_menu_a_class_filter'])) {
                                echo $tc['geodir_menu_a_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_sub_menu_a_class</small>
                    </td>
                    <td><textarea name="geodir_sub_menu_a_class_filter"
                                  placeholder=''><?php if (isset($tc['geodir_sub_menu_a_class_filter'])) {
                                echo $tc['geodir_sub_menu_a_class_filter'];
                            }?></textarea></td>
                </tr>


                <tr>
                    <td>
                        <small>geodir_location_switcher_menu_li_class</small>
                    </td>
                    <td><textarea name="geodir_location_switcher_menu_li_class_filter"
                                  placeholder='menu-item menu-item-type-social menu-item-type-social gd-location-switcher'><?php if (isset($tc['geodir_location_switcher_menu_li_class_filter'])) {
                                echo $tc['geodir_location_switcher_menu_li_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_location_switcher_menu_a_class</small>
                    </td>
                    <td><textarea name="geodir_location_switcher_menu_a_class_filter"
                                  placeholder=''><?php if (isset($tc['geodir_location_switcher_menu_a_class_filter'])) {
                                echo $tc['geodir_location_switcher_menu_a_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_location_switcher_menu_sub_ul_class</small>
                    </td>
                    <td><textarea name="geodir_location_switcher_menu_sub_ul_class_filter"
                                  placeholder='sub-menu'><?php if (isset($tc['geodir_location_switcher_menu_sub_ul_class_filter'])) {
                                echo $tc['geodir_location_switcher_menu_sub_ul_class_filter'];
                            }?></textarea></td>
                </tr>

                <tr>
                    <td>
                        <small>geodir_location_switcher_menu_sub_li_class</small>
                    </td>
                    <td><textarea name="geodir_location_switcher_menu_sub_li_class_filter"
                                  placeholder='menu-item gd-location-switcher-menu-item'><?php if (isset($tc['geodir_location_switcher_menu_sub_li_class_filter'])) {
                                echo $tc['geodir_location_switcher_menu_sub_li_class_filter'];
                            }?></textarea></td>
                </tr>



                <?php
                /**
                 * Allows more filter setting to be added to theme compatibility settings page.
                 *
                 * Called after the last setting in "Other filters" section of theme compatibility settings.
                 *
                 * @since 1.4.0
                 */
                do_action('gd_compat_other_filters');?>

                </tbody>
            </table>


            <h4><?php _e('Required CSS', 'geodirectory');?></h4>

            <table class="form-table gd-theme-compat-table">
                <tbody>
                <tr>
                    <td><textarea name="geodir_theme_compat_css"
                                  placeholder=''><?php if (isset($tc['geodir_theme_compat_css'])) {
                                echo $tc['geodir_theme_compat_css'];
                            }?></textarea></td>
                </tr>


                </tbody>
            </table>

            <h4><?php _e('Required JS', 'geodirectory');?></h4>

            <table class="form-table gd-theme-compat-table">
                <tbody>
                <tr>
                    <td><textarea name="geodir_theme_compat_js"
                                  placeholder=''><?php if (isset($tc['geodir_theme_compat_js'])) {
                                echo $tc['geodir_theme_compat_js'];
                            }?></textarea></td>
                </tr>


                </tbody>
            </table>


            <p class="submit">
                <input name="save" class="button-primary" type="submit"
                       value="<?php _e('Save changes', 'geodirectory');?>">
            </p>

        </div>
    </div>
<?php
}




/**
 * Displays 'GD Diagnostic Tools' page.
 *
 * @since 1.0.0
 * @package GeoDirectory
 */
function geodir_diagnostic_tools_setting_page()
{
    ?>
    <div class="inner_content_tab_main">
        <div class="gd-content-heading">


            <h3><?php _e('GD Diagnostic Tools', 'geodirectory');?></h3>
            <style>
                .gd-tools-table {
                    width: 100%;
                    border: 1px solid #666;
                }

                .gd-tool-results, .gd-tool-results td {
                    padding: 0px;
                }

                .gd-tool-results-remove {
                    float: right;
                    margin-top: 10px;
                }
            </style>
            <table class="form-table gd-tools-table">
                <tbody>
                <tr>
                    <td><strong><?php _e('Tool', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('Description', 'geodirectory');?></strong></td>
                    <td><strong><?php _e('Action', 'geodirectory');?></strong></td>
                </tr>


                <tr>
                    <td><?php _e('GD pages check', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('Checks if the GD pages are installed correctly or not.', 'geodirectory');?></small>
                    </td>
                    <td>
                        <input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" data-diagnose="default_pages"/>
                    </td>
                </tr>


                <tr>
                    <td><?php _e('Multisite DB conversion check', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('Checks if the GD database tables have been converted to use multisite correctly.', 'geodirectory');?></small>
                    </td>
                    <td><input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" data-diagnose="multisite_conversion"/>
                    </td>
                </tr>

                <tr>
                    <td><?php _e('Ratings check', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('Checks ratings for correct location and content settings', 'geodirectory');?></small>
                    </td>
                    <td><input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" data-diagnose="ratings"/>
                    </td>
                </tr>

                <tr>
                    <?php
                    $l_count = geodir_total_listings_count();
                    $step_max_items = geodir_get_diagnose_step_max_items();
                    if ($l_count > $step_max_items) {
                        $multiple = 'data-step="1"';
                    } else {
                        $multiple = "";
                    }
                    ?>
                    <td><?php _e('Sync GD tags', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('This tool can be used when tags are showing in the backend but missing from the front end.', 'geodirectory');?></small>
                        <?php
                        if ($l_count > $step_max_items) {
                            ?>
                            <table id="tags_sync_sub_table" class="widefat" style="display: none">
                                <?php
                                $all_postypes = geodir_get_posttypes('array');

                                if (!empty($all_postypes)) {
                                    foreach ($all_postypes as $key => $value) {
                                        ?>
                                        <tr id="tags_sync_<?php echo $key; ?>">
                                            <td>
                                                <?php echo $value['labels']['name']; ?>
                                            </td>
                                            <td>
                                                <input type="button" value="<?php _e('Run', 'geodirectory');?>"
                                                       class="button-primary geodir_diagnosis_button" data-ptype="<?php echo $key; ?>" data-diagnose="tags_sync"/>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </table>
                            <?php
                        }
                        ?>
                    </td>
                    <td>
                        <input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" <?php echo $multiple; ?> data-diagnose="tags_sync"/>

                    </td>
                </tr>

                <tr>
                    <td><?php _e('Sync GD Categories', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('This tool can be used when categories are missing from the details table but showing in other places in the backend (only checks posts with missing category info in details table)', 'geodirectory');?></small>
                    </td>
                    <td><input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" data-diagnose="cats_sync"/>
                    </td>
                </tr>


                <tr>
                    <td><?php _e('Clear all GD version numbers', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('This tool will clear all GD version numbers so any upgrade functions will run again.', 'geodirectory');?></small>
                    </td>
                    <td><input type="button" value="<?php _e('Run', 'geodirectory');?>"
                               class="button-primary geodir_diagnosis_button" data-diagnose="version_clear"/>
                    </td>
                </tr>
				<tr>
					<td><?php _e('Load custom fields translation', 'geodirectory');?></td>
					<td>
						<small><?php _e('This tool will load strings from the database into a file to translate via po editor.Ex: custom fields', 'geodirectory');?></small>
					</td>
					<td>
						<input type="button" value="<?php _e('Run', 'geodirectory');?>" class="button-primary geodir_diagnosis_button" data-diagnose="load_db_language"/>
					</td>
				</tr>
                <tr>
                    <td><?php _e('Reload Countries table', 'geodirectory');?></td>
                    <td>
                        <small><?php _e('This tool will drop and re-add the countries table, it is meant to refresh the list when countries are added/removed, if you have duplicate country problems you should merge those first or you could have orphaned posts.', 'geodirectory');?></small>
                    </td>
                    <td>
                        <input type="button" value="<?php _e('Run', 'geodirectory');?>" class="button-primary geodir_diagnosis_button" data-diagnose="reload_db_countries"/>
                    </td>
                </tr>
                <?php
                /**
                 * Allows you to add more setting to the GD>Tools settings page.
                 *
                 * Called after the last setting on the GD>Tools page.
                 * @since 1.0.0
                 */
                do_action('geodir_diagnostic_tool');?>

                </tbody>
            </table>

        </div>
    </div>
<?php
}