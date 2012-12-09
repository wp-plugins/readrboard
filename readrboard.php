<?php
/*
Plugin Name: ReadrBoard
Plugin URI: http://www.readrboard.com/
Description: ReadrBoard lets your readers express a reaction to any phrase, picture, video, or article on your website with the ease of a 'like' button.
Version: 0.0.3
Author: team@readrboard.com
*/

define('READRBOARD_VERSION', '0.0.3');

register_deactivation_hook( __FILE__, array( 'Readrboard', 'uninstall' ) );

class Readrboard {
    const OPTIONS = 'readrboard_options';
    // const BASEURL = 'http://local.readrboard.com:8080';
    const BASEURL = 'http://www.readrboard.com';

    public static function uninstall() {
        delete_option( self::OPTIONS );
    }
}

add_action('init', 'readrboard_init');

function readrboard_init() {
    add_action('wp_enqueue_scripts', 'readrboard_enqueue_scripts');
    add_action('admin_menu', 'readrboard_config_page');
    readrboard_admin_alert_check_for_clear();
    readrboard_admin_alert();
    add_filter('post_class', 'readrboard_add_post_class');
    add_filter('the_content', 'readrboard_edit_page_template');
}

function readrboard_enqueue_scripts() {
    $handle = "readrboard_engage_script";
    $src = sprintf(
        '%1s/static/engage.js?wordpressplugin=true',
        Readrboard::BASEURL
    );
    $deps = array();
    $ver = false;
    $in_footer = true;

    wp_enqueue_script(
        $handle,
        $src,
        $deps,
        $ver,
        $in_footer 
    );

}

function readrboard_config_page() {
    add_options_page( 'ReadrBoard Options', 'ReadrBoard', 'manage_options', 'readrboard-settings', 'readrboard_conf' );
}
function readrboard_add_post_class($test) {
    array_push( $test, "readrboard_page");
    return $test;
}

function readrboard_edit_page_template($template) {
    $summaryWidgetHook = sprintf(
        '<div class="readrboard_summary_widget_hook" ></div>'
    );
    $hiddenPageHrefNode = sprintf(
        '<a style="display:none;" class="readrboard_page_permalink" href="%1s"></a>',
        get_permalink()
    );

    return $summaryWidgetHook.$hiddenPageHrefNode.$template;
    // return $template;
}

