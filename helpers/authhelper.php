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

			$f3=Base::instance();

			$db = $this->controller->db;
			$query = 'SELECT * FROM login_attempts WHERE ip= :ip';
            $args = array(':ip' => $_SERVER['REMOTE_ADDR']);
            $ip_attempts = $db->query($query, $args);	//gets the ip_attempts entry for this ip address, showing the number of attempts, and whether the ip is blocked
        	if(!empty($ip_attempts) && $ip_attempts[0]['expiresat'] > strtotime('now')){	//checks if the user's ip has been blocked
        		$timeleft = (int) (($ip_attempts[0]['expiresat'] - strtotime('now'))/60);	//checks how much longer they are blocked for
        		StatusMessage::add('Your have been blocked due to too many unsuccessful login attempts. Try again in ' . $timeleft . ' minutes','danger');
        		return $f3->reroute('/user/login');
        	}	

			$code = $f3->get('SESSION.captcha');		//gets the captcha code stored in the session variable
			$input = $request->data['Type_the_above_text'];	//gets the users input 

			if($input == $code){	//checks the users input is the same as the captcha code
				return true;
			}
			else{
				StatusMessage::add('Invalid CAPTCHA code. Try again.','danger');
				return $f3->reroute('/user/login');		//reroutes them to the same page to avoid printing the 'invalid username or password' message
			}
		}

		/** Look up user by username and password and log them in */
		public function login($username,$password) {
			$f3=Base::instance();		

			$db = $this->controller->db;
            
            $query = 'SELECT * FROM users WHERE username= :username';
            $args = array(':username' => $username);
            
            $results = $db->query($query, $args);

            $validCredentials = true;

			if (!empty($results)) {	//finds the user, and checks the password matches their hashed version in the database
				if(password_verify($password, $results[0]['password'])){
					$user = $results[0];	
					$this->setupSession($user);
					return $this->forceLogin($user);
				}
				else $validCredentials = false;
			}
			else $validCredentials = false;

			if(!$validCredentials){
				$check = 'SELECT * FROM login_attempts WHERE ip= :ip';
	            $args = array(':ip' => $_SERVER['REMOTE_ADDR']);
	            $results = $db->query($check, $args);
				if(empty($results)){
					$addIP = 'INSERT INTO login_attempts (ip, attempts, expiresat) VALUES (:ip, 1, NULL)';
					$ipargs = array(':ip' => $_SERVER['REMOTE_ADDR']);
					$db->query($addIP, $ipargs);
					StatusMessage::add("4 attempt(s) remaining.",'danger');
				}
				else{
					$attempts = ((int) $results[0]['attempts']) + 1;
					$expires = null;
					if($attempts > 4){
						$expires = strtotime('+1 hour');
						$attempts = 0;
						StatusMessage::add('You have been blocked due to too many unsuccessful login attempts. You can try again in 1 hour','danger');
					}
					else{
						StatusMessage::add((5-$attempts) . " attempt(s) remaining.",'danger');
					}
					$updateIP = 'UPDATE login_attempts SET attempts = :attempts, expiresat = :expires WHERE ip = :ip';
					$updateArgs = array(':attempts' => $attempts, ':expires'=> $expires, ':ip' => $_SERVER['REMOTE_ADDR']);
					$db->query($updateIP, $updateArgs);
				}
			}
		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();							

			//Kill the session
			session_destroy();

			//remove the user's session code from the database
			if($this->controller->Model->Settings->getSetting('debug') != '1'){
				$code = $f3->get('COOKIE.RobPress_User');
				$user = $this->controller->Model->Users->fetch(array('code' => $code));
				$user->code = "";
				$user->save();
			}
			
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
