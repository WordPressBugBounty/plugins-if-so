<?php
/**
 *The main service for working with analytics data(reading,writing,etc)
 *
 * @author Nick Martianov
 *
 **/

namespace IfSo\PublicFace\Services\AnalyticsService;

use IfSo\PublicFace\Helpers\CookieConsent;
use IfSo\PublicFace\Services\AjaxTriggersService\AjaxTriggersService;

require_once(IFSO_PLUGIN_BASE_DIR . 'services/plugin-settings-service/plugin-settings-service.class.php');
require_once (__DIR__ . '/analytics-records.class.php');

class AnalyticsService {
    private static $instance;

    public $last_viewed_version_cookie_name = '_ifso_last_viewed';

    public $currently_viewing_cookie_name = 'ifso_viewing_triggers';

    public $records;

    private static $viewed_triggers =[];

    private static $already_had_conversion = [];

    public $isOn = true;

    public $useAjax = true;

    public $allow_counting = true;   //Allow counting of current user's views/conversions

    protected $settings_service;

    private function __construct(){
        $this->settings_service = \IfSo\Services\PluginSettingsService\PluginSettingsService::get_instance();
        $this->isOn = !$this->settings_service->disableAnalytics->get();
        if(defined('REST_REQUEST') && REST_REQUEST ) $this->isOn =  false;   //Disable analytics if its a request to the wp REST API (to avoid gutenberg from activating analytics)
        $this->useAjax = $this->settings_service->ajaxAnalytics->get();
        $this->records = new AnalyticsRecords();

        $th = $this;
        add_action('plugins_loaded',function() use (&$th){
            if (current_user_can('administrator')) $th->allow_counting = false;   //Disalow counting for administrators
        });

        add_action('wp_footer',[$this,'conversions_if_url_is_required']);
    }

    public static function get_instance(){
        if (NULL == self::$instance)
            self::$instance = new AnalyticsService();

        return self::$instance;
    }

    public function make_trigger_report_default_data($tid){
        $data_rules = \IfSo\PublicFace\Services\TriggersService\TriggerContextLoader::load_context(['id'=>$tid],null)->get_data_rules();
        if($data_rules===null) return false;
        $ret = [];
        $i=0;
        foreach($data_rules as $rule){
            $symbol = \IfSo\Admin\Services\InterfaceModService\InterfaceModService::get_instance()->generate_version_symbol($i++);
            $name = !empty($rule['version_name']) ? $rule['version_name'] : '';
            $ret[$rule['version_uid']] = ['symbol'=>$symbol,'conversions'=>0,'name'=>$name,'views'=>0,'recurr_views'=>0];
        }
        $ret['default'] = ['symbol'=>'Default','name'=>'','conversions'=>0,'views'=>0,'recurr_views'=>0];
        return $ret;
    }

    private function set_last_viewed_version_cookie($postid,$versionid,$versionUid){
        if($postid===0) return;
        //Set cookie indicating the triggers/versions seen during the current session to use in bounce/conversion callbacks etc - can be moved to a separate class later on
        if(isset($postid) && isset($versionid)){
            $viewed_arr = [];
            if(isset($_COOKIE[$this->last_viewed_version_cookie_name]) && is_array(json_decode(stripslashes($_COOKIE[$this->last_viewed_version_cookie_name]),true)))
                $viewed_arr = json_decode(stripslashes($_COOKIE[$this->last_viewed_version_cookie_name]),true);
            $viewed_arr[$postid] = ['id'=>$versionid,'uid'=>$versionUid];
            $_COOKIE[$this->last_viewed_version_cookie_name] = json_encode($viewed_arr);
            $cookie_expiration = $this->settings_service->analyticsCookieExpiration->get()===0 ? 0 : time() + $this->settings_service->analyticsCookieExpiration->get();
            CookieConsent::get_instance()->set_cookie($this->last_viewed_version_cookie_name,json_encode($viewed_arr),$cookie_expiration,'/');
        }
    }

    public function get_last_viewed_versions(){
        if(!empty($_COOKIE[$this->last_viewed_version_cookie_name]))
            return json_decode(stripslashes($_COOKIE[$this->last_viewed_version_cookie_name]),true);
        return [];
    }

