<?php
/**
 *
 * @author nicolaas[at]sunnysideup.co.nz
 * @description: adds a newsletter tickbox to member
 *
 **/

class MemberRoleSmartChimpExtension extends DataObjectDecorator
{
    protected static $sign_up_checkbox_label = 'Sign-up for newsletter';
    public static function set_sign_up_checkbox_label($v)
    {
        self::$sign_up_checkbox_label = $v;
    }
    public static function get_sign_up_checkbox_label()
    {
        return self::$sign_up_checkbox_label;
    }


    public function extraDBFields()
    {
        return array(
            'db' => array(
                'SignUpForMailChimpNewsletter' => 'Boolean'
            )
        );
    }


    public function updateCMSFields($fields)
    {
        if ($this->signupPage()) {
            $fields->addFieldToTab('Root.Main', new CheckboxField('SignUpForMailChimpNewsletter', self::get_sign_up_checkbox_label()));
        }
        return $fields;
    }

    /**
     * Return the member fields to be shown on {@link OrderForm}.
     * @return FieldSet
     */
    public function augmentEcommerceFields($fields)
    {
        if ($this->signupPage()) {
            $fields = $fields->push(new CheckboxField('SignUpForMailChimpNewsletter', self::get_sign_up_checkbox_label()));
        }
        return $fields;
    }


    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->owner->changed) {
            if (isset($this->owner->changed["SignUpForMailChimpNewsletter"])) {
                if ($page = $this->signupPage()) {
                    if ($this->owner->SignUpForMailChimpNewsletter) {
                        $outcome = $page->subscribe($this->owner->Email, $this->owner->FirstName, $this->owner->Surname);
                        if ($outcome !== true) {
                            $subject = "error subscribing ".$this->owner->Email.' ('.$this->owner->ID.'): -- '.$outcome.' -- on '.$page->Title.' ACTION REQUIRED';
                            USER_ERROR($subject, E_USER_WARNING);
                            $e = new Email($from = Email::getAdminEmail(), $to = Email::getAdminEmail(), $subject, $subject);
                            $e->send();
                        }
                    } else {
                        $outcome = $page->unsubscribe($this->owner->Email, $this->owner->FirstName, $this->owner->Surname);
                        if ($outcome !== true) {
                            $subject = "error unsubscribing ".$this->owner->Email.' ('.$this->owner->ID.'): -- '.$outcome.' -- on '.$page->Title.' ACTION REQUIRED';
                            USER_ERROR($subject, E_USER_WARNING);
                            mail(Email::getAdminEmail(), $subject, $subject);
                            $e = new Email($from = Email::getAdminEmail(), $to = Email::getAdminEmail(), $subject, $subject);
                            $e->send();
                        }
                    }
                } else {
                    USER_ERROR("There is no default newsletter page setup. This means the person can not be subscribed to the newsletter", E_USER_WARNING);
                }
            }
        }
    }

    public function signupPage()
    {
        return DataObject::get_one("SmartChimpSignupPage", "`IsDefaultList` = 1");
    }
}
