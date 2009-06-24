<?php

// Add a new CMS main menu item for link checker
if(!class_exists('CMSMenu')) {
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
