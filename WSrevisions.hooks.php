<?php
/**
 * Hooks for WSrevision extension
 *
 * @file
 * @ingroup Extensions
 */

class WSrevisionsHooks {


	/**
	 * Make sure timezone is set to UTC
	 */
	public function __construct() {
		date_default_timezone_set( 'UTC' );
	}

	/**
	 * @return mysqli
	 */
	public static function db_open(): mysqli {
		global $wgDBserver;
		global $wgDBname;
		global $wgDBuser;
		global $wgDBpassword;

		$conn = new MySQLi( $wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname );
		$conn->set_charset( "utf8" );

		return $conn;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return void
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'ws_check_nme', 'WSrevisionsHooks::getRevisionsInfo' );
		$parser->setFunctionHook( 'ws_size_diff', 'WSrevisionsHooks::getRevisionsSizeDiff' );
		$parser->setFunctionHook( 'ws_get_revid', 'WSrevisionsHooks::getRevisionsID' );
	}

	/**
	 * WSrevision have there been Major revisions in the last x days starting from date ...
	 *
	 * @param Parser $parser [description]
	 *
	 * @return array|string [either ("Yes" or "No") or ("0" or (string) the amount of revisions)]
	 */
	public static function getRevisionsInfo( Parser &$parser ) {
		global $wgDBprefix;

		$options = WSrevisionsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		$pid     = WSrevisionsFunctions::getPageID( $options );
		if ( ! $pid ) {
			return "No page ID";
		}
		$count = WSrevisionsFunctions::getCount( $options );
		$date  = WSrevisionsFunctions::getDate( $options );
		if ( ! $date ) {
			return "invalid date";
		}
		$interval = WSrevisionsFunctions::getInterval( $options );
		$datum    = WSrevisionsFunctions::getPreviousDate( $interval, $date );
		$date     = WSrevisionsFunctions::convertDateForSQL( $date );

		$sql = "SELECT rev_timestamp FROM " . $wgDBprefix . "revision WHERE rev_minor_edit='0' AND rev_page='";
		$sql .= $pid . "' AND (rev_timestamp < '" . $date . "' AND rev_timestamp > '" . $datum . "')";

		$db = self::db_open();
		$q  = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			if ( $count ) {
				$lst = $q->num_rows;
			} else {
				$lst = "Yes";
			}
		} else {
			if ( $count ) {
				$lst = 0;
			} else {
				$lst = "No";
			}
		}

		$db->close();

		return array( strval( $lst ), 'noparse' => false );
	}

	/**
	 * Get size difference between revisions
	 *
	 * @param Parser $parser
	 *
	 * @return string|array size in bytes (As string, can be negative)
	 */
	public static function getRevisionsSizeDiff( Parser &$parser ) {
		global $wgDBprefix;

		$options = WSrevisionsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		$pid     = WSrevisionsFunctions::getPageID( $options );
		if ( ! $pid ) {
			return "No page ID";
		}
		$date = WSrevisionsFunctions::getDate( $options );
		if ( ! $date ) {
			return "invalid date";
		}
		$interval  = WSrevisionsFunctions::getInterval( $options );
		$datum     = WSrevisionsFunctions::getPreviousDate( $interval, $date );
		$date      = WSrevisionsFunctions::convertDateForSQL( $date );
		$ignore_me = WSrevisionsFunctions::ignoreMinorEdits( $options );


		$sql = "SELECT rev_len FROM " . $wgDBprefix . "revision WHERE " . $ignore_me . " rev_page='";
		$sql .= $pid . "' AND (rev_timestamp <= '" . $date . "') ORDER BY rev_timestamp DESC LIMIT 1";
		$db  = self::db_open();
		$q   = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			$row                 = $q->fetch_assoc();
			$length_current_page = $row['rev_len'];
		} else {
			$db->close();
			return "No current revision for this page";
		}

		$sql = "SELECT rev_len FROM " . $wgDBprefix . "revision WHERE " . $ignore_me . " rev_page='";
		$sql .= $pid . "' AND (rev_timestamp < '" . $datum . "') ORDER BY rev_timestamp DESC LIMIT 1";
		$q   = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			$row                     = $q->fetch_assoc();
			$length_previous_version = $row['rev_len'];
		} else {
			$db->close();
			return $length_current_page;
		}
		$db->close();
		$ret = $length_current_page - $length_previous_version;

		return array( strval( $ret ), 'noparse' => false );

	}

	/**
	 * Get a revision id
	 *
	 * @param Parser $parser
	 *
	 * @return string|array id as string
	 */
	public static function getRevisionsID( Parser &$parser ) {
		global $wgDBprefix;

		$options = WSrevisionsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		$pid     = WSrevisionsFunctions::getPageID( $options );
		if ( ! $pid ) {
			return "No page ID";
		}
		$date = WSrevisionsFunctions::getDate( $options );
		if ( ! $date ) {
			return "invalid date";
		}
		$interval  = WSrevisionsFunctions::getInterval( $options );
		$datum     = WSrevisionsFunctions::getPreviousDate( $interval, $date );
		$date      = WSrevisionsFunctions::convertDateForSQL( $date );
		$ignore_me = WSrevisionsFunctions::ignoreMinorEdits( $options );

		$sql = "SELECT rev_id FROM " . $wgDBprefix . "revision WHERE " . $ignore_me . " rev_page='";
		$sql .= $pid . "' AND (rev_timestamp < '" . $datum . "') ORDER BY rev_timestamp DESC LIMIT 1";
		$db  = self::db_open();
		$q   = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			$row = $q->fetch_assoc();
			$ret = $row['rev_id'];
		} else {
			$db->close();
			return '0';
		}
		$db->close();

		return array( strval( $ret ), 'noparse' => false );

	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value. If no = is provided,
	 * true is assumed like this: [name] => true
	 *
	 * @param array string $options
	 *
	 * @return array $results
	 */
	public static function extractOptions( array $options ): array {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) === 2 ) {
				$name             = trim( $pair[0] );
				$value            = trim( $pair[1] );
				$results[ $name ] = $value;
			}
			if ( count( $pair ) === 1 ) {
				$name             = trim( $pair[0] );
				$results[ $name ] = true;
			}
		}
		//Now you've got an array that looks like this:
		//  [foo] => "bar"
		//	[apple] => "orange"
		//	[banana] => true
		return $results;
	}
}
