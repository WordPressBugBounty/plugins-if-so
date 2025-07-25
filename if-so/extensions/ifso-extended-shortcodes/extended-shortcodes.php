<?php
namespace IfSo\Extensions\IFSOExtendedShortcodes\ExtendedShortcodes;

require_once(IFSO_PLUGIN_BASE_DIR . 'public/services/analytics-service/analytics-service.class.php');
require_once( IFSO_PLUGIN_SERVICES_BASE_DIR . 'groups-service/groups-service.class.php' );
require_once(IFSO_PLUGIN_BASE_DIR . 'public/helpers/ifso-helpers.php');
require_once(IFSO_PLUGIN_BASE_DIR . 'public/helpers/ifso-request/If-So-Http-Get-Request.php');

require_once(__DIR__.'/models/user-languages/index.php');

use IfSo\PublicFace\Helpers\CookieConsent;
use IfSo\PublicFace\Services\AjaxTriggersService\AjaxTriggersService;
use IfSo\PublicFace\Services\AnalyticsService\AnalyticsService;
use IfSo\Services\GeolocationService;

if ( ! defined( 'ABSPATH' ) ) exit;

class ExtendedShortcodes {

	public static $instance;

    private function __construct(){}

	public static function get_instance() {
		if ( NULL == self::$instance )
		self::$instance = new ExtendedShortcodes();
		return self::$instance;
	}

    public function add_extended_shortcodes(){
        $this->doDKIShortcode();
        $this->do_analytics_conversion_shortcode();
        $this->do_user_details_shortcode();
        $this->do_login_link_shortcode();
        $this->do_show_post_shortcode();
        $this->do_groups_shortcode();
        $this->do_add_cookie_shortcode();
        $this->do_redirect_shortcode();
        $this->do_google_analytics_event_shortcode();

        do_action('ifso_extra_extended_shortcodes');
    }

    public function doDKIShortcode(){
        //Super shortcode IfsoDKI combines the functionality of the other extended shortcodes- others still here for compatability.DRY! Refactor?
        add_shortcode('ifsoDKI',function ($atts){return $this->render_dki_shortcode($atts);});
    }

    public function render_dki_shortcode($atts,$http_request=null){
        $ajax = (isset($atts['ajax']) && ($atts['ajax']==='yes' || $atts['ajax']==='true'));
        $before = (!empty($atts['before'])) ? $atts['before'] : '';
        $after = (!empty($atts['after'])) ? $atts['after'] : '';
        $ret = $this->render_dki($atts,$http_request);
        if($ret instanceof DKIFallback)
            $ret = esc_html($ret->content);
        elseif(!$ajax)
            $ret = $before . $ret . $after;

        return $ret;
    }

