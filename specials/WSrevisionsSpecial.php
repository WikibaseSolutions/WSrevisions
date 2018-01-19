<?php
/**
 * Overview for the WSrevisions extension
 *
 * @file
 * @ingroup Extensions
 */

class WSrevisionsSpecial extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WSrevisionsSpecial' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).

	 */
	public function execute( $sub ) {
	    global $IP;
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'wsrevisions-title' ) );

	}
}
