<?php
/**
 * Hooks for WSLookup extension
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
	 /*       echo "<p>$sql</p>";
					foreach ($result->result as $res) {
							echo "<pre>";
							echo $res['total'];
							print_r($res);
							echo "</pre>";
					}
*/
				 //$tmp = $result->result();
				 // echo "<pre>";
				 // echo  $dbr->numRows($result);
				 // print_r($result->result);
				 // echo "</pre>";
					return $result;
			} catch (Exception $e) {
					echo "<pre>";
					echo $e;
					echo "</pre>";
					return false;
			}
	}

	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'ws_check_nme', 'WSrevisionsHooks::getRevisionsInfo' );
		$parser->setFunctionHook( 'ws_size_diff', 'WSrevisionsHooks::getRevisionsSizeDiff' );
	}
	/**
	 * [getContent description]
	 * @param  Parser $parser [description]
	 * @return [type]         [description]
	 */
	public static function getRevisionsInfo( Parser &$parser ) {
		// Called in MW text like this: {{#something: }}

		// For named parameters like {{#something: foo=bar | apple=orange | banana }}
		// See: https://www.mediawiki.org/wiki/Manual:Parser_functions#Named_parameters
		$mysqldateformat = "YmdHis";
		$options = WSrevisionsHooks::extractOptions( array_slice(func_get_args(), 1) );
		global $wgOut;
		global $wgDBprefix;
		//echo "<BR><BR><BR>";
		//print_r($options);
		if (isset($options['pageid']) && $options['pageid'] != '' && is_numeric($options['pageid']) ) {
			$pid = $options['pageid'];
		} elseif( is_numeric(key($options)) ) {
			$pid = key($options);
		} else	return "No page ID";

		if (isset($options['count']) ) {
			$count=true;
		} else $count=false;
		if (isset($options['date']) && $options['date'] != '') {
			//echo "<BR><BR><BR><BR>Date = ".$options['date']."<BR>";
			$date = $options['date'];
			//echo "<BR>Date = $date<BR>";
		} else {
			$date = date($mysqldateformat);
			//echo "<BR>Date = $date<BR>";
		}

		if (isset($options['interval']) && $options['interval'] != '' && is_numeric($options['interval'])) {
			$interval = $options['interval'];
		} else $interval= 7;

		$datum = date($mysqldateformat, strtotime("-".$interval." days", strtotime($date)));
		$date = date($mysqldateformat, strtotime($date));
		//echo "<BR>Datum = $datum<BR>";


			//$sql = "SELECT rev_timestamp FROM ".$wgDBprefix."revision WHERE rev_minor_edit='0' AND rev_page='".$options['pageid']."'";
			$sql = "SELECT rev_timestamp FROM ".$wgDBprefix."revision WHERE rev_minor_edit='0' AND rev_page='".$pid."' AND (rev_timestamp < '".$date."' AND rev_timestamp > '".$datum."')";
			//$db = WSrevisionsHooks::rawSelect    20180111143222  20180118110116
			$db = self::db_open();
			$q = $db->query($sql);
			//echo "<BR><BR><BR>";
			//echo "<p>tussen $date en $datum</p>";
			//echo $sql;
			//print_r($q);
			if ($q->num_rows > 0 ) {
				if($count) {
					$lst = $q->num_rows;
				} else $lst = "Yes";
/*
          while ($row = $q->fetch_assoc()) {
						echo "<pre>";
            print_r($row);
echo "</pre>";

           //if (WSssd::debug) echo "<p>-- Agency id:".$row['agency_id']."has count : ".$row['cc']."</p>";

          }

          //if (WSssd::debug) echo "<p>numbers of lines with issue id : ".$value .":: ".count($data)."</p>";
					*/
        } else {
				if($count) {
					$lst=0;
				}else $lst="No";
			}
        $db->close();
				//echo $where;


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
