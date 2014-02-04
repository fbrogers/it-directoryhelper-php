<?php

class DirectoryHelperConfig{
	//general settings
	protected $directory_uri;
	protected $feed_uri;

	public function __construct(){
		//open config file
		$config = parse_ini_file('config.ini');
		$this->directory_uri = $config['DIRECTORY_URI'];
		$this->feed_uri = $config['FEED_URI'];
	}
}

class DirectoryHelper extends DirectoryHelperConfig{
	//object containers
	private $alerts	= [];
	private $docs 	= [];
	private $news 	= [];
	private $roles 	= [];
	private $staff 	= [];

	//to prevent object instantiation
	public function __construct($slug){
		parent::__construct();

		//get data from feed specific to the site
		$ch = curl_init($this->feed_uri.$slug);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$data = curl_exec($ch);
		curl_close($ch);

		//grab only objects that are type building
		$json = json_decode($data, true);

		//check to make sure only one site was returned
		if(!isset($json['sites'], $json['sites'][0])){
			throw new Exception("JSON Feed not found or malformed.", 1);
		}

		//isolate single site and CMS parts
		$site = $json['sites'][0];

		//create child objects
		foreach ($site['alerts'] as $alert) {
			$this->alerts[] = new DirectoryHelperAlert($alert);
		}
		foreach ($site['documents'] as $doc) {
			$this->docs[] = new DirectoryHelperDocument($doc);
		}
		foreach ($site['news'] as $article) {
			$this->news[] = new DirectoryHelperArticle($article);
		}
		foreach ($site['roles'] as $role) {
			$this->roles[] = new DirectoryHelperRole($role);
		}
	}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- HTML ACCESSORS ------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

	//the first alert (default)
	public function PrintAlert(){
		$output = null;
		if(!empty($this->alerts)){
			$output .= $this->alerts[0]->PrintAlert();
		}
		return $output;
	}

	//all alerts
	public function PrintAlerts(){
		$output = null;
		if(!empty($alerts)){
			foreach($this->alerts as $alert){
				$output .= $alert->PrintAlert();
			}
		}
		return $output;
	}

	//a single document
	public function PrintDocument($slug){
		$output = null;
		$document = null;

		//search for the slug in the document collection
		if(!empty($this->docs)){
			foreach($this->docs as $doc){
				if($doc->GetSlug() == $slug){
					$document = $doc;
				}
			}
		}

		//check type, call html accessor
		if($document instanceof DirectoryHelperDocument){
			$output .= $document->PrintDocument();
		}

		return $output;
	}
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE ALERT CLASS --------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperAlert extends DirectoryHelperConfig{
	private $id;
	private $title;
	private $message;
	private $url;
	private $start;
	private $end;
	private $isPlanned;
	private $isSiteWide;
	private $created;
	private $modified;

	public function __construct($json){
		parent::__construct();

		//populate properties with json values
		if(!empty($json)){
			$this->id           = $json['id'];
			$this->title        = strip_tags($json['title']);
			$this->message      = strip_tags($json['message']);
			$this->url          = $json['url'];
			$this->start        = $json['start'];
			$this->end          = $json['end'];
			$this->isPlanned    = $json['isPlanned'];
			$this->isSiteWide   = $json['isSiteWide'];
			$this->created      = $json['created'];
			$this->modified     = $json['modified'];
		}
	}

	public function PrintAlert(){
		$output = null;

		if($this->id == null){
			return $output;
		}

		$output .= '<div class="cautionbar">';
		$output .= '<p><strong>'.$this->title.':</strong>';

		if($this->url != null){
			$output .= '<a href="'.$this->url.'" class="external">'.$this->message.'</a>';
		} else {
			$output .= $this->message;
		}

		$output .= '</p></div>';
		$output .= '<div class="hr-blank"></div>';

		return $output;
	}
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE DOCUMENT CLASS -----------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperDocument extends DirectoryHelperConfig{
	private $id;
	private $name;
	private $slug;
	private $url;
	private $created;
	private $modified;

	public function __construct($json){
		parent::__construct();

		//populate properties with json values
		if(!empty($json)){
			$this->id           = $json['id'];
			$this->name         = strip_tags($json['name']);
			$this->slug         = $json['slug'];
			$this->url          = $json['url'];
			$this->created      = $json['created'];
			$this->modified     = $json['modified'];
		}
	}

	//used in slug search from the main object
	public function GetSlug(){
		return $this->slug;
	}

	public function PrintDocument(){
		$output = null;

		if($this->id == null){
			return $output;
		}

		$output .= '<a href="'.$this->directory_uri.$this->url.'">'.$this->name.'</a>';
		return $output;
	}
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE NEWS ARTICLE CLASS -------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperArticle extends DirectoryHelperConfig{
	
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE ROLE CLASS ---------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperRole extends DirectoryHelperConfig{
	
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE STAFF CLASS --------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperStaff extends DirectoryHelperConfig{

}

?>