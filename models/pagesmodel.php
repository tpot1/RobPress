<?php

class PagesModel {

	/** Create a new page */
	public function create($pagename) {
		$pagedir = getcwd() . "/pages/";
		touch($pagedir . $pagename . ".html");
	}

	/** Load the contents of a page */
	public function delete($pagename) {
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $pagename;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { return false; }
		unlink($file);
	}

	/** Get all available pages */
	public function fetchAll() {
		$pagedir = getcwd() . "/pages/";
		$pages = array();
		if ($handle = opendir($pagedir)) {
			while (false !== ($file = readdir($handle))) {
				if (!preg_match('![.]html!sim',$file)) continue;
				$title = ucfirst(str_ireplace("_"," ",str_ireplace(".html","",$file)));
				$pages[$title] = $file;
			}
			closedir($handle);
		}
		return $pages;
	}

	/** Load the contents of a page */
	public function fetch($pagename) {
		$f3 = Base::instance();

		//gets all pages in page directory
		$pages = array_values($this->fetchAll());

		//removes file extension for each page
		foreach($pages as $key=>$page){
			$pages[$key] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $page);
		}

		//checks only pages in the directory are shown, else shows 404 error
		if(in_array($pagename, $pages)){
			$pagedir = getcwd() . "/pages/";
			$file = $pagedir . $pagename;
			if(!file_exists($file)) {
				$file .= ".html";
			}
			if(!file_exists($file)) { return false; }
			return file_get_contents($file);
		}
		else return $f3->error(404);
		
	}

	/** Save contents of the page based on title and content field to file */
	public function save() {
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $this->title;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { return false; }
		if(!isset($this->content)) { return false; } 
		return file_put_contents($file,$this->content);
	}

}

?>
