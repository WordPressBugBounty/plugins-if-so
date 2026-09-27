<?php
namespace IfSo\PublicFace\Services\AnalyticsService;
if ( ! defined( 'ABSPATH' ) ) exit;
$esc_html = function($text){return esc_html($text);};
$date_format = 'Y-m-d';
$today = date($date_format);
$report_type = !empty($_REQUEST['report_type']) ? $_REQUEST['report_type'] : 'conversions';
$specific_trigger_mode = $report_type==='triggers' && isset($_REQUEST['trigger_id']) ? $_REQUEST['trigger_id'] : null;
$license_valid = \IfSo\Services\LicenseService\LicenseService::get_instance()->is_license_valid();
$persistent_controls_cookie_name = 'ifso_analytics_ui_controls';
$persistent_controls = !empty($_COOKIE[$persistent_controls_cookie_name]) ? json_decode(stripslashes($_COOKIE[$persistent_controls_cookie_name]),true) : [];
if(!empty($_REQUEST['start_date'])) $persistent_controls['start_date'] = $_REQUEST['start_date'];
if(!empty($_REQUEST['end_date'])) $persistent_controls['end_date'] = $_REQUEST['end_date']!==$today ? $_REQUEST['end_date'] : null;
if(!empty($_REQUEST['ifso_conversion_filter']) && !$specific_trigger_mode) $persistent_controls['conversion_filter'] = $_REQUEST['ifso_conversion_filter'];
if(isset($_REQUEST['trigger_status_filter'])) $persistent_controls['trigger_status_filter'] = $_REQUEST['trigger_status_filter'];
if(!empty($_REQUEST['recurrence_views'])) $persistent_controls['recurrence_views'] = ($_REQUEST['recurrence_views']==='include');
if(!empty($persistent_controls)) setcookie($persistent_controls_cookie_name, json_encode($persistent_controls),0,'/');
$start_date = !empty($persistent_controls['start_date']) && $license_valid ? $persistent_controls['start_date'] : date($date_format,strtotime('-30 days'));
$end_date = !empty($persistent_controls['end_date']) && $license_valid ? $persistent_controls['end_date'] : $today;
if($specific_trigger_mode)
    $conversion_events_filter = !empty($_REQUEST['ifso_conversion_filter']) && $license_valid ? $_REQUEST['ifso_conversion_filter'] : false;
else
    $conversion_events_filter = !empty($persistent_controls['conversion_filter']) && $license_valid ? $persistent_controls['conversion_filter'] : false;
$trigger_status_filter = !empty($persistent_controls['trigger_status_filter']) && $license_valid ? $persistent_controls['trigger_status_filter'] : false;
$recurrence_views = !empty($persistent_controls['recurrence_views']) && $license_valid ? $persistent_controls['recurrence_views'] : false;
$switch_report_type_url = admin_url('admin.php?page='.EDD_IFSO_PLUGIN_ANALYTICS_PAGE) . '&report_type=' . ($report_type==='conversions' || $specific_trigger_mode ? 'triggers' : 'conversions');
$trigger_posts = get_posts(['post_type'=>'ifso_triggers','posts_per_page'=>-1]);
$analytics_db = AnalyticsService::get_instance()->records;
$conversions = $analytics_db->get_conversions();
$conv_event_counts = $analytics_db->get_conversion_events_counts();
$conv_event_date_range = $analytics_db->get_conversion_events_date_range();
$view_event_counts = filter_events_with_nonexistant_triggers(
        $analytics_db->get_view_event_counts_by_trigger($start_date,$end_date,$specific_trigger_mode),$trigger_posts);
$conversion_events_selected = !empty($conversion_events_filter) && !empty(array_diff(array_column($conversions,'id'),$conversion_events_filter));
if(empty($_REQUEST['ifso_conversion_filter']) && $specific_trigger_mode) $conversion_events_filter = false;
$need_license_message = function($text,$term)use($license_valid){
  return $license_valid ? "" :
      "<a target='_blank' href='https://if-so.com/plans?utm_source=Plugin&utm_medium=analyticsPage&utm_campaign=lockedFeature&utm_term={$term}' class='need_license_message'>{$text}</a>";
};
$no_data_message = '<div class="yellow-noticebox no-data-notice"><p><b>No data to show.</b> Try adjusting the date range or filters, or check back once your triggers have had more time to collect data.</p></div>';
$get_hamburger_menu = function($view_url,$edit_url,$inject_menu_content='')use($report_type){
    $view_trigger = $report_type!=='conversions' ? "<a href='{$view_url}' target='_blank'><i class='fa fa-eye'></i> View Trigger</a>" :
        "<a href='#' onclick=\"window.open('{$view_url}','newwindow',report_popup_dimensions);return false;\"><i class='fa fa-bar-chart'></i> View full trigger report</a>";
    return "<span class='hamburger'>⋮</span>
            <div class='trigger-report-actions'>
                {$view_trigger}
                <a href='{$edit_url}' target='_blank'><i class='fa fa-pencil'></i> Edit Trigger</a>
                <a class='reset-trigger-btn' href='#'><i class='fa fa fa-trash-o'></i> Reset Trigger Data</a>
                {$inject_menu_content}
            </div>";
};

