<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$published = (get_post_status( get_the_ID() ) === 'publish');
$current_post_id =  $published ? get_the_ID() : 0;
?>
    <style>
        .analytics-container-wrap{
            border: 1px solid #C9C9C9;
            border-radius: 4px;
            height: 181px;
            overflow-y: auto;
            scrollbar-width: thin;
            position: relative;
            width: 100%;
            background: #fff;
        }
        .ifso-analytics-container .row{
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin: 0 auto;
            position:relative;
            line-height:2;
        }
        .ifso-analytics-container .row.odd{
            background-color:#f9f9f9;
        }
        .ifso-analytics-container .row .reset-notice{
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            text-align: center;
            background-color:#fff !important;
            color:#a00;
            display:none;
        }
        .ifso-analytics-container .row .reset-row-analytics{
            cursor:pointer;
            z-index:999;
        }
        .ifso-analytics-container .row .reset-row-analytics:hover + .reset-notice{
            display:block;
        }
        .ifso-analytics-container .row:first-of-type{
            font-weight: bold;
            background-color:transparent;
            cursor:auto;
            letter-spacing: -0.5px;
        }
        .ifso-analytics-container .row:first-of-type:hover .reset-notice{
            display:none;
        }
        .ifso-analytics-container .row>*{
            display: inline-block;
            min-width: 76px;
            text-align: center;
            font-size:13px;
            margin: 6px 0;
            width:auto;
            padding:0;
        }
        .ifso-analytics-container .row>*:nth-of-type(4){
            min-width: 57px;
        }
        .ifso-analytics-container .row>*:nth-of-type(5){
            width:11px;
            min-width:11px;
        }
        .ifso-analytics-container .row>*:first-child{
            min-width: 43px;
        }
        #refreshTriggerAnalytics{
            position: relative;
            left: -2px;
            font-size: 12px;
        }
        #refreshTriggerAnalytics .fa-refresh{
            color: #696DDB;
        }
        .tab-icon.analytics-tab-icon{
            height: 16px;
            vertical-align: text-top;
        }
        .ifso-toggle-conversion-info-modal-link{
            color: #696DDB;
            border: 1px solid #696DDB;
            cursor: pointer;
            border-radius: 2px;
            min-width:unset;
            font-size:6px;
            padding: 1px 3px 1px 2px;
            position: relative;
            top: -3px;
        }
        .ifso-conversion-info-modal{
            max-width: 600px!important;
        }
        .ifso-conversion-info-modal .conversion-settings{
            color: #585960;
            margin: 15px 0;
        }
        .ifso-conversion-info-modal .conversion-settings h4{
            margin:0;
        }
        .ifso-conversion-info-modal .conversion-settings p{
            margin:10px 0;
        }
        .ifso-conversion-info-modal-contents{
            padding:40px;
        }
        .ifso-conversion-info-modal-contents:not(.insideModal){
            display:none;
        }
        .analytics-container-wrap .analytics-expand-button{
            position: sticky;
            text-align: center;
            bottom: 0;
            z-index: 999;
            border:none;
            border-top: 1px solid #C9C9C9;
            background: #F9F9F9;
            width: 100%;
        }
        .analytics-container-wrap .analytics-expand-button span{
            vertical-align: middle;
        }
        .analytics-container-wrap .analytics-expand-button i{
            margin-right:6px;
            font-size: 11px;
            vertical-align: middle;
        }
        .ifso-analytics-ui-modal .analytics-container-for-modal{
            width: 570px;
            padding: 40px;
        }
        .not-published #analytics-loading-notice,.analytics-container-wrap:not(.not-published) .analytics-not-published-overlay{
            display: none;
        }
        .ifso-analytics-ui-modal .ifso-analytics-container{
            border: 1px solid #C9C9C9;
        }
        .analytics-container-wrap .analytics-not-published-overlay{
            background: rgb(240, 240, 241);
            height: 79%;
            position:relative;
        }
        .analytics-container-wrap .analytics-not-published-overlay .analytics-not-published-notice{
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%,-50%);
            width: 100%;
            text-align: center;
            color: #9c9c9c;
        }
    </style>

<div class="ifso-admin-tabs-header" data-tabset="analytics-tabs">
    <li class="ifso-tab default-tab" data-tab="ifso-analytics-tab"><img class="tab-icon analytics-tab-icon" src="<?php echo IFSO_PLUGIN_DIR_URL . 'admin/images/ifso_analytics_metabox_icon.svg'; ?>"> If-So Analytics</li>
    <?php do_action('ifso_triggerpage_analytics_metabox_tabs_header'); ?>
