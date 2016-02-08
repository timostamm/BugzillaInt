<?php


/**
 */


if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');


require_once DOKU_PLUGIN.'syntax.php';

/**
 */
class syntax_plugin_bugzillaint_link extends DokuWiki_Syntax_Plugin {
	
	
	/**
	 * Gets plugin type
	 *
	 * @return string
	 */
	public function getType() {
		return 'substition';
	}


	/**
	 * Gets plugin sort order
	 *
	 * @return number
	 */
	public function getSort() {
		return 10;
	}


	/**
	 * Plugin mode connection
	 *
	 * @param string $mode
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('[Bb]ug\s[0-9]+(?:\s<[a-z_,^>]+>)?', $mode, 'plugin_bugzillaint_link');
	}
	

	/**
	 * Handle matches
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler){
		$matches = array();
		
		// found link
		if ( preg_match('/^[Bb]ug\s([0-9]+)(?:\s<([a-z_,^>]+)>)?$/', $match, $submatch) ) {
			$matches['link'] = array(
				'id' => $submatch[1],
				'extras' => isset($submatch[2]) ? trim($submatch[2]) : $this->getConf('link_default_extras')
			);
		}
		
		return $matches;
	}
	
	
	
	/**
	 * Render the output
	 *
	 * @param string $mode
	 * @param Doku_Renderer $renderer
	 * @param array $data
	 * @return boolean
	 */
	public function render($mode, Doku_Renderer $renderer, $data) {
		if ($mode != 'xhtml') return false;

		// render link
		if ( isset( $data['link'] ) ) {
			
			$render = $this->loadHelper('bugzillaint_render', false);
			$attrs = $render->renderAttributes( $data['link'] );
						
			$label = $data['link']['id'];
			$url = $this->getConf('show_baseurl') . (int) $data['link']['id'];
			
			$renderer->doc .= '<span class="bugzillalink loading" '.$attrs.'>'
							.   '<a class="bzref" href="' . htmlspecialchars($url) . '">' 
							.     htmlspecialchars($label) 
							.   '</a>'
							. '</span>';
		}
		
		return true;		
	}
	
	
}
