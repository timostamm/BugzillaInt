<?php

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class action_plugin_bugzillaint_fetch extends DokuWiki_Action_Plugin {

	
    /**
     * @param Doku_Event_Handler $controller The plugin controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax');
    }

    
    /**
     * @param Doku_Event $event
     */
    public function handle_ajax(Doku_Event $event) {
        global $INPUT;
        global $USERINFO;
    	
        // event handling
    	if ($event->data != 'plugin_bugzillaint_fetch') {
        	return;
        }
        $event->preventDefault();
        $event->stopPropagation();
		
		try {
			
			$bugzillaclient = $this->loadHelper('bugzillaint_bugzillaclient', false);
			
			// TODO good point to extend auth to user-specific auth
			$bugzillaclient->setCredentials( 
				$this->getConf('bugzilla_login'), 
				$this->getConf('bugzilla_password')
			);
	        
	        $result = $this->fetchData( 
	        	$bugzillaclient, 
	        	$INPUT->param('lists', array(), true), 
	        	$INPUT->param('trees', array(), true),
	        	$INPUT->param('links', array(), true)
	        );
	
	        $json = new JSON();
	        header('Content-Type: application/json');
	        print $json->encode( $result );
		
        } catch (Exception $e) {
        	http_status(500);
        	header('Content-Type: text/plain');
        	print $e->getMessage();
        }
        
    }
    

    private function fetchData( $bugzillaClient, $lists, $trees, $links ) {
    	$result = array();
    	 
    	if ( count($lists) > 0 ) {
    		$result['lists'] = array();
    		foreach ( $lists as $i ) {
    			$result['lists'][] = $bugzillaClient->quicksearch( $i['quicksearch'], explode(',', $i['extras']), $i['group_by'] );
    		}
    	}
    	 
    	if ( count($trees) > 0 ) {
    		$result['trees'] = array();
    		foreach ( $trees as $i ) {
    			$tree = $bugzillaClient->getBugDependencyTrees( $i['id'], $i['depth'], explode(',', $i['extras']) );
    			foreach ($tree as $k => $v) {
    				$result['trees'][ $k ] = $v;
    			}
    		}
    	}
    	 
    	if ( count($links) > 0 ) {
    		$extras = array();
    		$ids = array();
    		foreach ( $links as $i ) {
    			$ids[] = $i['id'];
    			$extras = array_merge( $extras, explode(',', $i['extras']) );
    		}
    		$result['links'] = $bugzillaClient->getBugsInfos( $ids, $extras);
    	}
    	 
    	return $result;
    }
    
    
}