    public function do_conversion($type,$triggers,$allowed=[],$disallowed=[],$once_per_time=null,$name=null){
        $type_id = $type!==null ? (int)$type : 0;
        $name = $type_id;
        if($type_id!==0){
            $conversion_data = $this->records->get_conversion($type);
            if(isset($conversion_data->once_per)) $once_per_time = $conversion_data->once_per;
            if(!empty($conversion_data->trigger_filter)){
                $tf = json_decode($conversion_data->trigger_filter,true);
                if(!empty($tf)){
                    if($tf['type']==='exclude')
                        $disallowed = $tf['triggers'];
                    if($tf['type']==='include'){
                        $allowed = $tf['triggers'];
                        if(empty($allowed)) return;
                    }
                }
            }
        }
        if($once_per_time!==null && $name!==null){
            $convs = [];
            $limited_conversions_cookie_name = 'ifso-limited-conversions';
            if(!empty($_COOKIE[$limited_conversions_cookie_name])){
                $convs = is_array(json_decode(stripslashes($_COOKIE[$limited_conversions_cookie_name]),true)) ? json_decode(stripslashes($_COOKIE[$limited_conversions_cookie_name]),true) : [];
                if(!(!isset($convs[$name]) || (intval($convs[$name])<time() && intval($convs[$name])!==0))){
                    return;
                }
            }
            $convs[$name] = intval($once_per_time)!==0 ? time()+intval($once_per_time) : 0;
            asort($convs);
            CookieConsent::get_instance()->set_cookie($limited_conversions_cookie_name,json_encode($convs),intval(end($convs)),'/','preferences');
        }
        foreach ($triggers as $trigger=>$version){
            if(empty($version['uid'])) continue;
            if(!isset(self::$already_had_conversion[$type_id][$trigger]) && !in_array($trigger,$disallowed) && (!$allowed || is_array($allowed) && in_array($trigger,$allowed))){
                $this->records->create_conversion_event($trigger,$version['uid'],$type_id===0?null:$type_id);
                self::$already_had_conversion[$type_id][$trigger] = $version;
            }
        }
    }

    public function conversions_if_url_is_required(){
        if($this->isOn && $this->allow_counting && !is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)){
            $current_url = AjaxTriggersService::get_instance()->get_current_request()->getRequestURL();
            $parsed_current_url = parse_url($current_url);
            $conversions = $this->records->get_url_conversions($current_url,!empty($parsed_current_url['query']));
            if(!empty($conversions)){
                foreach($conversions as $conversion){
                    $old_use_ajax_val = $this->useAjax;
                    $this->useAjax = true;
                    echo do_shortcode("[ifso_conversion conversion='{$conversion->conv_id}']'");
                    $this->useAjax = $old_use_ajax_val;
                }
            }
        }
    }

    public function handle($rule_data) {
        if($this->isOn){
            if(!isset(self::$viewed_triggers[$rule_data->get_trigger_id()]) && $this->allow_counting){
                $tid = $rule_data->get_trigger_id();
                $is_recurrence_view = ($rule_data->get_rendering_recurrence_version()!==null);
                self::$viewed_triggers[$tid] = ['id'=>$rule_data->get_version_index(),'uid'=>$rule_data->get_version_uid(),'recurrence'=>$is_recurrence_view];
                $this->set_last_viewed_version_cookie($tid,$rule_data->get_version_index(),$rule_data->get_version_uid());
                if(!$this->useAjax)
                    $this->records->create_view_event($tid,$rule_data->get_version_uid(),$is_recurrence_view);
                else
                    CookieConsent::get_instance()->set_cookie($this->currently_viewing_cookie_name,json_encode(self::$viewed_triggers),0,'/');
            }
        }
    }

    public function handle_default($rule_data) {
        if($this->isOn){
            if(!isset(self::$viewed_triggers[$rule_data->get_trigger_id()]) && $this->allow_counting){
                $tid = $rule_data->get_trigger_id();
                self::$viewed_triggers[$tid] = ['id'=>'default','uid'=>$rule_data->get_version_uid(),'recurrence'=>false];
                $this->set_last_viewed_version_cookie($tid, 'default','default');
                if(!$this->useAjax)
                    $this->records->create_view_event($tid,'default',false);
                else
                    CookieConsent::get_instance()->set_cookie($this->currently_viewing_cookie_name,json_encode(self::$viewed_triggers),0,'/');
            }
        }
    }

    public function render_google_analytics_event_element($attrs,$event='ifso-trigger-viewed'){
        $attrs = apply_filters('ifso_ga4_event_attrs',$attrs,$event);
        $event_data_attr = esc_attr(json_encode($attrs));
        return "<ifsoTriggerAnalyticsEvent event_data='{$event_data_attr}' event_name='{$event}'></ifsoTriggerAnalyticsEvent>";
    }
}