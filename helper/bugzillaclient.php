<?php


// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class BugzillaException extends Exception {}
class BugNumberInvalidException extends BugzillaException {}


class helper_plugin_bugzillaint_bugzillaclient extends DokuWiki_Plugin {


	private $login;
	private $password;

	private $includeFields = array('id', 'summary', 'status', 'resolution', 'deadline', 'priority', 'severity');
	

	
	public function setCredentials( $login, $password ) {
		$this->login = $login;
		$this->password = $password;
	}
	
	
	/**
	 * Fetch dependency tree
	 *
	 * May throw a BugzillaException, for example if the credentials are invalid.
	 *
	 * @param $id
	 * @return associative array of bugs, with id as key and an array of bugs
	 */
	public function getBugDependencyTrees( $ids, $depth, $extras ) {
		$f_ids = is_array($ids) ? $ids : explode(',', $ids);
		return $this->getDependenciesRecursive( $f_ids, $depth == -1 ? $this->getConf('tree_depth') : $depth, $extras );
	}
	private function getDependenciesRecursive( $ids, $depth, $extras ) {
		$includeFields = array_merge( $this->includeFields, $this->extrasToIncludeFields($extras), array('depends_on') );
		$response = $this->bugzillaRPC('Bug.get', array('ids' => $ids, 'include_fields' => $includeFields ));
		$result = array();
		foreach ($response['bugs'] as $bug) {
			$result[ $bug['id'] ] = $bug;
		}
		foreach ( $result as &$bug ) {
			if ( isset($bug['depends_on']) && count($bug['depends_on']) > 0 ) {
				$bug['depends_on'] = $this->getDependenciesRecursive( $bug['depends_on'], $depth--, $extras );
			} else {
				unset($bug['depends_on']);
			}
		}
		return $result;
	}
	
	
	/**
	 * May throw a BugzillaException, for example if the credentials are invalid.
	 *
	 */
	public function quicksearch( $query, $extras, $groupBy ) {
	
	
		// XXX should use bugzilla 5 quicksearch
	
	
		// defaults
		$options = array(
			'status' => explode(',', 'NEW,OPEN,UNCO,REOP,ASSI')
		);
	
		// parse query
		if ( strpos($query, 'ALL') === 0 ) {
			$options['status'] = explode(',', 'OPEN,UNCO,RESO,VERI,NEW,CONFIRMED,IN_PROGRESS,ASSIGNED');
		}
		if ( strpos($query, 'OPEN') === 0 ) {
			$options['status'] = explode(',', 'OPEN,REOP,UNCO,NEW,CONFIRMED,IN_PROGRESS,ASSIGNED');
		}
		if ( strpos($query, 'UNCO') === 0 ) {
			$options['status'] = explode(',', 'UNCO');
		}
		if ( strpos($query, 'RESO') === 0 ) {
			$options['status'] = explode(',', 'RESO');
		}
		if ( strpos($query, 'VERI') === 0 ) {
			$options['status'] = explode(',', 'VERI');
		}
		if ( strpos($query, 'CLO') === 0 ) {
			$options['status'] = explode(',', 'CLO');
		}
		if ( strpos($query, 'FIXED') === 0 ) {
			$options['resolution'] = explode(',', 'FIXED');
		}
		if ( strpos($query, 'INVA') === 0 ) {
			$options['resolution'] = explode(',', 'INVA');
		}
		if ( strpos($query, 'WONT') === 0 ) {
			$options['resolution'] = explode(',', 'WONTFIX');
		}
		if ( strpos($query, 'DUP') === 0 ) {
			$options['resolution'] = explode(',', 'DUPLICATE');
		}
		if ( strpos($query, 'WORKS') === 0 ) {
			$options['resolution'] = explode(',', 'WORKSFORME');
		}
		if ( strpos($query, 'MOVED') === 0 ) {
			$options['resolution'] = explode(',', 'MOVED');
		}
	
		if ( preg_match('/product:([A-Za-z0-9_,]+)/', $query, $m) ) {
			$options['product'] = explode(',', $m[1]);
		}
		if ( preg_match('/component:([A-Za-z0-9_,]+)/', $query, $m) ) {
			$options['component'] = explode(',', $m[1]);
		}
		if ( preg_match('/classification:([A-Za-z0-9_,]+)/', $query, $m) ) {
			if ( isset($options['product']) == false || count($options['product']) == 0 ) {
				$c = explode(',', $m[1]);
				$r = $this->bugzillaRPC('Classification.get', array('names' => $c ) );
				$pa = array();
				foreach ( $r['classifications'] as $c ) {
					foreach ($c['products'] as $p) {
						$pa[] = $p['name'];
					}
				}
				$options['product'] = $pa;
			}
		}
		if ( preg_match('/status:([A-Za-z0-9,]+)/', $query, $m) ) {
			$options['status'] = explode(',', $m[1]);
		}
		if ( preg_match('/resolution:([A-Za-z0-9,]+)/', $query, $m) ) {
			$options['resolution'] = explode(',', $m[1]);
		}
		if ( preg_match('/summary:([A-Za-z0-9,]+)/', $query, $m) ) {
			$options['summary'] = explode(',', $m[1]);
		}
		if ( preg_match('/assigned_to:([A-Za-z0-9_,@.]+)/', $query, $m) ) {
			$options['assigned_to'] = explode(',', $m[1]);
		}
		if ( preg_match('/creator:([A-Za-z0-9_,@.]+)/', $query, $m) ) {
			$options['creator'] = explode(',', $m[1]);
		}
	
	
		// fix status
		$options['status'] = array_map(function($value) {
			$v = trim($value);
			$p = substr($v, 0, 3);
			$t = array(
					'ASS' => 'ASSIGNED',
					'UNC' => 'UNCONFIRMED',
					'REO' => 'REOPENED',
					'RES' => 'RESOLVED',
					'VER' => 'VERIFIED',
					'CLO' => 'CLOSED',
					'INV' => 'INVALID',
					'WON' => 'WONTFIX',
					'DUP' => 'DUPLICATE',
					'WOR' => 'WORKSFORME'
			);
			if ( isset($t[$p]) ) {
				return $t[$p];
			}
			return $v;
		}, $options['status'] );
		
		
		// add fixed params
		$options['limit'] = 100;
		$options['include_fields'] = array_merge( $this->includeFields, $this->extrasToIncludeFields($extras));
		if ( isset($groupBy) ) {
			$options['include_fields'][] = $groupBy;
		}
		
		
		// run search
		$response = $this->bugzillaRPC('Bug.search', $options );
		$result = in_array('dependencies', $extras) ? $this->fetchDependencies( $response['bugs'] ) : $response['bugs'];
		
		
		// group by
		if ( isset($groupBy) ) {
			usort($result, function ($a, $b) use ($groupBy) {
				$c = strcmp( $a[$groupBy], $b[$groupBy] );
				if ( $c == 0 ) return $a['id'] < $b['id'] ? -1 : 1;
				return $c;
			});
		}
		
		
		return $result;
	}
	
	
	