</div>

<div class="ifso-admin-tabs"  data-tabset="analytics-tabs">
    <div class="ifso-admin-page-tab-content ifso-analytics-tab">
        <div class="analytics-container-wrap <?php if(!$published) echo 'not-published'; ?>">
            <h4 id="analytics-loading-notice" style="margin:20px 0 28px 0;font-weight:normal;text-align:center;"><?php _e('Loading stats...', 'if-so'); ?> </h4>
            <div class="ifso-analytics-container" pid="<?php echo $current_post_id ?>"></div>
            <div class="analytics-container-for-modal"></div>
            <button onclick="event.preventDefault();analyticsUIModal.openModal();" class="analytics-expand-button nodisplay">
                <i class="fa fa-search-plus" aria-hidden="true"></i><span>Expand</span>
            </button>
            <div class="analytics-not-published-overlay"><span class="analytics-not-published-notice"><?php _e('Statistics will be available after publish', 'if-so'); ?></span></div>
        </div>
    </div>
    <?php do_action('ifso_triggerpage_analytics_metabox_tabs_content'); ?>
    <p style="margin: 9px 0;color: #777;font-size:0.9em;"> * If-So’s analytics ignores logged-in admin users. Use incognito mode to validate behavior</p>
</div>

<div class="ifso-conversion-info-modal-contents">
    <h2><?php _e('See how your content performs');?></h2>
    <p>
        <?php _e('Paste the shortcode into your conversion page (thank-you page, signup or purchase confirmation, etc.).', 'if-so');?>
        <br><br>
        <?php _e('A conversion will be attributed to the last version a visitor saw.'); ?>
    </p>
    <span class="shortcode"><input type="text" onfocus="this.select();" readonly="readonly" class="large-text code"></span>
    <div class="conversion-settings">
        <h4>Conversion Count Options</h4>
        <p>Decide how conversions are tracked for each visitor</p>
        <div class="settings-inputs">
            <input type="radio" value="session" name="once_per_option" checked> <span><b>Once per session</b> - Count only one conversion per browser session.</span><br>
            <input type="radio" value="delay" name="once_per_option"> <span><b>After a time limit</b> - Count another conversion after a chosen delay (in seconds).</span><br>
            <input type="radio" value="none" name="once_per_option"> <span><b>No limit</b> - Count a conversion each time the trigger is rendered.</span><br>
        </div>
    </div>
    <a target="_blank" href="https://www.if-so.com/help/documentation/analytics/?utm_source=Plugin&utm_medium=settings&utm_campaign=analyticsConversionOptionsLarnMore#anc_conversion-options"><?php _e('Learn more about conversions', 'if-so'); ?></a>
</div>

