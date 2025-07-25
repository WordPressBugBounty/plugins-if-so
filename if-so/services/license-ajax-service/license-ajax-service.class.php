<?php

namespace IfSo\Services\LicenseAjaxService;

class LicenseAjaxService {
    private static $instance;
    protected $license;
    protected $item_id;

    private function __construct() {
        $this->license = get_option( 'edd_ifso_license_key' );
        $this->item_id = get_option( 'edd_ifso_license_item_id' );
    }

    public static function get_instance() {
        if ( NULL == self::$instance )
            self::$instance = new LicenseAjaxService();

        return self::$instance;
    }

    public function return_license_data() {
        $license = $this->license;
        $item_id = $this->item_id;
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_name'  => $item_id , // the name of our product in EDD
            'url'        => home_url()
        );
        $response = wp_remote_post( EDD_IFSO_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            if ( is_wp_error( $response ) ) {
                $message = $response->get_error_message();
            } else {
                $message = __( 'An error occurred, please try again.' );
            }
        }
        else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            $message = false;
            if ( false === $license_data->success ) {
                //die("Im dead");
                if ( $license_data->error == 'expired' ) {
                    return $message = sprintf(
                        __( 'Your license key expired on %s. ' ),
                        date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                    );
                }
            }
        }
    }

    public function licenseAjaxController(){
        if(current_user_can('administrator') && check_admin_referer('ifso-admin-nonce','_ifsononce') && isset($_POST['page'])){
            switch ($_POST['page']){
                case 'triggerPage':
                    $this->triggerPage_message_action();
                    break;
                case 'licensePage':
                    $this->licensePage_message_action();
                    break;
            }
        }
    }

    public function triggerPage_message_action(){
        $message_license_expired = $this->return_license_data();
        $arrow_svg = function($color='#fff'){return '<svg style="vertical-align:middle;margin-left:8px;transform:translateY(-1px);" class="license-message-arrow" xmlns="http://www.w3.org/2000/svg" width="11" height="13" viewBox="0 0 19 18"> <path id="arrow" fill="' . $color . '" d="M10.054 18l-1.549-1.56 6.299-6.342H.002V7.894h14.802l-6.299-6.34 1.549-1.559 8.943 9.002z"></path> </svg>';};
        $lockedConditionBox_message = function($link,$text)use($arrow_svg){
            return '
                <a href="' . $link . '" target="_blank">
                <div class="get-license clearfix" style="margin-top: 0;background: #f8f8f8;padding: 8px 30px;border-top: 1px solid #e5e5e5;color: #d66249;">
                    <div style="color:#d25134" class="text">
                        '.$text.'
                    </div>
                    <a href="'. $link . '" class="get-license-btn_red" target="_blank">'.__("CONTINUE", 'if-so').$arrow_svg('#d25134').'</a>
                </div>
                </a>';
        };
        $lockedVersionBox_message = function($link,$text)use($arrow_svg){
            return '
                <a href="'. $link . '" target="_blank">
                <div class="get-license clearfix">
                    <div class="text">
                        '.$text.'
                    </div>
                    <a href="' . $link . '" class="get-license-btn" target="_blank" >'.__('CONTINUE', 'if-so'). $arrow_svg() .'</a>
                </div>
                </a>';
        };
        if ($message_license_expired) {
            $lockedConditionBox = $lockedConditionBox_message('https://www.if-so.com/plans?utm_source=Plugin&utm_medium=FreeTrial&utm_campaign=wordpessorg&utm_term=lockedCondition',
                                                                __($message_license_expired .  'Click here to get a new license if you do not have one.', 'if-so'));
            $lockedVersionBox = $lockedVersionBox_message('https://www.if-so.com/plans?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=triggerTop&utm_content=a',
                                                                __($message_license_expired . 'Click here to get a new license if you do not have one.', 'if-so'));
        }
        else if (true == get_option( 'edd_ifso_user_deactivated_license') ) {
            $lockedConditionBox = $lockedConditionBox_message('https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=lockedConditon&utm_content=b',
                                                                __('License activation is required to use this condition.', 'if-so'));
            $lockedVersionBox = $lockedVersionBox_message('https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=lockedConditon&utm_content=b',
                                                            __('Activate your license key to unlock the full power of If-So. Don`t have a license? Click here to get one', 'if-so'));
        }  else {
            $lockedConditionBox = $lockedConditionBox_message('https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=lockedConditon&utm_content=a',
                                                                __('License activation is required to use this condition.', 'if-so') );
            $lockedVersionBox = $lockedVersionBox_message('https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=lockedConditon&utm_content=a',
                                                                __('Activate your license key to unlock the full power of If-So. Don`t have a license? Click here to get one.', 'if-so'));
        }

        $ret = [
            'condition'=>$lockedConditionBox,
            'version'=>$lockedVersionBox
        ];
        echo json_encode($ret,JSON_UNESCAPED_SLASHES);
        wp_die();
    }

    public function licensePage_message_action(){
        $message_license_expired = $this->return_license_data();
        if ($message_license_expired) {
            $noLicenseMessageBox = '<div class="no_license_message">'. __($message_license_expired , 'if-so') . '<a style="color:#fff;font-weight: 600;" href="https://www.if-so.com/plans?utm_source=Plugin&utm_medium=direct&utm_campaign=gopro&utm_term=licenseExpired&utm_content=b" target="_blank">'.__(" Click here to get a new license", 'if-so') .'</a>.</div>';
        }

        else if ( true == get_option( 'edd_ifso_user_deactivated_license' ) ) {
            $noLicenseMessageBox = '<div class="no_license_message">'. __("Activate your license key to unlock all features. ", 'if-so') . '<a href="https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=licensePage&utm_content=b" target="_blank">'. __("Click here to get a license key if you do not have one", 'if-so') . '</a>.</div>';
        }

        else {
            $noLicenseMessageBox = '<div class="no_license_message">'. __("Activate your license key to unlock all features. ", 'if-so') .'<a href="https://www.if-so.com/free-license?utm_source=Plugin&utm_medium=direct&utm_campaign=getFree&utm_term=licensePage&utm_content=a" target="_blank">'. __("Click here to get a license key if you do not have one", 'if-so') . '</a>.</div>';
        }
        //echo json_encode($noLicenseMessageBox,JSON_UNESCAPED_SLASHES);
        echo $noLicenseMessageBox;
        wp_die();
    }
}