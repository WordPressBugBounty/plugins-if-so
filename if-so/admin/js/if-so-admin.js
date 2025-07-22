(function( $ ) {
    'use strict';

    /* Tab switching code for admin pages */
    $(document).on("click", ".ifso-admin-tabs-header .ifso-tab", function() {
        if ( $(this).hasClass("selected-tab") )
            return;
        var selectedTab = $(this).data('tab');
        var contentToShow = "." + selectedTab;
        var $selectedTab = $(".selected-tab");
        var firstOpen = $selectedTab.length===0;
        var contentToHide = "." + $selectedTab.data("tab");
        window.location.hash = selectedTab;
        // switch classes
        $selectedTab.removeClass("selected-tab");
        $(this).addClass("selected-tab");
        // switch contents
        if(firstOpen)
            $(contentToShow).stop(true).fadeIn();
        else{
            $(contentToHide).stop(true).fadeOut('fast', function() {$(contentToShow).stop(true).fadeIn();});
        }
    });

    $(document).ready(function(){
        //Geo page -switch tabs according to the hash
        if($('[data-tab]').length>0){
            if(window.location.hash!='' && $('[data-tab=' + window.location.hash.substring(1) + ']').length>0){
                $('[data-tab=' + window.location.hash.substring(1) + ']').click();
            }
            else if($('.ifso-tab.default-tab').length>0){
                $('.ifso-tab.default-tab').click();
            }
        }

        /*Send test email - send AJAX*/
        if($('#ifso_send_test_email').length>0){
            $('#ifso_send_test_email').on('click',function(){ajaxPost({action:'send_test_mail'},function(a){alert('A testing email was sent successfully. Please check your spam folder if you do not see it in your inbox.')},function(a,b){var errText = a.responseText || '';alert('Something went wrong! Please check your internet connection and try again!\n'+errText)})});
        }
    })


})(jQuery);


function ajaxPost(data,callback,errCallback){
    if(data==undefined) data = {};
    if(callback==undefined) callback = function(){};	//Not using default parameters to prevent from breaking in IE
    if(errCallback==undefined) errCallback = function(){};
    if(typeof(nonce)!=='undefined' && nonce)
        data['_ifsononce'] = nonce;
    jQuery.post(ajaxurl,data,callback).fail(errCallback);
}


function resetAllAnalyticsDataAction(){ /* Resets the analytics via button on the settings page */
    if(confirm('Are you sure you want to reset all of the analytics data accumulated by if-so? This data cannot be recovered')){
        ajaxPost({action:'ifso_analytics_req',an_action:'resetAllAnalytics',postid:0}, function(){
            alert('All of the if-so analytics data has been deleted!');
        },function(){alert('Something went wrong. Please check your connection and try again!');})
    }
}