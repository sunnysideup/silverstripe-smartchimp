<?php
/**
 * Shows a widget encouraging users to sign up
 * by months or years.
 * 
 * @package blog
 */
class SmartChimpSignupWidget extends Widget
{

    public static $db = array(
        'BetterTitle' => 'Varchar',
        'Introduction' => 'Text',
        'TextOnButton' => 'Varchar'
    );
    
    public static $has_one = array(
        "NewsletterSignUpPage" => "SiteTree"
    );
    
    public static $title = 'Sign Up for Newsletter';

    public static $cmsTitle = 'Sign Up for Newsletter';
    
    public static $description = 'Links to newsletter signup';

    public function Title()
    {
        return $this->BetterTitle;
    }
    
    
    public function getCMSFields()
    {
        $objects = DataObject::get("SmartChimpSignupPage");
        $fields = new FieldSet();
        if ($objects) {
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
        } else {
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
