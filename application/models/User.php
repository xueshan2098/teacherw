<?php
   require_once 'RoleUser.php'; 
   class User extends Zend_Db_Table_Abstract{
   	   protected $_name = "user";
   	   protected $_dependentTables = array('RoleUser', 'Teach_body', 'Educate_body', 'Teachno_body');
   }
?>