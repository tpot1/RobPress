<?php

class UsersModel extends GenericModel {

	/** Update the password for a user account */
	public function setPassword($password) {
		$this->password = password_hash($password, PASSWORD_DEFAULT);
	}	
	
	public function credentialCheck($username, $displayname, $password, $email="default"){
		if($username != h($username)){
			StatusMessage::add('Invalid characters in username', 'danger');
			return false;
		}
		else if($displayname != h($displayname)){
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
		else if($email != h($email)){
			StatusMessage::add('Invalid characters in email', 'danger');
			return false;
		}
		else return true;
	}
}

?>
