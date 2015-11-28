<?php

class UsersModel extends GenericModel {

	/** Update the password for a user account */
	public function setPassword($password) {
		$this->password = $password;
	}	
	
	public function credentialCheck($username, $displayname, $password, $email="default"){
		if($username != htmlspecialchars($username)){
			StatusMessage::add('Invalid characters in username', 'danger');
			return false;
		}
		else if($displayname != htmlspecialchars($displayname)){
			StatusMessage::add('Invalid characters in displayname', 'danger');
			return false;
		}
		else if($username == ""){
			StatusMessage::add("Username can't be empty.",'danger');
			return false;
		}
		else if($password == ""){
			StatusMessage::add("Password can't be empty.",'danger');
			return false;
		}
		else if($email != htmlspecialchars($email)){
			StatusMessage::add('Invalid characters in email', 'danger');
			return false;
		}
		else return true;
	}
	
}

?>
