<?php
/**
 * Shows a widget encouraging users to sign up
 * by months or years.
 * 
 * @package blog
 */
class SmartChimpSignupWidget extends Widget {

	static $db = array(
		'BetterTitle' => 'Varchar',
		'Introduction' => 'Text',
		'TextOnButton' => 'Varchar'
	);
	
	static $has_one = array(
		"NewsletterSignUpPage" => "SiteTree"
	);
	
	static $title = 'Sign Up for Newsletter';

	static $cmsTitle = 'Sign Up for Newsletter';
	
	static $description = 'Links to newsletter signup';

	function Title() {
		return $this->BetterTitle;
	}
	
	
	function getCMSFields() {
		$objects = DataObject::get("SmartChimpSignupPage");
		$fields = new FieldSet();
		if($objects) {
			$fields->push(
				new TextField(
					'BetterTitle',
					'Title'
				)
			);
			$fields->push(
				new TextAreaField(
					'Introduction',
					'Introduction',
					$row = 5,
					$cols = 5
				)
			);
			$fields->push(
				new TextField(
					'TextOnButton',
					'Text On Button'
				)
			);
			$fields->push(
				new DropdownField(
					'NewsletterSignUpPageID',
					'Signup Page',
					$objects->toDropdownMap()
				)
			);
		}
		else {
			$fields->push(
				new LiteralField(
					'NoSmartChimpSignupPage',
					'<p>Please first create a sign-up page.</p>'
				)
			);
		}
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
	

}

