<?php

require_once(IFSO_PLUGIN_BASE_DIR . 'services/geolocation-service/geolocation-service.class.php');
require_once(IFSO_PLUGIN_BASE_DIR . 'public/services/analytics-service/analytics-service.class.php');
use IfSo\Services\GeolocationService;

function is_geo_data($geoDatas) {
	return ( isset($geoDatas['success']) && $geoDatas['success'] == true );
}
function get_monthly($geoDatas) {
	if ( is_geo_data($geoDatas) ) {
		return $geoDatas['bank'];
	}
	return 0;
}
function get_queries($geoDatas) {
	if ( is_geo_data($geoDatas) ) {
		return intval($geoDatas['realizations']);
	}
	return 0;
}

if(!function_exists('ifso_jal_install')){
    function ifso_jal_install() {
        global $wpdb;
        $db_version = '1.0';
        $db_prefix = $wpdb->prefix;
        $local_user_table_name = $db_prefix . 'ifso_local_user';
        $daily_sessions_table_name = $db_prefix . 'ifso_daily_sessions';
        $charset_collate = $wpdb->get_charset_collate();

        if($wpdb->get_var("SHOW TABLES LIKE 'ifso_local_user'") || $wpdb->get_var("SHOW TABLES LIKE 'ifso_daily_sessions'"))    //If tables with the old table names still exist, rename them to the new names
            $wpdb->query("RENAME TABLE ifso_local_user TO {$local_user_table_name}, ifso_daily_sessions TO {$daily_sessions_table_name}");


        $sql = "CREATE TABLE IF NOT EXISTS {$local_user_table_name} (
        `id` int(11) NOT NULL AUTO_INCREMENT,
		`user_email` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
		`user_bank` int(7) NOT NULL,
		`user_sessions` int(7) NOT NULL,
		`alert_values` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
		`pro_bank` INT NOT NULL DEFAULT '0',
		`geo_bank` INT NOT NULL DEFAULT '0',
		`used_pro_sessions` INT NOT NULL DEFAULT '0',
		`used_geo_sessions` INT NOT NULL DEFAULT '0',
		`pro_renewal_date` DATE NULL DEFAULT NULL,
		`geo_renewal_date` DATE NULL DEFAULT NULL,
		PRIMARY KEY(id)
		) $charset_collate;";

        $wpdb->query("DROP TABLE IF EXISTS {$local_user_table_name}");
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );


        $license = get_option( 'edd_ifso_geo_license_key' );
        $geoDatas = GeolocationService\GeolocationService::get_instance()->get_status($license,false);
        $analyticsRecords = \IfSo\PublicFace\Services\AnalyticsService\AnalyticsService::get_instance()->records;
        $geo_monthly_queries = get_monthly($geoDatas);
        $geo_queries_used = get_queries($geoDatas);
        $alert_values =$wpdb->get_var("SELECT alert_values FROM {$local_user_table_name}");
        if($alert_values==NULL) $alert_values = '100 95 75';
        $wp_user_email = get_option('admin_email');

        $sql = "INSERT IGNORE INTO {$local_user_table_name} (`id`, `user_email`, `user_bank`, `user_sessions`, `alert_values`) VALUES (1,'{$wp_user_email}', '{$geo_monthly_queries}', '{$geo_queries_used}', '{$alert_values}')";
        $wpdb->query($sql);


        dbDelta("CREATE TABLE IF NOT EXISTS {$daily_sessions_table_name} (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`sessions_date` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
			`num_of_sessions` int(11) NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `sessions_date` (`sessions_date`)
			) $charset_collate;");

        dbDelta("CREATE TABLE {$analyticsRecords->conversions_table_name} (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
			`once_per` int(11) NULL,
			`trigger_filter` json NULL,
			`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
			) $charset_collate;");

        dbDelta("CREATE TABLE IF NOT EXISTS {$analyticsRecords->conversions_events_table_name} (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`trigger_id` int(11) NOT NULL,
			`version_uid` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
			`conversion_type` int(11) NULL,
			`datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
			) $charset_collate;");

        dbDelta("CREATE TABLE IF NOT EXISTS {$analyticsRecords->views_events_table_name} (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`trigger_id` int(11) NOT NULL,
			`version_uid` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
            `is_recurrence` boolean NOT NULL DEFAULT false,
			`datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
			) $charset_collate;");

        dbDelta("CREATE TABLE IF NOT EXISTS {$analyticsRecords->conversion_urls_table_name} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `conv_id` int(11) NOT NULL,
            `url` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
            `with_query_string` boolean NOT NULL DEFAULT false,
            CONSTRAINT items UNIQUE(conv_id, url, with_query_string),
            PRIMARY KEY (`id`)
            ) $charset_collate;");

        add_option( 'ifso_db_version', $db_version );
    }
}