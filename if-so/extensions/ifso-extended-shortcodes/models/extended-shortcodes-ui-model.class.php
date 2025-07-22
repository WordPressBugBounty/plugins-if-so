<?php
namespace IfSo\Extensions\IFSOExtendedShortcodes\ExtendedShortcodes\UIModel;
class ExtendedShortcodesUIModel{
    const ICON_DIR_URL = IFSO_PLUGIN_DIR_URL . '/admin/images/dki-modal/';
    /* @var $shortcodes  ShortcodeUI[]*/
    public $shortcodes = [];
    public function __construct(){
        $dki_sc = new ShortcodeUI('ifsoDKI','DKI','','');
        $dki_geo_type = new ShortcodeUIType('geo','Geolocation','Display the visitor`s location name (country, state, city, etc.). <a target="_blank" href="https://www.if-so.com/geolocation-dki/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Demo.</a>',self::ICON_DIR_URL . 'geo.svg');
        $dki_geo_type->add_field(new AttributeUI('show','Location Type','',
            [new OptionUI('country'),new OptionUI('state'),new OptionUI('city'),new OptionUI('continent'),new OptionUI('timezone')]));
        $dki_language_type = new ShortcodeUIType('language','Browser Language','Display the visitor`s browser-defined language/s.',self::ICON_DIR_URL . 'language.svg');
        $dki_language_type->add_field(new AttributeUI('show','Language(s) to show','',
            [new OptionUI('primary-only','Primary Only'),new OptionUI('all','All'),new OptionUI('all-except-primary','All Except Primary'),
            new OptionUI('count','Count'),new OptionUI('count-without-primary','Count Without Primary')]));
        $dki_referrer_type = new ShortcodeUIType('referrer','Referrer','Display the referring URL or domain that led the user to the site. <a target="_blank" href="https://www.if-so.com/what-is-your-referrer/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Demo.</a>',self::ICON_DIR_URL . 'referrer.svg');
        $dki_referrer_type->add_field(new AttributeUI('show','Show','',
            [new OptionUI('full','Full URL'),new OptionUI('domain-only','Domain Only')]));
        $dki_viewcount_type = new ShortcodeUIType('viewcount','View Count','Visit Count – The total number of pages visited by a specific user.<br> Trigger View Count – The number of times a specific trigger was seen by a visitor.
',self::ICON_DIR_URL . 'viewcount.svg');
        $dki_viewcount_type->add_field(new AttributeUI('show','Show','',
            [new OptionUI('visit-count','Visit Count'),new OptionUI('post-viewcount','Trigger View Count')]));
        $dki_viewcount_type->add_field(new AttributeUI('id','Trigger ID','For trigger view count only.'));
        $dki_qs_type = new ShortcodeUIType('querystring','Query String','Displays a value from a URL parameter. Example: domain.com?firstname=<b>David</b> – shows “David”. <a target="_blank" href="https://www.if-so.com/conditional-content/inline-name/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Demo.</a> 
',self::ICON_DIR_URL . 'querystring.svg');
        $dki_qs_type->add_field(new AttributeUI('parameter','Parameter','The parameter name without the “?” or “&” prefix. For instance: example.com?<span style="color:#6665E7;">param</span>=value'));
        $dki_qs_type->add_field(new AttributeCheckboxUI('persist','Persist','Once the dynamic value is displayed, it will be saved and shown across all pages.','yes'));
        $dki_google_ads_type = new ShortcodeUIType('google-ads','Google Ads','Display a value from Google Ads tracking parameters. The value can display the triggered keyword automatically or be set manually. <a target="_blank" href="https://www.if-so.com/dynamic-keyword-insertion-for-google-ads/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Learn More.</a>',self::ICON_DIR_URL . 'google-ads.svg');
        $dki_google_ads_type->add_field(new AttributeUI('parameter','Parameter','The parameter name without the “?” or “&” prefix. For instance: example.com?<span style="color:#6665E7;">param</span>=value'));
        $dki_google_ads_type->add_field(new AttributeCheckboxUI('persist','Persist','Once the dynamic value is displayed, it will be saved and shown across all pages.','yes'));
        $dki_day_of_week_type = new ShortcodeUIType('day-of-week','Day of the Week','Insert the current day (e.g. ' . date('l') .').',self::ICON_DIR_URL . 'day-of-week.svg');
        $dki_time_type = new ShortcodeUIType('time','Local Time Display','Automatically shows the event time in the visitor’s local timezone.<a target="_blank" href="https://www.if-so.com/auto-local-time-display/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Demo.</a>',self::ICON_DIR_URL . 'time.svg');
        $dki_time_type->add_field(new AttributeUI('show','Show',
            '"user-geo-timezone-sensitive" shows the site visitor\'s local time, according to the Geolocation service and consumes a session',
            [/*new OptionUI('site-timezone','Site Timezone'),*/new OptionUI('user-geo-timezone-sensitive','Auto-Local Timezone')]));
        $dki_time_type->add_field(new AttributeUI('time','Time','Set time only for a daily event (HH:MM), or add a date for a one-time event (HH:MM mm/dd/yyyy)',null,'','HH:MM dd/mm/yyyy'));
        $now = new \DateTime('now',\IfSo\PublicFace\Helpers\WpDateTimeZone::getWpTimezone());;
        $dki_time_type->add_field(new AttributeUI('format','Display Format','Choose how the time will appear to the user. Additional formatting options can be set manually. <a href="https://www.if-so.com/auto-local-time-display/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=fieldDescription" target="_blank">Learn More</a>',
            [new OptionUI('H:i', 'H:i - '.$now->format('H:i')),new OptionUI('g:i A', 'g:i A - '.$now->format('g:i A')),new OptionUI('H:i  m/d', 'H:i  m/d - '.$now->format('H:i  m/d')),new OptionUI('H:i  m/d/Y', 'H:i  m/d/Y - '.$now->format('H:i  m/d/Y')),new OptionUI('H:i  m/d/y ', 'H:i  m/d/y - '.$now->format('H:i  m/d/y')),new OptionUI('H:i T', 'H:i T - '.$now->format('H:i T')),new OptionUI('l, F m Y', 'l, F m Y - '.$now->format('H:i, l, F m Y'))]));
        $dki_ip_type = new ShortcodeUIType('ip','IP address',"Show the visitor&#39;s IP address.",self::ICON_DIR_URL . 'ip.svg');
        $dki_url_type = new ShortcodeUIType('url','URL','',self::ICON_DIR_URL . 'url.svg');
        $dki_url_type->add_field(new AttributeUI('url','URL',''));
        $dki_sc->set_fields([$dki_geo_type,$dki_language_type,$dki_referrer_type,$dki_viewcount_type,$dki_qs_type,$dki_google_ads_type,$dki_day_of_week_type,$dki_time_type,$dki_ip_type,/*$dki_url_type*/]);

        $show_post_sc = new ShortcodeUI('ifso-show-post','Show Post','Displays content from another post or page. Handy for designing dynamic content with your page builder and injecting it into a trigger. <a target="_blank" href="https://www.if-so.com/faq-items/can-i-create-content-using-my-page-builder-and-use-it-as-a-dynamic-if-so-version/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Learn More.</a>',self::ICON_DIR_URL . 'show-post.svg');
        $show_post_sc->add_field(new AttributeUI('id','Post ID'));
        $show_post_sc->add_field(new AttributeUI('show','Show Options','',[new OptionUI('content',"Post content"),new OptionUI('title',"Post title")]));
        $show_post_sc->add_field(new AttributeUI('type',"Post's Page Builder",'If you used a listed page builder to create the post, select it here.',
            [new OptionUI('regular','None'),new OptionUI('divi','Divi'),new OptionUI('elementor','Elementor'),new OptionUI('wpb','WPBakery')]));
        $show_post_sc->add_field(new AttributeUI('the_content','Apply the \'the_content\' filter','Alter this option if the shortcode breaks the content design',
            [new OptionUI('yes'),new OptionUI('no')]));

        $redirect_sc = new ShortcodeUI('ifso-redirect','Redirect','Forward visitors to a different URL when they encounter the shortcode. Use this shortcode inside a trigger to create conditional redirects <a href="https://www.if-so.com/help/documentation/conditional-redirect/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription" target="_blank">.',self::ICON_DIR_URL . 'redirect.svg');
        $redirect_sc->add_field(new AttributeUI('url','Redirect URL'));
        $redirect_sc->add_field(new AttributeUI('type','Redirect type','',
            [new OptionUI(301),new OptionUI(302),new OptionUI(307),new OptionUI(308),new OptionUI('js','JS')]));
        $redirect_sc->add_field(new AttributeCheckboxUI('forward_query_params','Forward Query Parameters','','yes'));
        $redirect_sc->add_field(new AttributeCheckboxUI('exclude_admins','Exclude admins','','yes'));
        $redirect_sc->add_field(new AttributeUI('do_once_per','Prevent repeated redirects – duration',
            'To avoid repeated redirections each time the visitor encounters the shortcode, enter a limiting duration in seconds (e.g., 86400 for one day).'));
        $redirect_sc->add_field(new AttributeUI('name','Redirect Identifier','Set a unique identifier of your choice. Required to use the "prevent repeated redirects" option.
',));

        $login_link_sc = new ShortcodeUI('ifso_login_link','Login/Out Link','Show a login or logout link based on the user\'s status, and choose a page to redirect to after login.',self::ICON_DIR_URL . 'login-link.svg');
        $login_link_sc->add_field(new AttributeUI('login_text','"Log in" link text (optional)'));
        $login_link_sc->add_field(new AttributeUI('logout_text','"Log out" link text (optional)'));
        $login_link_sc->add_field(new AttributeUI('login_redirect','URL to Redirect After Login'));

        $user_details_sc = new ShortcodeUI('ifso_user_details','User Details','Display data from the logged-in WordPress user. <a target="_blank" href="https://www.if-so.com/user-details-dki/?utm_source=Plugin&utm_medium=DKIModal&utm_campaign=shortcodeDescription">Learn More.</a>',self::ICON_DIR_URL . 'referrer.svg');
        $user_details_sc->add_field(new AttributeUI('show','Show','',
            [new OptionUI('firstName','First Name'),new OptionUI('lastName','Last Name'),new OptionUI('fullName','Full Name'),new OptionUI('email','Email'),new OptionUI('username','Username')]));
        $user_details_sc->add_field(new AttributeUI('default','Default value','Displays if the user is not logged in or the field is not available'));


        $ga4_event_sc = new ShortcodeUI('ifso-GA4-event','GA4 Event',
            'Pass a custom event to Google Analytics 4 when the shortcode is executed. All additional attributes and their values in the shortcodes will be passed as event parameters.',self::ICON_DIR_URL . 'ga4-event.svg');
        $ga4_event_sc->add_field(new AttributeUI('ga4_event_type','Event name','The event name to be recorded in GA4.'));


        $this->shortcodes = apply_filters('ifso_extended_shortcodes_ui',[$dki_sc,$show_post_sc,$redirect_sc,$login_link_sc,$user_details_sc,/*$ga4_event_sc*/]);
    }

    public function get_shortcodes_and_types(){
        $ret = [];
        foreach($this->shortcodes as $sc){
            $sc_types = $sc->get_types();
            if(empty($sc_types))
                $ret[] = $sc;
            else
                $ret = array_merge($ret,$sc_types);
        }
        return $ret;
    }
}

abstract class UIElement{
    public string $name;
    public string $prettyName;
    public string $description;
    public bool $is_composite = false;
    public function __construct($name,$prettyName,$description='') {
        $this->name = $name;
        $this->prettyName = $prettyName;
        $this->description = $description;
    }
}

class AttributeUI extends  UIElement{
    /* @var $options  OptionUI[]|null*/
    public ?array $options;
    public string $default;
    public string $placeholder;
    public function __construct($name,$prettyName,$description='',$options=null,$default='',$placeholder='') {
        parent::__construct($name,$prettyName,$description);
        $this->options = $options;
        $this->default = $default;
        $this->placeholder = $placeholder;
    }
}

class OptionUI extends UIElement{
    public function __construct($name, $prettyName=null) {
        if($prettyName===null)
            $prettyName = $name;
        parent::__construct($name, $prettyName);
    }
}

class AttributeCheckboxUI extends UIElement{
    public string $uncheckedValue ;
    public string $checkedValue;
    public function __construct($name, $prettyName, $description, $checkedValue,$uncheckedValue='') {
        parent::__construct($name, $prettyName, $description);
        $this->checkedValue = $checkedValue;
        $this->uncheckedValue = $uncheckedValue;
    }
}

abstract class UIElementComposite extends  UIElement{
    public string $icon_url;
    /* @var $fields  UIElement[]*/
    public array $fields = [];
    public bool $is_composite = true;

    public function __construct($name,$prettyName,$description='',$icon_url='') {
        parent::__construct($name,$prettyName,$description);
        $this->icon_url = $icon_url;
    }

    public function set_fields($fields){
        foreach($fields as $field){
            if($field instanceof ShortcodeUIType)
                $field->parent = $this;
        }
        $this->fields = $fields;
    }

    public function add_field($field) {
        if($field instanceof ShortcodeUIType)
            $field->parent = $this;
        $this->fields[] = $field;
    }

    public function get_fields():array{
        return $this->fields;
    }

    public function get_types():array{
        $ret = [];
        foreach ($this->fields as $field){
            if($field instanceof ShortcodeUIType)
                $ret[] = $field;
        }
        return $ret;
    }
}

class ShortcodeUI extends  UIElementComposite{
    public function get_fields():array{
        $ret = [];
        foreach($this->fields as $field){
            if($field->is_composite && $field instanceof UIElementComposite)
                $ret = array_merge($ret,$field->get_fields());
            else
                $ret[] = $field;
        }
        return $ret;
    }

    public function get_shortcode(){
        return $this->name;
    }

    public function get_type(){
        return '';
    }
}

class ShortcodeUIType extends  UIElementComposite{
    public UIElementComposite $parent;
    public string $type_attr;

    public function get_shortcode(){
        return $this->parent->name;
    }

    public function get_type(){
        return $this->name;
    }
}