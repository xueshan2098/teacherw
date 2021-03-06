<?php 
   /**
    * @author linbin
    */
   require_once APPLICATION_PATH.'/utils/LogUtils.php';
   class DatabaseUtils{
   	   
   	   /**
   	    * The fetch table and return data
   	    * @param  object $table The table object
   	    * @param  resource $db The database link
   	    * @param array $datas {
   	    *     'field' => 'value'
   	    * }
   	    * @param string $flag the value are 'row' or 'all'
   	    * @return object|array $row the result set about the table
   	    */
   	   public function fetchData($table,$db,$datas,$flag='row'){
   	   	   //$type = var_dump($datas); 
   	   	   $length = count($datas);
   	   	   $where = null;
   	   	   $order = null;
   	   	   $count = null;
   	   	   $offset = null;
   	   	   $row = null;
   	   	   if(!isset($table) || !is_array($datas) || !isset($db)){
   	   	   	   die("DatabaseUtils::fetchData The table object is unset or the datas is not an array or the link is null");
   	   	   }
   	   	   
   	   	   foreach ($datas as $key => $val){
   	   	   	   if($key == 'order'){
   	   	   	   	   if(!isset($order)){
   	   	   	   	   	   $order = array();
   	   	   	   	   }
   	   	   	   	   $order = explode(',', $val);
   	   	   	   } else if($key == 'count'){
   	   	   	   	   $count = intval($val);
   	   	   	   } else if($key == 'offset'){
   	   	   	   	   $offset = intval($val);
   	   	   	   } else{
   	   	   	   	   if(!isset($where)){
   	   	   	   	   	   $where = $db->quoteInto($key.'=?',$val);
   	   	   	   	   }else{
   	   	   	   	   	   $where .= $db->quoteInto(" AND ".$key.'=?',$val);
   	   	   	   	   }
   	   	   	   }
   	   	   }
//    	   	   $logs->printObject($order);
   	   	   
   	   	   if($flag == 'row'){
   	   	   	   $row = $table->fetchRow($where,$order,$offset);
   	   	   } else if($flag == 'all'){
   	   	   	   $row = $table->fetchAll($where,$order,$count,$offset);
   	   	   }
   	   	   
   	   	   return $row;
   	   }
   	   
   	   /**
   	    * To update data to database
   	    * @param object $table
   	    * @param array $datas  datas {
   	    *      'field' => 'value'
   	    * }
   	    * @return int
   	    */
   	   public function insert($table, $datas){
   	   	    if(!isset($table) || !isset($datas)){
   	   	    	die("DataBaseUtils::update  The table and datas must be exsist!!");
   	   	    }  
   	   	    
   	   	    return $table->insert($datas);
   	   }
   	   
   	   /**
   	    * delete the data
   	    * @param object $table
   	    * @param resource $db the database
   	    * @param array $where the where condition default '' {id="...", ....}
   	    * @return
   	    */
   	   public function delete($table, $db, $where=null){
	   	   	if (!isset($table, $db)){
	   	   		die("DataBaseUtils::delete  The table and database must be exsist!!");
	   	   	}
	   	   	$dwhere = null;
	   	   	if(isset($where)){
	   	   		foreach ($where as $key => $value){
	   	   			if(!isset($dwhere)){
	   	   				$dwhere = $db->quoteInto("$key = ?", $value);
	   	   			}else{
	   	   				$dwhere .= $db->quoteInto(" AND $key = ?", $value);
	   	   			}
	   	   		}
	   	   	}
	   	   	
	   	   	return $table->delete($dwhere);
   	   }
   	   
   	   /**
   	    * 插入数据
   	    * @param object $table          The table object
   	    * @param resource $db           The database adapter   
   	    * @param array $set             set {'field' => 'data'}
   	    * @param array|null $where      where {'field' => 'data'}
   	    * @return int                   The effect result row
   	    */
   	   public function update($table, $db, $set, $where=null){
   	   	   if(!isset($table, $set)){
   	   	   	  die("DatabaseUtils::updata The table and set must be exsist!!");
   	   	   }
   	   	   $dwhere = null;
   	   	   if(isset($where)){
   	   	   	   foreach ($where as $key => $value){
   	   	   	   	  if(!isset($dwhere)){
   	   	   	   	  	  $dwhere = $db->quoteInto("$key = ?", $value);
   	   	   	   	  }else{
   	   	   	   	      $dwhere .= $db->quoteInto(" AND $key = ?", $value);
   	   	   	   	  }
   	   	   	   }
   	   	   }
   	   	   
   	   	   return $table->update($set, $dwhere);
   	   }
   	   /**
   	    * To change rowset to array
   	    * @param object $row    Zend_Db_table_rowset
   	    * @return array         The resultset(array)
   	    */
   	   public function changeToArray($row){
	   	   	$arrs = array();
	   	   	for($i = 0;$i < $row->count();$i++){
	   	   		$temp = $row[$i];
	   	   		foreach ($temp as $key => $value){
	   	   			$arrs[$i][$key] = $value;
	   	   		}
	   	   	}
	   	   	return $arrs;
   	   }
   	   /**
   	    * @param object $rows   The row that would be used
   	    * @param string $class  The class name that would be used
   	    * @param string $role   The role that used
   	    * @param object $select The where condition
   	    */
   	   public function findDependentRow($rows,$class, $role=null, $select=null){
   	   	    if(!isset($class) || !isset($rows)){
   	   	    	die("DatabaseUtils::findDependentRow the class or the rows should not be null");
   	   	    }
   	   	    return $rows->findDependentRowset($class,$role,$select);
   	   }
   	   
   	   /**
   	    * To deal with many to many relationship
   	    * @param object $rows
   	    * @param string $matchTable the rows table
   	    * @param string $intersectionTable
   	    * @param string $callerRefRule The role that interactionTable to origin table
   	    * @param string $matchTable The role that interactionTable to destination table
   	    */
   	   public function findManyToManyRow($rows,$matchTable,$intersectionTable,$callerRefRule=null,$matchRefRule = null){
   	        if(!isset($matchTable) || !isset($intersectionTable)){
   	        	die("DatabaseUtils::findManyToManyRow the matchTable and intersectionTable should not be null!!");
   	        }
   	        return $rows->findManyToManyRowset($matchTable,$intersectionTable,$callerRefRule,$matchRefRule);
   	   }
   	   
   	   /**
   	    * To count the table rows number
   	    * @param string $tablename       The sql table name
   	    * @param string $field           The sql field
   	    * @param resource $db            The database resource
   	    * @return int                    The table column number
   	    */
   	   public function getTableCount($tablename, $db,$field=null){
   	   	   if(!isset($tablename) || !isset($db)){
   	   	   	   die("DatabaseUtils::getTableCount The tablename and db must be exsist!!");
   	   	   }
   	   	   if(!isset($field)){
   	   	   	   $field = "*";
   	   	   }
   	   	   $result = $db->fetchOne(
   	   	      "SELECT count(".$field.") from ".$tablename
   	   	   );
   	  	   return intval($result);
   	   }
   }
?>