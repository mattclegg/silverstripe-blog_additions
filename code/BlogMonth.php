<?php 

class BlogMonth extends BlogHolder {
	
	static $has_many = array(
		'ImportedBlogEntries'=>'ImportedBlogEntry'
	);

	function getCMSFields(){
		$f=parent::getCMSFields();		
		
		if($x=DataObject::get('ImportedBlogEntry',"BlogMonthID='$this->ID'")){
		
			$x->removeDuplicates('Author');
				
			$f->addFieldToTab('Root.Content.ImportedArticles', $manager=new DataObjectManager($this,'ImportedBlogEntries','ImportedBlogEntry',array(
				'Author'	=>'Author',
				'Title'		=>'Title',
				'Link' 		=>'Source'
			)));
			
			$manager->removePermission('add');
			$manager->setColumnWidths(array('Author' => 25,'Title' => 30,'Link'  => 45));
			$manager->setFilter(
			   'Author',
			   'Filter by Author',
			   	$x->toDropdownMap('Author','Author')
			);
		
		}
		
		return $f;
	}
	

	function allowed_children() {
		return array('BlogEntry'); 
	}

	static $Entries =null;
	
	public function PaginatedEntries() {
		if(self::$Entries==null){
			$this->setEntries();
		}
		return self::$Entries;
	}
	
	public function Entries($limit = '', $tag = '', $date = '', $retrieveCallback = null, $filter = '') {
		if(self::$Entries==null){
			$this->setEntries($limit, $tag, $date, $retrieveCallback, $filter);
		}
		if($x=self::$Entries){
			return $x->getRange($x->pageStart, $x->pageLength);
		}else{
			return new DataObjectset();
		}
	}
	
	
	function setEntries($limit = '', $tag = '', $date = '', $retrieveCallback = null, $filter = ''){
	
		$tagCheck = '';
		$dateCheck = '';
		
		if($tag) {
			$SQL_tag = Convert::raw2sql($tag);
			$tagCheck = "AND \"BlogEntry\".\"Tags\" LIKE '%$SQL_tag%'";
		}

		if($date) {
			if(strpos($date, '-')) {
				$year = (int) substr($date, 0, strpos($date, '-'));
				$month = (int) substr($date, strpos($date, '-') + 1);

				if($year && $month) {
					if(method_exists(DB::getConn(), 'formattedDatetimeClause')) {
						$db_date=DB::getConn()->formattedDatetimeClause('"BlogEntry"."Date"', '%m');
						$dateCheck = "AND CAST($db_date AS " . DB::getConn()->dbDataType('unsigned integer') . ") = $month AND " . DB::getConn()->formattedDatetimeClause('"BlogEntry"."Date"', '%Y') . " = '$year'";
					} else {
						$dateCheck = "AND MONTH(\"BlogEntry\".\"Date\") = '$month' AND YEAR(\"BlogEntry\".\"Date\") = '$year'";
					}
				}
			} else {
				$year = (int) $date;
				if($year) {
					if(method_exists(DB::getConn(), 'formattedDatetimeClause')) {
						$dateCheck = "AND " . DB::getConn()->formattedDatetimeClause('"BlogEntry"."Date"', '%Y') . " = '$year'";
					} else {
						$dateCheck = "AND YEAR(\"BlogEntry\".\"Date\") = '$year'";
					}
				}
			}
		}

		// Build a list of all IDs for BlogHolders that are children of us
		$holderIDs = $this->BlogHolderIDs();
		
		// If no BlogHolders, no BlogEntries. So return false
		if(empty($holderIDs)) return false;
		
		// Otherwise, do the actual query
		if($filter) $filter .= ' AND ';
		$filter .= '"ParentID" IN (' . implode(',', $holderIDs) . ") $tagCheck $dateCheck";

		$order = '"BlogEntry"."Date" DESC';

		// By specifying a callback, you can alter the SQL, or sort on something other than date.
		if($retrieveCallback) return call_user_func($retrieveCallback, 'BlogEntry', $filter, $limit, $order);
		
		$sqlQuery = singleton('BlogEntry')->buildSQL($filter,$order);
		$sqlQuery->orderby=null;
		
		$order2 = '"Date" DESC';
		$sqlQuery2 = singleton('ImportedBlogEntry')->buildSQL("BlogMonthID=$this->ID",$order2);
		$sqlQuery2->orderby=null;
		
		$x= singleton('BlogEntry')->buildDataObjectSet($sqlQuery->execute());
		if($x){
			$x->merge(singleton('ImportedBlogEntry')->buildDataObjectSet($sqlQuery2->execute()));
		}else{
			$x=singleton('ImportedBlogEntry')->buildDataObjectSet($sqlQuery2->execute());
		}
		if($x){
			$x->sort('Date DESC');
			
			$sqlQuery->limit($limit);
			$x->parseQueryLimit($sqlQuery);
		}
		self::$Entries=$x;
	
		return $x;
	}
	
}

class BlogMonth_controller extends BlogHolder_controller {



}