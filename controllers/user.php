<?php
class User extends Controller {

	public function view($f3) {		
		$userid = $f3->get('PARAMS.3');
		if(empty($userid)) {
			return $f3->reroute('/');
		}
		$u = $this->Model->Users->fetch($userid);
		if(empty($u['username'])) {
			return $f3->reroute('/');
		}

		$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));

		$f3->set('u',$u);
		$f3->set('articles',$articles);
		$f3->set('comments',$comments);
	}

	public function add($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			$check = $this->Model->Users->fetch(array('username' => $username));
			if (!empty($check)) {
				StatusMessage::add('User already exists','danger');
			} else if($password != $password2) {
				StatusMessage::add('Passwords must match','danger');
			} else{
				$user = $this->Model->Users;
				$user->copyfrom('POST');
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
				$user->setPassword($password);
				if(empty($displayname)) {
					$user->displayname = $user->username;
				}

				//Set the users password
				$user->setPassword($user->password);
				
				$settings = $this->Model->Settings;
				$debug = $settings->getSetting('debug');

				if(!$debug){
					if($user->credentialCheck($username, $displayname, $password, $email)){
						$user->save();	
						StatusMessage::add('Registration complete','success');
						return $f3->reroute('/user/login');
					}
				}
				else{
					$user->save();	
					StatusMessage::add('Registration complete','success');
					return $f3->reroute('/user/login');
				}
				
			}
		}
	}

	public function login($f3) {
		/** YOU MAY NOT CHANGE THIS FUNCTION - Make any changes in Auth->checkLogin, Auth->login and afterLogin() */
		if ($this->request->is('post')) {

			//Check for debug mode
			$settings = $this->Model->Settings;
			$debug = $settings->getSetting('debug');

			//Either allow log in with checked and approved login, or debug mode login
			list($username,$password) = array($this->request->data['username'],$this->request->data['password']);
			if (
				($this->Auth->checkLogin($username,$password,$this->request,$debug) && ($this->Auth->login($username,$password))) ||
				($debug && $this->Auth->debugLogin($username))) {

					$this->afterLogin($f3);

			} else {
				StatusMessage::add('Invalid username or password','danger');
			}
		}		
	}

	/* Handle after logging in */
	private function afterLogin($f3) {
				StatusMessage::add('Logged in succesfully','success');

				//Redirect to where they came from
				if(isset($_GET['from'])) {				//TODO**********************VULNERABILITY HERE********************
					$f3->reroute($_GET['from']);
				} else {
					$f3->reroute('/');	
				}
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out succesfully','success');
		$f3->reroute('/');	
	}


	public function profile($f3) {	
		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			$u->copyfrom('POST');
			$u->bio = htmlspecialchars($u->bio);
			//Handle avatar upload
			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
				$validExts = array(
					'png',
					'jpg',
					'gif',
					'jpeg',
					'bmg'
				);

				$validTypes = array(
					'image/png',
					'image/jpg',
					'image/gif',
					'image/jpeg',
					'image/bmg'
				);

				$name = $_FILES['avatar']['name'];
				$ext = end((explode(".", $name))); 

				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$type = finfo_file($finfo, $_FILES['avatar']['tmp_name']);

				$size = $_FILES['avatar']['size'];

				$valid = True;

				if (!in_array($ext, $validExts)){
					$valid = False;
				}
				
				if (!in_array($type, $validTypes)){
					$valid = False;				
				}
				
				if(!getimagesize($_FILES['avatar']['tmp_name'])){
					$valid = False;
				}

				if(!$valid){
					\StatusMessage::add('Invalid file','danger');
					return $f3->reroute('/user/profile');
				}

				if ($size > 1048576){	//file must be less than 1MB
					\StatusMessage::add('File too large! Must be less than 1MB','danger');
					return $f3->reroute('/user/profile');
				}
		
				$url = File::Upload($_FILES['avatar']);		
				$u->avatar = $url;
			} else if(isset($reset)) {
				$u->avatar = '';
			}
			if($u->credentialCheck($u->username, $u->displayname, $u->password)){
				$u->save();
				\StatusMessage::add('Profile updated successfully','success');
				return $f3->reroute('/user/profile');
			}
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	public function promote($f3) {
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$u->level = 2;
		$u->save();
		return $f3->reroute('/');
	}

}
?>
