<?php
namespace IfSo\PublicFace\Services\TriggersService\Triggers;
use IfSo\PublicFace\Services\AnalyticsService\AnalyticsService;

require_once( plugin_dir_path ( __DIR__ ) . 'trigger-base.class.php');
class ABTestingTrigger extends TriggerBase {
    private ?int $cached_total_views = null;
	public function __construct() {
		parent::__construct('AB-Testing');
	}
	protected function is_valid($trigger_data) {
		$rule = $trigger_data->get_rule();
        if (empty($rule['AB-Testing']))
            return false;
		if (empty($rule['ab-testing-sessions']))
            return true;
		$sessions_bound = $rule['ab-testing-sessions'];
		if ($sessions_bound === 'Custom' && empty($rule['ab-testing-custom-no-sessions']))
			return false;
		if (!$this->is_views_count_valid($trigger_data,$rule))
			return false;
		return true;
	}
	private function is_views_count_valid($trigger_data,$rule) {
		$sessions_bound = $rule['ab-testing-sessions'];
		if ($sessions_bound === 'Custom') {
			$sessions_bound = $rule['ab-testing-custom-no-sessions'];
		}
		if ($sessions_bound !== 'Unlimited' &&
			$this->get_total_trigger_views($trigger_data->get_trigger_id()) >= (int)$sessions_bound)
			return false;
		else
			return true;
	}
	public function handle($trigger_data) {
        $ret = false;
		$trigger_id = $trigger_data->get_trigger_id();
		$rule = $trigger_data->get_rule();
        $content = $trigger_data->get_content();
		/*$version_index = $trigger_data->get_version_index();
		$data_rules = &$trigger_data->get_data_rules();
		$views_count = (int) $rule['number_of_views'];*/
        $views_count = $this->get_total_trigger_views($trigger_id);
        $views_count++;
        $tdata = !empty($rule['AB-Testing']) ? explode('||',$rule['AB-Testing']) : null;
        if($tdata!==null && count($tdata)===1){     //OLD WAY - COMPAT
            $perc = $tdata[0];
            $factors = array("20%" => 5,
                "25%" => 4,
                "33%" => 3,
                "50%" => 2,
                "75%" => 4,
                "100%" => 1);
            $factor = $factors[$perc];
            $fact_remainder = $views_count % $factor;
            if ($perc == "20%" && $fact_remainder == 0) {
                $ret = $content;
            }if ($perc == "25%" && $fact_remainder == 0) {
                $ret = $content;
            } else if ($perc == "33%" && $fact_remainder == 0) {
                $ret = $content;
            } else if ($perc == "50%" && $fact_remainder == 0) {
                $ret = $content;
            } else if ($perc == "75%" &&
                in_array($fact_remainder, array(0, 1, 2))) {
                $ret = $content;
            } else if ($perc == "100%") {
                $ret = $content;
            }
        }
        if($tdata!==null && count($tdata)===3){     //NEW WAY
            $trigger_chosen_version = (int)$tdata[1];
            $trigger_maximum_versions = (int)$tdata[2];
            $reduced = $views_count % $trigger_maximum_versions;
            if(($reduced===0 && $trigger_chosen_version===$trigger_maximum_versions) || $reduced===$trigger_chosen_version ){
                $ret = $content;
            }
        }

        /*if($ret!==false && false){
            $views_count += 1;
            $data_rules[$version_index]['number_of_views'] = $views_count;
            $data_rules_cleaned =
                str_replace("\\", "\\\\\\", json_encode($data_rules));
            update_post_meta( $trigger_id, 'ifso_trigger_rules', $data_rules_cleaned);
       }*/

        $this->cached_total_views = null;
	
		return $ret;
	}

    private function get_total_trigger_views($tid){
        if($this->cached_total_views!==null)
            return $this->cached_total_views;
        $total = 0;
        foreach(AnalyticsService::get_instance()->get_analytics_fields($tid) as $version_an){
            if(!empty($version_an['views']))
                $total += $version_an['views'];
        }

        $this->cached_total_views = $total;

        return $total;
    }
}