<?php

class Form {

	public function __construct() {
	}

	public function start($options=array()) {
		$action = isset($options['action']) ? $options['action'] : '';
		$enctype = (isset($options['type']) && $options['type'] == 'file') ? 'enctype="multipart/form-data"' : ''; //Handle file uploads
		
		$token = randomCode(10);
		$f3=Base::instance();	
		$f3->set('SESSION.token',$token);

		return '<form role="form" method="post" action="'.$action.'" '.$enctype.'>
				<input type="hidden" " name="token" value="' . $token . '">';	
	}

	public function file($options) {
		return '<input type="file" class="form-control" id="' . $options['field'] . '" name="' . $options['field'] . '" placeholder="' . $options['placeholder'] . '" value="' . $options['value'] . '">';
	}

	public function checkbox($options) {
		$checked = (isset($options['value']) && !empty($options['value'])) ? 'checked="checked"' : '';
			$output = '
				<div class="checkbox">
				<label>
				<input type="checkbox" name="'.$options['field'].'" '.$checked.' value="1">'.$options['label'].'
				</label>
				</div>';
		return $output;
	}

	public function select($options) {
		$output = '<select class="form-control" id="' . $options['field'] . '" name="' . $options['field'] . '">'; 
		foreach($options['items'] as $value=>$label) {
			$checked = ($options['value'] == $value) ? 'selected="selected"' : '';
			$output .= '<option value="'.$value.'" '.$checked.'>'.$label.'</option>';
		}
		$output .= '</select>';
		return $output;
	}


	public function checkboxes($options) {
		$output = '';	
		foreach($options['items'] as $value=>$label) {
			$checked = (is_array($options['value']) && in_array($value,$options['value'])) ? 'checked="checked"' : '';
			$output .= '
				<div class="checkbox">
				<label>
				<input type="checkbox" name="'.$options['field'].'[]" '.$checked.' value="'.$value.'">'.$label.'
				</label>
				</div>';
		}
		return $output;
	}

	public function hidden($options) {
		return '<input type="hidden" id="' . $options['field'] . '" name="' . $options['field'] . '" value="' . $options['value'] . '">';
	}

	public function text($options) {
		return '<input type="text" class="form-control" id="' . $options['field'] . '" name="' . $options['field'] . '" placeholder="' . $options['placeholder'] . '" value="' . $options['value'] . '">';
	}

	public function datetime($options) {
		return '<input type="text" class="datetime form-control" id="' . $options['field'] . '" name="' . $options['field'] . '" placeholder="' . $options['placeholder'] . '" value="' . $options['value'] . '">';
	}

	public function textarea($options) {
		return '<textarea style="height: 200px" class="form-control" id="' . $options['field'] . '" name="' . $options['field'] . '">' . $options['value'] . '</textarea>';
	}

	public function wysiwyg($options) {
		$f3 = Base::instance();
		$base = $f3->get('site.base');
		return '<textarea style="height: 200px" class="wysiwyg form-control" id="' . $options['field'] . '" name="' . $options['field'] . '">' . $options['value'] . '</textarea>
		<script type="text/javascript">CKEDITOR.replace(\'' . $options['field'] . "', {
toolbarGroups: [
 		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'colors' },
 		{ name: 'links' },
 		{ name: 'insert' },
	],
	filebrowserUploadUrl: '$base/lib/upload.php'
}


);</script>
		";
	}


	public function password($options) {
		return '<input type="password" class="form-control" id="' . $options['field'] . '" name="' . $options['field'] . '" placeholder="' . $options['placeholder'] . '" value="' . $options['value'] . '">';
	}

	public function submit($options) {
		if(!isset($options['class'])) { $options['class'] = 'btn-primary'; }
		return '<input type="submit" class="btn '.$options['class'].'" id="' . $options['field'] . '" name="' . $options['field'] . '" value="' . $options['label'] . '">';
	}

	public function end() {
		return '</form>';
	}

	public function add($field,$options=array()) {
		$options['label'] = $label = isset($options['label']) ? $options['label'] : ucfirst($field);
		$type = isset($options['type']) ? $options['type'] : 'text';
		if(isset($options['value'])) { $options['value'] = $options['value']; }
		else if(isset($_POST[$field])) { $options['value'] = $_POST[$field]; }
		elseif(!isset($options['value']) && isset($options['default'])) { $options['value'] = $options['default']; }
		else { $options['value'] = ''; }

		$options['field'] = $field;
		if(!isset($options['placeholder'])) { $options['placeholder'] = ''; }

		if(in_array($type,array('submit','hidden')) || (isset($options['div']) && $options['div'] == 0)) {
			return $this->$type($options);
		}

		$input = $this->$type($options);
		$result = <<<EOT
<div class="form-group">
<label for="$field">$label</label>
$input 
</div>	
EOT;
		return $result;
	}	

	public function captcha(){

		$val=rand(9,true).rand(9,true).rand(9,true).rand(9,true).rand(9,true).rand(9,true);	//creates the captcha code

		$f3=Base::instance();	
		$f3->set('SESSION.captcha',$val);	//stores the code in the session variable

		header('Content-Type: image/jpeg');
		$im = imagecreatetruecolor(140, 40);	//creates an image
		$background_colour = imagecolorallocate($im, 150, 150, 170);
		imagefill($im, 0, 0, $background_colour);
		$line_colour = imagecolorallocate($im, 5, 19, 175);
		for ($i = 0; $i < 10; $i++) {		//draws random lines to obscure the image, making it harder for computers to read the numbers
		    imageline(
		        $im,
		        rand(1, 200),
		        rand(1, 200),
		        rand(1, 50),
		        rand(1, 50),
		        $line_colour
		    );
		}

		$text_color = imagecolorallocate($im, 175, 19, 10);
		imagestring($im, 5, 5, 5,  $val , $text_color);	//adds the code as text to the image
		
		ob_start();
		imagejpeg($im, NULL, 100);
	    $rawImageBytes = ob_get_clean();
	    $return = "<img src='data:image/jpeg;base64," . base64_encode( $rawImageBytes ) . "' />";	//returns the image to be displayed on the page
		imagedestroy($im);

		return $return;
	}

	public function confirm($confirmName, $cancelName, $cancelLink, $confirmType = 'btn-danger', $cancelType = 'btn-primary'){
		return '<input type="submit" value="' . $confirmName . '" ' . ' class="btn '.$confirmType .'">
				<a class="btn ' . $cancelType . '" href="' . $cancelLink . '">' . $cancelName . '</a>';
	}

}

?>
