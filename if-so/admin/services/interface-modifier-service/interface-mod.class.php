<?php
namespace IfSo\Admin\Services\InterfaceModService;

use IfSo\Services\PluginSettingsService\PluginSettingsService;

require_once(IFSO_PLUGIN_BASE_DIR . 'services/plugin-settings-service/plugin-settings-service.class.php');

if ( ! defined( 'ABSPATH' ) ) exit;

class InterfaceModService{

    private static $instance;

    private function __construct(){
    }

    public static function get_instance(){
        if (NULL == self::$instance)
            self::$instance = new InterfaceModService();

        return self::$instance;
    }

    public function replace_newtrigger_title_placeholder($title,$post){

        if($post->post_type == 'ifso_triggers'){
            $ret = __('Trigger title (optional)','if-so');
            return $ret;
        }

        return $title;
    }

    public function add_export_button($actions,$post){
        if ($post->post_type=='ifso_triggers' && current_user_can('edit_posts')) {
            $actions['export'] = '<a href="' . admin_url('admin-ajax.php?action=trigger_export_req&exporttrigger&postid=' . $post->ID, basename(__FILE__) ) . '&_ifsononce=' . $this->create_trigger_port_nonce()  . '" title="'. __('Export this trigger', 'if-so').'" rel="permalink">'. __('Export', 'if-so') .'</a>';
        }
        return $actions;
    }

    public function add_scan_button($actions,$post){
        if ($post->post_type=='ifso_triggers' && current_user_can('edit_posts'))
            $actions['scan'] = $this->create_scan_button($post->ID);
        return $actions;
    }

    public function add_trigger_scan_button_triggerpage($post) {
        return;     //removed for now
        if ($post->post_type=='ifso_triggers' && current_user_can('edit_posts'))
            echo $this->create_scan_button($post->ID,'Find Shortcode');
    }

    public function create_scan_button($post_id,$button_text='Find Shortcode'){
        $url = admin_url('admin-ajax.php?action=trigger_scan_req&postid=' . $post_id, basename(__FILE__) ) . '&_ifsononce=' . wp_create_nonce('trigger-scan');
        $title = __('Scan posts for usages of this shortcode', 'if-so');
        $text =  __($button_text , 'if-so');
        return <<<HTM
                 <a href="{$url}" title="{$title}" rel="permalink"  onclick="window.open('{$url}', 'newwindow', 'width=980,height=900'); return false;">{$text}</a>
HTM;
    }

    public function trigger_scan_page(){
        require_once(IFSO_PLUGIN_BASE_DIR . 'admin/partials/ifso_trigger_scan_page_display.php');
    }

