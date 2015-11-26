<?php

class UsersModel extends GenericModel {

	/** Update the password for a user account */
	public function setPassword($password) {
		$this->password = $password;
	}		
	
	public function fetch($conditions = array(),$options=array()) {
		if(is_numeric($conditions)) { $conditions = array('id' => $conditions); }
		$conditions = $this->prepare($conditions);
		return $this->load($conditions,$options);

	}

}

?>