    public function render_dki($atts,$http_request=null){
        if(!isset($atts['type']))
            return false;
        $ajax = (isset($atts['ajax']) && ($atts['ajax']==='yes' || $atts['ajax']==='true'));
        if($ajax){
            $ajax_triggers_service = AjaxTriggersService::get_instance();
            if(is_admin() || !$ajax_triggers_service->is_inside_ajax_triggers_request())
                return $ajax_triggers_service->handle_dki($atts);
        }
        $type = $atts['type'];
        $show = (isset($atts['show'])) ? $atts['show'] : '';
        $fallback = (isset($atts['fallback'])) ? $atts['fallback'] : '';
        if($http_request===null){
            if(AjaxTriggersService::get_instance()->is_inside_ajax_triggers_request())
                $http_request = AjaxTriggersService::get_instance()->get_request();
            else
                $http_request = \IfSo\PublicFace\Helpers\IfSoHttpGetRequest\IfSoHttpGetRequest::create();
        }

        if($type==='geo'){
            if(empty($fallback)){
                $fallback = 'Unknown';
            }
            $geo_data = GeolocationService\GeolocationService::get_instance()->get_user_location();
            if(!empty($geo_data)) {
                switch ( $show ) {
                    case 'country':
                        if ( $geo_data->get('countryName')!==null) {
                            return $geo_data->get('countryName');
                        }
                        break;
                    case 'state':
                        if ($geo_data->get('stateProv')!==null) {
                            return $geo_data->get('stateProv');
                        }
                        else return new DKIFallback('Your state');
                        break;
                    case 'city':
                        if ($geo_data->get('city')!==null) {
                            return $geo_data->get('city');
                        }
                        break;
                    case 'continent':
                        if ($geo_data->get('continentName')!==null) {
                            return $geo_data->get('continentName');
                        }
                        break;
                    case 'timezone':
                        if ($geo_data->get('timeZone')!==null) {
                            $tzArr = explode('/',$geo_data->get('timeZone'));
                            if(is_array($tzArr) && count($tzArr)>1) return $tzArr[1];
                            return $geo_data->get('timeZone');
                        }
                        break;
                }
            }
        }
        elseif($type==='language') {
            $user_languages = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            $languages = [];

            preg_match_all("/[a-zA-Z-]{2,10}/",
                $user_languages,
                $languages);


            if ($languages && is_array($languages[0]) && !empty($languages[0]))
                $languages = $languages[0];
            else
                return new DKIFallback($fallback);

            switch ($show) {
                case 'primary-only':
                    return get_language_name($languages[0]);
                case 'all':
                    return build_user_languages_visual($languages);
                case 'all-except-primary':
                    array_shift($languages);
                    return build_user_languages_clean_visual($languages);
                case 'count':
                    return count($languages);
                case 'count-without-primary':
                    array_shift($languages);
                    return count($languages);
            }
        }
        elseif($type==='referrer'){
            if(empty($fallback)){
                $fallback = 'No referrer / hidden';
            }
            $request_referrer = $http_request->getReferrer();
            if(!empty($request_referrer)){
                $referrer = trim(wp_strip_all_tags($request_referrer), '/');
                $clean_referer = trim($request_referrer, '/');
                $referrer = parse_url($referrer, PHP_URL_HOST);
                $referrer = str_replace('https://', '', $referrer);
                $referrer = str_replace('http://', '', $referrer);
                $referrer = str_replace('www.', '', $referrer);

                if ( !empty( $referrer ) ) {
                    switch ( $show ) {
                        case 'domain-only':
                            return $referrer;
                    }

                    return esc_html($clean_referer);
                }
            }
        }
        elseif($type==='viewcount'){
            if($show==='visit-count'){
                $num_of_visits = 0;
                if ( isset($_COOKIE['ifso_visit_counts']) )
                    $num_of_visits = $_COOKIE['ifso_visit_counts'];

                return esc_html($num_of_visits);
            }
            else{
                $pid = (isset($atts['id'])) ? $atts['id'] : false;
                if($pid){
                    $analytics_service = AnalyticsService::get_instance();
                    $fields = $analytics_service->get_analytics_fields($pid);
                    $ret = 0;
                    if($fields){
                        foreach($fields as $version){
                            $ret += (int) $version['views'];
                        }
                        return $ret;
                    }
                }
            }
        }
        elseif($type==='querystring' || $type === 'google-ads'){
            if(!empty($atts['parameter'])){
                $persist = (isset($atts['persist']) && $atts['persist']!=='no');
                $param = $atts['parameter'];
                $get_param = $http_request->getParam($param);
                if(!empty($get_param)){
                    $ret = filter_var($get_param,FILTER_SANITIZE_SPECIAL_CHARS);  //avoid XSS
                    if($persist && !empty($ret))
                        $this->persist_qs_dki($param,$ret);
                    return $ret;
                }
                if($persist && !$ajax){
                    $persisted_data = $this->get_persistent_qs_dki();
                    if(!empty($persisted_data) && isset($persisted_data[$param]))
                        return $persisted_data[$param];
                }
            }
        }
        elseif($type==='day-of-week'){
            return date('l');
        }
        elseif($type==='time'){
            $format = (!empty($atts['format'])) ? $atts['format'] : 'H:i:s';
            if($show==='site-timezone')
                return current_time($format);
            if($show==='user-geo-timezone-sensitive'){
                if( isset($atts['time']) && strtotime($atts['time']) ){
                    $geo_data = GeolocationService\GeolocationService::get_instance()->get_user_location();
                    if($geo_data->get('timeZone')!==null){
                        $time_obj = new \DateTime($atts['time'],\IfSo\PublicFace\Helpers\WpDateTimeZone::getWpTimezone());
                        $time_obj->setTimezone(new \DateTimeZone($geo_data->get('timeZone')));
                        return $time_obj->format($format);
                    }
                }
            }
        }
        elseif($type==='ip'){
            return GeolocationService\GeolocationService::get_instance()->get_user_ip();
        }
        elseif($type==="url"){
            return (!empty($atts['url'])) ? esc_url($this->make_url_string_from_template($atts['url'],AjaxTriggersService::get_instance()->get_current_request()->getRequestURL())) : '';
        }

        $ret = apply_filters('ifso_dki_types_extension','',$type,$show,$atts,$http_request);
        if(!empty($ret))
            return $ret;

        return new DKIFallback($fallback);
    }

