<?php

class WSrevisionsFunctions {

	/**
	 * Get Wiki sql timeformat
	 * @return string time format
	 */
	public static function mysqldateformat(): string {
		return "YmdHis";
	}

	/**
	 * Return pageID from parser options
	 *
	 * @param array $options parser options given by user
	 *
	 * @return string|bool pageid or false
	 */
	public static function getPageID( array $options ) {
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
	 * @param array $options parser options given by user
	 *
	 * @return bool true or false -- yes or no
	 */
	public static function getCount( array $options ): bool {
		if ( isset( $options['count'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return ignore Minor Edits from parser options
	 *
	 * @param array $options [parser options given by user]
	 *
	 * @return string
	 */
	public static function ignoreMinorEdits( array $options ): string {
		if ( isset( $options['ignoreme'] ) ) {
			return "rev_minor_edit='0' AND ";
		} else {
			return "";
		}
	}

	/**
	 * check if user input is a valid date
	 *
	 * @param string $date
	 *
	 * @return int|false
	 */
	public static function isValidDate( string $date ) {
		return strtotime( $date );
	}

	/**
	 * Return date from parser options
	 *
	 * @param array $options [parser options given by user]
	 *
	 * @return string [date if given, otherwise current datetime]
	 */
	public static function getDate( array $options ): string {
		if ( isset( $options['date'] ) ) {
			if ( $options['date'] != '' && self::isValidDate( $options['date'] ) ) {
				$date = $options['date'];
			} else {
				return false;
			}
		} else {
			$date = date( self::mysqldateformat() );
		}

		return $date;
	}

	/**
	 * Return interval from parser options
	 *
	 * @param array $options [parser options given by user]
	 *
	 * @return int [time interval in days]
	 */
	public static function getInterval( array $options ): int {
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
	 * @param int $interval [interval]
	 * @param string $date [datetime string]
	 *
	 * @return string|bool datetime
	 */
	public static function getPreviousDate( int $interval, string $date ) {
		return date( self::mysqldateformat(),
			strtotime( "-" . $interval . " days", strtotime( $date ) ) );
	}

	/**
	 * Convert datetime string to wiki standard
	 *
	 * @param string $date [datetime string]
	 *
	 * @return string|bool [date in wiki standard]
	 */
	public static function convertDateForSQL( string $date ) {
		return date( self::mysqldateformat(), strtotime( $date ) );
	}
}
