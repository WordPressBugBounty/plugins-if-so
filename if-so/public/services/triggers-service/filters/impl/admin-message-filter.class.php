<?php

namespace IfSo\PublicFace\Services\TriggersService\Filters;

require_once( plugin_dir_path ( __DIR__ ) . 'filter-base.class.php');

class AdminMessageFilter extends FilterBase {
    private static array $viewed_notices = [];
    public function change_text($text,$trigger_data=null) {
        if (current_user_can('administrator') && !empty($trigger_data) && $trigger_data->get_version_index()!=='DEFAULT'){
            $trigger_type = $trigger_data->get_rule()['trigger_type'];
            if($trigger_type==='AB-Testing'){
                if(!in_array($trigger_type,self::$viewed_notices)){
                    self::$viewed_notices[] = $trigger_type;
                    $text .= $this->ab_testing_noticebox();
                }
            }
        }
        return $text;
    }

    public function before_apply(){}
    public function after_apply(){}


    private function ab_testing_noticebox() {
        $notice = 'AB-Testing';
        $hide_notice_cookie_name = 'ifso_admin_fe_notice_hide_' .  $notice;
        $icon_url = IFSO_PLUGIN_DIR_URL . 'admin/images/abt_notice_icon.png';
        $minified_class = isset($_COOKIE[$hide_notice_cookie_name]) ? 'minified' : '';
        return <<<BOX
            <style>
                .ifso-fe-admin-notice-wrap{
                    bottom: 25px;
                    left: 25px;
                    position: fixed;
                    z-index:99;
                }
                .ifso-fe-admin-notice-wrap.minified .ifso-fe-admin-notice,.ifso-fe-admin-notice-wrap:not(.minified) .ifso-fe-admin-mini-notice{
                    display: none;
                }
                .ifso-fe-admin-notice-wrap.minified .ifso-fe-admin-mini-notice{
                    display: block;
                    width: 58px;
                    height: 58px;
                    text-align: center;
                    box-shadow: 0px 1px 14px 0px #00000040;
                    border-radius: 50%;
                    cursor: pointer;
                    position: relative;
                    background:#fff;
                    border: 2px solid #6a6dd4;
                }
                .ifso-fe-admin-notice-wrap.minified .ifso-fe-admin-mini-notice .page-icon{
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width:55%;
                }
                .ifso-fe-admin-notice {
                    display: flex;
                    align-items:center;
                    border: 2px solid #b6b7e4;
                    background:#fff;
                    border-left: 5px solid #6B6DCD;
                    border-radius: 4px;
                    padding-left:15px;
                    max-width:410px;
                }
                .ifso-fe-admin-notice:before{
                    min-width: 24px;
                    max-width:24px;
                    height: 24px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 14px;
                    font-weight: bold;
                    content:"i";
                    background: #6A6DD4;
                }
                .ifso-fe-admin-notice .notice-content{
                    padding: 13px 22px 14px 17px;
                    color: #1f1a2d;
                }
                .ifso-fe-admin-notice .notice-content>*{
                    margin:0;
                    font-family:'Verdana';
                    font-size:14px;
                    line-height: 1.4;
                }
                .ifso-fe-admin-notice .notice-content h3{
                    margin-bottom:2px;
                    font-size: 16px;
                    font-weight: 600;
                    line-height: 2;
                }
                .ifso-fe-admin-notice .closeX{
                    margin-right:18px;
                    color: #1e0547;
                    cursor: pointer;
                }
                
                @media only screen and (max-width: 600px){
                    .ifso-fe-admin-notice-wrap{
                        left:0;
                        bottom:0;
                    }
                    .ifso-fe-admin-notice{
                        width: 100vw;
                        max-width:none;
                        border:none;
                        border-top:2px solid #b6b7e4;
                    }
                    .ifso-fe-admin-notice .closeX{
                        margin-right:36px;
                    }
                }
            </style>
            <div notice="{$notice}" class="ifso-fe-admin-notice-wrap abt {$minified_class}">
                <div class="ifso-fe-admin-mini-notice" onclick="this.parentElement.classList.remove('minified');ifso_scope.createCookie('{$hide_notice_cookie_name}','1',-1);">
                    <img class="page-icon" src="{$icon_url}">
                </div>
                <div class="ifso-fe-admin-notice">
                    <div class="notice-content">
                        <h3>This page is running A/B tests.</h3>
                        <p>A/B tests do not run for logged-in admins, to make the versions rotate, log out or open the site in incognito mode.</p>
                    </div>
                    <span class="closeX" onclick="this.parentElement.parentElement.classList.add('minified');ifso_scope.createCookie('{$hide_notice_cookie_name}','1',30);">&#10006;&#xFE0E;</span>
                </div>
            </div>
            
            <script>
                var notices_wrapper_class = 'ifso-fe-notices-wrapper';
                var notices_wrapper = document.querySelector('body>.'+notices_wrapper_class);
                if(notices_wrapper===null || typeof(notices_wrapper)==='undefined'){
                    notices_wrapper = document.createElement('div');
                    notices_wrapper.className = notices_wrapper_class;
                    document.body.appendChild(notices_wrapper);
                }
                var notice_wrap = document.querySelector(':not(.'+notices_wrapper_class + ') .ifso-fe-admin-notice-wrap[notice="{$notice}"]');
                if(notices_wrapper.querySelector('.' + notices_wrapper_class + ' [notice="{$notice}"]')===null)
                    notices_wrapper.appendChild(notice_wrap);
                else
                    notice_wrap.remove();
            </script>
            
BOX;
    }
}