    private function persist_qs_dki($param,$data){
        $persistent_data = $this->get_persistent_qs_dki();
        $persistent_data[$param] = $data;
        $persist_dki_expires = apply_filters('ifso_persist_dki_expiration',0);
         CookieConsent::get_instance()->set_cookie('ifso-persist-qs-dki',json_encode($persistent_data),$persist_dki_expires,'/','preferences');
        $_COOKIE['ifso-persist-qs-dki'] = json_encode($persistent_data);
    }

    private function get_persistent_qs_dki(){
        $persistent_dki = (!empty($_COOKIE['ifso-persist-qs-dki']) && json_decode(stripslashes($_COOKIE['ifso-persist-qs-dki']),true)) ? json_decode(stripslashes( $_COOKIE['ifso-persist-qs-dki']),true) : null;
        if(!empty($persistent_dki)){
            return $persistent_dki;
        }
        return [];
    }

    public function do_analytics_conversion_shortcode(){
        if(wp_doing_ajax() || !is_admin()){
            add_shortcode('ifso_conversion', function($atts) {
                $analytics_service = AnalyticsService::get_instance();
                $allowed_triggers = (isset($atts['triggers']) && strtolower($atts['triggers'])!='all') ? explode(',',$atts['triggers'])  : false;
                $disallowed_triggers = (isset($atts['exclude'])) ? explode(',',$atts['exclude'])  : [];
                if(isset($atts['do_once_per'])){
                    $once_per_time = strtolower($atts['do_once_per']) === 'session' ? 0 : intval($atts['do_once_per']);
                    $name = !empty($atts['name']) ? esc_attr($atts['name']) : 'default-conversion';
                }
                if($analytics_service->isOn && $analytics_service->allow_counting){
                    if($analytics_service->useAjax){
                        $once_per_attrs = isset($once_per_time) ? "once_per_time='{$once_per_time}' ifso_name='{$name}'" : "";
                        $el = "<div class='ifso-conversion-complete' {$once_per_attrs} ". ($allowed_triggers ? 'allowed_triggers="' . esc_attr(implode(',',$allowed_triggers)) . '"' : '')  . ($disallowed_triggers ? 'disallowed_triggers="' . esc_attr(implode(',',$disallowed_triggers)) . '"' : '') . ' style="display:none;height:0;"></div>';  //public javascript file catches uses this div as trigger for conversion to fire
                        return $el;
                    }
                    else{
                        if(!empty($_COOKIE[$analytics_service->last_viewed_version_cookie_name])){
                            $viewed_arr = json_decode(stripslashes($_COOKIE[$analytics_service->last_viewed_version_cookie_name]),true);
                            if(is_array($viewed_arr)){
                                if(isset($once_per_time))
                                    $analytics_service->do_conversion($viewed_arr,$allowed_triggers,$disallowed_triggers,$once_per_time,$name);
                                $analytics_service->do_conversion($viewed_arr,$allowed_triggers,$disallowed_triggers);
                            }
                        }
                    }
                }
            });
        }
    }

