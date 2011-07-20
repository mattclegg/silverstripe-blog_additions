<?php 

class OverrideSQLQuery extends SQLQuery {

	public $sql = null;

	function sql() {
		if($this->sql==null){
			$sql = DB::getConn()->sqlQueryToString($this);
			if($this->replacementsOld) $sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
			return $sql;
		}else{
			return $this->sql;
		}
	}

}