<?php

// Add a new CMS main menu item for link checker
if(method_exists('LeftAndMain', 'add_menu_item')) {	// SS 2.3.0 or greater
	LeftAndMain::add_menu_item(
		'linkchecker',
		_t('LeftAndMain.LINKCHECKER', 'Link Checker'),
		'admin/linkchecker',
		'LinkCheckerAdmin'
	);
} else {		// Earlier versions (2.3.0 and lower)
	Object::addStaticVars('LeftAndMain', array(
		'extra_menu_items' => array(
			'Link Checker' => 'admin/linkchecker/',
		)
	));
}

// Add URL rule for the link checker
Director::addRules(100, array(
	'admin/linkchecker/$Action/$ID' => 'LinkCheckerAdmin',
));

?>