<?php 

class ImportedBlogEntry extends DataObject {

	static $db = array(
		'Title'		=>'Text',
		'MenuTitle'	=> 'Text',
		'Date' 		=> "Datetime",
		'Link' 		=> "varchar(2083)",
		'Content'	=> 'Text',
		'Author'	=> 'Text',
		'Archived'	=> 'Boolean'
	);

	static $has_one = array(
		'ShowThumbnail'=>'Image',
		'BlogMonth'=>'BlogMonth'
	);
	
	
	function AbsoluteLink(){
		return $this->Link;
	}
}
