<?php 

class BlogMaster extends BlogTreeExtension {

	function AllChildren(){
		$x=parent::AllChildren();
		$x->merge(DataObject::get('BlogCategory'));
		$x->removeDuplicates();
		$x->sort('MenuTitle');
		return $x;
	}

	function Children(){
		return $this->AllChildren();
	
	}
	
	
}

class BlogMaster_Controller extends BlogTreeExtension_Controller {

}
