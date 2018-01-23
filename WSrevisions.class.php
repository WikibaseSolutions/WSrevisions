<?php

class WSrevionsFunctions {

	/**
	 * Get Wiki sql timeformat
	 * @return [string] [time format]
	 */
	public static function mysqldateformat() {
		return "YmdHis";
	}

	/**
	 * Return pageID from parser options
	 *
	 * @param  [array] $options         [parser options given by user]
	 *
	 * @return [string or boolean]      [pageid or false]
	 */
	public static function getPageID( $options ) {
		if ( isset( $options['pageid'] ) && $options['pageid'] != '' && is_numeric( $options['pageid'] ) ) {
			$pid = $options['pageid'];
		} elseif ( is_numeric( key( $options ) ) ) {
			$pid = key( $options );
		} else {
			$pid = false;
		}

		return $pid;
	}

	/**
	 * Return count from parser options
	 *
	 * @param  [array] $options [parser options given by user]
	 *
	 * @return [bool]           [true or false -- yes or no]
	 */
	public static function getCount( $options ) {
		if ( isset( $options['count'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return ignore Mindor Edits from parser options
	 *
	 * @param  [array] $options [parser options given by user]
	 *
	 * @return [bool]           [true or false -- yes or no]
	 */
	public static function ignoreMinorEdits( $options ) {
		if ( isset( $options['ignoreme'] ) ) {
			return "rev_minor_edit='0' AND ";
		} else {
			return "";
		}
	}

	/**
	 * check if user input is a valid date
	 *
	 * @param $date
	 *
	 * @return bool
	 */
	public static function isValidDate( $date ) {
		return (bool)strtotime( $date );
	}

	/**
	 * Return date from parser options
	 *
	 * @param  [array] $options   [parser options given by user]
	 *
	 * @return [string]           [date if given, otherwise current datetime]
	 */
	public static function getDate( $options ) {
		if ( isset( $options['date'] ) ) {
			if ( $options['date'] != '' && WSrevionsFunctions::isValidDate( $options['date'] ) ) {
				$date = $options['date'];
			} else return false;
		} else {
			$date = date( WSrevionsFunctions::mysqldateformat() );
		}

		return $date;
	}

	/**
	 * Return interval from parser options
	 *
	 * @param  [array] $options [parser options given by user]
	 *
	 * @return [int]            [time interval in days]
	 */
	public static function getInterval( $options ) {
		if ( isset( $options['interval'] ) && $options['interval'] != '' && is_numeric( $options['interval'] ) ) {
			$interval = $options['interval'];
		} else {
			$interval = 7;
		}

		return $interval;
	}

	/**
	 * Calculate previous date from date and interval
	 *
	 * @param  [int] $interval    [interval]
	 * @param  [string] $date     [datetime string]
	 *
	 * @return [string]           [datetime]
	 */
	public static function getPreviousDate( $interval, $date ) {
		$datum = date( WSrevionsFunctions::mysqldateformat(), strtotime( "-" . $interval . " days", strtotime( $date ) ) );

		return $datum;
	}

	/**
	 * Convert datetime string to wiki standard
	 *
	 * @param  [string] $date [datetime string]
	 *
	 * @return [string]       [date in wiki standard]
	 */
	public static function convertDateForSQL( $date ) {
		$date = date( WSrevionsFunctions::mysqldateformat(), strtotime( $date ) );

		return $date;
	}

}

?>
