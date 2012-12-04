<?php

/*
 *@author: nicolaas [at] sunny side up . co . nz
 *
 **/


class SmartChimpNewsletter extends DataObject {

	static $has_one = array(
		"Parent" => "SmartChimpSignupPage"
	);

	static $db = array(
		"Hide" => "Boolean",	
		"CampaignID" => "Varchar(30)",//id
		"Date" => "Datetime",//send_time
		"Title" => "Varchar(255)",//title
		"Subject" => "Varchar(255)",//title
		"PermaLink" => "Varchar(255)",//archive_url
		"WebID" => "Int", //web_id
		"Status" => "Varchar(20)", //should be sent!
		"TextContent" => "Text", //html
		"HTMLContent" => "HTMLText", //text
		"Done" => "Boolean"
	);

	

	static $indexes = array(
		"CampaignID" => true
	);

	static $casting = array(
		"DateNice" => "Text",
		"ShowOrHide" => "Text",
		"YearMonth" => "Int",
		"Month" => "Int",
		"Link" => "Text"
	);

	function getYearMonth() {
		return $this->obj('Date')->format("F")." ".$this->obj('Date')->format("Y");
	}

	function getDateNice() {
		return $this->obj('Date')->Long();
	}

	function ShowOrHide() {
		return $this->Hide ? "hide" : "show";
	}

	function Link(){
		return str_replace('&amp;', '&', $this->PermaLink);
	}

	function requiredDefaultRecords() {
		parent::requiredDefaultRecords();
	}
	public static $default_sort = "\"Date\" DESC";
	public static $defaults = array();//use fieldName => Default Value
	public static $searchable_fields = array("Title" => "PartialMatchFilter");
	public static $field_labels = array("Title" => "Title");
	public static $summary_fields = array("Title" => "Title", "DateNice" => "Date", "ShowOrHide" => "ShowOrHide");
	public static $singular_name = "MailChimp Newsletter";
	public static $plural_name = "MailChimp Newsletters";

	static function clean_up_characters() {
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'’','\'');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'…','');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'“','\"');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'“','\"');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'–','-');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'�','\"');");
		DB::query("UPDATE `SmartChimpNewsletter` SET `TextContent` = REPLACE(`TextContent`,'� ','\"');");
	}

	static $field_types = array(
		"CampaignID" => "TextField",//id
		"Date" => "TextField",//send_time
		"Title" => "TextField",//title
		"Subject" => "TextField",//title
		"PermaLink" => "TextField",//archive_url
		"WebID" => "NumericField", //web_id
		"Status" => "TextField", //should be sent!
		"TextContent" => "TextareaField", //html
		"HTMLContent" => "TextareaField", //text
		"Done" => "CheckboxField"		
	);

}


