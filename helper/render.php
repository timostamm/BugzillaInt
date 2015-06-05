<?php


// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class helper_plugin_bugzillaint_render extends DokuWiki_Plugin {

	
	public function renderAttributes( $dataAttrs ) {
		
		$attrs = array(
			'bugzilla_baseurl' 						=> $this->getConf('bugzilla_baseurl'),
			'severity_threshold_red' 				=> $this->getConf('severity_threshold_red'),
			'severity_threshold_orange' 			=> $this->getConf('severity_threshold_orange'),
			'priority_threshold_red' 				=> $this->getConf('priority_threshold_red'),
			'priority_threshold_orange' 			=> $this->getConf('priority_threshold_orange'),
			'deadline_threshold_days_red'			=> $this->getConf('deadline_threshold_days_red'),
			'deadline_threshold_days_orange'		=> $this->getConf('deadline_threshold_days_orange'),
			'extra_depends_on' 						=> $this->getLang('extra_depends_on'),
			'extra_blocks' 							=> $this->getLang('extra_blocks'), 
			'color_new' 							=> $this->getConf('color_new'),
			'color_assigned' 						=> $this->getConf('color_assigned'),
			'color_reopened' 						=> $this->getConf('color_reopened'),
			'color_resolved_fixed' 					=> $this->getConf('color_resolved_fixed'),
			'color_resolved_invalid' 				=> $this->getConf('color_resolved_invalid'),
			'color_resolved_wontfix' 				=> $this->getConf('color_resolved_wontfix'),
			'color_resolved_duplicate' 				=> $this->getConf('color_resolved_duplicate'),
			'color_resolved_worksforme' 			=> $this->getConf('color_resolved_worksforme'),
			'color_resolved_moved' 					=> $this->getConf('color_resolved_moved')
		);
		
		return $this->makeAttributes( $dataAttrs, $attrs );
	}
	
	
	private function makeAttributes( $data, $attrs ) {
		$a = array();
		foreach ($data as $k => $v) {
			if ( $v === false ) continue;
			$a[] = 'data-' . $k . '="' . htmlspecialchars($v) . '"';
		}
		foreach ($attrs as $k => $v) {
			if ( $v === false ) continue;
			$a[] = '' . $k . '="' . htmlspecialchars($v) . '"';
		}
		return join(' ', $a);
	}
	
	
	
   
}