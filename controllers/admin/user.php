<?php
namespace Admin;
class User extends AdminController {
	public function index($f3) {
		$users = $this->Model->Users->fetchAll();
		$f3->set('users',$users);
	}
	public function edit($f3) {	
		$id = $f3->get('PARAMS.3');
		if(empty($id)) {
			return $f3->reroute('/admin/user');
		}
		$u = $this->Model->Users->fetch($id);
		if(empty($u['username'])) {
			return $f3->reroute('/admin/user');
		}
		if($this->request->is('post')) {
			$u->copyfrom('POST', function($arr){	//ensures parameters can't be added - they must match the given array of keys
				return array_intersect_key($arr, array_flip(array('username','displayname','password','level','bio','avatar')));
			});
			$newpass = $this->request->data['pass_word'];
			if($newpass == ""){
				if($u->credentialCheck($u->username, $u->displayname)){
					$u->save();
					\StatusMessage::add('Profile updated successfully','success');
					return $f3->reroute('/admin/user');
				}	
			}
			else{
				if($u->credentialCheck($u->username, $u->displayname, $newpass)){
					$u->setPassword($u->password);
					$u->save();
					\StatusMessage::add('Profile updated successfully','success');
					return $f3->reroute('/admin/user');
				}
			}
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	public function delete($f3) {
		$id = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			if($id == $this->Auth->user('id')) {
				\StatusMessage::add('You cannot remove yourself','danger');
				return $f3->reroute('/admin/user');
			}
			//Remove all posts and comments
			$posts = $this->Model->Posts->fetchAll(array('user_id' => $id));
			foreach($posts as $post) {
				$post_categories = $this->Model->Post_Categories->fetchAll(array('post_id' => $post->id));
				foreach($post_categories as $cat) {
					$cat->erase();
				}
				$comments = $this->Model->Comments->fetchAll(array('blog_id' => $postid));
				foreach($comments as $comment) {
					$comment->erase();
				}
				$post->erase();
			}
			$comments = $this->Model->Comments->fetchAll(array('user_id' => $id));
			foreach($comments as $comment) {
				$comment->erase();
			}
			$u->erase();
			\StatusMessage::add('User has been removed','success');
			return $f3->reroute('/admin/user');
		}
		$_POST = $u->cast();
		$f3->set('u',$u);
	}
}
?>