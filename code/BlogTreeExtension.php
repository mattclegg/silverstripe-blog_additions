<?php 

class BlogTreeExtension extends BlogTree {

	static $can_create = false;

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
	
	
	function CustomMenuTitle(){
		$this->setEntries();
		if(self::$Entries){
			$x=self::$Entries->Count();
			if($x>1){
				return "{$this->MenuTitle} ($x {$this->Parent()->Title} entries)";
			}
		}
		return parent::CustomMenuTitle();
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
		$filter2 = '"BlogMonthID" IN (' . implode(',', $holderIDs) . ")";
		$sqlQuery2 = singleton('ImportedBlogEntry')->buildSQL($filter2,$order2);
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
	
	}
	
	
}


class BlogTreeExtension_controller extends BlogTree_controller {

	function BlogEntries($limit = null) {
		$x=parent::BlogEntries($limit);
		
		
		$sqlQuery = new SQLQuery("count(ID)","ImportedBlogEntry","Date=CURDATE()");
		
		if(!$sqlQuery->execute()->value()>0){
		
			if($sidebar=$this->SideBar()){
			
				foreach($sidebar->Widgets() as $Widget)
					if($Widget->ClassName=='RSSWidget' && $y=$Widget->UpdatedFeedItems())
						foreach ($y as $FeedItem){
						
							$Link=Convert::raw2sql($FeedItem->Link);
							
							$sqlQuery = new SQLQuery("COUNT(ID)","ImportedBlogEntry","Link='$Link'");
							if(!$sqlQuery->execute()->value()>0){
	
								$date = new SS_Datetime();
								$date->setValue($FeedItem->Date->value);
								
								$year=date('Y', strtotime($date->value));
								$month=date('F', strtotime($date->value));
								
								$sqlQuery = new SQLQuery("ID","SiteTree","Title='$month' AND ParentID=(SELECT ID FROM SiteTree WHERE Title='$year' AND ParentID=$this->ParentID)");
								$month_id=$sqlQuery->execute()->value();
								if($month_id>0){
								
									$entry=new ImportedBlogEntry();
									
									$entry->Title=$FeedItem->Title;
									$entry->MenuTitle=$FeedItem->Title;
									$entry->Date=$date;
									
									$entry->Link=$FeedItem->Link;
									//$entry->Content=$FeedItem->Content;
									$entry->Author=$Widget->RSSTitle;
									$entry->ShowThumbnailID=(int)$Widget->Thumbnail;
									$entry->Archived=0;
									
									$entry->BlogMonthID=$month_id;
									
									$entry->write();
								}
							}
						}
			}
		}
		return $x;
	}


}