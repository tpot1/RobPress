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
			} else if($this->Model->Settings->getSetting('debug') != '1' && $Type_the_above_text != $f3->get('SESSION.captcha')){
				StatusMessage::add('Invalid CAPTCHA code. Try again.','danger');
			} else{
				$user = $this->Model->Users;
				$user->copyfrom('POST', function($arr){	//ensures parameters can't be added - they must match the given array of keys
					return array_intersect_key($arr, array_flip(array('username','displayname','email','password','password2')));
				});
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
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
				if(isset($_GET['from']) && substr($_GET['from'], 0, 1) == '/') {	//checks the reroute starts with a '/' so it can't link to pages out of the domain
					$f3->reroute($_GET['from']);
				} else {
					$f3->reroute('/');	
				}
	}

	public function logout($f3) {
		if($this->request->is('post')) {
			$this->Auth->logout();
			StatusMessage::add('Logged out succesfully','success');
			$f3->reroute('/');	
		}
	}


	public function profile($f3) {	
		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		//$oldpass = $u->password;
		if(empty($u)){
			return $f3->reroute('/');
		}
		else if($this->request->is('post')) {
			$u->copyfrom('POST', function($arr){	//ensures parameters can't be added - they must match the given array of keys
				return array_intersect_key($arr, array_flip(array('displayname','Old_Password','New_Password','bio')));
			});
			//Handle avatar upload
			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
				$url = File::Upload($_FILES['avatar']);		
				$u->avatar = $url;
			} else if(isset($reset)) {
				$u->avatar = '';
			}
			$oldpass = $this->request->data['Old_Password'];
			$newpass = $this->request->data['New_Password'];
			if($oldpass == ""){
				if($u->credentialCheck($u->username,$u->displayname)){
					$u->save();
					\StatusMessage::add('User updated succesfully','success');
					if($newpass != ""){
						\StatusMessage::add('To change your password, you must first enter your old password','danger');
					}
					return $f3->reroute('/user/profile');
				}
			}
			if(password_verify($oldpass, $u['password'])){
				if($u->credentialCheck($u->username,$u->displayname,$newpass)){
					$u->setPassword($newpass);
					$u->save();
					\StatusMessage::add('User updated succesfully','success');
					return $f3->reroute('/user/profile');
				}
			}
			else{
				\StatusMessage::add('Invalid old password','danger');
			}
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	/*public function promote($f3) {		//commented this out since it seems unneccesary, and allows users to promote themselves
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$u->level = 2;
		$u->save();
		return $f3->reroute('/');
	}*/

}
?>
