<?php
/**
 * Hooks for WSrevision extension
 *
 * @file
 * @ingroup Extensions
 */

class WSrevisionsHooks {

	public static $db_prefix = "";

	public function __construct() {
		date_default_timezone_set('UTC');
			global $wgDBprefix;
			self::$db_prefix = $wgDBprefix;
	}

	public static function db_open() {
			global $wgDBserver;
			global $wgDBname;
			global $wgDBuser;
			global $wgDBpassword;

			$conn = new MySQLi($wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname);
			$conn->set_charset("utf8");
			return $conn;
	}

	public static function db_real_escape($txt) {
			$db = WSssdHooks::db_open();
			$txt = $db->real_escape_string($txt);
			$db->close();
			return $txt;

	}

	public static function rawSelect($sql) {
			$dbr = wfGetDB(DB_SLAVE);
			$dbr->IngoreErrors = true;
			try {
					$result = $dbr->query($sql, __METHOD__);
					return $result;
			} catch (Exception $e) {
					echo "<pre>";
					echo $e;
					echo "</pre>";
					return false;
			}
	}

/**
 * When extension first called.. Setup hooks for magic words
 * @param  Parser $parser [description]
 * @return [type]         [description]
 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'ws_check_nme', 'WSrevisionsHooks::getRevisionsInfo' );
		$parser->setFunctionHook( 'ws_size_diff', 'WSrevisionsHooks::getRevisionsSizeDiff' );
	}

	/**
	 * [WSrevision have there been Major revisions in th last x days starting from date ...]
	 * @param  Parser $parser [description]
	 * @return [string]         [either ("Yes" or "No") or (0 or the amount of revisions)]
	 */
	public static function getRevisionsInfo( Parser &$parser ) {
		$mysqldateformat = "YmdHis";
		$options = WSrevisionsHooks::extractOptions( array_slice(func_get_args(), 1) );
		global $wgDBprefix;

		if (isset($options['pageid']) && $options['pageid'] != '' && is_numeric($options['pageid']) ) {
			$pid = $options['pageid'];
		} elseif( is_numeric(key($options)) ) {
			$pid = key($options);
		} else	return "No page ID";

		if (isset($options['count']) ) {
			$count=true;
		} else $count=false;

		if (isset($options['date']) && $options['date'] != '') {
			$date = $options['date'];
		} else {
			$date = date($mysqldateformat);
		}

		if (isset($options['interval']) && $options['interval'] != '' && is_numeric($options['interval'])) {
			$interval = $options['interval'];
		} else $interval= 7;

		$datum = date($mysqldateformat, strtotime("-".$interval." days", strtotime($date)));
		$date = date($mysqldateformat, strtotime($date));
		$sql = "SELECT rev_timestamp FROM ".$wgDBprefix."revision WHERE rev_minor_edit='0' AND rev_page='".$pid."' AND (rev_timestamp < '".$date."' AND rev_timestamp > '".$datum."')";
		$db = self::db_open();
		$q = $db->query($sql);

		if ($q->num_rows > 0 ) {
			if($count) {
				$lst = $q->num_rows;
			} else $lst = "Yes";
    } else {
			if($count) {
				$lst=0;
			}else $lst="No";
		}

    $db->close();
		return array($lst, 'noparse' => false);
	  }


	public static function getRevisionsSizeDiff( Parser &$parser ) {

	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value. If no = is provided,
	 * true is assumed like this: [name] => true
	 *
	 * @param array string $options
	 * @return array $results
	 */
	public static function extractOptions( array $options ) {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) === 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
				$results[$name] = $value;
			}
			if ( count( $pair ) === 1 ) {
				$name = trim( $pair[0] );
				$results[$name] = true;
			}
		}
		//Now you've got an array that looks like this:
		//  [foo] => "bar"
		//	[apple] => "orange"
		//	[banana] => true
		return $results;
	}
}
