<?php

use IfSo\Extensions\IFSOExtendedShortcodes\ExtendedShortcodes\UIModel;

require_once IFSO_PLUGIN_BASE_DIR . 'extensions/ifso-extended-shortcodes/models/extended-shortcodes-ui-model.class.php';
$extended_sc_ui = new UIModel\ExtendedShortcodesUIModel();
?>
<style>
    .ifso-modal-dki .closebutton{
        fill: #515962!important;
        background-color: transparent!important;
        border: 2px solid #515962;
        border-radius: 50%;
        top:20px!important;
        right: 20px !important;
        width: 24px!important;
        padding:3px!important;
    }
    .ifso-modal-dki .ifso-modal-dki-content h3{
        color:#515962;
        width: 100%;
        margin: 34px 0 8px 0;
        font-size:1.5em;
    }
    .ifso-modal-dki-content{
        display: flex;
        justify-content: space-between;
        max-height: 75vh;
        min-height: 75vh;
    }
    .ifso-modal-dki-content .dki-types-column, .ifso-modal-dki-content .dki-fields-column{
        display:inline-block;
        vertical-align: top;
    }
    .ifso-modal-dki-content .dki-types-column{
        width: 270px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        border-right: 1px solid #E9E8EB;
    }
    .ifso-modal-dki-content .dki-types-column, .ifso-modal-dki-content .dki-fields{
        overflow-y: auto;
        scrollbar-color: #c9c9c9 transparent;
        scrollbar-width: thin;
    }
    .ifso-modal-dki-content .dki-types-column .dki-type-btn{
        width: 100%;
        border: none;
        background: #fff;
        color: #515962;
        margin:0;
        border-radius:0;
        text-align: left;
    }
    .ifso-modal-dki-content .dki-types-column h3{
        margin-bottom:0;
    }
    @media screen and (max-height: 750px) {
        .ifso-modal-dki-content .dki-types-column h3{
            margin-bottom:1em;
        }
    }
    .ifso-modal-dki-content .dki-types-column .dki-type-btn, .ifso-modal-dki-content .dki-types-column h3{
        padding-left: 30px;
        min-height:42px;
    }
    .ifso-modal-dki-content .dki-types-column .dki-type-btn.active{
        background: #DADBFE;
    }
    .ifso-modal-dki-content .dki-types-column .dki-type-btn .dki-type-icon{
        width:20px;
        height: 20px;
        margin:0 10px 0 0;
    }
    .ifso-modal-dki-content .dki-fields-column{
        width: calc(100% - 270px); margin-bottom: 0;
        /*margin-bottom:2em;*/
        padding: 0 1vw 0 1vw;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-type-fields:not(.active){
        display:none;
    }
    .ifso-modal-dki-content .dki-shortcode-info{
        margin-bottom:1em;
        min-height: 15%;
    }
    .ifso-modal-dki-content .dki-shortcode-info .description{
        color:#777;
        max-width: 600px;
    }
    .ifso-modal-dki-content .dki-fields{
        height: 65%;
    }
    @media screen and (max-height: 800px), (max-width: 700px) {
        .ifso-modal-dki-content{
            max-height: 95vh;
            min-height: 95vh;
        }
        .ifso-modal-dki-content .dki-fields{
            height: 60%;
        }
    }
    @media screen and (max-height: 550px) {
        .ifso-modal-dki-content .dki-fields{
            height: 55%;
        }
    }
    .ifso-modal-dki-content .dki-fields-column .dki-results-wrap-wrap{
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 1em;
        border-top: 1px solid #D9D9D9;
        padding-top: 1em;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-result-wrap{
        position: relative;
        flex-basis: 80%;
        margin-right: 5px;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-result-wrap .cpy-shortcode-btn{
        position: absolute;
        transform: translate(5%, 12.5%);
        background-color:#fff;
        color:#000;
        border-radius: 4px;
        padding: 0px 8px;
        right:4px;
        top:0;
        border:none;
        width: 32px;
        height: 32px;
        box-sizing: content-box;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-result-wrap .cpy-shortcode-btn img{
        height: 50%;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-result-wrap .cpy-shortcode-btn:hover{
        background-color:#F5F5F5;
    }
    /*.ifso-modal-dki-content .dki-fields-column .dki-result-wrap .cpy-shortcode-btn:active{
        background-color:#2AC253;
        color:#fff;
    }*/
    .ifso-modal-dki-content .dki-fields-column .dki-result-wrap input{
        color: #6c6c6c;
        padding: 8px 16px;
        width: 101%;
        background-color: #EEF1F3;
        border: none;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field{
        margin-bottom: 20px;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field input,.ifso-modal-dki-content .dki-fields-column .dki-field select{
        border-color:#C9C9C9;
        color: #6c6c6c;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field input:focus,.ifso-modal-dki-content .dki-fields-column .dki-field select:focus{
        border-color:#696DDB;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field label{
        display: block;
        color: #515962;
        font-weight:400;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field label:has(+.switch),
    .ifso-modal-dki-content .dki-fields-column .dki-field label.switch{
        display: inline-block;
        vertical-align: middle;
        margin: 0 8px 0 0;
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field label.switch{
        transform: scale(0.5) translate(-50%,10%);
    }
    .ifso-modal-dki-content .dki-fields-column .dki-field .dki-field-description{
        color: #6c6c6c;
        margin-top: 3px;
    }
    .ifso-modal-dki-content .dki-insert-result{
        height: 100%;
        margin-top: 5px;
        background-color: #696ddb;
        color: #fff;
        padding: 0.5em 1em;
        border-radius: 4px;
        border: none;
        white-space: nowrap;
    }
    .ifso-modal-dki-content .dki-insert-result:hover{
        background-color:#5659B7;
    }
    .ifso-modal-dki-content .dki-insert-result:active{
        background-color:#2AC253;
    }
    .dki-general-fields:not(.active){
        display: none;
    }
    .ifso-modal-dki{
        width:60vw;
        max-width: 1180px!important;
    }
    @media screen and (max-width: 1250px){
        .ifso-modal-dki{
            width:75vw;
        }
    }
    @media screen and (max-width: 800px){
        .ifso-modal-dki{
            width:90vw;
        }
        .ifso-modal-dki-content .dki-types-column .dki-type-btn .dki-type-icon{
            margin:0;
        }
        .ifso-modal-dki-content .dki-types-column{
            width:20%;
        }
        .ifso-modal-dki-content .dki-fields-column{
            width:80%;
        }
        .ifso-modal-dki-content .dki-types-column .dki-type-btn, .ifso-modal-dki-content .dki-types-column h3{
            padding-left:10px;
        }

    }
    @media screen and (max-width: 650px){
        .ifso-modal-dki-content .dki-types-column .dki-type-btn{
            font-size:75%;
            padding: 5% 0;
        }
        .ifso-modal-dki-content .dki-types-column h3{
            font-size:100%;
        }
    }
    /* AJAX SWITCHER START */
    .dki-field .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
        top:-3px;
    }
    /* Hide default HTML checkbox */
    .dki-field .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    /* The slider */
    .dki-field .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }
    .dki-field .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }
    .dki-field input:checked + .slider {
        background-color: #696ddb;
    }
    .dki-field input:focus + .slider {
        box-shadow: 0 0 1px #696ddb;
    }
    .dki-field input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }
    .dki-field .slider.round {
        border-radius: 34px;
    }
    .dki-field .slider.round:before {
        border-radius: 50%;
    }
    /* AJAX SWITCHER END */
</style>

<div class="ifso-modal-content ifso-modal-dki-content">
    <div class="dki-types-column">
        <h3>DKI Shortcode</h3>
        <?php
        foreach($extended_sc_ui->get_shortcodes_and_types() as $sc){
            $sc_string = $sc->get_shortcode();
            $type_attr = !empty($sc->get_type()) ? "type='{$sc->name}'" : '';
            echo "<button class='dki-type-btn' title='{$sc->prettyName}' description='{$sc->description}' shortcode='{$sc_string}' {$type_attr}><img class='dki-type-icon' src='{$sc->icon_url}'>{$sc->prettyName}</button>";
        }
        ?>
    </div>
    <div class="dki-fields-column">
        <div class="dki-shortcode-info">
            <h3 class="title">Title</h3>
            <p class="description" style="font-size:1.1em;">Lorem ipsum</p>
        </div>

        <div class="dki-fields">
            <div class="dki-types-fields">
                <?php foreach($extended_sc_ui->get_shortcodes_and_types() as $shortcode){
                    $fields = $shortcode->get_fields();
                    $fields_html = '';
                    foreach ($fields as $attr){
                        $label = "<label>{$attr->prettyName}</label>";
                        $description = !empty($attr->description) ? "<div class='dki-field-description'>{$attr->description}</div>" : '';
                        if($attr instanceof UIModel\AttributeCheckboxUI)
                            $input = "<label class='switch'><input class='dki-input switch' type='checkbox' checked_val='{$attr->checkedValue}' unchecked_val='{$attr->uncheckedValue}'><span class='slider round'></span></label>";
                        elseif($attr instanceof UIModel\AttributeUI){
                            if(empty($attr->options))
                                $input = "<input class='dki-input' type='text' value='{$attr->default}' placeholder='{$attr->placeholder}'>";
                            else{
                                $options_html = '';
                                foreach($attr->options as $opt){
                                    $options_html .= "<option value='{$opt->name}'>{$opt->prettyName}</option>";
                                }
                                $input = "<select class='dki-input'>{$options_html}</select>";
                            }
                        }
                        $nodisplay = !empty($attr->options) && count($attr->options)===1 ? 'style="display:none"' : '';
                        $fields_html .= "<div {$nodisplay} class='dki-field' field='{$attr->name}'>{$label} {$input} {$description}</div>";
                    }
                    echo "<div class='dki-type-fields' shortcode='{$shortcode->get_shortcode()}' type='{$shortcode->get_type()}'>{$fields_html}</div>";

                } ?>
            </div>
            <div class="dki-general-fields">
                <div class="dki-field" field="ajax"><label style="display: inline-block;">Load with AJAX</label> <label class="switch"><input class="dki-input switch" type="checkbox" checked_val='yes'><span class="slider round"></span></label></div>
                <div class="dki-field" field="fallback"><label>Fallback Value</label> <input class="dki-input" type="text"><div class="dki-field-description">Shown if dynamic value is unavailable.</div></div>
                <div class="dki-field" field="before"><label>Text Before Value (Optional)</label> <input class="dki-input" type="text"><div class="dki-field-description">Shown only if a dynamic value is available (ignored in fallback content).</div></div>
                <div class="dki-field" field="after"><label>Text After Value (Optional)</label> <input class="dki-input" type="text"><div class="dki-field-description">Shown only if a dynamic value is available (ignored in fallback content).</div></div>
            </div>
        </div>
        <div class="dki-results-wrap-wrap">
            <div class="dki-result-wrap">
                <span class="shortcode dki-result">
                    <input type="text" onfocus="this.select();" readonly="readonly" value="" class="large-text code">
                    <button class="cpy-shortcode-btn"><img src="<?php echo $extended_sc_ui::ICON_DIR_URL . 'copy-icon.svg'; ?>"></button>
                </span>
            </div>
            <button class="dki-insert-result">Insert Shortcode</button>
        </div>
    </div>
</div>

<!-- DKI insert modal END  -->

<script>
    (function( $ ) {
        $(document).ready(function () {
            //DKI modal
            var DKIModal = function (content_el) {
                this.active_shortcode_type = null;
                this.active_dki_type = null;
                this.insert_callback = null;
                this.shortcode = '';
                this.content = content_el;
                this.modal = new TinyModal('ifso-modal-dki');
                this.modal.createModal($(".ifso-modal-dki-content")[0]);
                this.init_input_events();
            };
            DKIModal.prototype = {
                init_input_events :function(){
                    var _this = this;
                    $('.ifso-modal-dki-content .dki-type-btn').on('click', function () {
                        _this.switch_dki_type(this.getAttribute('shortcode'),this.getAttribute('type'));
                    });
                    $('.dki-fields-column .dki-field .dki-input').on('input', function () {
                        _this.display_shortcode()
                    });
                    $('.ifso-modal-dki-content .dki-insert-result').on('click',function(){
                        _this.insert_and_close_modal();
                    });
                    $('.ifso-modal-dki-content .dki-result .cpy-shortcode-btn').on('click',function(){
                        navigator.clipboard.writeText(_this.shortcode);
                    });
                },
                open_modal: function (insert_cb) {
                    this.insert_callback = insert_cb;
                    this.modal.openModal();
                    this.switch_dki_type('ifsoDKI','geo');
                    this.reset_all_fields();
                },
                insert_and_close_modal: function () {
                    if(this.insert_callback!==null)
                        this.insert_callback(this.shortcode);
                    this.modal.closeModal();
                },
                switch_dki_type: function (shortcode,type, clicked_btn = null) {
                    this.reset_selected_type_fields();
                    if (this.active_dki_type !== null || this.active_shortcode_type !== null) {
                        this.modal.element.querySelector('.dki-fields-column .dki-type-fields.active').classList.remove('active');
                        this.modal.element.querySelector('.dki-type-btn.active').classList.remove('active');
                    }
                    this.active_dki_type = type;
                    this.active_shortcode_type = shortcode;
                    $(this.get_active_fields_selector()).addClass('active');

                    if(clicked_btn==null){
                        var btn_selector = '.dki-type-btn[shortcode="' + shortcode + '"]';
                        if(type!==null) btn_selector+= '[type="' + type + '"]';
                        clicked_btn = this.modal.element.querySelector(btn_selector);
                    }
                    this.modal.element.querySelector('.dki-shortcode-info .title').innerHTML = clicked_btn.getAttribute('title');
                    this.modal.element.querySelector('.dki-shortcode-info .description').innerHTML = clicked_btn.getAttribute('description');
                    this.active_shortcode_type==='ifsoDKI' ? this.modal.element.querySelector('.dki-general-fields').classList.add('active') : this.modal.element.querySelector('.dki-general-fields').classList.remove('active');
                    clicked_btn.classList.add('active');

                    this.display_shortcode();
                },
                switch_shortcode_title_and_description : function (title,description){
                  this.modal.element.querySelector('.dki-shortcode-info .title').innerHTML = title;
                  this.modal.element.querySelector('.dki-shortcode-info .description ').innerHTML = description;
                },
                display_shortcode: function () {
                    this.modal.element.querySelector('.dki-result-wrap .dki-result input').value = this.make_shortcode()
                },
                make_shortcode: function () {
                    var _this = this;
                    var ret = '[' + this.active_shortcode_type;
                    if(this.active_dki_type!==null) ret+= ' type="' + this.active_dki_type + '"'
                    var field_val_to_sc_attr = function (field) {
                        var val = _this.get_field_value(field);
                        if (val === '') return '';
                        var fieldParent=field;
                        while(fieldParent.getAttribute('field')===null)
                            fieldParent = fieldParent===null ? field.parentElement : fieldParent.parentElement;

                        return ' ' + fieldParent.getAttribute('field') + '="' + _this.get_field_value(field) + '"'
                    };
                    if(this.active_shortcode_type==='ifsoDKI')
                        this.get_general_field_inputs().forEach(function (field) {ret += field_val_to_sc_attr(field);});
                    this.get_type_field_inputs().forEach(function (field) {ret += field_val_to_sc_attr(field);});
                    ret += ']';
                    this.shortcode = ret;
                    return ret;
                },
                get_field_value: function (field) {
                    if (field.tagName === 'INPUT' && field.type === 'checkbox')
                        return field.checked ? field.getAttribute('checked_val') :
                            field.getAttribute('unchecked_val')!=='' && field.getAttribute('unchecked_val')!==null ? field.getAttribute('unchecked_val') : '';
                    else
                        return field.value;
                },
                get_general_field_inputs: function () {
                    return Array.prototype.slice.call(this.modal.element.querySelectorAll('.dki-fields-column .dki-general-fields .dki-field .dki-input'));
                },
                get_type_field_inputs: function () {
                    if (this.active_dki_type === null && this.active_shortcode_type===null)
                        return [];
                    return Array.prototype.slice.call(this.modal.element.querySelectorAll(this.get_active_fields_selector() + ' .dki-input'));

                },
                get_active_fields_selector : function (){
                    var active_el_selector = '.ifso-modal-dki-content .dki-fields-column .dki-type-fields[shortcode="' + this.active_shortcode_type + '"]';
                    active_el_selector+= this.active_dki_type!==null ? '[type="' + this.active_dki_type + '"]' : '';
                    return active_el_selector;
                },
                reset_fields: function (fields) {
                    fields.forEach(function (el) {
                        if (el.tagName === 'INPUT' && el.type === 'checkbox')
                            el.checked = false;
                        else if (el.tagName === 'SELECT')
                            el.value = el.options[0].value;
                        else
                            el.value = '';
                    });
                },
                reset_all_fields: function () {
                    this.reset_fields(this.get_general_field_inputs());
                    this.reset_fields(this.get_type_field_inputs());
                },
                reset_selected_type_fields: function () {
                    this.reset_fields(this.get_type_field_inputs());
                }
            };

            if ($(".ifso-modal-dki-content").length) {
                var dki_modal = new DKIModal();
                initOpenButtons();
                document.addEventListener('ifso_triggerpage_version_added',initOpenButtons);
                window.dki_modal = dki_modal;
            }

            function initOpenButtons(){
                $('.ifso-insert-dki-modal:not([active])').on('click',function (e){
                    e.preventDefault();
                    var cb = (sc) => {
                        var textarea = jQuery(this).closest('.wp-editor-wrap').find('.wp-editor-area')[0];
                        var editor =  tinymce.get(textarea.id);
                        if(editor!==null && textarea.style.display==='none')
                            editor.execCommand('mceInsertContent', false, sc);
                        else
                            textarea.value += sc;
                    }
                    dki_modal.open_modal(cb);
                }).attr('active',1);
            }
        });
    })( jQuery );
</script>