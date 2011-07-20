<?php 
class BlogHolderCustom extends DataObjectDecorator {
	function extraStatics() {
		return array();
	}
	
	function updateCMSFields(FieldSet &$fields) {
		 
	}
	
}
class BlogHolderCustom_Controller extends DataObjectDecorator {

	function RSSAndBlogEntries($limit=null, $paginate=null){
		if($x=$this->BlogEntries($limit, $paginate))
			return $x;
	}
	
	function BlogEntries($limit, $paginate=true) {
	
		require_once('Zend/Date.php');
		
		if($limit === null) $limit = BlogTree::$default_entries_limit;

		// only use freshness if no action is present (might be displaying tags or rss)
		if ($this->owner->LandingPageFreshness && !$this->request->param('Action')) {
			$d = new Zend_Date(SS_Datetime::now()->getValue());
			$d->sub($this->owner->LandingPageFreshness);
			$date = $d->toString('YYYY-MM-dd');
			
			$filter = "\"BlogEntry\".\"Date\" > '$date'";
		} else {
			$filter = '';
		}
		// allow filtering by author field and some blogs have an authorID field which
		// may allow filtering by id
		if(isset($_GET['author']) && isset($_GET['authorID'])) {
			$author = Convert::raw2sql($_GET['author']);
			$id = Convert::raw2sql($_GET['authorID']);
			
			$filter .= " \"BlogEntry\".\"Author\" LIKE '". $author . "' OR \"BlogEntry\".\"AuthorID\" = '". $id ."'";
		}
		else if(isset($_GET['author'])) {
			$filter .=  " \"BlogEntry\".\"Author\" LIKE '". Convert::raw2sql($_GET['author']) . "'";
		}
		else if(isset($_GET['authorID'])) {
			$filter .=  " \"BlogEntry\".\"AuthorID\" = '". Convert::raw2sql($_GET['authorID']). "'";
		}
		
		if(!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) $_GET['start'] = 0; 
		
		$date = $this->owner->SelectedDate();
		
		
		$x=new DataObjectSet();
		
		if($sidebar=$this->getOwner()->SideBar()){
		
			foreach($sidebar->Widgets() as $Widget)
				if($Widget->ClassName='RSSWidget')
				if($y=$Widget->UpdatedFeedItems())
					foreach ($y as $FeedItem){

						$x->merge(new ArrayData(array(
							'ID'		=> strtotime($FeedItem->Date->value),
							'Title' 	=> $FeedItem->Title,
							'MenuTitle'	=> $FeedItem->Title,
							'Date' 		=> $FeedItem->Date,
							'Link' 		=> $FeedItem->Link,
							'Content'	=> $FeedItem->Content,
							'Author'	=> $Widget->RSSTitle,
							'ShowThumbnail'=>DataObject::get_by_id('Image',(int)$Widget->Thumbnail),
							'Archived'	=> false
						)));
					}
		}
		
		//$x->merge($this->Entries(null, $this->owner->SelectedTag(), ($date) ? $date : '', null, $filter));
		
		$x->sort('Date', 'DESC');
		if($paginate){
			$o = $x->getRange((int)$_GET['start'], (int)$limit); 
			$o->setPageLimits((int)$_GET['start'], (int)$limit, $x->Count()); 
			return $o;
		}else{
			return $x;
		}
	}
	
	
	/**
	 * Get entries in this blog.
	 * @param string limit A clause to insert into the limit clause.
	 * @param string tag Only get blog entries with this tag
	 * @param string date Only get blog entries on this date - either a year, or a year-month eg '2008' or '2008-02'
	 * @param callback retrieveCallback A function to call with pagetype, filter and limit for custom blog sorting or filtering
	 * @param string $where
	 * @return DataObjectSet
	 */
	public function Entries($limit = '', $tag = '', $date = '', $retrieveCallback = null, $filter = '') {
	
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
		$holderIDs = $this->owner->BlogHolderIDs();
		
		// If no BlogHolders, no BlogEntries. So return false
		if(empty($holderIDs)) return false;
		
		// Otherwise, do the actual query
		if($filter) $filter .= ' AND ';
		$filter .= '"ParentID" IN (' . implode(',', $holderIDs) . ") $tagCheck $dateCheck";

		$order = '"BlogEntry"."Date" DESC';

		// By specifying a callback, you can alter the SQL, or sort on something other than date.
		if($retrieveCallback) return call_user_func($retrieveCallback, 'BlogEntry', $filter, $limit, $order);
		
		return DataObject::get('BlogEntry', $filter, $order, '', $limit);
	}
	
	
}

class BlogHolderCustom_RSSWidget extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				"Thumbnail"=>"int"
			)
		);
	}

	
	function updateCMSFields(FieldSet &$fields) {
		$fields->push(new DropDownField("Thumbnail","Picture",DataObject::get('Image','ParentID=1097')->toDropDownMap()));
	}
	
	function UpdatedFeedItems() {
		$output = new DataObjectSet();

		// Protection against infinite loops when an RSS widget pointing to this page is added to this page 
		if(stristr($_SERVER['HTTP_USER_AGENT'], 'SimplePie')) { 
			return $output;
		}
		
		include_once(Director::getAbsFile(SAPPHIRE_DIR . '/thirdparty/simplepie/simplepie.inc'));
		
		
		//Debug::show($this->getOwner()->Thumbnail);
		
		$t1 = microtime(true);
		
		$file = new SimplePie_File($this->getOwner()->AbsoluteRssUrl);
		$test = new SimplePie_Locator($file);
		if($test->is_feed($file)){
			$feed = new SimplePie($this->getOwner()->AbsoluteRssUrl, TEMP_FOLDER);
			$feed->init();
			if($items = $feed->get_items(0, $this->getOwner()->NumberToShow)) {
				foreach($items as $item) {
					
					// Cast the Date
					$date = new SS_DateTime('Date');
					$date->setValue($item->get_date());
	
	
					$description = new HTMLText('Content');
					$description->setValue($item->get_description());
					
					$output->push(new ArrayData(array(
						'Title' => $item->get_title(),
						'Date' => $date,
						'Link' => $item->get_link(),
						'Content'=>$description
					)));
				}
				
			}
		}
		return $output;
	}
}