    private function scan_posts_for_ifso_triggers($tid = null){
        global $wp_post_types;
        if(isset($wp_post_types['ifso_triggers']))
            $wp_post_types['ifso_triggers']->exclude_from_search = false;
        if(!is_numeric($tid))
            $tid = null;
        $ret=['triggers'=>[],'conversions'=>[]];
        $args = [
            'posts_per_page' => -1,
            'post_type' => 'any',
        ];
        $query = new \WP_Query($args);
        $tid_regex_part = $tid===null ? '.+' : $tid;
        $trigger_sc_regex = '/\[.*ifso.+id.?\=[\'\"]'. $tid_regex_part .'[\'\"].*\]|ifso\/ifso-block.+\"selected\"\:' . $tid_regex_part .'/';   //if-so trigger shortcode or Gutenberg block
        $relevant_conversion_exists = function($content,$tid){
            $conversion_sc_regex = '/\[.*ifso\_conversion(.*)\]/U';
            if(preg_match_all($conversion_sc_regex,$content,$matches)){
                if($tid===null) return true;
                if(!empty($matches[0])){
                    foreach($matches[0] as $i=>$match){
                        if(empty($matches[1][$i])) return true;
                        $match_atts = shortcode_parse_atts($matches[1][$i]);
                        $allowed_triggers = (isset($match_atts['triggers']) && strtolower($match_atts['triggers'])!='all') ? explode(',',$match_atts['triggers'])  : false;
                        $disallowed_triggers = (isset($match_atts['exclude'])) ? explode(',',$match_atts['exclude'])  : [];
                        if(in_array($tid,$disallowed_triggers)) return false;
                        if(!$allowed_triggers || in_array($tid,$allowed_triggers)) return true;
                    }
                }
            }
            return false;
        };

        if($query->have_posts()){
            while($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $title = (!empty(get_the_title())) ? get_the_title() : "No Title (ID: {$id})";
                $edit_url = get_edit_post_link($id);
                $content = get_the_content();
                $link = get_permalink();
                if(get_post_type()==='ifso_triggers'){      //The contents of if-so triggers "live" inside the post meta
                    $postmeta = get_post_meta($id);
                    $content = !empty($postmeta['ifso_trigger_version']) ? implode(' ',$postmeta['ifso_trigger_version']) : '';
                    $content .= !empty($postmeta['ifso_trigger_default']) ? implode(' ',$postmeta['ifso_trigger_default']) : '';
                }
                $occ_data = ['title'=>$title,'edit'=>$edit_url,'content'=>$content,'link'=>$link];
                if(preg_match($trigger_sc_regex,$content))
                    $ret['triggers'][] = $occ_data;
                if($relevant_conversion_exists($content,$tid))
                    $ret['conversions'][] = $occ_data;
            }
        }
        wp_reset_postdata();
        if(!empty(PluginSettingsService::get_instance()->extraOptions->trigger_events['loadOnAllPages'])){
            $loadOnAllPagesData = PluginSettingsService::get_instance()->extraOptions->trigger_events['loadOnAllPages']->get();
            $occ_data = ['title'=>'"Load triggers on all pages" field','edit'=>admin_url( 'admin.php?page=' . EDD_IFSO_PLUGIN_SETTINGS_PAGE ),'content'=>$loadOnAllPagesData,'link'=>admin_url( 'admin.php?page=' . EDD_IFSO_PLUGIN_SETTINGS_PAGE )];;
            if(preg_match($trigger_sc_regex,$loadOnAllPagesData))
                $ret['triggers'][] = $occ_data;
            if($relevant_conversion_exists($loadOnAllPagesData,$tid))
                $ret['conversions'][] = $occ_data;
        }
        return($ret);
    }

    public function add_import_button($arr){
        if (current_user_can('edit_posts')) {
            $html = '<div class="wrap" style="margin-bottom:0;color: #0073aa;"> <form action="' . admin_url('admin-ajax.php?action=trigger_export_req&importtrigger=true&_ifsononce=' . $this->create_trigger_port_nonce()) . '" method="post" enctype="multipart/form-data"><label for="triggerToImport" style="font-weight:normal;display: inline-block;margin-bottom: 5px;cursor: pointer;"><span>+ '. __('Import  trigger', 'if-so') .'</span><input style="display:none" type="file" onchange="form.submit()" name="triggerToImport" id="triggerToImport"></label></form></div>';
        }
        echo $html;
        return $arr;
    }

    public function add_duplicate_button($arr,$post){
        if($post->post_type=='ifso_triggers'){
            if (current_user_can('edit_posts')) {
                $html = '<a href="' . admin_url('admin-ajax.php?action=trigger_export_req&duplicatetrigger=true&postid='.$post->ID.'&_ifsononce=' . $this->create_trigger_port_nonce()) . '">'. __('Duplicate', 'if-so') .'</a>';
            }
            $arr[] = $html;
        }
        return $arr;
    }

    private function create_trigger_port_nonce(){
        return wp_create_nonce('trigger-port');
    }

    public function trigger_imported_notice(){
        if(isset($_REQUEST['ifsoTriggerImported'])){
            if($_REQUEST['ifsoTriggerImported'] =='success'){
            ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Trigger imported successfully', 'if-so'); ?></p>
                </div>
                <?php
            }
            if($_REQUEST['ifsoTriggerImported'] =='fail'){
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e('Failed at importing trigger', 'if-so'); ?></p>
                </div>
                <?php
            }
        }
    }

    public function add_editor_modal_button(){
        global $post;
        if(isset($post) && $post->post_type !=='ifso_triggers' && !(isset($_GET['action']) && $_GET['action'] === 'elementor')){
            echo '<a href="'. admin_url( 'edit.php' ).'?post_type=ifso_triggers&TB_iframe=true&width=1024&height=600" id="ifso-editor-button" class="button thickbox" title="If-So triggers"><img style="bottom:1px;position:relative;width:11px;" src="'. plugin_dir_url(__FILE__) . '../../images/logo-256x256.png">'. __('Dynamic Content', 'if-so') .'</a>';
        }
        if((wp_doing_ajax() && $_REQUEST['action']==='load_tinymce_repeater') || (isset($post) && $post->post_type ==='ifso_triggers')){        //Adds if-so DKI button and the modal that it opens
            echo '<a href="#" class="button ifso-insert-dki-modal">If-So Shortcodes</a>';
            if((isset($post) && $post->post_type ==='ifso_triggers'))
                require_once(IFSO_PLUGIN_BASE_DIR . 'admin/partials/ifso_dki_modal_display.php');
        }

    }

