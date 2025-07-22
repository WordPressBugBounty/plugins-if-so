<?php
if(!current_user_can('administrator') || !check_admin_referer('trigger-scan','_ifsononce'))
    wp_die();
$postid = (!empty($_REQUEST['postid'])) ? $_REQUEST['postid'] : null;
$posts = $this->scan_posts_for_ifso_triggers($postid);
$scan_all_triggers = admin_url('admin-ajax.php?action=trigger_scan_req', basename(__FILE__) ) . '&_ifsononce=' . wp_create_nonce('trigger-scan');
$render_table_contents = function($posts){
    $site_url = get_site_url();
    $edit_post_icon_url = IFSO_PLUGIN_DIR_URL . '/admin/images/edit.svg';
    ?>
    <tr>
        <th style="width:40%;">Post Title</th>
        <th style="width:40%;">Post URL</th>
        <th style="width:20%;">Edit Post</th>
    </tr>
    <?php
    foreach ($posts as $post){
        $chopped_link = str_replace($site_url,'',$post['link']);
        echo "<tr>";
        echo "<td>{$post['title']}</td>";
        echo "<td><a target='_blank' href='{$post['link']}'>{$chopped_link}</a></td>";
        echo "<td><a target='_blank' href='{$post['edit']}'><img src='{$edit_post_icon_url}'></a></td>";
        echo "</tr>";
    }
};
?>
    <style>
        body{
            padding:50px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .main-title{
            color:#1E0546;
            margin-bottom:18px;
        }
        .ifso-scan-table-heading{
            font-size:20px;
        }
        .ifso-scan-table{
            width:100%;
            /*border-collapse: collapse;*/
            border-spacing:0;
            border-radius:4px;
            border:1px solid #B7B7B7;
            font-size:14px;
        }
        .ifso-scan-table td{
            padding: 10px;
        }
        .ifso-scan-table tr:nth-of-type(odd){
            background:#F5F5F5;
        }
        .ifso-scan-table td:last-of-type{
            text-align: center;
        }
        #ifso-trigger-scan-table tr:first-of-type{
            background-color:#DADBFE;
        }
        #ifso-conversion-scan-table tr:first-of-type{
            background-color:#C3DEFE;
        }
        .ifso-scan-table tr:first-of-type th{
            padding:8px 16px;
            text-align:left;
        }
        .ifso-scan-table tr:first-of-type th:last-of-type{
            text-align: center;
        }
        .ifso-scan-table-subheading{
            margin: 4px 0 16px 0;
            color:#515962;
        }
        .yellow-noticebox{
            display:flex;
            position:relative;
            color:#e7bc27;
            border:1px solid #e7bc27;
            background:#FFFCF4;
            padding:16px 20px;
            line-height:1.4;
            margin: 32px 0;
            align-items:center;
            font-size:14px;
        }
        .yellow-noticebox:before{  /*NOTICEBOX ICON*/
            width: 26px;
            height: 26px;
            min-width: 26px;
            min-height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-right: 12px;
            content:"!";
            background: #F5CF48
        }
    </style>

    <h1 class="main-title">Trigger & Conversion Post Locator</h1>
    <span style="padding:8px 12px;border:1px solid #1E0546;color: #1E0546;border-radius:4px;display: inline-block;">
        Trigger ID : <?php echo is_numeric($postid) ?  $postid : 'ALL'; ?>
    </span>
    <p class="yellow-noticebox">Note! The results include only If-So shortcodes contained within the post content and the If-So "Show on all pages" field. (Shortcodes entered using PHP in the website's template files and shortcodes entered into meta fields are not listed.)</p>
    <h2 class="ifso-scan-table-heading"style="margin:0;">If-So Trigger occurrences</h2>
    <p class="ifso-scan-table-subheading">Trigger occurrences were found in <?php echo count($posts['triggers']); ?> post<?php echo (count($posts['triggers'])===1) ? '' : 's';?></p>
<?php if(!empty($posts['triggers'])){ ?>
    <table id="ifso-trigger-scan-table" class="ifso-scan-table">
        <?php echo $render_table_contents($posts['triggers']) ?>
    </table>
<?php } ?>
    <h2 class="ifso-scan-table-heading" style="margin:40px 0 0 0;">Conversions Occurrences</h2>
    <p class="ifso-scan-table-subheading">Conversion shortcode associated with this trigger were found in <?php echo count($posts['conversions']); ?> post<?php echo (count($posts['conversions'])===1) ? '' : 's';?></p>
<?php if(!empty($posts['conversions'])){ ?>
    <table id="ifso-conversion-scan-table" class="ifso-scan-table">
        <?php echo $render_table_contents($posts['conversions']); ?>
    </table>
<?php } if($postid!==null){?><p><a href="<?php echo $scan_all_triggers; ?>">Look for pages containing any if-so trigger shortcode</a></p><?php } ?>
<?php
exit();