    public function do_user_details_shortcode(){
        add_shortcode('ifso_user_details',function($atts){
            $user = wp_get_current_user();
            if(isset($user->ID) && 0!== $user->ID){     //If user is logged in
                $user_meta = get_user_meta($user->ID);
                $user_data = [
                    'first_name' =>$user_meta['first_name'][0],
                    'last_name' =>$user_meta['last_name'][0],
                    'nickname' =>$user_meta['nickname'][0],
                    'email' =>$user->data->user_email,
                ];
                if(isset($atts['show'])){
                    switch ($atts['show']){
                        case 'firstName':
                            return $user_data['first_name'];
                        case 'lastName':
                            return $user_data['last_name'];
                        case 'fullName':
                            return trim("{$user_data['first_name']} {$user_data['last_name']}");
                        case 'email':
                            return $user_data['email'];
                        case 'username':
                            return $user_data['nickname'];
                    }
                }
                return $user_data['nickname'];
            }
            elseif(!empty($atts['default'])){
                return esc_html($atts['default']);
            }
        });
    }

    public function do_login_link_shortcode(){
        add_shortcode('ifso_login_link',function($atts){
            $request = AjaxTriggersService::get_instance()->get_request();
            $current_url = !empty($request) ? $request->getRequestURL() : false;
            if(!$current_url)$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $redirect_to = !empty($atts['login_redirect']) ? $atts['login_redirect'] : $current_url;

            if(!is_user_logged_in()){
                $link_text = !empty($atts['login_text']) ? esc_html($atts['login_text']) : 'Log In';
                $link_url = esc_url(wp_login_url($redirect_to));
            }
            else{
                $link_text = !empty($atts['logout_text']) ? esc_html($atts['logout_text']) : 'Log Out';
                $link_url = esc_url(wp_logout_url($redirect_to));
            }

            return "<a class='ifso_loginout_link' href='{$link_url}'>{$link_text}</a>";
        });
    }

