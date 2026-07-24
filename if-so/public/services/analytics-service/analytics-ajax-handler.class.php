<?php
/**
 *HTTP API for interacting with the analytics service via AJAX.
 *Available methods for admin and public use are listed in the handle and public_handle methods respectively
 *
 * @author Nick Martianov
 *
 **/
namespace IfSo\PublicFace\Services\AnalyticsService;

require_once(__DIR__ . '/analytics-service.class.php');


class AnalyticsAjaxHandler {
    private static $instance;
    protected $analytics_service;

    private function __construct() {
        $this->analytics_service =  AnalyticsService::get_instance();
    }

    public static function get_instance() {
        if ( NULL == self::$instance )
            self::$instance = new AnalyticsAjaxHandler();

        return self::$instance;
    }

    public function handle(){
        //HANDLE IT
        $allowed = (current_user_can('administrator') || current_user_can('editor'));
        $refcheck = (!empty($_REQUEST['_ifsononce']) && check_admin_referer('ifso-admin-nonce','_ifsononce'));
        $allowed_noadmin = (!empty($_REQUEST['page_url']) && strpos($_REQUEST['page_url'], admin_url()) === false);
        $refcheck_noadmin = (!empty($_REQUEST['nonce']) && check_ajax_referer( 'ifso-nonce', 'nonce' ));
        if(wp_doing_ajax() && isset($_REQUEST['an_action'])  && isset($_REQUEST['postid'])){
            if(($allowed && $refcheck) || ($allowed_noadmin && $refcheck_noadmin)){
                if($_REQUEST['an_action']==='doConversion') $this->do_conversion();
                if($_REQUEST['an_action']==='ajaxViews') $this->ajax_views();
                if($allowed && $refcheck){
                    switch ($_REQUEST['an_action']){
                        case 'getTriggerReport':
                            $res = $this->get_trigger_report();
                            if(!empty($res)) echo json_encode($res);
                            break;
                        case 'resetFields':
                            $this->reset_fields();
                            if(!empty($_REQUEST['rdrback'])){wp_redirect(wp_get_referer());die();}
                            break;
                        case 'resetAllAnalytics':
                            $this->reset_all_triggers_analytics();
                            break;
                    }
                }
            }
        }
        wp_die();
    }

    public function public_handle(){
        //HANDLE IT
        if(check_ajax_referer( 'ifso-nonce', 'nonce' ) && wp_doing_ajax() && isset($_REQUEST['an_action'])){
            switch ($_REQUEST['an_action']){
                case 'doConversion':
                    $this->do_conversion();
                    break;
                case 'ajaxViews':
                    $this->ajax_views();
                    break;
            }
        }
        wp_die();
    }

    public function conversions_handle(){
        $allowed = ((current_user_can('administrator') || current_user_can('editor')));
        $refcheck = (!empty($_REQUEST['_ifsononce']) && check_admin_referer('ifso-admin-nonce','_ifsononce'));
        if($allowed && $refcheck && wp_doing_ajax() && isset($_REQUEST['ifso_conversion_action'])){
            switch ($_REQUEST['ifso_conversion_action']) {
                case 'delete_conversion':
                    $this->delete_conversion();
                    break;
                case 'setup_conversion':
                    $this->setup_conversion();
                    break;
                case 'reset_conversion':
                    $this->reset_conversion();
                    break;
                case 'exclude_trigger_from_conversion':
                    $this->exclude_trigger_from_conversion();
                    break;
            }
        }
        wp_redirect(wp_get_referer());
        exit();
    }

    private function delete_conversion(){
        if(!empty($_REQUEST['conv_id']))
            $this->analytics_service->records->delete_conversion($_REQUEST['conv_id']);
    }

    private function reset_conversion(){
        if(!empty($_REQUEST['conv_id']))
            $this->analytics_service->records->delete_conversion_events($_REQUEST['conv_id']);
    }

