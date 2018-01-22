<?php
/**
 * Hooks for WSrevision extension
 *
 * @file
 * @ingroup Extensions
 */

class WSrevisionsHooks {


	public function __construct() {
		date_default_timezone_set( 'UTC' );
	}

	public static function db_open() {
		global $wgDBserver;
		global $wgDBname;
		global $wgDBuser;
		global $wgDBpassword;

		$conn = new MySQLi( $wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname );
		$conn->set_charset( "utf8" );

		return $conn;
	}

	public static function db_real_escape( $txt ) {
		$db  = WSssdHooks::db_open();
		$txt = $db->real_escape_string( $txt );
		$db->close();

		return $txt;
	}

	/**
	 * When extension first called.. Setup hooks for magic words
	 *
	 * @param  Parser $parser [description]
	 *
	 * @return [type]         [description]
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'ws_check_nme', 'WSrevisionsHooks::getRevisionsInfo' );
		$parser->setFunctionHook( 'ws_size_diff', 'WSrevisionsHooks::getRevisionsSizeDiff' );
	}

	/**
	 * [WSrevision have there been Major revisions in th last x days starting from date ...]
	 *
	 * @param  Parser $parser [description]
	 *
	 * @return [string]         [either ("Yes" or "No") or (0 or the amount of revisions)]
	 */
	public static function getRevisionsInfo( Parser &$parser ) {
		global $wgDBprefix;

		$options = WSrevisionsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		$pid     = WSrevionsFunctions::getPageID( $options );
		if ( ! $pid ) {
			return "No page ID";
		}
		$count    = WSrevionsFunctions::getCount( $options );
		$date     = WSrevionsFunctions::getDate( $options );
		if( !$date ) return "invalid date";
		$interval = WSrevionsFunctions::getInterval( $options );
		$datum    = WSrevionsFunctions::getPreviousDate( $interval, $date );
		$date     = WSrevionsFunctions::convertDateForSQL( $date );


		$sql = "SELECT rev_timestamp FROM " . $wgDBprefix . "revision WHERE rev_minor_edit='0' AND rev_page='" . $pid . "' AND (rev_timestamp < '" . $date . "' AND rev_timestamp > '" . $datum . "')";

		$db  = self::db_open();
		$q   = $db->query( $sql );

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

		return array( $lst, 'noparse' => false );
	}

	/**
	 * Get size difference between revisions
	 * @param Parser $parser
	 *
	 * @return int size in bytes
	 */
	public static function getRevisionsSizeDiff( Parser &$parser ) {
		global $wgDBprefix;

		$options = WSrevisionsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		$pid     = WSrevionsFunctions::getPageID( $options );
		if ( ! $pid ) {
			return "No page ID";
		}
		$date     = WSrevionsFunctions::getDate( $options );
		if( !$date ) return "invalid date";
		$interval = WSrevionsFunctions::getInterval( $options );
		$datum    = WSrevionsFunctions::getPreviousDate( $interval, $date );
		$date     = WSrevionsFunctions::convertDateForSQL( $date );


		$sql = "SELECT rev_len FROM " . $wgDBprefix . "revision WHERE rev_minor_edit='0' AND rev_page='" . $pid . "' AND (rev_timestamp <= '".$date."') ORDER BY rev_timestamp DESC LIMIT 1";
		$db  = self::db_open();
		$q   = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			$row = $q->fetch_assoc();
			$length_current_page = $row['rev_len'];
		} else return "No current revision for this page";

		$sql = "SELECT rev_len FROM " . $wgDBprefix . "revision WHERE rev_minor_edit='0' AND rev_page='" . $pid . "' AND (rev_timestamp < '".$datum."') ORDER BY rev_timestamp DESC LIMIT 1";
		$q   = $db->query( $sql );

		if ( $q->num_rows > 0 ) {
			$row = $q->fetch_assoc();
			$length_previous_version = $row['rev_len'];
		} else return $length_current_page;
		$db->close();
		$ret = $length_current_page - $length_previous_version;

		return array( $ret, 'noparse' => false );

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
	public static function extractOptions( array $options ) {
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
