<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";

session_start();

$p = $CFG->dbprefix;

$OUTPUT->header();
?>
<link rel="stylesheet" type="text/css"
    href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css"/>
<?php

$registrations = findAllRegistrations();
if ( count($registrations) < 1 ) $registrations = false;

$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

$rest_path = U::rest_path();
$install = $rest_path->extra;

if ( ! isset($registrations[$install])) {
    echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
    $OUTPUT->footer();
    return;
}
$tool = $registrations[$install];

$screen_shots = U::get($tool, 'screen_shots');
if ( !is_array($screen_shots) || count($screen_shots) < 1 ) $screen_shots = false;

$title = $tool['name'];
$text = $tool['description'];
$ltiurl = $tool['url'];
$fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
$icon = false;
if ( $fa_icon !== false ) {
    $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
}

// Check if the tsugi.php is upgraded for this tool
$register_json = $tool['url'].'register.json';
$json_str = Net::doGet($register_json);
$json_obj = json_decode($json_str);
$register_good = $json_obj && isset($json_obj->name);

// Start the output
?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<div id="image-dialog" title="Image Dialog" style="display: none;">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" style="width:100%" id="popup-image">
</div>
<div id="url-dialog" title="URL Dialog" style="display: none;">
    <h1>Single Tool URLs</h1>
    <p>LTI 1.x Launch URL (Expects an LTI launch)<br/><?= $tool['url'] ?>
    (requires key and secret)
    </p>
<?php if ( $register_good ) { ?>
    <p>Canvas Tool Configuration URL (XML)<br/>
    <a href="<?= $tool['url'] ?>canvas-config.xml" target="_blank"><?= $tool['url'] ?>canvas-config.xml</a></p>
    <p>Tsugi Registration URL (JSON)<br/>
    <a href="<?= $tool['url'] ?>register.json" target="_blank"><?= $tool['url'] ?>register.json</a></p>
<?php } ?>
    <h1>Server-wide URLs</h1>
    <p>App Store (Supports IMS Deep Linking/Content Item)<br/>
    <?= $CFG->wwwroot ?>/lti/store
    (Requires key and secret)
    </p>
    <p>App Store Canvas Configuration URL<br/>
    <a href="<?= $CFG->wwwroot ?>/lti/store/canvas-config.xml" target="_blank"><?= $CFG->wwwroot ?>/lti/store/canvas-config.xml</a></p>
</div>
<?php

