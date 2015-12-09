<?php

	class AuthHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}

		/** Attempt to resume a previously logged in session if one exists */
		public function resume() {
			$f3=Base::instance();				

			//Ignore if already running session	
			if($f3->exists('SESSION.user.id')) return;

			//Log user back in from cookie
			if($f3->exists('COOKIE.RobPress_User')) {
				$code = $f3->get('COOKIE.RobPress_User');

				$user = $this->controller->Model->Users->fetch(array('code' => $code));

				$this->forceLogin($user);
			}
		}		

		/** Perform any checks before starting login */
		public function checkLogin($username,$password,$request,$debug) {

			//DO NOT check login when in debug mode
			if($debug == 1) { return true; }

			//return true;

			$f3=Base::instance();	

			$code = $f3->get('SESSION.captcha');		//checks the captcha code stored in the session variable, but this seems to expire quite quickly
			$input = $request->data['Type_the_above_text'];	//gets the users input 

			if($code == null){		//since the session keeps expiring, I add a special message for this - if this is displayed I need to restart the session manually
				StatusMessage::add('Problem with CAPTCHA. Try again.','danger');
				//return $f3->reroute('/user/login');
				return false;
			}

			if($input == $code){	//checks the users input is the same as the captcha code
				return true;
			}
			else{
				StatusMessage::add('Invalid CAPTCHA code. Try again.','danger');
				return $f3->reroute('/user/login');
				//return false;
			}
		}

		/** Look up user by username and password and log them in */
		public function login($username,$password) {
			$f3=Base::instance();		

			$db = $this->controller->db;
            
            $query = 'SELECT * FROM users WHERE username= :username';
            $args = array(':username' => $username);
            
            $results = $db->query($query, $args);

			if (!empty($results)) {	//finds the user, and checks the password matches their hashed version in the database
				if(password_verify($password, $results[0]['password'])){
					$user = $results[0];	
					$this->setupSession($user);
					return $this->forceLogin($user);
				}
			}

		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();							

			//Kill the session
			session_destroy();

			//remove the user's session code from the database
			$code = $f3->get('COOKIE.RobPress_User');
			$user = $this->controller->Model->Users->fetch(array('code' => $code));
			$user->code = "";
			$user->save();

			//Kill the cookie
			setcookie('RobPress_User','',time()-3600,'/');
		}

		/** Set up the session for the current user */
		public function setupSession($user) {
			//Remove previous session
			session_destroy();

			//Setup new session
			session_id(md5($user['id']));

			//Setup cookie for storing user details and for relogging in
			$code = randomCode(10);	//generates a random code

			$u = $this->controller->Model->Users->fetch(array('id' => $user['id']));
			$u->code = $code;	//stores the code in the database for the user
			$u->save();

			//setcookie('RobPress_User',base64_encode(serialize($user)),time()+3600*24*30,'/');
			setcookie('RobPress_User',$code,time()+3600*24*30,'/');

			//And begin!
			new Session();
		}

		/** Not used anywhere in the code, for debugging only */
		public function specialLogin($username) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3 = Base::instance();
			$user = $this->controller->Model->Users->fetch(array('username' => $username));
			$array = $user->cast();
			return $this->forceLogin($array);
		}

		/** Not used anywhere in the code, for debugging only */
		public function debugLogin($username,$password='admin') {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$user = $this->controller->Model->Users->fetch(array('username' => $username));

			//Create a new user if the user does not exist
			if(!$user) {
				$user = $this->controller->Model->Users;
				$user->username = $user->displayname = $username;
				$user->email = "$username@robpress.org";
				$user->setPassword($password);
				$user->created = mydate();
				$user->bio = '';
				$user->level = 2;
				$user->save();
			}

			//Update user password
			$user->setPassword($password);

			//Move user up to administrator
			if($user->level < 2) {
				$user->level = 2;
				$user->save();
			}

			//Log in as new user
			return $this->forceLogin($user);			
		}

		/** Force a user to log in and set up their details */
		public function forceLogin($user) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3=Base::instance();					

			if(is_object($user)) { $user = $user->cast(); }

			$f3->set('SESSION.user',$user);
			return $user;
		}

		/** Get information about the current user */
		public function user($element=null) {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(empty($element)) { return $f3->get('SESSION.user'); }
			else { return $f3->get('SESSION.user.'.$element); }
		}

	}

?>
