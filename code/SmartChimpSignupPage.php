<?php

class SmartChimpSignupPage extends Page {

	static $icon = "smartchimp/images/treeicons/SmartChimpSignupPage";

	static $db = array(
		//	@todo:	provide optional dropdown for entering username/password??
		'MCApiKey' => 'Varchar(50)',	//	api_key
		'MCListKey' => 'Varchar(50)',	//	list_unique_id
		'MCSuccessContent'	=> 'HTMLText',
		"DoubleOptin" => "Boolean",
		"SendWelcomeMail" => "Boolean",
		"SendGoodbey" => "Boolean",
		"SendDeleteNotification" => "Boolean",
		"IsDefaultList" => "Boolean",
		"FirstFieldRequired" => "Boolean",
		"LastFieldRequired" => "Boolean"
	);

	static $has_many = array(
		"SmartChimpNewsletters" => "SmartChimpNewsletter"
	);

	public static $defaults = array(
		"DoubleOptin" => 1,
		"SendWelcomeMail" => 0,
		"SendGoodbey" => 0,
		"IsDefaultList" => 1
	);

	static $mc_api_version = '1.2.1';

	protected static $api = null;

	function getAPI() {
		if($this->MCApiKey && $this->MCListKey) {
			if(!(self::$api instanceOf MCAPI)) {
				require_once(Director::baseFolder().'/smartchimp/thirdparty/mcapi/'.self::$mc_api_version.'/MCAPI.class.php');
				self::$api = new MCAPI("$this->MCApiKey");
			}
			return self::$api;
		}
	}


	function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Content.SentNewsletters',array(
			new LiteralField('HowToRetrieve', '<p>To retrieve sent newsletters, simply save this page or <a href="'.$this->Link("update").'?flush=1">click here</a>.</p>'),
			$this->SmartChimpNewslettersTable()
		));
		$fields->addFieldsToTab('Root.Content.MailChimpConfig',array(
			new CheckboxField('IsDefaultList', 'This is the default newsletter'),
			new TextField('MCApiKey', _t('SmartChimp.MCAPIKEY', 'API Key')),
			new TextField('MCListKey', _t('SmartChimp.MCLISTKEY', 'Unique ID for List')),
			new HTMLEditorField('MCSuccessContent', _t('SmartChimp.MCSuccessContent', 'Signup Success Content'))
		));
		$fields->addFieldsToTab('Root.Content.Subscribe',array(
			new CheckboxField('DoubleOptin', "Double Opt-In Process (send email to confirm registration)"),
			new CheckboxField('SendWelcomeMail', "Send Welcome Mail")
		));
		$fields->addFieldsToTab('Root.Content.Unsubscribe',array(
			new CheckboxField('SendGoodbey', "Send Goodbey Email"),
			new CheckboxField('SendDeleteNotification', "Send notification of unsubscribe")
		));
		$fields->addFieldsToTab("Root.Content.RequiredFields",array(
			new CheckboxField('FirstRequired', "First name is required"),
			new CheckboxField('LastRequired', "Last name is required")
		));
		$this->extend('updateSmartChimpCMSFields');

		return $fields;
	}

	function SmartChimpNewslettersTable() {
		$table = new HasManyComplexTableField(
			$controller = $this,
			$name = "SmartChimpNewsletters",
			$sourceClass = "SmartChimpNewsletter",
			$fieldList = null,
			$detailFormFields = null,
			$sourceFilter = "ParentID = ".$this->ID
		);
		$table->setPageSize(100);
		$table->setPermissions(array('export', 'show', 'edit', 'delete'));
		return $table;
	}

	function SmartChimpNewslettersShow() {
		return DataObject::get("SmartChimpNewsletter", "\"ParentID\" = ".$this->ID." AND \"Hide\" <> 1");
	}

	function RetrieveCampaigns() {
		$dos = new DataObjectSet();
		SmartChimpNewsletter::clean_up_characters();
		$api = $this->getAPI();
		if($api && $this->ID) {
			$campaignArray = $api->campaigns(array("list_id" => $this->MCListKey));
			if(is_array($campaignArray) && count($campaignArray)) {
				foreach($campaignArray as $key => $campaign) {
					if($campaign["status"] == "sent") {
						$obj = DataObject::get_one("SmartChimpNewsletter", "`ParentID` = ".$this->ID." AND `CampaignID` = '".$campaign["id"]."'");
						if($obj) {
							//do nothing
						}
						else {
							$content =  $api->campaignContent($campaign["id"]);
							if($content) {
								$obj = new SmartChimpNewsletter();
								$obj->ParentID = $this->ID;
								$obj->Date = $campaign["send_time"];//
								$obj->Title = $campaign["title"];//
								$obj->Subject = $campaign["subject"];//
								$obj->PermaLink = $campaign["archive_url"];//
								$obj->CampaignID = $campaign["id"];//
								$obj->WebID = $campaign["web_id"];//
								$obj->Status = $campaign["status"];//should be sent!
								//$obj->TextContent = $content["text"] ;//html
								//$obj->HTMLContent = $content["html"];//text
							}
							else {
								user_error(" could not retrieve content for newsletter with subject: ".$campaign["subject"]." AND ID".$campaign["id"], E_USER_NOTICE);
							}
							$obj->write();
						}
					}
				}
			}
		}
	}


	function subscribe($email, $firstname, $lastname) {
		$api = $this->getAPI();
		if($api) {
			$mergeVars = array(
				'FNAME'	=> $firstname,
				'LNAME'	=> $lastname
			);
			//NOTE: update existing is set to false to not accidentally resubscribe someone.
			if(true === $api->listSubscribe($id = $this->MCListKey, $email, $mergeVars, $email_type='html', $this->DoubleOptin, $update_existing=false, $replace_interests=true, $this->SendWelcomeMail)) {
				return true;
			}
			else {
				return $api->errorMessage;
			}
		}
	}

	function unsubscribe($email) {
		$api = $this->getAPI();
		if($api) {
			if(true === $api->listUnsubscribe($id = $this->MCListKey, $email, $delete_member=false, $this->SendGoodbey, $this->SendDeleteNotification)) {
				return true;
			}
			else {
				return $api->errorMessage;
			}
		}
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($this->IsDefaultList && $this->ID) {
			$others = DataObject::get("SmartChimpSignupPage", "`SmartChimpSignupPage`.`ID` <> ".intval($this->ID)." AND `SmartChimpSignupPage`.`IsDefaultList` = 1");
			if($others) {
				foreach($others as $other) {
					$other->IsDefaultList = 0;
					$other->writeToStage('Stage');
					$other->publish('Stage', 'Live');
				}
			}
		}
	}

	function onAfterWrite() {
		parent::onAfterWrite();
		$this->RetrieveCampaigns();
	}



	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$pages = DataObject::get("SmartChimpSignupPage");
		if($pages) {
			if($pages->count() == 1) {
				foreach($pages as $page) {
					if(!$page->IsDefaultList) {
						$page->IsDefaultList = 1;
						$page->writeToStage('Stage');
						$page->publish('Stage', 'Live');
						Database::alteration_message($page->ClassName.' created/updated: added IsDefaultList = true setting as there is only one SmartChimpSignupPage', 'edited');
					}
				}
			}
		}
	}


}


