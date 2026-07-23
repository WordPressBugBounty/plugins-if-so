<?php
/**
 *Checks whether the plugin has been updated and if it was, run the relevant compatibility routine
 *
 * @author Nick Martianov
 *
 **/
namespace IfSo\Services\AfterUpgradeService;

class AfterUpgradeService {
    private static $instance, $code_version, $db_version;

    private function __construct() {
        self::$code_version = IFSO_WP_VERSION;
        self::$db_version = get_option('ifso_wp_version');
        add_action('admin_init', [self::class,'try_convert_trigger_data_for_new_analytics_system']);
    }

    public static function get_instance() {
        if ( NULL == self::$instance )
            self::$instance = new AfterUpgradeService();

        return self::$instance;
    }

    public static function isUpdated(){
        if(self::$code_version!=self::$db_version){
            return true;
        }
        return false;
    }

    private static function onUpdateHandler(){
        //Actions that are done whenever the code version is detected to be different form the one written down in DB(on update)

        //Try to activate the licenses that are already recored in the DP
        self::reactivate_licenses();

        //Create the tables required to run the plugin, this also runs on activation
        require_once IFSO_PLUGIN_BASE_DIR . 'extensions/ifso-tables/ifso-table-creator.php';
        \ifso_jal_install();
        self::reset_metabox_order();
        //Add new columns to ifso_local_user_table to help track license renewals
        self::create_license_renew_columns_if_not_exist();
        self::try_convert_trigger_data_for_new_analytics_system();
    }

    public static function handle(){
        if(self::isUpdated()){
            try{
                self::onUpdateHandler();
                update_option('ifso_wp_version',self::$code_version);
                self::$db_version = self::$code_version;
                return true;
            }
            catch (\Exception $e){
                error_log('If-so after upgrade service has thrown an exception : ' . $e->getMessage());
            }

        }
        return false;
    }


    private static function reactivate_licenses(){
        require_once IFSO_PLUGIN_BASE_DIR . 'services/license-service/license-service.class.php';
        require_once IFSO_PLUGIN_BASE_DIR . 'services/license-service/geo-license-service.class.php';


        // retrieve our license key & item name from the DB
        $license = get_option('edd_ifso_license_key');
        $item_id = get_option('edd_ifso_license_item_id');
        $status = get_option('edd_ifso_license_status');

        $geo_license = get_option('edd_ifso_geo_license_key');
        $geo_item_id = get_option('edd_ifso_geo_license_item_id');
        $geo_status = get_option('edd_ifso_geo_license_status');

        if($license && $status){
            $license_service = \IfSo\Services\LicenseService\LicenseService::get_instance();
            $license_service->activate_license(trim($license),$item_id);

        }

        if($geo_license && $geo_status){
            $geo_license_service =  \IfSo\Services\GeoLicenseService\GeoLicenseService::get_instance();
            $geo_license_service->activate_license(trim($geo_license),$geo_item_id);
        }
    }

    private static function reset_metabox_order(){
        global $wpdb;
        //$wpdb->update($wpdb->prefix.'usermeta',['meta_key'=>''],['meta_key'=>'meta-box-order_ifso_triggers']);
        $wpdb->delete($wpdb->prefix.'usermeta',['meta_key'=>'meta-box-order_ifso_triggers']);
    }

    private static function create_license_renew_columns_if_not_exist(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'ifso_local_user';
        $checkrow = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = 'pro_renewal_date'");
        if(empty($checkrow)){
            //$wpdb->query("ALTER TABLE `ifso_local_user` ADD `pro_renewal_date` DATE NULL DEFAULT NULL AFTER `alert_values`, ADD `geo_renewal_date` DATE NULL DEFAULT NULL AFTER `Pro_renewal_date`;");
            $wpdb->query("ALTER TABLE `{$table_name}` ADD `pro_bank` INT NOT NULL DEFAULT '0' AFTER `alert_values`, ADD `geo_bank` INT NOT NULL DEFAULT '0' AFTER `pro_bank`, ADD `used_pro_sessions` INT NOT NULL DEFAULT '0' AFTER `geo_bank`, ADD `used_geo_sessions` INT NOT NULL DEFAULT '0' AFTER `used_pro_sessions`, ADD `pro_renewal_date` DATE NULL DEFAULT NULL AFTER `used_geo_sessions`, ADD `geo_renewal_date` DATE NULL DEFAULT NULL AFTER `pro_renewal_date`;");
        }
    }

