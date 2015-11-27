<?php

class PostsModel extends GenericModel {

	public function fetchPublished() {
		$posts = $this->fetchAll(array('published' => 'IS NOT NULL'),array('order' => 'published DESC'));
		
		return $posts;
	}

}

?>
