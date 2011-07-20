<?php 

class BlogCategory extends BlogTreeExtension {
	
	static $has_one = array(
		//'RootPage' => 'RootPage'
	);

	function allowed_children() {
		return array('BlogYear'); 
	}
	
	function getCMSFields() {
		$f = parent::getCMSFields();
		//$f->addFieldToTab("Root.Behaviour", new DropDownField("RootPageID", "Related Root page",Dataobject::get("RootPage")->map("ID", "Title", "Please Select")));
		return $f;
	}
	

	function onBeforeWrite(){
		if($this->ID && $this->owner->Children()->Count()==0){
			
				$page=new BlogYear();
				$page->Title=date("Y");
				$page->ParentID=$this->ID;
				$page->write();
				$page->publish('Stage', 'Live');
				
				$page->destroy();
				unset($page);
		
		}
		/**
		if($this->ID && $this->RootPageID){
			$x=DataObject::get_by_id('RootPage',$this->RootPageID);
			if($x->BlogCategoryID!=$this->ID){
				$x->BlogCategoryID=$this->ID;
				$x->write();
			}
		}
		**/
	
		parent::onBeforeWrite();
	}
	
	function CustomMenuTitle(){
		return $this->MenuTitle;
	}
	
}

class BlogCategory_controller extends BlogTree_controller {



}