<?php


/**
 */


if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');


require_once DOKU_PLUGIN.'syntax.php';

/**
 */
class syntax_plugin_bugzillaint_tree extends DokuWiki_Syntax_Plugin {
	
	
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
		$this->Lexer->addSpecialPattern('<[Bb]ugtree\s+[0-9]+[^>]*>', $mode, 'plugin_bugzillaint_tree');
	}
	

	/**
	 * Handle matches
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler){
		$matches = array();

		// found tree
		if ( preg_match('/<[Bb]ugtree\s+([0-9]+)[^>]*>/', $match, $submatch) ) {
			$matches['tree'] = array(
				'id' => $submatch[1],
				'depth' => preg_match('/depth:([0-9])/i', $match, $found) ? $found[1] : $this->getConf('tree_depth'),
				'hideResolved' => !!preg_match('/hideResolved/i', $match, $found),
				'extras' => preg_match('/extras:([a-z_,]+)/i', $match, $found) ? trim($found[1]) : $this->getConf('list_default_extras') 
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

		// render tree
		if ( isset( $data['tree'] ) ) {
			
			$render = $this->loadHelper('bugzillaint_render', false);
			$attrs = $render->renderAttributes( $data['tree'] );
			
			$label = $data['tree']['id'];
			$url = $this->getConf('show_baseurl') . $data['tree']['id'];
			
			$renderer->doc .= '<div class="bugzillatree loading" '. $attrs .'>'
							. '  <p class="heading">'
						    . '    <a class="bzref" href="'.htmlspecialchars($url).'">'.htmlspecialchars($label).'</a>'
						    . '    <span class="blocked-by-msg">' .htmlspecialchars($this->getLang('msg_blocked_by')). '</span>'
						    . '  </p>'
							. '  <ul>'
							. '    <li class="placeholder"></li>'
							. '    <li class="empty-msg">'
							. '      <div class="li">' .htmlspecialchars($this->getLang('msg_empty')). '</div>'
							. '    </li>'
							. '  </ul>'
							. '</div>';
		}
		
		return true;		
	}
	
	
}