function get_conversion_meta_el_attributes($conv){
    $conv_safe = (object) array_map(function($el){return esc_attr($el);},get_object_vars($conv));
    $conv_urls_json = json_encode(AnalyticsService::get_instance()->records->get_conversion_urls($conv->id));
    $once_per_attr = isset($conv_safe->once_per) ? "once_per='{$conv_safe->once_per}'" : '';
    $trigger_filter_attr = !empty($conv_safe->trigger_filter) ? "data-trigger_filter='{$conv_safe->trigger_filter}'" : '';
    return "data-urls='{$conv_urls_json}' conv_id='{$conv_safe->id}' conv_name='{$conv_safe->name}' {$once_per_attr} {$trigger_filter_attr}";
}
function get_ifso_conversion_form_inputs($conv_action,$html_or_qs=true){
    $ret = '';
    $values = ['ifso_conversion_action'=>$conv_action,'action'=>'ifso_conversions_req','_ifsononce'=>wp_create_nonce('ifso-admin-nonce')];
    if($html_or_qs){
        foreach ($values as $key => $value)
            $ret.= "<input type='hidden' name='{$key}' value='{$value}'>";
    }
    else
        $ret = http_build_query($values);
    return $ret;
}
function get_trigger_data($tid,$trigger_posts){
    $edit_trigger_url =  home_url() . "/wp-admin/post.php?action=edit&post={$tid}";
    $view_trigger_url = get_post_permalink($tid);
    $tname = $trigger_posts[array_search($tid,array_column($trigger_posts,'ID'))]->post_title;
    $trigger_status = get_post_status($tid);
    return ['edit_url'=>$edit_trigger_url,'view_url'=>$view_trigger_url,'name'=>esc_html($tname),'status'=>$trigger_status];
}
function calculate_conversion_rate($conversions,$views){
    return (int)$conversions===0 || (int)$views===0 ? 0 : round((float)$conversions / (float)$views * 100,1);
}
function filter_events_with_nonexistant_triggers($events,$trigger_posts){
    return array_filter($events,function($event)use($trigger_posts){
        return in_array($event->trigger_id,array_column($trigger_posts,'ID'));
    });
}
function conversion_trigger_filter_allows_trigger($conv,$tid){
    if(!empty($conv->trigger_filter)){
        $tf = json_decode($conv->trigger_filter);
        if(($tf->type==='exclude' && in_array($tid, $tf->triggers)) || ($tf->type==='include' && (empty($tf->triggers) || !in_array($tid, $tf->triggers)))) return false;
    }
    return true;
}
?>
<div class="wrap analytics-conversions-page <?php if(!$license_valid) echo 'need_license'; ?>">
    <h2><?php _e('If-So Dynamic Content | Analytics'); ?></h2>