<script>
    var pid;
    var conversionInfoModal;
    var analyticsUIModal;

    jQuery(document).ready(function () {
        pid = parseInt(jQuery('.ifso-analytics-container').attr('pid'));
        analyticsUIModal = new TinyModal('ifso-analytics-ui-modal');
        analyticsUIModal.createModal(jQuery('.analytics-container-for-modal')[0]);
        conversionInfoModal = new TinyModal('ifso-conversion-info-modal');
        conversionInfoModal.createModal(jQuery('.ifso-conversion-info-modal-contents')[0]);
        generateConversionShortcode(pid,document.querySelector('.conversion-settings input[name="once_per_option"][checked]').value);
        jQuery(conversionInfoModal.element).find('.conversion-settings input[name="once_per_option"]').on('change',function(e){
            generateConversionShortcode(pid,e.target.value);
        });
        refreshAnalyticsDisplay(pid);
    });

    function generateConversionShortcode(pid,once_per_value){
        var sc_input = conversionInfoModal.element.querySelector('.shortcode input');
        if(pid!==0){
            var shortcode_base = '[ifso_conversion triggers="' + pid + '"';
            var once_per_attrs = '';
            if(once_per_value==='session' || once_per_value==='delay'){
                if(once_per_value==='session') once_per_attrs='do_once_per="0"';
                if(once_per_value==='delay') once_per_attrs='do_once_per="120"';
                once_per_attrs += ' name="' + pid + '"';
                sc_input.value = shortcode_base + ' ' + once_per_attrs + ']';
            }
            else
                sc_input.value = shortcode_base + ']';
        }
        else{
            sc_input.value = 'The shortcode will be available after publish';
        }
    }

    function refreshAnalyticsDisplay(postid){
        document.querySelectorAll('#refreshTriggerAnalytics>i').forEach(function(e){e.classList.add('spin');});
        getAnalyticsData(postid);
    }

    function getAnalyticsData(postid){
        if(pid==0)
            buildAnalyticsDisplay(null);
        else
            ajaxPost({action:'ifso_analytics_req',an_action:'getFields',postid:postid}, buildAnalyticsDisplay)
    }

    function buildAnalyticsDisplay(res){
        var container = document.querySelector('.ifso-analytics-container');
        if(container===null || typeof(container)==='undefined') return
        container.innerHTML = '';
        document.querySelector('#analytics-loading-notice').className = '';
        var data = res===null ? [] : JSON.parse(res);
        var conversionHeaderElement = document.createElement('span');
        conversionHeaderElement.innerHTML = 'Conversions <span class="ifso-toggle-conversion-info-modal-link" onclick="conversionInfoModal.openModal();"><i class="fa fa-plus" aria-hidden="true"></i></span>';
        var refreshTableElement = document.createElement('span');
        refreshTableElement.innerHTML = '<a id="refreshTriggerAnalytics" href="javascript:refreshAnalyticsDisplay(pid);""><i class="fa fa-refresh" aria-hidden="true"></i></a>';
        container.appendChild(createRow(['<?php _e('Version', 'if-so'); ?>','<?php _e('Views', 'if-so'); ?>',conversionHeaderElement,'<?php _e('Conv. rate', 'if-so'); ?>',refreshTableElement]));
        for(var x = 0;x<=data.length-1;x++){
            var views = Number(data[x]['views']) + Number(data[x]['recurrence_views']);
            var convRate = ( views !=0) ? (Number(data[x]['conversion'])*100/views ).toFixed(2) + '%' : '0.00%';
            var resetX = document.createElement('span');
            resetX.className = 'reset-row-analytics';
            resetX.innerHTML = '⋮';
            resetX.setAttribute('onclick','resetVersionFields(this.parentElement.getAttribute("myversion"))');      //Inline to preserve the event when copying the node into a modal
            var newrow = createRow([data[x]['version_name'],views,data[x]['conversion'],convRate,resetX]);
            newrow.setAttribute('myversion',x);
            if(data[x]['version_name']=='Default') newrow.setAttribute('myversion','default');
            if(x%2==0) newrow.className += ' odd';
            container.appendChild(newrow);
        }
        document.querySelector('#analytics-loading-notice').className = 'nodisplay';
        if (document.querySelector('.analytics-noticebox')!= null && typeof(document.querySelector('.analytics-noticebox'))!== 'undefined') document.querySelector('.analytics-noticebox').classList.remove('whileLoading');
        document.querySelectorAll('#refreshTriggerAnalytics>i').forEach(function(e){e.classList.remove('spin');});

        var modal_content = document.querySelector('.analytics-container-for-modal');
        var expand_button = document.querySelector('.analytics-container-wrap .analytics-expand-button');
        modal_content.innerHTML = '';
        modal_content.appendChild(container.cloneNode(true));

        if(data.length>3)       //minimum number of versions to display the "expand" button
            expand_button.classList.remove('nodisplay');
        else
            expand_button.classList.add('nodisplay');
    }

    function resetAllFields(){
        if(confirm('<?php _e("Are you sure you want to reset this trigger stats?", 'if-so'); ?>')){
            ajaxPost({action:'ifso_analytics_req',an_action:'resetFields',postid:pid}, function(){
                refreshAnalyticsDisplay(pid);
            })
        }
    }

    function resetVersionFields(version){
        if(confirm('<?php _e("Are you sure you want to reset this version stats?", 'if-so'); ?>')){
            ajaxPost({action:'ifso_analytics_req',an_action:'resetFields',postid:pid,versionid:version}, function(){
                refreshAnalyticsDisplay(pid);
            })
        }
    }

    function createRow(children){
        var row = document.createElement('div');
        row.className = 'row';
        for(var i=0;i<=children.length-1;i++){
            if(typeof(children[i])!=='object'){
                var el = document.createElement('span');
                el.innerHTML = (children[i] && children[i]!='false') ?  children[i] : 0;
                row.appendChild(el);
            }
            else {
                row.appendChild(children[i]);
            }
        }
        var reset_notice = document.createElement('div');
        reset_notice.className = 'reset-notice';
        reset_notice.innerHTML = 'Reset version stats';
        row.appendChild(reset_notice);
        return row;
    }
</script>