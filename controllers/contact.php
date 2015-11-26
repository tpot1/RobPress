<?php

class Contact extends Controller {

	public function index($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			$from = "From: $from";
			
			$to = $this->Model->Settings->getSetting('email');	//defining who the request is sent to here rather than in the html, where it could be edited

			mail($to,$subject,$message,$from);

			StatusMessage::add('Thank you for contacting us');
			return $f3->reroute('/');
		}	
	}

}

?>
