<?php
namespace IfSo\PublicFace\Services\AnalyticsService;
class AnalyticsRecords{
    private $wpdb;
    public $conversions_table_name;
    public $conversions_events_table_name;
    public $conversion_urls_table_name;
    public $views_events_table_name;

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->conversions_table_name = $wpdb->prefix . 'ifso_analytics_conversions';
        $this->conversions_events_table_name = $wpdb->prefix . 'ifso_analytics_conversions_events';
        $this->conversion_urls_table_name = $wpdb->prefix . 'ifso_analytics_conversions_urls';
        $this->views_events_table_name = $wpdb->prefix . 'ifso_analytics_views';
    }

    public function create_conversion($name){
        $this->wpdb->insert($this->conversions_table_name,['name'=>$name]);
        return $this->wpdb->insert_id;
    }

    public function get_conversion($cid){
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->conversions_table_name} WHERE id=%d",[$cid]));
    }

    public function get_conversions(){
        $general_conversion = [''=>(object)['id'=>0,'name'=>'General']];       //JANK
        return $general_conversion+$this->wpdb->get_results("SELECT * FROM {$this->conversions_table_name}",OBJECT_K);
    }

    public function update_conversion_name($cid,$newname){
        $this->wpdb->update($this->conversions_table_name,['name'=>$newname],['id'=>$cid]);
    }

    public function update_conversion_fields($cid,$fields){
        $this->wpdb->update($this->conversions_table_name,$fields,['id'=>$cid]);
    }

    public function delete_conversion($cid){
        //Replace with one JOINed statement?
        $this->wpdb->delete($this->conversions_table_name,['id'=>$cid],['%d']);
        $this->delete_conversion_urls($cid);
        $this->delete_conversion_events($cid);
    }

    public function set_conversion_urls($cid,$urls){
        $insert_sql = "INSERT IGNORE INTO {$this->conversion_urls_table_name} (conv_id,url,with_query_string) VALUES ";
        $delete_sql = $this->wpdb->prepare("DELETE FROM {$this->conversion_urls_table_name} WHERE conv_id=%d",[$cid]);
        foreach($urls as $i=>$url){
            $insert_sql .= $this->wpdb->prepare('(%d, %s, %d)' . ($i===count($urls)-1 ? ';' : ','),[$cid,$url->url,(int)$url->with_query_string]);
            $delete_sql .= $this->wpdb->prepare(' AND (url!=%s OR with_query_string!=%d)',[$url->url,(int)$url->with_query_string]);
        }
        if(!empty($urls))
            $this->wpdb->query($insert_sql);
        $this->wpdb->query($delete_sql);
    }

    public function get_url_conversions($url,$withqs=false){
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->conversion_urls_table_name} WHERE url='%s'",[$url]);
        if($withqs){
            $queryless_Url = explode('?',$url)[0];
            $sql = $this->wpdb->prepare($sql . "AND with_query_string = true OR url='%s'",[$queryless_Url]);
        }
        return $this->wpdb->get_results($sql);
    }

    public function get_conversion_urls($cid){
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->conversion_urls_table_name} WHERE conv_id='%d'",[$cid]));
    }

    public function delete_conversion_urls($cid){
        $this->wpdb->delete($this->conversion_urls_table_name,['conv_id'=>$cid],['%d']);
    }

    public function create_conversion_event($trigger_id,$version_uid,$conv_type=null){
        $this->wpdb->insert($this->conversions_events_table_name,['trigger_id'=>$trigger_id,'version_uid'=>$version_uid,'conversion_type'=>$conv_type]);
    }

    public function import_events($events,$convs_or_views=true){
        $tbl = ($convs_or_views) ? $this->conversions_events_table_name : $this->views_events_table_name;
        $third_column = ($convs_or_views) ? 'conversion_type' : 'is_recurrence';
        $insert_sql = "INSERT INTO {$tbl} (trigger_id,version_uid,{$third_column}) VALUES";
        if(empty($events)) return;
        foreach ($events as $i=>$event){
            if(isset($event['trigger_id']) && isset($event['version_uid'])){
                if($convs_or_views && !(isset($event['conversion_type']) || $event['conversion_type']===null)) continue;
                if(!$convs_or_views){
                    $is_recurrence = isset($event['is_recurrence']) && (bool)$event['is_recurrence'];
                    $insert_sql .= $this->wpdb->prepare('(%d,%s,%d)',[$event['trigger_id'],$event['version_uid'],$is_recurrence]);
                }
                elseif(isset($event['conversion_type']) || $event['conversion_type']===null){
                    if($event['conversion_type']!=null)
                        $insert_sql.= $this->wpdb->prepare('(%d,%s,$d)',[$event['trigger_id'],$event['version_uid'],$event['conversion_type']]);
                    else
                        $insert_sql.= $this->wpdb->prepare('(%d,%s,NULL)',[$event['trigger_id'],$event['version_uid']]);
                }
                $insert_sql .= ($i===count($events)-1 ? ';' : ',');
            }
        }
        $this->wpdb->query($insert_sql);
    }

    public function get_conversion_events_counts_by_trigger($conv_type=null,$trigger_id=null, $start_time=null, $end_time=null){
        $datetime_condition = $this->make_datetime_condition_sql($start_time,$end_time);
        $trigger_id_condition = $trigger_id===null ? '' : $this->wpdb->prepare('trigger_id=%d',[$trigger_id]);
        if($conv_type===null) $conv_type_condition = '';
        else $conv_type_condition = $conv_type===0 ? 'conversion_type IS NULL' : $this->wpdb->prepare('conversion_type=%s',[$conv_type]);
        $condition_sql = $conv_type_condition . (!empty($conv_type_condition) && !empty($datetime_condition) ? ' AND ' : '') . $datetime_condition;
        $condition_sql .= ((!empty($condition_sql) && !empty($trigger_id_condition)) ? ' AND ' : '') . $trigger_id_condition;
        if(!empty($condition_sql)) $condition_sql = " WHERE {$condition_sql}";
        return $this->wpdb->get_results("
                            SELECT trigger_id, version_uid,conversion_type, COUNT(id) as count FROM `{$this->conversions_events_table_name}`
                            {$condition_sql} GROUP BY trigger_id,version_uid,conversion_type;");
    }

    public function get_conversion_events_counts(){
        return $this->wpdb->get_results(
                "SELECT conversion_type, COUNT(*) as count FROM {$this->conversions_events_table_name} GROUP BY conversion_type",OBJECT_K);
    }

    public function get_conversion_events_date_range(){
        return $this->wpdb->get_results("SELECT conversion_type, MIN(datetime) as earliest, MAX(datetime) as latest
                                            FROM {$this->conversions_events_table_name} GROUP BY conversion_type",OBJECT_K);
    }

    public function delete_conversion_events($cid){
        $this->wpdb->delete($this->conversions_events_table_name,['conversion_type'=>$cid],['%d']);
    }

    public function create_view_event($trigger_id,$version_uid,$isRecurrence){
        if(empty($trigger_id) || empty($version_uid)) return;
        $this->wpdb->insert($this->views_events_table_name,['trigger_id'=>$trigger_id,'version_uid'=>$version_uid,'is_recurrence'=>$isRecurrence]);
    }

    public function get_view_event_counts_by_trigger($start_time=null,$end_time=null,$trigger_id=null){
        $datetime_condition = $this->make_datetime_condition_sql($start_time,$end_time);
        $where_sql = !empty($datetime_condition) ? "WHERE {$datetime_condition}" : '';
        if($trigger_id!==null) $where_sql .= (!empty($where_sql) ? ' AND' : 'WHERE') . $this->wpdb->prepare(' trigger_id=%d',$trigger_id);
        return $this->wpdb->get_results("
                            SELECT trigger_id, version_uid, is_recurrence,  COUNT(id) as count FROM `{$this->views_events_table_name}`
                            {$where_sql} GROUP BY trigger_id,version_uid,is_recurrence;");
    }

    public function count_trigger_views($trigger_id, $only_unique=true){
        $recurrence_cond = $only_unique ? " AND is_recurrence=0" : "";
        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(id) as count FROM `{$this->views_events_table_name}` WHERE trigger_id=%d{$recurrence_cond};",[$trigger_id]));
    }

    public function delete_data_by_trigger($trigger_id=null,$version_uid=null){
        if($trigger_id===null && $version_uid===null){
            $this->wpdb->query("TRUNCATE TABLE {$this->views_events_table_name}");
            $this->wpdb->query("TRUNCATE TABLE {$this->conversions_events_table_name}");
        }
        elseif($trigger_id!==null){
            $where_array = ['trigger_id'=>$trigger_id];
            if($version_uid!==null) $where_array['version_uid'] = $version_uid;
            $this->wpdb->delete($this->views_events_table_name,$where_array);
            $this->wpdb->delete($this->conversions_events_table_name,$where_array);
        }
    }

    private function make_datetime_condition_sql($start_time,$end_time){
        return $start_time!==null && $end_time!==null ?
            $this->wpdb->prepare(" datetime BETWEEN %s AND %s",[$start_time,"{$end_time} 23:59:59"]) : "";
    }

    public function make_trigger_conversion_report($tid){
        $trigger_context = \IfSo\PublicFace\Services\TriggersService\TriggerContextLoader::load_context(['id'=>$tid],null);
        $dr = $trigger_context->get_data_rules();
    }


}