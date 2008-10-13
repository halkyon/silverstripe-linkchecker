<?php

// Add a new CMS main menu item for link checker
if(method_exists('LeftAndMain', 'add_menu_item')) {	// SS 2.3.0 or greater
	LeftAndMain::add_menu_item(
		'linkcheck',
		_t('LeftAndMain.LINKCHECKER', 'Link Checker'),
		'admin/linkcheck',
		'LinkCheckAdmin'
	);
} else {		// Earlier versions (2.3.0 and lower)
	Object::addStaticVars('LeftAndMain', array(
		'extra_menu_items' => array(
			'Link Checker' => 'admin/linkcheck/',
		)
	));
}

// Add URL rule for the link checker
Director::addRules(100, array(
	'admin/linkcheck/$Action/$ID' => 'LinkCheckAdmin',
));

?>