    public static function try_convert_trigger_data_for_new_analytics_system(){
        $cron_hook_name = 'analytics_import_cron_hook';
        $transient_name = 'ifso_attempted_analytics_data_import';
        $ifso_analytics_data_updated = 'ifso_updated_analytics_db';
        if(empty(get_option($ifso_analytics_data_updated))){
            add_action('admin_notices', function(){
                echo '<div class="notice notice-warning"><p>If-So analytics data is being imported into its new analytics system</p></div>';
            });
            $next_scheduled_cron = wp_next_scheduled($cron_hook_name);
            if($next_scheduled_cron && $next_scheduled_cron<time()) wp_unschedule_event($next_scheduled_cron,$cron_hook_name);
            if(!wp_next_scheduled($cron_hook_name)) wp_schedule_event(time(), 'every_three_mins', $cron_hook_name);
            if(!get_transient($transient_name)){
                set_transient($transient_name,true,60);
                set_time_limit(0);
                ignore_user_abort(true);
                if(self::convert_trigger_data_for_new_analytics_system())
                    add_option($ifso_analytics_data_updated, true);
            }
        }
    }

    private static function convert_trigger_data_for_new_analytics_system($max_exec_time=15){        //Adds version UIDs to all trigger versions
        global $wpdb;                                                               //Moves all of the views and conversions in the trigger data to the new analytics system
        $start_time = microtime(true);
        $trigger_rules_meta_key = 'ifso_trigger_rules';
        $default_analytics_meta_key = 'ifso_default_analytics';
        $triggers_data = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '{$trigger_rules_meta_key}'" );
        $default_analytics_data = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '{$default_analytics_meta_key}'",OBJECT_K );
        foreach ($triggers_data as $trigger_data) {
            $convs_import = [];
            $views_import = [];
            $need_update = false;
            $tdata = json_decode($trigger_data->meta_value);
            $pid = $trigger_data->post_id;
            $add_default_data = (!empty($default_analytics_data[$pid]) && !empty(json_decode($default_analytics_data[$pid]->meta_value)));
            if($add_default_data)
                $tdata[] = (object) array_merge(['version_uid'=>'default'],json_decode($default_analytics_data[$pid]->meta_value,true));
            if(!empty($tdata) && is_array($tdata)){
                foreach($tdata as $vid=>$vdata){
                    if(empty($vdata->version_uid)){
                        $need_update = true;
                        $vdata->version_uid = uniqid($vid);
                    }
                    if(!empty($vdata->conversion)){
                        for($i=0;$i<$vdata->conversion;$i++)
                            $convs_import[] = ['trigger_id'=>$pid,'version_uid'=>$vdata->version_uid,'conversion_type'=>null];
                        unset($vdata->conversion);
                        $need_update = true;
                    }
                    if(!empty($vdata->views)){
                        for($i=0;$i<$vdata->views;$i++)
                            $views_import[] = ['trigger_id'=>$pid,'version_uid'=>$vdata->version_uid];
                        unset($vdata->views);
                        $need_update = true;
                    }
                    if(!empty($vdata->recurrence_views)){
                        for($i=0;$i<$vdata->recurrence_views;$i++)
                            $views_import[] = ['trigger_id'=>$pid,'version_uid'=>$vdata->version_uid,'is_recurrence'=>true];
                        unset($vdata->recurrence_views);
                        $need_update = true;
                    }
                }
                if($add_default_data) unset($tdata[count($tdata)-1]);
                if($need_update)
                    $wpdb->update($wpdb->postmeta,['meta_value'=>json_encode($tdata)],['post_id'=>$pid,'meta_key'=>$trigger_rules_meta_key]);
                \IfSo\PublicFace\Services\AnalyticsService\AnalyticsService::get_instance()->records->import_events($convs_import,true);
                \IfSo\PublicFace\Services\AnalyticsService\AnalyticsService::get_instance()->records->import_events($views_import,false);
                $wpdb->delete($wpdb->postmeta,['meta_key'=>$default_analytics_meta_key,'post_id'=>$pid]);
            }
            if(microtime(true) - $start_time >= $max_exec_time) return false;
        }
        return true;
    }

}