	/**
	 * Fetch info about several bugs.
	 *
	 * May throw a BugzillaException, for example if the credentials are invalid.
	 *
	 * @param $ids
	 * @return associative array of bugs, with id as key and an array of bugs
	 */
	public function getBugsInfos( $ids, $extras ) {
	
		// setup fields to include
		$includeFields = array_merge( $this->includeFields, $this->extrasToIncludeFields($extras) );
	
		// normalize param
		$f_ids = is_array($ids) ? $ids : explode(',', $ids);
	
		// fetch bug infos
		try {
			$response = $this->bugzillaRPC('Bug.get', array('ids' => $f_ids, 'include_fields' => $includeFields));
			$result = array();
			foreach ($response['bugs'] as $bug) {
				$result[ $bug['id'] ] = $bug;
			}
	
		} catch (BugNumberInvalidException $e) {
			// a bug does not exist, but we still need info about all the other bugs
			$result = array();
			foreach ($f_ids as $f_id) {
				try {
					$response = $this->bugzillaRPC('Bug.get', array('ids' => $f_id, 'include_fields' => $includeFields));
					$result["$f_id"] = $response['bugs'][0];
				} catch (BugNumberInvalidException $ee) {
					$result["$f_id"] = array( 'id' => "$f_id", 'error' => $ee->getMessage() );
				}
			}
		}
	
		// fetch extra dependency info?
		return in_array('dependencies', $extras) ? $this->fetchDependencies( $result ) : $result;
	}
	
	
	private function extrasToIncludeFields($extras) {
		$fields = array();
		foreach ($extras as $e) {
			if ( $e == 'dependencies' ) {
				$fields[] = 'depends_on';
				$fields[] = 'blocks';
			} else if ( $e == 'assigned_to' ) {
				$fields[] = 'assigned_to';
			} else if ( $e == 'lastchange' ) {
				$fields[] = 'last_change_time';
			} else if ( $e == 'deadline' ) {
				$fields[] = 'deadline';
			} else if ( $e == 'status' ) {
				$fields[] = 'status';
				$fields[] = 'resolution';
			} else if ( $e == 'version' ) {
				$fields[] = 'version';
			} else if ( $e == 'priority' ) {
				$fields[] = 'priority';
			} else if ( $e == 'severity' ) {
				$fields[] = 'severity';
			} else if ( $e == 'time' ) {
				$fields[] = 'estimated_time';
				$fields[] = 'remaining_time';
				$fields[] = 'actual_time';
			} else if ( $e == 'classification' ) {
				$fields[] = 'classification';
			} else if ( $e == 'product' ) {
				$fields[] = 'product';
			} else if ( $e == 'component' ) {
				$fields[] = 'component';
			}
		}
		return $fields;
	}
	
	
	/**
	 * Takes an array of bugs and adds the properties "depends_on_resolved" and "blocks_resolved".
	 *
	 * @param array $bugs - associative array with bug id as key
	 */
	protected function fetchDependencies( &$bugs ) {
	
		// collect all dependencies, blocks and depends_on
		$dependencies = array();
		foreach ($bugs as $bug) {
			if (count($bug['blocks']) > 0) {
				$dependencies = array_merge($dependencies, $bug['blocks']);
			}
			if (count($bug['depends_on']) > 0) {
				$dependencies = array_merge($dependencies, $bug['depends_on']);
			}
		}
	
		// fetch status of all dependencies
		$response = $this->bugzillaRPC('Bug.get', array('ids' => $dependencies, 'include_fields' => array('id', 'status')));
	
		// collect resolved dependencies
		$resolved_dependencies = array();
		foreach ($response['bugs'] as $bug) {
			if ( $bug['status'] == 'RESOLVED' ) {
				$resolved_dependencies[] = $bug['id'];
			}
		}
	
		// add properties
		foreach ($bugs as &$bug) {
			if ( isset($bug['depends_on']) ) {
				$bug['depends_on_resolved'] = array();
				foreach ( $bug['depends_on'] as $id ) {
					if ( in_array($id, $resolved_dependencies) ) $bug['depends_on_resolved'][] = $id;
				}
			}
			if ( isset($bug['blocks']) ) {
				$bug['blocks_resolved'] = array();
				foreach ( $bug['blocks'] as $id ) {
					if ( in_array($id, $resolved_dependencies) ) $bug['blocks_resolved'][] = $id;
				}
			}
		}
	
		return $bugs;
	}
	
	
	protected function bugzillaRPC( $method, $parameters ) {
	
		// prep params
		$params = array();
		if ( !!$this->login && !!$this->password ) {
			$params["Bugzilla_login"] = $this->login;
			$params["Bugzilla_password"] = $this->password;
		}
		foreach ($parameters as $k => $v) {
			$params[ $k ] = $v;
		}
	
		// make request
		$context = stream_context_create(array('http' => array(
				'method' => "POST",
				'header' => array("Content-Type: text/xml"),
				'content' => $this->xmlrpc_encode_request($method, $params)
		)));
		$response = @file_get_contents($this->getConf('bugzilla_baseurl').'/xmlrpc.cgi', false, $context);
	
		// check response and parse result
		if ( $response === false ) {
			$err = error_get_last();
			throw new Exception($err['message']);
		}
		$result = $this->xmlrpc_decode( $response );
	
		// check result for errors
		if ( $this->xmlrpc_is_fault($result) ) {
			if ( $result['faultCode'] == 101) {
				throw new BugNumberInvalidException($result['faultString'], $result['faultCode']);
			} else {
				throw new BugzillaException($result['faultString'], $result['faultCode']);
			}
		} else if ( count( $result['faults'] ) > 0 ) {
			throw new BugzillaException("Error: " . print_r($result['faults']) );
		}
	
		// return result
		return $result;
	}
	
	
	
