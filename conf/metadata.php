<?php
/**
 */

$meta['bugzilla_login'] = array('string');
$meta['bugzilla_password'] = array('password');
$meta['bugzilla_baseurl'] = array('string');
$meta['tree_depth'] = array('numeric', '_min'=>2, '_max'=>10);
$meta['link_default_extras'] = array('string');
$meta['tree_default_extras'] = array('string');
$meta['list_default_extras'] = array('string');
$meta['severity_threshold_red']  = array('multichoice','_choices' => array('-','blocker','major','normal','minor','enhancement'));
$meta['severity_threshold_orange']  = array('multichoice','_choices' => array('-','blocker','major','normal','minor','enhancement'));
$meta['priority_threshold_red']  = array('multichoice','_choices' => array('-','P1','P2','P3','P4','P5'));
$meta['priority_threshold_orange']  = array('multichoice','_choices' => array('-','P1','P2','P3','P4','P5'));
$meta['deadline_threshold_days_red'] = array('numeric');
$meta['deadline_threshold_days_orange'] = array('numeric');
$meta['color_new'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_assigned'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_reopened'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_fixed'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_invalid'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_wontfix'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_duplicate'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_worksforme'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
$meta['color_resolved_moved'] = array('multichoice','_choices' => array('gray','red','orange','green','lightblue','darkblue'));
