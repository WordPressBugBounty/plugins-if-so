<?php
define( 'EDD_IFSO_STORE_URL', 'https://if-so.com' );
define( 'EDD_IFSO_ITEM_NAME', 'If>So Dynamic WordPress Content - Monthly Subscription' );
define( 'EDD_IFSO_PLUGIN_LICENSE_PAGE', 'wpcdd_admin_menu_license' );
define( 'EDD_IFSO_PLUGIN_GEO_PAGE', 'wpcdd_admin_geo_license' );
define( 'EDD_IFSO_PLUGIN_SETTINGS_PAGE', 'wpcdd_admin_menu_settings' );
define( 'EDD_IFSO_PLUGIN_GROUPS_PAGE', 'wpcdd_admin_menu_groups_list' );
define( 'EDD_IFSO_PLUGIN_DKI_PAGE', 'wpcdd_admin_dki_display' );
define( 'IFSO_GMAPS_API_KEY', 'AIzaSyBDDK41tRpT0bHllZdEUkcorGfVHAPlxNc' );
//define("IFSO_PLUGIN_MAIN_FILE_NAME", __FILE__);

define("IFSO_PLUGIN_BASE_DIR",
    plugin_dir_path ( dirname( __FILE__ ) ) );

define("IFSO_PLUGIN_MAIN_FILE_NAME", IFSO_PLUGIN_BASE_DIR . 'if-so.php');

define("IFSO_PLUGIN_SERVICES_BASE_DIR",
    plugin_dir_path ( dirname( __FILE__ ) ) . 'public/services/' );

define('IFSO_PLUGIN_DIR_URL',
    plugin_dir_url(dirname(__FILE__)));

define("IFSO_WP_VERSION", '1.9.4');
define("IFSO_API_VERSION", 'v3');

define( 'W3TC_DYNAMIC_SECURITY', 'IFSO_W3TC_DYNAMIC_SECURITY' );