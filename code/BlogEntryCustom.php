<?php
class BlogEntryCustom extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				"Archived"=>"Boolean"
			),
		);
	}
	
	function updateCMSFields(FieldSet &$fields) {
		$fields->addFieldToTab('Root.Content.Main', new CheckboxField("Archived"),'Content'); 
	}
	
}

