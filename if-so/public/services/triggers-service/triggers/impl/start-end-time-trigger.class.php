<?php

namespace IfSo\PublicFace\Services\TriggersService\Triggers;

require_once( plugin_dir_path ( __DIR__ ) . 'trigger-base.class.php');
require_once('time-date-trigger-base.class.php');

class StartEndTimeTrigger extends TimeDateTriggerBase {
    private $timezone;
	protected function is_valid($trigger_data) {
		if ( !parent::is_valid($trigger_data) )
			return false;

		$rule = $trigger_data->get_rule();
		return $rule["Time-Date-Schedule-Selection"] == "Start-End-Date";
	}

	public function handle($trigger_data) {
		$rule = $trigger_data->get_rule();
		$content = $trigger_data->get_content();
        $this->timezone = !empty($rule['Date-Time-User-Timezone']) && $rule['Date-Time-User-Timezone']==='user-geo' ? $this->get_timezone('geo') : $this->get_timezone();
        $currDate = new \DateTime('now',$this->timezone);

		if ( ( isset($rule['Time-Date-Start']) &&
			   isset($rule['Time-Date-End']) && 
			   $rule['Time-Date-Start'] == "None" &&
			   $rule['Time-Date-End'] == "None" ) || 
			 ( empty($rule['time-date-end-date']) && 
			  	empty($rule['time-date-start-date']) ) ) {
			return $content;
		}

		if ( ( isset($rule['Time-Date-Start']) && 
			   $rule['Time-Date-Start'] == "None" ) ||
			  empty($rule['time-date-start-date']) ) {

			// No start date
			$endDate = $this->create_date($rule['time-date-end-date']);

			if ($currDate <= $endDate) {
				// Yes! we are in the right time frame
				return $content;
			}

		} else if ( ( isset($rule['Time-Date-End']) && 
			   		  $rule['Time-Date-End'] == "None" ) ||
			  		  empty($rule['time-date-end-date']) ) {

			// No end date
			$startDate = $this->create_date($rule['time-date-start-date']);

			if ($currDate >= $startDate) {
				// Yes! we are in the right time frame
				return $content;
			}
		} else {
			// Both have dates
			$startDate = $this->create_date($rule['time-date-start-date']);
			$endDate = $this->create_date($rule['time-date-end-date']);

			if ($currDate >= $startDate &&
				$currDate <= $endDate) {

				// Yes! we are in the right time frame

				return $content;
			}
		}

		return false;
	}

    private function create_date($date_string){
        $format = "Y/m/d H:i";
        if(preg_match('/^\d+\:\d+/',$date_string))      // TIME only
            $format = 'H:i';
        //elseif(preg_match('/\d+\/\d+\/\d+ \d+\:\d+/',$date_string))     // DATE and TIME
        //    $format = "Y/m/d H:i";
        return \DateTime::createFromFormat($format,$date_string,$this->timezone);
    }
}