<?php
if ( ! defined( 'ABSPATH' ) ) exit; 


$current_post_id = get_the_ID();
$published = (get_post_status( $current_post_id ) == 'publish' );

if($published && !isset($_COOKIE['ifso_hide_pagebuilder_notice'])): ?>
	<?php do_action('show_pagebuilders_noticebox'); ?>
<?php endif; ?>
<div class="ifso-admin-tabs-header" data-tabset="shortcode-tabs">
    <li class="ifso-tab default-tab" data-tab="shortcode-tab">Trigger Shortcode</li>
    <?php if ($published): ?> <li class="ifso-tab" data-tab="popup-tab">Pop-up</li> <?php endif; ?>
</div>
<div class="ifso-admin-tabs" data-tabset="shortcode-tabs">
    <div class="ifso-admin-page-tab-content shortcode-tab">
        <h4 style="margin:8px 0;font-weight:normal;"><?php _e('Paste this shortcode to display the trigger', 'if-so'); ?></h4>
        <?php
        if ($published):
            $shortcode = sprintf( '[ifso id="%1$d"]', $current_post_id);
            $trigger_name = get_the_title($current_post_id);?>
            <span class="shortcode shortcode-with-cpybtn">
                <input type="text" onfocus="this.select();" readonly="readonly" value='<?php echo $shortcode; ?>' style="font-size:16px;" class="large-text code">
                <button class="cpy-shortcode-btn" onclick="event.preventDefault();var input= this.parentElement.querySelector('input');navigator.clipboard.writeText(input.value)"><img src="<?php echo IFSO_PLUGIN_DIR_URL . '/admin/images/dki-modal/copy-icon.svg'; ?>"></button>
            </span>
            <div class="ifso-toggle-sections-wrap">
                <?php echo IfSo\Admin\Services\InterfaceModService\InterfaceModService::get_instance()->create_scan_button($current_post_id); ?>
                <p class="ifso-sc-metabox-toggle-link php-shortcode-toggle-link"><?php _e('PHP code (for developers)', 'if-so'); ?></p>
                <div class="php-shortcode-toggle-wrap ifso-sc-metabox-toggle-wrap">
                    <?php $php_code = sprintf( '<?php ifso(%1$d); ?>', $current_post_id);?>
                    <div class="metabox-item">
                        <p>Paste this function call into your website's code</p>
                        <span class="shortcode"><input type="text" onfocus="this.select();" readonly="readonly" value='<?php echo $php_code; ?>' class="large-text code"></span>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <span class="shortcode"><input type="text" readonly="readonly" value='<?php _e('Shortcode will be available after publish', 'if-so');?>' class="large-text code" style="color: #9c9c9c;padding-left: 20px;"></span>
        <?php endif; ?>
    </div>
    <div class="ifso-admin-page-tab-content popup-tab">
        <div class="ifso-popup-notice-wrap">
            <?php
            if(defined('IFSO_TRIGGER_EVENTS_ON') && IFSO_TRIGGER_EVENTS_ON)
                do_action('ifso_shortcode_metabox_option_popup');
            else{
                ?>
                <p class="purple-noticebox"><span>Turn any trigger into a conditional pop-up using our free 'Trigger Events' add-on. <a href="https://www.if-so.com/trigger-events-extension?utm_source=Plugin&utm_medium=helpBox&utm_campaign=TriggerEvents" target="_blank">Free Download</a></span></p>
            <?php } ?>
        </div>
    </div>
</div>