    public function do_shortcode($content,$param=false){
        if(PluginSettingsService::get_instance()->applyTheContentFilterOption->get())
            return do_shortcode($content,$param);
        else
            return $content;
    }

    public function groups_page_notices(){
        if(!empty($_COOKIE['ifso-group-action-notice'])){
            $notice = $_COOKIE['ifso-group-action-notice'];
            $ret = '';

            if($notice === 'no-name-to-add'){
                $ret = '
                <div class="notice error is-dismissible" >
                    <p>'. __( 'You did not enter an audience name.', 'if-so' ) . '</p>
                </div>';
            }

            elseif($notice === 'already-exists'){
                $ret = '
                <div class="notice error is-dismissible" >
                    <p>'. __( 'An audience with that name already exists.', 'if-so' ) . '</p>
                </div>';
            }

            elseif($notice==='illegal-group-name'){
                $ret = '
                <div class="notice error is-dismissible" >
                    <p>'. __( 'Audience names can\'t contain commas or quotation marks.', 'if-so' ) . '</p>
                </div>';
            }

            elseif($notice === 'successfully-added'){
                $ret = '
                <div class="notice updated is-dismissible" >
                    <p>'. __( 'The audience has been successfully created', 'if-so' ) . '</p>
                </div>';
            }

            elseif($notice === 'successfully-removed'){
                $ret = '
                <div class="notice error is-dismissible" >
                    <p>'. __( 'The audience has been successfully removed.', 'if-so' ) . '</p>
                </div>';
            }

            setcookie('ifso-group-action-notice','no-name-to-add',time() - 3600*24,'/');

            echo $ret;
        }

    }

    private function get_active_plugins(){
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $active = get_option('active_plugins');
        $ret = [];
        foreach($plugins as $key=>$val){
            if (in_array($key,$active)){
                $ret[] = $val['Name'];
            }
        }

        return $ret;
    }

    public function show_pagebuilders_noticebox(){
        return false;   //possibly we dont need this anymore
        $active_plugins = $this->get_active_plugins();
        $page_builder_list = [
            'Elementor',
            'Fusion Builder',
            'Divi Builder',
            'Elementor Pro',
            'Page Builder by SiteOrigin',
            'Brizy',
            'Brizy Pro',
            'Beaver Builder Plugin (Lite Version)',
            'Beaver Builder Plugin (Standard Version)',
            'Visual Composer'
        ];
        $active_page_builders = array_intersect($active_plugins,$page_builder_list);
        if(empty($active_page_builders)) return false;

    ?>
        <div class="pagebuilders-noticebox purple-noticebox">
            <span class="closeX" style="border-color:#c0bc25;">X</span>
            <p>We noticed that you are using <?php echo implode(', ', $active_page_builders); ?>. If you encounter any issues after pasting the shortcode go to <a href="<?php echo admin_url( 'admin.php?page=' . EDD_IFSO_PLUGIN_SETTINGS_PAGE ); ?>" target="_blank">If-So > Settings </a> and change the status of the "the_content" filter checkbox.</p>
        </div>
    <?php
    }

    public function allow_divi_shortcodes_in_ajax_calls($actions){
        $actions[] = 'ddtest_handle_divi_section_shortcode';
        $actions[] = 'render_ifso_shortcodes';

        return $actions;
    }

    public function tinymce_modify_settings($settings){
        $current_post_type = get_post_type();

        if((!empty($current_post_type) && $current_post_type == 'ifso_triggers')){  //Only on if-so trigger page
            $settings['valid_elements'] = "*[*]";   //Allow all elements(don't filter out)
            $settings['relative_urls'] = false;     //Don't force url's to relative
            if(!PluginSettingsService::get_instance()->tmceForceWrapper->get())
                $settings['forced_root_block'] = false; //Don't wrap text in an html tag(p)
        }

        return $settings;
    }

    public function add_plugin_links($links,$flie){
        if($flie === basename(IFSO_PLUGIN_BASE_DIR) . '/' .basename(IFSO_PLUGIN_MAIN_FILE_NAME)){
            $new_links = [];
            $new_links['faq'] = "<a href='https://www.if-so.com/help/?utm_source=Plugin&utm_medium=Help&utm_campaign=PluginsPage' target='_blank'>Docs & FAQs</a>";
            $new_links['trigger_instructions'] = "<a href='https://www.if-so.com/help/documentation/how-to-create-dynamic-content-trigger/?utm_source=Plugin&utm_medium=Help&utm_campaign=PluginsPage' target='_blank'>Creating a Dynamic Trigger</a>";
            $new_links['dki'] = "<a href='https://www.if-so.com/help/documentation/dynamic-keyword-insertion/?utm_source=Plugin&utm_medium=Help&utm_campaign=PluginsPage' target='_blank'>DKI</a>";
            $new_links['extensions'] = "<a href='https://www.if-so.com/add-ons-and-integrations/?utm_source=Plugin&utm_medium=Help&utm_campaign=PluginsPage' target='_blank'>Extensions & Integrations</a>";
            return array_merge($links,$new_links);
        }

        return $links;

    }

    public function menu_links_new_tab(){
        ?>
        <script type="text/javascript">
            if(jQuery && typeof(jQuery)!=='undefined'){
                jQuery(document).ready(function($) {
                    var links_to_change = ['.ifso-dki-menu-link-child','.ifso-addons-menu-link-child'];
                    $.each(links_to_change,function (key,val) {
                        $(val).parent().attr('target','_blank');
                    })
                });
            }
        </script>
        <?php
    }

    public function admin_notices_presistant(){
        $is_dismissed = function ($notice){return (!empty($_COOKIE['ifso_hide_notice_'.$notice]));};
        $ret = <<<HTM
<script>
 function ifso_notice_createCookie(name, value, days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    else {
        expires = "";
    }
    document.cookie = name+"="+value+expires+"; path=/";
}
function ifso_never_show_notice(name,el){
     ifso_notice_createCookie('ifso_hide_notice_'+name,1,365);
     document.querySelector('.notice[ifso_notice="' + name +'"]').style.display = 'none';
}
</script>
HTM;
        if(defined('ELEMENTOR_VERSION') && !defined('IFSO_ELEMENTOR_ON') && !$is_dismissed('install-ifso-elementor')){      //Elementor integration
            $notice_name = 'install-ifso-elementor';
            $ret .= <<<HTM
<div class="notice notice-warning" style="position: relative;" ifso_notice="{$notice_name}" >
    <p>Set up conditional Elementor elements using the If-So & Elementor Integration</p>
    <p><a class="button button-primary" href='https://www.if-so.com/elementor-personalization/?utm_source=Plugin&utm_medium=suggestions&utm_campaign=elementor-top-notice' target='_blank'>Free download</a></p>
    <button class="ifso-neveragain notice-dismiss" href="#" onclick="ifso_never_show_notice('{$notice_name}');"></button>
</div>
HTM;

    }
        if(!empty($ret))
            echo $ret;
    }

    public function ajax_render_preview_content(){
        $role_allowed = (current_user_can('administrator') || current_user_can('editor') );
        $refcheck = (!empty($_REQUEST['ifso_render_preview_nonce']) && check_admin_referer('ifso-render-preview','ifso_render_preview_nonce'));
        if(wp_doing_ajax() && $role_allowed && $refcheck && !empty($_REQUEST['render_content'])){
            $content = $_REQUEST['render_content'];
            echo do_shortcode(stripslashes($content));
        }
        wp_die();
    }

    public function generate_version_symbol($version_number) {
        $version_number = intval($version_number+64+1);
        $num_of_characters_in_abc = 26;
        $base_ascii = 64;
        $version_number = intval($version_number) - $base_ascii;

        $postfix = '';
        if ($version_number > $num_of_characters_in_abc) {
            $postfix = intval($version_number / $num_of_characters_in_abc) + 1;
            $version_number %= $num_of_characters_in_abc;
            if ($version_number == 0) {
                $version_number = $num_of_characters_in_abc;
                $postfix -= 1;
            }
        }

        $version_number += $base_ascii;
        return chr($version_number) . strval($postfix);
    }
}

