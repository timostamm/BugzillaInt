<?php


/**
 */


if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');


require_once DOKU_PLUGIN.'syntax.php';

/**
 */
class syntax_plugin_bugzillaint_list extends DokuWiki_Syntax_Plugin {
	
	
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
		$this->Lexer->addSpecialPattern('<[Bb]uglist\s+[^>]*>', $mode, 'plugin_bugzillaint_list');
	}
	

	/**
	 * Handle matches
	 */
	public function handle($match, $state, $pos, &$handler){
		$matches = array();

		// found list
		if ( preg_match('/<[Bb]uglist\s+([^>]*)>/', $match, $submatch) ) {
			$matches['list'] = array(
				'quicksearch' => $submatch[1],
				'group_by' => preg_match('/group_by:([a-z_]+)/i', $match, $found) ? $found[1] : null,
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
	public function render($mode, &$renderer, $data) {
		if ($mode != 'xhtml') return false;

		// render list
		if ( isset( $data['list'] ) ) {
			
			$render = $this->loadHelper('bugzillaint_render', false);
			$attrs = $render->renderAttributes( $data['list'] );
			
			$renderer->doc .= '<div class="bugzillalist loading" '.$attrs.'>'
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