if ( $fa_icon ) {
    echo('<i class="hidden-xs fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
}
echo("<b>".htmlent_utf8($title)."</b>\n");
echo('<p class="hidden-xs">'.htmlent_utf8($text)."</p>\n");
if ( $screen_shots ) {
    echo('<div class="row">'."\n");
    echo('<div class="col-sm-4">'."\n");
}
$script = isset($tool['script']) ? $tool['script'] : "index";
$path = $tool['url'];


echo("<p>\n");
echo('<a href="'.$rest_path->parent.'" class="btn btn-default" role="button">Back to Store</a>');
echo(' ');
if ( isset($_SESSION['gc_count']) ) {
    echo('<a href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
    echo('" title="Install in Classroom" target="iframe-frame"'."\n");
    echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
    echo('<img height=32 width=32 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
}
echo(' ');
echo('<a href="#" class="btn btn-default" role="button" onclick="showModal(\'Tool URLs\',\'url-dialog\');">Tool URLs</a>');
echo(' ');
echo('<a href="'.$rest_path->parent.'/test/'.urlencode($install).'" class="btn btn-default" role="button">Test</a> ');
echo("</p>\n");

echo("<ul>\n");
if ( isset($CFG->privacy_url) ) {
    echo('<li><p><a href="'.$CFG->privacy_url.'"
       target="_blank">'.__('Privacy Policy').'</a></p></li>'."\n");
}
$privacy_description = array(
 'anonymous' => __('Does not require student email or name.'),
 'public' => __('Requires student email and name.'),
 'name_only' => __('Requires student name but not email.')
);
$privacy_level = U::get($tool, 'privacy_level');
if ( is_string($privacy_level) ) {
    echo('<li><p>');
    echo(__('Privacy Level:'));
    echo(' ');
    $pdesc = U::get($privacy_description, $privacy_level);
    if ( $pdesc ) {
        echo($pdesc);
    } else {
        echo($privacy_level);
    }
    echo("</p></li>\n");
}
$placements = U::get($tool, 'placements');
if ( is_array($placements) && in_array('homework_submission', $placements) ) {
    echo('<li><p>');
    echo(__('Sends grades back to learning system'));
    echo("</p></li>\n");
}
$analytics = U::get($tool, 'analytics');
if ( is_array($analytics) && in_array('internal', $analytics) ) {
    echo('<li><p>');
    echo(__('Has internal analytics visualization'));
    echo("</p></li>\n");
}
if ( $CFG->launchactivity ) { 
    echo('<li><p>');
    echo(__('Can send analytics to learning record store.'));
    echo("</p></li>\n");
}
if ( is_array(U::get($tool, 'languages')) ) {
    echo('<li><p>');
    echo(__('Languages:'));
    echo(' ');
    $first = true;
    foreach(U::get($tool, 'languages') as $language) {
        if ( ! $first ) echo(', ');
        $first = false;
        echo(htmlentities($language));
    }
    echo("</p></li>\n");
}
if ( isset($CFG->sla_url) ) {
    echo('<li><p><a href="'.$CFG->sla_url.'"
       target="_blank">'.__('Service Level Agreement').'</a></p></li>'."\n");
}
if ( is_string(U::get($tool, 'license')) ) {
    echo('<li><p>');
    echo(__('License:'));
    echo(' ');
    echo(htmlentities(U::get($tool, 'license')));
}
$source_url = U::get($tool, 'source_url');
if ( is_string($source_url) ) {
    echo('<li><p><a href="'.$source_url.'"
       target="_blank">'.__('Source code').'</a></p></li>'."\n");
}

if ( isset($CFG->google_classroom_secret) ) {
    echo('<li><p>');
    echo(__('Supports'));
    echo(" Google Classroom");
    echo("</p></li>\n");
}

$launch_url = U::get($tool, 'url');
if ( $CFG->providekeys ) {
    echo('<li><p>');
    echo(__('Supports'));
    echo(" IMS Learning Tools Interoperability&reg (LTI)");
    echo("</p></li>\n");
}

echo('<li><p>');
echo(__('Supports'));
echo(' IMS Content Item&reg; / IMS Deep Linking');
echo("</p></li>\n");

echo('<li><p>');
echo(__('Supports'));
echo(' Canvas Configuration URL');
echo("</p></li>\n");


echo("</ul>\n");

if ( $screen_shots ) {
    echo("<!-- .col-sm-4--></div>\n");
    echo('<div class="col-sm-8">'."\n");
    echo("<center>\n");
    echo('<div class="bxslider">');
    foreach($screen_shots as $screen_shot ) {
        echo('<div><img title="'.htmlentities($title).'" onclick="$(\'#popup-image\').attr(\'src\',this.src);showModal(this.title,\'image-dialog\');" src="'.$screen_shot.'"></div>'."\n");
    }
    echo("</center>\n");
    echo("<!-- .col-sm-8--></div>\n");
    echo("</div>\n");
}

echo("<!-- \n");print_r($tool);echo("\n-->\n");
$OUTPUT->footerStart();
?>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.js">
</script>
<script>
$(document).ready(function() {
    $('.bxslider').bxSlider({
        useCSS: false,
        adaptiveHeight: false,
        slideWidth: "240px",
        infiniteLoop: false,
        maxSlides: 3
    });
});
</script>
<?php
    $OUTPUT->footerEnd();