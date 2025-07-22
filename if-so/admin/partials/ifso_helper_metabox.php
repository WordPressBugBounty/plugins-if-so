<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$link_icon = '<i class="fa fa-external-link link-icon "></i>';
?>

<button class="helper-metabox-toggle" onclick="(function(){document.querySelector('.helper-metabox-container').classList.toggle('nodisplay');})();"
        type="button" style="position:fixed;bottom:3vh;right:1vw;border-radius:50%;width:60px;height:60px;z-index:999;background:#fff;border:none;box-shadow:0 0 8.23px 5.48px #0000000D;">
        <span style="border-radius:50%;border:4px solid #6A6DD4;color:#6A6DD4;width:37px;height:37px;font-size:20px;padding:5px;display: inline-block;line-height:20px;font-weight:800;">?</span>
</button>

<?php if(!empty($_REQUEST['post']) && !empty($_REQUEST['action']) && $_REQUEST['action']==='edit'  && !isset($_COOKIE['ifso_hide_need_help'])){ ?>
<div class="ifso-modal-need-help">
    <span class="closeX">X</span>
    <span class="content"><p>Is everything working as expected?</p> We are here to help.</span>
</div>
<?php } ?>

<div class="helper-metabox-container nodisplay">
	<ul class="ifso-helper-metabox-doc">
        <li><a target="_blank" href="https://www.if-so.com/help/troubleshooting/?utm_source=Plugin&utm_medium=helpBox&utm_campaign=troubleshooting"><?php _e('Quick Troubleshooting','if-so'); echo $link_icon; ?></a></li>
        <li><a target="_blank" href="https://www.if-so.com/help/documentation/?utm_source=Plugin&utm_medium=helpBox&utm_campaign=gettingStarted"><?php _e('Getting Started','if-so'); echo $link_icon; ?></a></li>
        <li><a target="_blank" href="https://www.if-so.com/dynamic-content/examples/?utm_source=Plugin&utm_medium=helpBox&utm_campaign=examples"><?php _e('Inspiration','if-so'); echo $link_icon; ?></a></li>
	</ul>
    <div class="extra-links">
        <a class="contact-support-link" href="https://www.if-so.com/help/support/?utm_source=Plugin&amp;utm_medium=helpBox&amp;utm_campaign=contactSupport" target="_blank">Contact support</a>
        <a class="feedback-link" href="https://www.if-so.com/feedback/?utm_source=Plugin&amp;utm_medium=helpBox&amp;utm_campaign=contactSupport" target="_blank">Feedback</a>
    </div>
</div>
