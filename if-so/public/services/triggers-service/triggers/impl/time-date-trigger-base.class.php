<?php

namespace IfSo\PublicFace\Services\TriggersService\Triggers;

require_once( plugin_dir_path ( __DIR__ ) . 'trigger-base.class.php');

abstract class TimeDateTriggerBase extends TriggerBase {
	public function __construct() {
		parent::__construct('Time-Date');
	}

	protected function is_valid($trigger_data) {
		$rule = $trigger_data->get_rule();

		if ( !isset($rule["Time-Date-Schedule-Selection"]) )
			return false;
		else if ( empty($rule["Time-Date-Schedule-Selection"]) )
			return false;

		return true;
	}


    protected function get_timezone($tz_type='wp'){
        $tz = \IfSo\PublicFace\Helpers\WpDateTimeZone::getWpTimezone();
        if($tz_type === 'geo'){
            $user_geo_tz = \IfSo\Services\GeolocationService\GeolocationService::get_instance()->get_user_location()->get('timeZone');
            if($user_geo_tz!==null)
                $tz =  new \DateTimeZone($user_geo_tz);
        }
        return $tz;

    }
}