class SmartChimpSignupPage_Controller extends Page_Controller {

	static $allowed_actions = array("Form", "update");

	function init() {
		parent::init();
	}


	function update() {
		$this->RetrieveCampaigns();
		return array();
	}

	function Form() {
		if (Session::get('SmartChimp.SUCCESS')) {
			Session::clear('SmartChimp.SUCCESS');
			return false;
		}
		$requiredFields = new RequiredFields('email');
		if($this->FirstFieldRequired) {
			$requiredFields->appendRequiredFields(array('fname'));
		}
		if($this->LastFieldRequired) {
			$requiredFields->appendRequiredFields(array('lname'));
		}
		$form = new Form($this, 'Form',
			new FieldSet(
				new TextField('fname', 'First name'),
				new TextField('lname', 'Last name'),
				new TextField('email', 'Email address')
			),
			new FieldSet(
				new FormAction('SignupAction', 'Sign up')
			),
			$requiredFields
		);
		$this->extend('updateSmartChimpForm', $form);
		return $form;
	}

	function ShortForm() {
		if (Session::get('SmartChimp.SUCCESS')) {
			Session::clear('SmartChimp.SUCCESS');
			return false;
		}
		$form = new Form($this, 'Form',
			new FieldSet(
				new TextField('email', 'Email Address')
			),
			new FieldSet(
				new FormAction('SignupAction', 'Sign up')
			),
			new RequiredFields('email')
		);

		$this->extend('updateSmartChimpForm', $form);

		return $form;
	}

	public function mcsuccess() {
		if (Session::get('SmartChimp.SUCCESS')) {
			$this->Content = $this->MCSuccessContent;
		}
		return array();
	}

	function SignupAction($raw_data, $form) {
		$data = Convert::raw2sql($raw_data);
		$outcome = $this->subscribe($data['email'], $data['fname'],  $data['lname']);
		if (true === $outcome) {
			Session::set('SmartChimp.SUCCESS', true);
			return $this->mcsuccess();
		}
		else {
			$form->sessionMessage($outcome, 'warning');
			Director::redirectBack();
		}
	}



}