    public function do_show_post_shortcode(){
        add_shortcode('ifso-show-post',function($atts){
            if(!empty($atts['id'])){
                $pid = $atts['id'];
                $show = !empty($atts['show']) ? strtolower($atts['show']) : 'content';
                $raw = (isset($atts['the_content']) && (strtolower($atts['the_content']) === 'no' || strtolower($atts['the_content']) === 'false'));
                $type = (!empty($atts['type'])) ? strtolower($atts['type']) : 'default';
                $post = get_post($pid);
                if(empty($post) || !is_object($post)) return;

                if($type==='wpb' && $show!=='title'){
                    if (method_exists('WPBMap', 'addAllMappedShortcodes')) \WPBMap::addAllMappedShortcodes();
                    $vccss = get_post_meta($pid, '_wpb_shortcodes_custom_css', true);
                    if(!empty($vccss)){
                        $vccss = strip_tags($vccss);
                        $extra_content ="<style type=\"text/css\" data-type=\"vc_shortcodes-custom-css\">{$vccss}</style>";
                        $post->post_content .= $extra_content;
                    }
                }
                if(($type==='divi' || $type==='etbuilder') && $show!=='title'){
                    if(defined('ET_BUILDER_DIR')){
                        require_once ET_BUILDER_DIR . 'class-et-builder-element.php';
                        $content = do_shortcode("[et_pb_section global_module='{$pid}'][/et_pb_section]");
                        if(!empty($content) && method_exists(\ET_Builder_Element::class,'get_style')){
                            $extra_style_content = "<style class='extra-divi-styles'>" . \ET_Builder_Element::get_style() . "</style>";
                            $post->post_content = $extra_style_content . $content;
                        }
                    }
                }
                if($type==='elementor' && $show!=='title' && class_exists('\Elementor\Plugin') &&  isset(\Elementor\Plugin::$instance)){
                    $with_css = !(!empty($atts['without_css']) && $atts['without_css']==='yes');
                    $content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $pid, $with_css );
                    $post->post_content = $content;
                }

                if(!empty($post->post_status) && $post->post_status==='publish' && empty($post->post_password)){
                    if($show === 'title')
                        return $post->post_title;
                    else
                        return $raw ? $post->post_content : apply_filters('the_content',$post->post_content);
                }
            }
        });
    }

    public function do_groups_shortcode(){
        add_shortcode('ifso-audience',function($atts){
            if($this->is_edit_page_or_publish_action()) return;
            $groups_service = \IfSo\PublicFace\Services\GroupsService\GroupsService::get_instance();
            $type = (!empty($atts['type'])) ? strtolower(trim($atts['type'])) : '';
            $group = (!empty($atts['audience'])) ? trim($atts['audience']) : '';
            $from = (!empty($atts['from'])) ? trim($atts['from']) : 'atts';
            $show = (!empty($atts['show'])) ? trim($atts['show']) : '';

            if($from==='query' || $from==='query-condition'){
                $param = (!empty($atts['param'])) ? trim($atts['param']) : 'ifso_audience';
                $request = AjaxTriggersService::get_instance()->get_request();
                $param_value = !empty($request) ? $request->getParam($param) : (!empty($_REQUEST[$param]) ? $_REQUEST[$param] : '');
                if($from==='query'){
                    $group = filter_var($param_value,FILTER_SANITIZE_SPECIAL_CHARS);
                }
                elseif($from==='query-condition'){
                    $param_target = (!empty($atts['param_value'])) ? trim($atts['param_value']) : null;
                    if(empty($param_target) || empty($param_value) || (!empty($param_target) && $param_value!==$param_target))
                        $group = null;
                }
            }

            if(!empty($group)){
                if($type==='add')
                    $groups_service->add_user_to_group($group);
                elseif($type==='remove')
                    $groups_service->remove_user_from_group($group);
            }
            if($type==='display'){
                if($show==='user-audiences')
                    return implode(', ',$groups_service->get_user_groups());
            }
        });
    }

    public function do_add_cookie_shortcode(){
        add_shortcode('ifso-add-cookie',function($atts){
            if($this->is_edit_page_or_publish_action()) return;
            $increment = !empty($atts['increment']) && strtolower($atts['increment'])==='yes';
            if(!empty($atts['name']) && (isset($atts['value']) || $increment)){
                if($increment)
                    $value = !empty($_COOKIE[$atts['name']]) ? intval($_COOKIE[$atts['name']])+1 : 1;
                else
                    $value = $atts['value'];
                $time =  0;
                if(!empty($atts['time'])) $time = time() + (int) $atts['time'];
                CookieConsent::get_instance()->set_cookie($atts['name'],$value,$time,'/','preferences');
                $_COOKIE[$atts['name']] = $value;
            }
        });
        add_shortcode('ifso-remove-cookie',function ($atts){
            if($this->is_edit_page_or_publish_action()) return;
            if(!empty($atts['name'])){
                setcookie($atts['name'],'',-666,'/');
                unset($_COOKIE[$atts['name']]);
            }
        });
    }

    public function do_redirect_shortcode(){
        add_shortcode('ifso-redirect',function ($atts){
            //TODO: add a globally accessible method for checking whether we're in some kind of editor
            if($this->is_edit_page_or_publish_action()) return;
            $ajaxTriggersService = AjaxTriggersService::get_instance();
            $url = (!empty($atts['url'])) ? $this->make_url_string_from_template($atts['url'],$ajaxTriggersService->get_current_request()->getRequestURL()) : null;
            $type = $ajaxTriggersService->is_inside_ajax_triggers_request() ? 'js' :  (!empty($atts['type']) ? $atts['type'] : 301)  ;
            if(!empty($atts['name']) && !empty($atts['do_once_per'])) {
                if (isset($_COOKIE['ifso-rdr-' . $atts['name']])) return;
                else {
                    $time = strtolower($atts['do_once_per']) === 'session' ? 0 : time() + intval($atts['do_once_per']);
                    CookieConsent::get_instance()->set_cookie('ifso-rdr-' . $atts['name'], '1', $time, '/', 'preferences');
                }
            }
            if($url!==null){
                if(!empty($atts['forward_query_params']) && $atts['forward_query_params']==='yes')
                    $url = add_query_arg($ajaxTriggersService->get_current_request()->getParams(),$url);

                if(!empty($atts['exclude_admins']) && $atts['exclude_admins']==='yes' && current_user_can('administrator'))
                    return "<p style='color:#6A6DD4;background:#F7F7FC;border: 1px solid #6A6DD4;padding:10px 8px;border-radius:4px;'>Redirect skipped because you're logged in as an admin. Intended redirect URL: <a href='{$url}' target='_blank'>{$url}</a>. To see the regular behavior, open the page in a new incognito window or set the exclude_admins parameter in the redirect shortcode to \"no\".</p>";

                if($type!=='js'){
                    $code = (is_numeric($type)) ? (int)$type : 301;
                    wp_redirect(esc_url_raw($url),$code);
                    exit();
                }
                else
                    return '<script>(function(){var escaped_url = "' . esc_url($url) .'";var el = document.createElement("textarea");el.innerHTML = escaped_url;location.href = el.value;})()</script>';
            }
        });
    }

    public function do_google_analytics_event_shortcode(){
        add_shortcode('ifso-GA4-event',function ($atts){
            if(!empty($atts['ga4_event_type'])){
                $analytics_service = AnalyticsService::get_instance();
                return $analytics_service->render_google_analytics_event_element($atts,'custom');
            }
        });
    }

    public function modify_ifso_shorcode_add_edit($data){
        if($data['post_type']!='ifso_triggers'){
            $pattern = '/\[ifso (id\=)(([\"\']{0,1})(\d+)([\"\']{0,1}))( .+){0,1}\]/';
            $old_content = stripslashes($data['post_content']);
            $data['post_content'] = preg_replace($pattern,'[ifso ${1}"${4}" <a target="_blank" href="?post=${4}&action=edit">edit</a>]',$old_content);
        }
        return $data;
    }

    public function is_edit_page_or_publish_action(){
        if ((is_admin() && (!defined('DOING_AJAX') || !\DOING_AJAX)) || (function_exists('get_current_screen') && get_current_screen()!==null && ((!empty(get_current_screen()->parent_base)  && get_current_screen()->parent_base == 'edit') || get_current_screen()->is_block_editor()))) return true;
        if(class_exists('\Elementor\Plugin')  && (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode())) return true;
        if(did_action('publish_page')) return true;
        return false;
    }

    private function make_url_string_from_template($template_url, $current_url) {
        $current_url_parsed = parse_url($current_url);
        $domain_parts = !empty($current_url_parsed['host']) ?  explode('.',$current_url_parsed['host']) : [];
        $current_url_parts = [
            'scheme'=>!empty($current_url_parsed['scheme']) ? $current_url_parsed['scheme'] : '',
            'host'=>!empty($current_url_parsed['host']) ? $current_url_parsed['host'] : '',
            'path'=>!empty($current_url_parsed['path']) ? $current_url_parsed['path'] : '',
            'query'=>!empty($current_url_parsed['query']) ? $current_url_parsed['query'] : '',
            'domain'=>count($domain_parts)>1 ? implode('.',array_slice($domain_parts,0,count($domain_parts)-1)) : (count($domain_parts)===1 ? $domain_parts[0] : ''),
            'tld'=>count($domain_parts)>1 ? end($domain_parts) : '',
        ];
        return is_string($template_url) ? str_replace(['{{SCHEME}}','{{HOST}}','{{PATH}}','{{QUERY}}','{{DOMAIN}}','{{TLD}}'],[$current_url_parts['scheme'],$current_url_parts['host'],$current_url_parts['path'],$current_url_parts['query'],$current_url_parts['domain'],$current_url_parts['tld']],$template_url) : $template_url;
    }

}

class DKIFallback{
    public $content;
    public function __construct($content=''){
        $this->content = $content;
    }
}

?>