<?php if(!$specific_trigger_mode){ ?>
    <h2>Your Conversions</h2>
    <button style="margin-bottom:15px;" class="create_conversion_btn button button-secondary">+ Create a new conversion</button>
    <table class="conversion_display_tbl">
        <tbody>
            <tr>
                <th>ID</th><th>Name</th><th>Target URL</th><th>Date Created</th><th>First Conversion Date</th><th>Latest Conversion Date</th><th>Conversions</th><th>Actions</th>
            </tr>
            <?php
            foreach($conversions as $ckey => $conv){
                $conv_urls = $analytics_db->get_conversion_urls($conv->id);
                $conv_urls_string = '';
                foreach($conv_urls as $urlckey => $conv_url){
                    $xmore = ($urlckey===0 && count($conv_urls)>1) ? "<span class='xmore edit_conversion_btn'>(+" . count($conv_urls)-1 . " more)</span>" : '';
                    $conv_urls_string.="<span class='conversion_url'>" . esc_url($conv_url->url) ."{$xmore}</span>";
                    break;
                }
                $meta_el_attrs = get_conversion_meta_el_attributes($conv);
                $count = !empty($conv_event_counts[$ckey]) ? $conv_event_counts[$ckey]->count : 0;
                $earliest = !empty($conv_event_date_range[$ckey]) ? (new \DateTime($conv_event_date_range[$ckey]->earliest))->format($date_format) : '';
                $latest = !empty($conv_event_date_range[$ckey]) ? (new \DateTime($conv_event_date_range[$ckey]->latest))->format($date_format . ' H:i') : '';
                $created_at = !empty($conv->created_at) ? (new \DateTime($conv->created_at))->format($date_format) : '';
                $btns_nongeneral = ($conv->id!==0) ? " | <button class='reset_conversion_btn'>Reset</button> | <button class='delete_conversion_btn'>Delete</button>" : '';
                $buttons = "<div class='conversion_action_buttons'><button class='edit_conversion_btn'>Edit</button>{$btns_nongeneral}</div>";
                echo "<tr class='conversion_meta_wrap' {$meta_el_attrs}>
                        <td>{$conv->id}</td><td>{$esc_html($conv->name)}</td><td class='conv_urls'>{$conv_urls_string}</td><td>{$created_at}</td>
                        <td>{$earliest}</td><td>{$latest}</td><td>{$count}</td><td>{$buttons}</td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>
<?php } ?>
    <h2 class="analytics_report_title"><?php echo $report_type==='conversions' ? 'Conversion' : 'Trigger' ?> Report</h2>
<?php if(!$specific_trigger_mode){ ?>
    <p class="analytics_report_description">
        <input type="radio" onchange="location.href='<?php echo $switch_report_type_url;?>'" name="report_type_switch"<?php if($report_type==='conversions')echo 'checked';?>>
        <label><b>Conversion report</b> - Break down each conversion goal by trigger and version</label><br>
        <input type="radio" onchange="location.href='<?php echo $switch_report_type_url;?>'" name="report_type_switch"<?php if($report_type!=='conversions')echo 'checked';?>>
        <label><b>Trigger report</b> - See how each trigger performs across all conversion goals</label>
    </p>
<?php } ?>
    <form method="post" class="report-controls">
        <?php echo $need_license_message('Unlock advanced reports and custom conversions. Click here to start a free trial or upgrade.','reportControls'); ?>
        <div class="report-controls-mainrow">
            <span class="report-controls-date">
                <label for="start_date">Start Date</label>
                <input required type="date" value="<?php echo esc_attr($start_date); ?>" name="start_date">
                <img style="height:10px;" src="<?php echo IFSO_PLUGIN_DIR_URL . '/admin/images/right_arrow_long.svg';?>">
                <label for="end_date">End Date</label>
                <input required type="date" name="end_date" max="<?php echo esc_attr($today); ?>" value="<?php echo esc_attr($end_date); ?>" >
            </span>
            <div class="trigger-filter-wrap">
                <img src="<?php echo IFSO_PLUGIN_DIR_URL . '/admin/images/filter.svg';?>">
                <select onmousedown="event.preventDefault();event.target.blur();document.querySelector('.conversions_filter_wrap').classList.toggle('nodisplay')"><option>Conversion Type</option></select>
            </div>
            <div class="trigger-filter-wrap">
                <img src="<?php echo IFSO_PLUGIN_DIR_URL . '/admin/images/filter.svg';?>">
                <select name="trigger_status_filter">
                    <option value="">Trigger Status (All)</option>
                    <option <?php echo $trigger_status_filter==='publish' ? 'SELECTED' : ''; ?> value="publish">Published</option>
                    <option <?php echo $trigger_status_filter==='draft' ? 'SELECTED' : ''; ?> value="draft">Draft</option>
                </select>
            </div>
            <div class="recurrence-views-wrap">
                <i class="fa fa-user-o" style="top:-2px;"></i>
                <select name="recurrence_views">
                    <option <?php echo !$recurrence_views ? 'SELECTED' : ''; ?> value="exclude">Unique views</option>
                    <option <?php echo $recurrence_views ? 'SELECTED' : ''; ?> value="include">Total views</option>
                </select>
                <a href="#" title="Total Views: The total number of times the version has been rendered. &#10;&#13; Unique Views: The number of times the version has been rendered, excluding views that occurred due to the recurrence option." onclick="return false;" class="general-tool-tip ifso_tooltip">?</a>
            </div>
            <button class="button button-secondary" type="submit">Apply</button>
        </div>
        <p class="conversions_filter_wrap <?php echo $conversion_events_selected ? '' : 'nodisplay'; ?>">
            <span>Conversions: </span> &nbsp;&nbsp;<?php
            foreach($conversions as $conv){
                $checked = (!$conversion_events_filter || in_array($conv->id, $conversion_events_filter)) ? 'checked' : '';
                echo "<input value='{$conv->id}' {$checked} type='checkbox' name='ifso_conversion_filter[]'><label>{$esc_html($conv->name)}</label> ";
            }
            ?>
        </p>
    </form>
<?php if($report_type==='conversions'): ?>
    <div class="conversions_wrap">
        <?php
            foreach($conversions as $conv_key=>$conv){
                if($conversion_events_filter && !in_array($conv->id, $conversion_events_filter)) continue;
                $buttons_html = "<div class='conversion_action_buttons'><button class='edit_conversion_btn'>Conversion Settings</button></div>";
                $meta_el_attrs = get_conversion_meta_el_attributes($conv);
                $count = !empty($conv_event_counts[$conv_key]) ? $conv_event_counts[$conv_key]->count : 0;
                $expand_el = '<span class="conversion_report_extend_btn">❯</span>';
                echo "<div class='conversion_meta_wrap' {$meta_el_attrs}>{$expand_el}{$esc_html($conv->name)} | Total Conversions: {$count}{$buttons_html}</div>";
                $conversion_report = [];
                $conversion_events_counts = filter_events_with_nonexistant_triggers(
                    $analytics_db->get_conversion_events_counts_by_trigger($conv->id,null,$start_date,$end_date),$trigger_posts);
                $triggers = array_unique(array_column($conversion_events_counts,'trigger_id'));
                foreach($triggers as $tid){
                    $tdata = AnalyticsService::get_instance()->make_trigger_report_default_data($tid);
                    if(!$tdata) continue;
                    $conversion_report[$tid] = $tdata;
                }
                foreach($conversion_events_counts as $count)
                    $conversion_report[$count->trigger_id][$count->version_uid]['conversions'] = (int) $count->count;
                foreach($view_event_counts as $count){
                    if(!isset($conversion_report[$count->trigger_id][$count->version_uid])) continue;
                    if($count->is_recurrence)
                        $conversion_report[$count->trigger_id][$count->version_uid]['recurr_views'] = (int) $count->count;
                    else
                        $conversion_report[$count->trigger_id][$count->version_uid]['views'] = (int) $count->count;
                }
                $conversion_report = array_filter($conversion_report,function($tid)use($conv){
                    return conversion_trigger_filter_allows_trigger($conv,$tid);},ARRAY_FILTER_USE_KEY);
                echo '<div class="conversion_trigger_reports_wrap">';
                foreach($conversion_report as $tid=>$trigger_report){
                    $tdata = get_trigger_data($tid,$trigger_posts);
                    if($trigger_status_filter && $trigger_status_filter!==$tdata['status'])continue;
                    $hamburger_menu = $get_hamburger_menu($switch_report_type_url."&trigger_id={$tid}",$tdata['edit_url'],
                        $conv->id===0 ? "" :"<a class='exclude-trigger-btn' href='#'><i class='fa fa-chain-broken'></i> Disconnect from Conversion</a>");
        ?>
                <table class="trigger-conversion-report" <?php echo "trigger_id='{$tid}' conv_id='{$conv->id}'"; ?>>
                    <tbody>
                    <?php
                        echo "<tr><th colspan='5'><span class='trigger_name' style='margin-right:18px;'>{$tdata['name']} <span>(ID:{$tid})</span></span>{$hamburger_menu}</th></tr>";
                        echo "<tr><th>Version</th><th>Name</th><th>Views</th><th>Conversions</th><th>Conv. %</th></tr>";
                        foreach($trigger_report as $version){
                            $views = $recurrence_views ? (int)$version['views'] + (int)$version['recurr_views'] : (int)$version['views'];
                            $cRate = calculate_conversion_rate($version['conversions'],$views);
                            echo "<tr><td>{$version['symbol']}</td><td>{$esc_html($version['name'])}</td>
                                <td>{$views}</td><td>{$version['conversions']}</td><td>$cRate%</td></tr>";
                        }
                    ?>
                    </tbody>
                </table>
            <?php
                }
                if(empty($conversion_report)) echo $no_data_message;
                echo '</div>';
            }
            ?>
    </div>
<?php elseif ($report_type==='triggers') : ?>
    <div class="triggers_wrap">
        <?php
            $triggers = array_filter(array_unique(array_column($view_event_counts,'trigger_id')));
            if(empty($triggers)) echo $no_data_message;
            $all_converison_counts = $analytics_db->get_conversion_events_counts_by_trigger(null,null,$start_date,$end_date);
            $triggers_reports = [];
            $trigger_conversions = [];
            foreach($triggers as $tid)
                $triggers_reports[$tid] = AnalyticsService::get_instance()->make_trigger_report_default_data($tid);
            foreach($view_event_counts as $count){
                if(isset($triggers_reports[$count->trigger_id][$count->version_uid])){
                    if($count->is_recurrence) $triggers_reports[$count->trigger_id][$count->version_uid]['recurr_views'] = (int) $count->count;
                    else $triggers_reports[$count->trigger_id][$count->version_uid]['views'] = (int) $count->count;
                }
            }
            foreach($all_converison_counts as $count){
                if($conversion_events_filter && !in_array((int)$count->conversion_type, $conversion_events_filter)) continue;
                if(isset($triggers_reports[$count->trigger_id][$count->version_uid])){
                    if(empty($triggers_reports[$count->trigger_id][$count->version_uid]['conversions']))
                        $triggers_reports[$count->trigger_id][$count->version_uid]['conversions'] = [];
                    $triggers_reports[$count->trigger_id][$count->version_uid]['conversions'][$count->conversion_type] = $count->count;
                    $trigger_conversions[] =  $count->conversion_type;
                }
            }
            $trigger_conversions =  array_unique($trigger_conversions);
            sort($trigger_conversions,SORT_NUMERIC);
            foreach($triggers_reports as $tid=>$trigger_report){
                $displayed_conversions = array_filter($trigger_conversions,function($cid)use($conversions,$tid){
                    return (isset($conversions[$cid]) && conversion_trigger_filter_allows_trigger($conversions[$cid],$tid));
                });
                $tdata = get_trigger_data($tid,$trigger_posts);
                if($trigger_status_filter && $trigger_status_filter!==$tdata['status'])continue;
                $conv_name_headings = '';
                foreach($displayed_conversions as $cid)
                    $conv_name_headings .= "<th colspan='2'>{$esc_html($conversions[$cid]->name)}</th>";
                $tname_element = !empty($tdata['name']) ? "<b class='trigger_name'>{$tdata['name']}</b>" : '';
                $hamburger_menu = $get_hamburger_menu($tdata['view_url'],$tdata['edit_url']);
                echo "<div class='trigger_analytics_report_wrap'><div class='trigger_analytics_report_meta' trigger_id='{$tid}' id='trigger-{$tid}'>
                            {$tname_element}<span class='trigger_id'>Trigger ID: {$tid}</span>{$hamburger_menu}</div>";
                ?>
                <table class="trigger-analytics-report">
                    <tr class="conversion_name_row"><th colspan="3"></th><?php echo $conv_name_headings; ?></tr>
                    <tr class="column_head_row">
                        <th>Version</th><th>Name</th><th>Views</th>
                        <?php for($i=0;$i<count($displayed_conversions);$i++) echo '<th>Conversions</th><th>Conv. %</th>'; ?>
                    </tr>
                    <?php
                        foreach($trigger_report as $vdata){
                            if($vdata['conversions'] === 0 ) $vdata['conversions'] = [];
                            $views = $recurrence_views ? (int)$vdata['views'] + (int)$vdata['recurr_views'] : (int)$vdata['views'];
                            echo "<tr>";
                            echo "<td>{$vdata['symbol']}</td><td>{$esc_html($vdata['name'])}</td><td>{$views}</td>";
                            foreach($displayed_conversions as $cid){
                                $convNumber = !empty($vdata['conversions'][$cid]) ? $vdata['conversions'][$cid] : 0;
                                $cRate = calculate_conversion_rate($convNumber,$views);
                                echo "<td>{$convNumber}</td><td>{$cRate}%</td>";
                            }
                            echo "</tr>";
                        }

                    ?>
                </table></div>
                <?php
                if($specific_trigger_mode) echo "<a class='to-all-triggers-btn button button-secondary' href='{$switch_report_type_url}'>View All Triggers Report</a>";
            }
        ?>
    </div>
<?php endif; ?>

    <div class="conversion_setup_modal  <?php if(!$license_valid) echo 'need_license'; ?>">
        <h2 class="only_create">Create a new conversion</h2>
        <h2 class="only_edit">Edit conversion</h2>
        <?php echo $need_license_message("Creating custom conversions requires a license key. Don't have one? Click here to get your license.",'createConversion'); ?>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" >
            <input type="hidden" name="conv_id">
            <input type="hidden" name="conversion_url_arr">
            <input type="hidden" name="conversion_once_per">
            <input type="hidden" name="conversion_trigger_filter">
            <div class="form_option_wrap">
                <h4 class="option_subtitle">
                    Conversion name
                    <a href="#" title="A name to help you identify the conversion" onclick="return false;" class="general-tool-tip ifso_tooltip">?</a>
                </h4>
                <input type="text" name="conversion_name" required placeholder="Name">
            </div>
            <div class="form_option_wrap show_in_general">
                <h4 class="option_subtitle">
                    Target URLs
                    <a href="#" title="Enter the page URL you want to track as a conversion" onclick="return false;" class="general-tool-tip ifso_tooltip">?</a>
                </h4>
                <div class="conversion_urls_greybox conversions_greybox">
                    <p class="greybox_row template"><span class="url"></span><span class="withqs"></span><span class="delete-btn url-del-btn">X</span></p>
                    <div class="conversion_greybox_description">
                        <div class="conversion_greybox_description"><span style="display:inline-block;width:76%">Targeted URLs:</span><span>QS matching</span></div>
                    </div>
                    <div class="conversion_greybox_contents"></div>
                </div>
                <input type="text" class="conversion_url_input" placeholder="https://example.com/">
                <p style="margin:8px 0 2px 0;">
                    <input type="checkbox" class="conversion_url_withqs_input"> <label>Strict QS matching</label>
                    <a href="#" title="Check this to track conversions only if the visitor's URL matches your defined URL including the exact query strings. If unchecked, the system will count conversions regardless of any query strings." onclick="return false;" class="general-tool-tip ifso_tooltip">?</a><br>
                </p>
                <button type="button" class="add_url_btn button button-secondary">+ Add URL</button>
            </div>
            <div class="form_option_wrap show_once_per_option">
                <h4 class="option_subtitle">
                    Conversion count options
                    <a href="#" title="Decide how conversions are tracked for each visitor" onclick="return false;" class="general-tool-tip ifso_tooltip">?</a>
                </h4>
                <input value="none" name="conv_once_per_UI"   type="radio"><label><b>Always</b> - Count every conversion event.</label><br>
                <input value="session" name="conv_once_per_UI"  type="radio"><label><b>Once per session</b> - Count only one conversion per browser session.</label><br>
                <input value="time" name="conv_once_per_UI"  type="radio"><label><b>After a time limit </b> - Count only one conversion per specified timeframe.
                    <a href="#" onclick="return false;" title="After a visitor converts, they won't be counted again until this time window passes." class="general-tool-tip ifso_tooltip">?</a><br></label>
            </div>
            <div class="form_option_wrap once_per_option nodisplay">
                <h4 class="option_subtitle">Time between conversions (in seconds)</h4>
                <input min="0" type="number">
            </div>
            <div class="form_option_wrap show_triggers_options_option">
                <h4 class="option_subtitle">
                    Target triggers
                    <a href="#" title="By default, this conversion applies to all triggers. If you want to limit tracking to specific triggers, select them below to create a targeted list." onclick="return false;" class="general-tool-tip ifso_tooltip">?</a><br>
                </h4>
                <select>
                    <option value="all">All Triggers</option>
                    <option value="specific">Specific Triggers</option>
                </select>
            </div>
            <div class="form_option_wrap trigger_filter_trigger_option">
                <h4 class="option_subtitle allowed nodisplay">Select triggers to include</h4>
                <h4 class="option_subtitle disallowed">
                    Exclude triggers
                    <a href="#" title="This conversion will be tracked for all triggers except those you select." onclick="return false;" class="general-tool-tip ifso_tooltip">?</a>
                </h4>
                <div class="yellow-noticebox no_triggers_message form_option_wrap nodisplay">
                    No triggers selected. At least one trigger must be selected for this conversion to be tracked.
                </div>
                <div class="conversion_triggers_greybox conversions_greybox nodisplay">
                    <p class="greybox_row template"><span class="trigger"></span><span class="delete-btn trigger-del-btn">X</span></p>
                    <div class="conversion_greybox_description allowed">Included triggers:</div>
                    <div class="conversion_greybox_description disallowed">Excluded triggers:</div>
                    <div class="conversion_greybox_contents trigger_filter"></div>
                </div>
                <select><?php echo array_reduce($trigger_posts,function($total,$trigger_post){
                        $total .= "<option value='{$trigger_post->ID}'>" . esc_html($trigger_post->post_title) . " (ID : {$trigger_post->ID})</option>";
                        return $total;
                    },'<option value="">Select a trigger</option>'); ?></select>
            </div>
            <?php echo get_ifso_conversion_form_inputs('setup_conversion'); ?>
            <div class="form_option_wrap submit_btn_option show_in_general">
                <button class="only_create button button-primary" type="submit">Create Conversion</button>
                <button class="only_edit button button-primary" type="submit">Update</button>
            </div>
        </form>
    </div>
    <div class="conversion_delete_modal">
        <h3>Are you sure you want to delete this conversion?</h3>
        <p>All records associated with this conversion will be permanently deleted.</p>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" >
            <input type="hidden" name="conv_id">
            <?php echo get_ifso_conversion_form_inputs('delete_conversion'); ?>
            <button class="button button-primary" type="submit">Delete</button>
            <button class="button button-secondary" type="button" onclick="conversion_delete_modal.closeModal()">Cancel</button>
        </form>
    </div>
    <div class="conversion_reset_modal">
        <h3>Reset conversion data?</h3>
        <p>All recorded conversions for this conversion type will be permanently deleted.</p>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" >
            <input type="hidden" name="conv_id">
            <?php echo get_ifso_conversion_form_inputs('reset_conversion'); ?>
            <button class="button button-primary" type="submit">Reset Data</button>
            <button class="button button-secondary" type="button" onclick="conversion_reset_modal.closeModal()">Cancel</button>
        </form>
    </div>
    <div class="exclude_trigger_form_conversion_modal">
        <h3>Disconnect this trigger from the conversion?</h3>
        <p>Disconnecting this trigger will stop it from tracking new data <b>for this conversion only</b>. Tracking for all other conversions will continue unaffected.</p>
        <p>
            The trigger will no longer appear in this conversion's report, but all existing historical data will be preserved. You can reconnect the trigger at any time from <b>Conversion Settings.</b></p>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" >
            <input type="hidden" name="conv_id">
            <input type="hidden" name="trigger_id">
            <?php echo get_ifso_conversion_form_inputs('exclude_trigger_from_conversion'); ?>
            <button class="button button-primary" style="background:#b32d2e" type="submit">Disconnect</button>
            <button class="button button-secondary" type="button" onclick="conversion_delete_modal.closeModal()">Cancel</button>
        </form>
    </div>
    <div class="trigger_reset_modal">
        <h3>Are you sure you want to reset this trigger's data?</h3>
        <p>Resetting this trigger will permanently delete all recorded views and conversions.</p>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" >
            <input type="hidden" name="postid">
            <input type="hidden" name="rdrback" value="1">
            <input type="hidden" name="action" value='ifso_analytics_req'>
            <input type="hidden" name="an_action" value='resetFields'>
            <?php wp_nonce_field('ifso-admin-nonce','_ifsononce') ?>
            <button class="button button-primary" type="submit">Reset Data</button>
            <button class="button button-secondary" type="button" onclick="trigger_reset_modal.closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
    var conversion_setup;
    var conversion_delete_modal;
    var conversion_reset_modal;
    var exclude_trigger_modal;
    var trigger_reset_modal;
    var report_popup_dimensions = 'width='+Math.max(window.screen.width/2,800)+','+'height='+(window.screen.height-66);
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.conversion_report_extend_btn').forEach((el)=>{el.addEventListener('click', (e)=>{
            e.target.closest('.conversion_meta_wrap').classList.toggle('hidereports')
        })});
        document.querySelector('.report-controls').addEventListener('submit',(e)=>prevent_submit_if_start_date_after_end_date(e));
        conversion_delete_modal = new TinyModal('delete_conversion');
        conversion_delete_modal.createModal(document.querySelector('.conversion_delete_modal'));
        document.querySelectorAll('.delete_conversion_btn').forEach((el)=>{el.addEventListener('click',(e)=>open_simple_conversion_modal(e,conversion_delete_modal))});
        exclude_trigger_modal = new TinyModal('exclude_trigger_from_conversion');
        exclude_trigger_modal.createModal(document.querySelector('.exclude_trigger_form_conversion_modal'));
        document.querySelectorAll('.exclude-trigger-btn').forEach((el)=>{el.addEventListener('click',(e)=>open_simple_conversion_modal(e,exclude_trigger_modal))});
        conversion_reset_modal = new TinyModal('reset_conversion');
        conversion_reset_modal.createModal(document.querySelector('.conversion_reset_modal'));
        document.querySelectorAll('.reset_conversion_btn').forEach((el)=>{el.addEventListener('click',(e)=>open_simple_conversion_modal(e,conversion_reset_modal))});
        trigger_reset_modal = new TinyModal('reset_trigger');
        trigger_reset_modal.createModal(document.querySelector('.trigger_reset_modal'));
        document.querySelectorAll('.reset-trigger-btn').forEach((el)=>{el.addEventListener('click',(e)=>{
            e.preventDefault();
            trigger_reset_modal.element.querySelector('[name="postid"]').value = e.target.closest('[trigger_id]').getAttribute('trigger_id');
            trigger_reset_modal.openModal();
        })});
        document.querySelectorAll('.hamburger').forEach((el)=>{el.addEventListener('click',(e)=>on_toggle_hamburger_menu(e),false)});
        document.body.addEventListener('click',(e)=>on_try_hide_hamburger_menu(e));
        document.querySelectorAll('.copy_shortcode_btn').forEach((el)=>{el.addEventListener('click',(e)=>{on_copy_shortcode(e)})});
        var conversion_setup_modal = new TinyModal('setup_conversion');
        conversion_setup_modal.createModal(document.querySelector('.conversion_setup_modal'));
        conversion_setup = new ConversionSetup(conversion_setup_modal);
    });
    function open_simple_conversion_modal(e,modal){
        e.preventDefault();
        var meta_el = e.target.closest('[conv_id]');
        modal.element.querySelector('[name="conv_id"]').value = meta_el.getAttribute('conv_id');
        if(modal.element.querySelector('[name="trigger_id"]')!==null)
            modal.element.querySelector('[name="trigger_id"]').value = meta_el.getAttribute('trigger_id');
        modal.openModal();
    }
    function on_copy_shortcode(e){
        var cid = e.target.closest('.conversion_meta_wrap').getAttribute('conv_id');
        var conv_attr = parseInt(cid) !== 0 ? ' conversion="' + cid + '"' : '';
        navigator.clipboard.writeText('[ifso_conversion'+conv_attr+']') ;
    }
    function on_toggle_hamburger_menu(e){
        var hamburger_open_el = document.querySelector('.hamburger-open');
        var parent_el = e.target.closest('th, .trigger_analytics_report_meta');
        if(hamburger_open_el!==null)
            hamburger_open_el.classList.remove('hamburger-open');
        if(hamburger_open_el!==parent_el)
            parent_el.classList.add('hamburger-open');
    }
    function on_try_hide_hamburger_menu(e){
        if(document.querySelector('.hamburger-open')!==null && !e.target.classList.contains('hamburger') && e.target.closest('.trigger-report-actions')===null)
            document.querySelector('.hamburger-open').classList.remove('hamburger-open');
    }
    function prevent_submit_if_start_date_after_end_date(e){
        if(Date.parse(e.target.querySelector('[name="start_date"]').value) > Date.parse(e.target.querySelector('[name="end_date"]').value)){
            e.preventDefault();
            alert('Please select an end date that is on or after the start date');
        }
    }
    var ConversionSetup = function(modal){
        this.modal = modal;
        this.url_greybox_content_element = this.modal.element.querySelector('.conversion_greybox_contents');
        this.trigger_filter_greybox_content_element = this.modal.element.querySelector('.conversion_greybox_contents.trigger_filter');
        this.show_trigger_options_select = this.modal.element.querySelector('.show_triggers_options_option select');
        this.init();
        this.add_event_listeners();
    }
    ConversionSetup.prototype = {
        add_event_listeners : function(){
            this.modal.element.querySelector('.add_url_btn').addEventListener('click',(e)=>this.add_url_pressed(e));
            this.modal.element.querySelector('.conversion_url_input').addEventListener('keydown',(e)=> {this.add_url_enter_pressed(e)});
            this.modal.element.querySelector('.conversion_url_input').addEventListener('focusout',(e)=> {this.add_url_pressed(e)});
            this.modal.element.querySelector('.conversion_url_withqs_input').addEventListener('focusout',(e)=> {this.add_url_pressed(e)});
            this.modal.element.querySelectorAll('.show_once_per_option [type="radio"]').forEach((el)=>{el.addEventListener('change',(e)=>this.show_once_per_changed(e));});
            this.modal.element.querySelector('.once_per_option input').addEventListener('change',(e)=>this.set_once_per(e.target.value));
            this.show_trigger_options_select.addEventListener('change',(e)=>this.show_trigger_options_changed(e));
            this.modal.element.querySelector('.trigger_filter_trigger_option select').addEventListener('change',(e)=>this.trigger_filter_trigger_option_changed(e));
            document.querySelector('.create_conversion_btn').addEventListener('click',(e)=>this.open_create(e));
            document.querySelectorAll('.edit_conversion_btn').forEach((el)=>{el.addEventListener('click',(e)=>this.open_edit(e))});
            this.modal.element.querySelector('form').addEventListener('submit',(e)=>this.on_form_submit(e));
        },
        init : function(id=null,name=null,urls=[],once_per=null,trigger_filter=null){
            this.init_done = false;
            this.conversion_id = id;
            this.conversion_name = name;
            this.urls = urls;
            this.once_per = once_per;
            this.trigger_filter = trigger_filter;
            this.init_fields();
            this.init_done = true;
        },
        init_fields : function(){
            this.modal.element.classList.toggle('general_conversion',(parseInt(this.conversion_id)===0));
            this.modal.element.querySelector('[name="conversion_name"]').value = this.conversion_name;
            this.modal.element.querySelector('[name="conv_id"]').value = this.conversion_id;
            this.url_greybox_content_element.innerHTML = '';
            this.trigger_filter_greybox_content_element.innerHTML = '';
            this.set_url_greybox_visibility();
            this.urls.forEach((url)=>this.add_url(url.url,!!parseInt(url.with_query_string)));
            var once_per_radio_value = this.once_per===null ? 'none' : parseInt(this.once_per)===0 ? 'session' : 'time';
            var once_per_selected = this.modal.element.querySelector(
                '.show_once_per_option [type="radio"][value="' + once_per_radio_value + '"]');
            once_per_selected.checked = true;
            if(this.once_per!==null) this.modal.element.querySelector('.once_per_option input').value = this.once_per;
            once_per_selected.dispatchEvent(new Event('change'));
            this.show_trigger_options_select.value = (this.trigger_filter!==null && this.trigger_filter.type==='include') ? 'specific' : 'all';
            this.show_trigger_options_select.dispatchEvent(new Event('change'));
            if(this.trigger_filter!==null)
                this.trigger_filter.triggers.forEach((tid)=>this.add_trigger_filter_trigger(tid,null));
        },
        open_create : function(e){
            this.init();
            this.modal.openModal();
            this.modal.element.classList.add('only_create');
            this.modal.element.classList.remove('only_edit');
        },
        open_edit : function(e){
            var meta_wrap_el = e.target.closest('.conversion_meta_wrap');
            var conv_urls = JSON.parse(meta_wrap_el.dataset.urls);
            var trigger_filter = meta_wrap_el.dataset.trigger_filter!==undefined ? JSON.parse(meta_wrap_el.dataset.trigger_filter) : null;
            this.init(meta_wrap_el.getAttribute('conv_id'),meta_wrap_el.getAttribute('conv_name'),conv_urls,meta_wrap_el.getAttribute('once_per'),trigger_filter);
            this.modal.openModal();
            this.modal.element.classList.add('only_edit');
            this.modal.element.classList.remove('only_create');
        },
        add_url_enter_pressed : function(e){
            if (e.keyCode === 13) {
                e.preventDefault();
                this.add_url_pressed(e);
            }
        },
        add_url_pressed : function(e){
            var parent =  e.target.closest('.form_option_wrap');
            var url_input = parent.querySelector('.conversion_url_input');
            var qs_input = parent.querySelector('.conversion_url_withqs_input');
            if(url_input.value==='' || (e.type==='focusout' && e.relatedTarget===url_input || e.relatedTarget===qs_input)) return;
            try{ new URL(url_input.value); } //URL validation - invalid URL will throw an error
            catch(err){
                url_input.setCustomValidity("Enter a valid URL");
                url_input.reportValidity();
                url_input.setCustomValidity("");
                return;
            }
            this.add_url(url_input.value,qs_input.checked);
            url_input.value = '';
        },
        add_url : function(url,with_qs){
            if(this.init_done) this.urls.push({'url':url,'with_query_string':with_qs});
            var newrow = this.modal.element.querySelector('.conversion_urls_greybox .greybox_row.template').cloneNode(true);
            newrow.querySelector('.url').innerHTML = url;
            newrow.querySelector('.withqs').innerHTML = with_qs ? 'Yes' : 'No';
            newrow.querySelector('.url-del-btn').addEventListener('click',(e)=>{
                this.delete_url_by_index(this.get_greybox_row_index(this.url_greybox_content_element,e.target));})
            newrow.classList.remove('template');
            this.url_greybox_content_element.appendChild(newrow);
            this.set_url_greybox_visibility()
        },
        delete_url_by_index : function(index){
            this.urls.splice(index,1);
            this.url_greybox_content_element.children[index].remove();
            this.set_url_greybox_visibility()
        },
        set_url_greybox_visibility : function(){
            this.show_or_hide_element(this.modal.element.querySelector('.conversion_urls_greybox'),this.urls.length!==0)
        },
        show_once_per_changed : function(e){
            var once_per_values = {'none':null,'session':0,'time':null};
            this.set_once_per(once_per_values[e.target.value]);
            this.show_or_hide_element(this.modal.element.querySelector('.once_per_option'),e.target.value==='time')
        },
        set_once_per : function(val){
            this.once_per = val;
            this.modal.element.querySelector('[name="conversion_once_per"]').value = val;
        },
        show_trigger_options_changed : function(e){
            var all = (e.target.value==='all');
            this.show_or_hide_element(this.modal.element.querySelector('.option_subtitle.allowed'),!all);
            this.show_or_hide_element(this.modal.element.querySelector('.option_subtitle.disallowed'),all);
            this.show_or_hide_element(this.modal.element.querySelector('.conversion_greybox_description.allowed'),!all);
            this.show_or_hide_element(this.modal.element.querySelector('.conversion_greybox_description.disallowed'),all);
            if(this.init_done) this.trigger_filter = {'type':all ? 'exclude' : 'include','triggers':[]};
            this.set_trigger_filter_greybox_visibility();
        },
        trigger_filter_trigger_option_changed : function(e){
            if(e.target.value==='') return;
            var allow_or_disallow = this.show_trigger_options_select.value!=='all';
            this.add_trigger_filter_trigger(e.target.value,allow_or_disallow);
            e.target.value = '';
        },
        add_trigger_filter_trigger : function(tid,allowed=true){
            if(this.init_done){
                if(this.trigger_filter===null) this.trigger_filter = {'type':null,'triggers':[]};
                if(allowed!==null) this.trigger_filter.type = allowed ? 'include' : 'exclude';
                if(!this.trigger_filter.triggers.includes(tid))
                    this.trigger_filter.triggers.push(tid);
                else return;
            }
            this.set_trigger_filter_greybox_visibility();
            var trigger_edit_url = ifso_base_url + '/wp-admin/post.php?action=edit&post=' + tid;
            var trigger_text_el = this.modal.element.querySelector('.trigger_filter_trigger_option select option[value="' + tid + '"]');
            var trigger_text = trigger_text_el!==null ? trigger_text_el.innerHTML : '(ID : ' + tid + ') - DELETED';
            var newrow = this.modal.element.querySelector('.conversion_triggers_greybox .greybox_row.template').cloneNode(true);
            newrow.querySelector('.trigger').innerHTML = '<a ' + (trigger_text_el!==null ? '' : 'class="delete-btn"') +
                ' href="' + trigger_edit_url + '" target="_blank">'+ trigger_text+ '</a>'
            newrow.querySelector('.trigger-del-btn').addEventListener('click',(e)=>{
                this.delete_trigger_filter_trigger_by_index(this.get_greybox_row_index(this.trigger_filter_greybox_content_element,e.target));})
            newrow.classList.remove('template');
            this.trigger_filter_greybox_content_element.appendChild(newrow);
        },
        delete_trigger_filter_trigger_by_index : function(index){
            this.trigger_filter.triggers.splice(index,1);
            this.trigger_filter_greybox_content_element.children[index].remove();
            this.set_trigger_filter_greybox_visibility();
        },
        set_trigger_filter_greybox_visibility : function(){
            if(this.trigger_filter===null)this.trigger_filter_greybox_content_element.innerHTML = '';
            this.show_or_hide_element(this.modal.element.querySelector('.conversion_triggers_greybox'),this.trigger_filter!==null && this.trigger_filter.triggers.length>0);
            this.show_or_hide_element(this.modal.element.querySelector('.no_triggers_message'),this.trigger_filter!==null && this.trigger_filter.type==='include' && this.trigger_filter.triggers.length===0);
        },
        on_form_submit : function(e){
            this.modal.element.querySelector('[name="conversion_url_arr"]').value = JSON.stringify(this.urls);
            this.modal.element.querySelector('[name="conversion_once_per"]').value = this.once_per;
            this.modal.element.querySelector('[name="conversion_trigger_filter"]').value = this.trigger_filter!==null ? JSON.stringify(this.trigger_filter) : null;
        },
        get_greybox_row_index(greybox_el,el){
            return Array.prototype.slice.call(greybox_el.children).indexOf(el.closest('.greybox_row'));
        },
        show_or_hide_element(element,show=true){
            element.classList.toggle('nodisplay',!show);
        }
    }
</script>