	protected function xmlrpc_encode_request($method, $params) {
		$x = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
		$x .= '<methodCall>';
		$x .= '<methodName>' . htmlspecialchars($method) . '</methodName>';
		$x .= '<params><param><value><struct>';
		foreach ( $params as $k => $v ) {
			$x .= '<member>';
			$x .= '<name>' . htmlspecialchars( $k ) . '</name>';
			$x .= '<value>';
			if ( is_array($v) ) {
				$x .= '<array><data>';
				foreach ( $v as $i ) {
					$x .= '<value>';
					$x .= '<string>' . htmlspecialchars( $i ) . '</string>';
					$x .= '</value>';
				}
				$x .= '</data></array>';
			} else {
				$x .= '<string>' . htmlspecialchars( $v ) . '</string>';
			}
			$x .= '</value>';
			$x .= '</member>';
		}
		$x .= '</struct></value></param></params>';
		$x .= '</methodCall>';
		return $x;
	}
	
	
	protected function xmlrpc_is_fault($result) {
		return isset($result['faultString']) && isset($result['faultCode']);
	}
	
	
	protected function xmlrpc_decode($text) {
		$x = simplexml_load_string($text);
		if ( $x->fault->value->struct ) {
			return $this->xmlrpc_decode_node( $x->fault->value->struct );
		}
		return $this->xmlrpc_decode_node( $x->params->param->value->struct );
	}
	
	
	private function xmlrpc_decode_node($node) {
		if ( $node->getName() == 'int' ) {
			return (int) $node->__toString();
		}
		else if ( $node->getName() == 'string' ) {
			return $node->__toString();
		}
		else if ( $node->getName() == 'array' ) {
			$a = array();
			foreach ( $node->data->value as $i ) {
				$a[] = $this->xmlrpc_decode_node( $i->children()[0] );
			}
			return $a;
		}
		else if ( $node->getName() == 'struct' ) {
			$a = array();
			foreach ( $node->member as $i ) {
				$k = $i->name->__toString();
				$v = $this->xmlrpc_decode_node( $i->value->children()[0] );
				$a[ $k ] = $v;
			}
			return $a;
		}
	}
	
   
}