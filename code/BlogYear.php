<?php 

class BlogYear extends BlogTreeExtension {

	function allowed_children() {
		return array('BlogMonth'); 
	}
	
	function getCMSFields() {
		$f = parent::getCMSFields();
		$f->addFieldToTab("Root.Behaviour", new DropDownField("RootPageID", "Related Root page",Dataobject::get("RootPage")->map("ID", "Title", "Please Select")));
		return $f;
	}
	

	function onBeforeWrite(){
		if($this->ID && $this->owner->Children()->Count()==0){
			$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
			
			foreach ($months as $month){
				$page=new BlogMonth();
				$page->Title=$month;
				$page->ParentID=$this->ID;
				$page->write();
				$page->publish('Stage', 'Live');
				$page->destroy();
				unset($page);
			}
		
		}
		parent::onBeforeWrite();
		
	}

	
}

class BlogYear_controller extends BlogTree_controller {

}