function readrboard_conf() {
if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

?>
<?php if ( !empty($_POST['submit'] ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>

<script>

    var readrboard = window.readrboard = {};

    readrboard.url = <?php echo '"' . Readrboard::BASEURL . '"'; ?>;

    //todo: load these smarter later
    (function($){

        function plugin_jquery_postMessage($){
            /*
             * jQuery postMessage - v0.5 - 9/11/2009
             * http://benalman.com/projects/jquery-postmessage-plugin/
             *
             * Copyright (c) 2009 "Cowboy" Ben Alman
             * Dual licensed under the MIT and GPL licenses.
             * http://benalman.com/about/license/
             */
            var g,d,j=1,a,b=this,f=!1,h="postMessage",e="addEventListener",c,i=b[h]&&!$.browser.opera;$[h]=function(k,l,m){if(!l){return}k=typeof k==="string"?k:$.param(k);m=m||parent;if(i){m[h](k,l.replace(/([^:]+:\/\/[^\/]+).*/,"$1"))}else{if(l){m.location=l.replace(/#.*$/,"")+"#"+(+new Date)+(j++)+"&"+k}}};$.receiveMessage=c=function(l,m,k){if(i){if(l){a&&c();a=function(n){if((typeof m==="string"&&n.origin!==m)||($.isFunction(m)&&m(n.origin)===f)){return f}l(n)}}if(b[e]){b[l?e:"removeEventListener"]("message",a,f)}else{b[l?"attachEvent":"detachEvent"]("onmessage",a)}}else{g&&clearInterval(g);g=null;if(l){k=typeof m==="number"?m:typeof k==="number"?k:100;g=setInterval(function(){var o=document.location.hash,n=/^#?\d+&/;if(o!==d&&n.test(o)){d=o;l({data:o.replace(n,"")})}},k)}}}
        }
        
        //todo: pull these from utils for real
        var utils = {
            getQueryParams: function(optQueryString) {
                //RDR.util.getQueryParams:

                var queryString = optQueryString || window.location.search;

                var urlParams = {};
                var e,
                a = /\+/g,  // Regex for replacing addition symbol with a space
                r = /([^&=]+)=?([^&]*)/g,
                d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
                q = queryString.substring(1);

                while (e = r.exec(q))
                    urlParams[d(e[1])] = d(e[2]);

                return urlParams;
            },
            getQueryStrFromUrl: function(url){
                var qIndex = url.indexOf('?'),
                    hrefBase,
                    hrefQuery,
                    qParams;

                 //if qIndex == -1, there was no ?
                if(qIndex == -1 ) {
                    hrefBase = url;
                    hrefQuery = "";
                }else{
                    hrefBase = url.slice(0, qIndex);
                    hrefQuery = url.slice(qIndex);
                }
                return hrefQuery;
            }
        };

        readrboard.urlPs = utils.getQueryParams();
        $(function(){
            plugin_jquery_postMessage($);
            
            $('#readrboardParams')[0].reset();

            $.receiveMessage(
                function(e){ 
                    
                    readrboard.newParams = $.parseJSON(e.data);
                    if(readrboard.newParams.new_short_name){
                        $('#readrboardParams').find('#short_name').val(readrboard.newParams.new_short_name);
                        $('#readrboardParams').submit();
                    }
                    else if(readrboard.newParams.refresh){
                        window.location.reload();
                    }
                },
                readrboard.url
            );
        });

    //todo - load our jQuery instead if this isn't reliable
    })(jQuery);
</script>


<div class="wrap">
    <style>
        .wrap h2{
            padding: 10px 16px 14px;
        }
        
        .iframeWrap{
            margin: auto;
            width: 900px;
        }
    </style>

    <h2>
        <?php _e('ReadrBoard Settings'); ?>
    </h2>
    <div class="iframeWrap">
        <?php
            readrboard_printIframe();
        ?>
    </div>

     <form id="readrboardParams" style="display:none;" action="" method="post"> 
        <input type="text" id="short_name" name="short_name" hidden="hidden" />
     </form> 
</div>
<?php
}

function readrboard_printIframe() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
        $has_ssl = true;
    }
    if($has_ssl){
        $prot = "https://";
    }else{
        $prot = "http://";
    }
    $domain = $_SERVER['HTTP_HOST'];
    $xdm_url = $prot.$domain;
    $domainNormalized = preg_replace('#^www\.(.+\.)#i', '$1', $domain);
    $options = readrboard_get_options();
    $short_name = $options['short_name'];

    $blogName = get_bloginfo( 'name' );

    $qs = array(
        "hostplatform=wordpress",
        "host_xdm_url=" . $xdm_url,
        "hostdomain=" . $domainNormalized,
        "short_name=" . $short_name,
        "company_name=" . $blogName
    );

    echo sprintf(
        '<iframe src="%1s/wordpress/?%2s&%3s&%4s&%5s&%6s" height="1500px;" width="900px;"></iframe>',
        Readrboard::BASEURL,
        $qs[0],
        $qs[1],
        $qs[2],
        $qs[3],
        $qs[4]
    );
}

//todo: move all of these into the Readrboard class
function readrboard_set_option($option, $val) { 
    //todo optimize later.
    $options = readrboard_get_options();
    $options[$option] = $val;
    readrboard_set_options($options);
}

function readrboard_set_options($options) {
    //todo: optimize to not fetch from the DB everytime unless requested
    update_option("readrboard_options", $options);
    return $options;
}

function readrboard_get_options() {
    //todo: optimize to not fetch from the DB everytime unless requested
    $readrboard_options = get_option("readrboard_options");
    return $readrboard_options;
}

function readrboard_clear_admin_alert() { 
    readrboard_set_option('accountIsSetup', True);
}

function readrboard_admin_alert_check_for_clear() {
    $options = readrboard_get_options();

    $new_short_name = $_POST['short_name'];
    if($new_short_name){

        readrboard_clear_admin_alert();

        $short_name = $new_short_name;
        readrboard_set_option('short_name', $new_short_name);
        readrboard_set_option('$new_short_name', "");
        $_POST = array();
    }
}

function readrboard_admin_alert() {
    $options = readrboard_get_options();
    $accountIsSetup = $options['accountIsSetup'];
 
    if ( !$accountIsSetup && !isset($_POST['submit']) ) {
        function readrboard_warning() {
            
            echo "
            <div id='readrboard-warning' class='updated fade'><p><strong>".__('Readrboard is almost ready.')."</strong> ".sprintf(__('Please complete the guided setup in the <a href="%1$s">ReadrBoard Settings</a> panel.'), "options-general.php?page=readrboard-settings")."</p></div>
            ";
        }
        add_action('admin_notices', 'readrboard_warning');
        return;
    }
}

?>
<?php

?>