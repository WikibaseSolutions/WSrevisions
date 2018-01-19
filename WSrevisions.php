<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WSrevisions' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WSrevisions'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WSrevisionsAlias'] = __DIR__ . '/WSrevisions.i18n.alias.php';
	$wgExtensionMessagesFiles['WSrevisionsMagic'] = __DIR__ . '/WSrevisions.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for WSrevisions extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the WSrevisions extension requires MediaWiki 1.25+' );
}