    private function setup_conversion(){
        $set_conversion_urls = function($conv_id,$request_param){
            $conv_urls_arr = json_decode(stripslashes($request_param));
            $this->analytics_service->records->set_conversion_urls($conv_id,$conv_urls_arr);
        };
        if(isset($_REQUEST['conv_id']) && $_REQUEST['conv_id']==='0' && isset($_REQUEST['conversion_url_arr']))
            return $set_conversion_urls(0,$_REQUEST['conversion_url_arr']);
        if(!\IfSo\Services\LicenseService\LicenseService::get_instance()->is_license_valid()) return;
        if(empty($_REQUEST['conversion_name'])) return;
        if(!empty($_REQUEST['conv_id'])){
            $conv_id = $_REQUEST['conv_id'];
            $this->analytics_service->records->update_conversion_name($conv_id,$_REQUEST['conversion_name']);
        }
        else
            $conv_id = $this->analytics_service->records->create_conversion($_REQUEST['conversion_name']);
        if(isset($_REQUEST['conversion_url_arr'])) $set_conversion_urls($conv_id,$_REQUEST['conversion_url_arr']);
        $extra_fields = ['once_per'=>null];
        if(!empty($_REQUEST['conversion_once_per']) || $_REQUEST['conversion_once_per']==='0')
            $extra_fields['once_per'] = $_REQUEST['conversion_once_per'];
        if(!empty($_REQUEST['conversion_trigger_filter'])){
            $tf_string = stripslashes($_REQUEST['conversion_trigger_filter']);
            if($tf = json_decode($tf_string)){
                if($tf->type==='exclude' && empty($tf->triggers))
                    $extra_fields['trigger_filter'] = null;
                else
                    $extra_fields['trigger_filter'] = $tf_string;
            }
        }
        if(!empty($extra_fields))
            $this->analytics_service->records->update_conversion_fields($conv_id,$extra_fields);
    }

    private function exclude_trigger_from_conversion(){
        if(empty($_REQUEST['conv_id']) || empty($_REQUEST['trigger_id'])) return;
        $cid = $_REQUEST['conv_id'];
        $tid = $_REQUEST['trigger_id'];
        $conv = $this->analytics_service->records->get_conversion($cid);
        $tf = json_decode($conv->trigger_filter)===null ? ['type'=>'exclude','triggers'=>[]] : json_decode($conv->trigger_filter,true);
        if($tf['type']==='exclude')
            $tf['triggers'][] = $tid;
        if($tf['type']==='include' && in_array($tid,$tf['triggers']))
            array_splice($tf['triggers'],array_search($tid,$tf['triggers']),1);
        $this->analytics_service->records->update_conversion_fields($cid,['trigger_filter'=>json_encode($tf)]);
    }
    private function get_trigger_report(){
        $report = $this->analytics_service->make_trigger_report_default_data($_REQUEST['postid']);
        $views = $this->analytics_service->records->get_view_event_counts_by_trigger(null,null,$_REQUEST['postid']);
        $conversions = $this->analytics_service->records->get_conversion_events_counts_by_trigger(null,$_REQUEST['postid']);
        foreach($views as $view){
            $key = $view->is_recurrence ? 'recurr_views' : 'views';
            $report[$view->version_uid][$key] = $view->count;
        }
        foreach($conversions as $cdata)
            $report[$cdata->version_uid]['conversions'] += (int) $cdata->count;
        return $report;
    }
    private function reset_fields(){
        if(isset($_REQUEST['postid'])){
            if(isset($_REQUEST['versionid']))
                $this->analytics_service->records->delete_data_by_trigger($_REQUEST['postid'],$_REQUEST['versionid']);
            else
                $this->analytics_service->records->delete_data_by_trigger($_REQUEST['postid']);
        }
    }

    private function do_conversion(){
        if(isset($_REQUEST['viewed_triggers']) && isset($_REQUEST['conversions'])){
            $viewed_triggers = json_decode(stripslashes($_REQUEST['viewed_triggers']),true);
            $conversions = json_decode(stripslashes($_REQUEST['conversions']),true);
            if(is_array($viewed_triggers) && is_array($conversions)){
                foreach ($conversions as $conversion){
                    $conv_type = isset($conversion['conversion_type']) ? $conversion['conversion_type'] : null;
                    if(isset($conversion['once_per_time']) && !empty($conversion['name']))
                        $this->analytics_service->do_conversion($conv_type,$viewed_triggers,$conversion['allowed'],$conversion['disallowed'],$conversion['once_per_time'],$conversion['name']);
                    else
                        $this->analytics_service->do_conversion($conv_type,$viewed_triggers,$conversion['allowed'],$conversion['disallowed']);
                }
            }
        }
    }

    private function ajax_views(){
        if(isset($_REQUEST['data']) && !empty($_REQUEST['data'])){
            $data_json = json_decode(stripslashes($_REQUEST['data']),true);
            if(is_array($data_json)){
                foreach($data_json as $postid=>$version){
                    if(!empty($version['uid']))
                        $this->analytics_service->records->create_view_event($postid,$version['uid'],$version['recurrence']);
                }
            }
        }
        \IfSo\PublicFace\Helpers\CookieConsent::get_instance()->set_cookie($this->analytics_service->currently_viewing_cookie_name,'',0,'/');
    }

    private function reset_all_triggers_analytics(){
        $this->analytics_service->records->delete_data_by_trigger();
    }
}