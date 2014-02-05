<?php

class DirectoryHelperConfig{
	protected $directory_uri;
	protected $archive_uri;
	protected $feed_uri;
	protected $blank_img;
	protected $blank_user;
	protected $allowed_html;

	public function __construct(){
		//open config file
		$config = parse_ini_file('config.ini');

		//set config properties
		$this->directory_uri    = $config['DIRECTORY_URI'];
		$this->archive_uri      = $config['ARCHIVE_URI'];
		$this->feed_uri         = $config['FEED_URI'];
		$this->collapse_uri     = $config['COLLAPSE_URI'];
		$this->blank_img        = $config['BLANK_IMG'];
		$this->blank_user       = $config['BLANK_USER'];
		$this->allowed_html     = "<a><p><br><ol><ul><li><strong><em>";
	}
}

class DirectoryHelper extends DirectoryHelperConfig{
	private $slug;
	private $staff_collapsed;

	//object containers
	private $alerts	= [];
	private $docs 	= [];
	private $news 	= [];
	private $roles 	= [];

	//to prevent object instantiation
	public function __construct($slug){
		parent::__construct();

		//defaults
		$this->slug = $slug;
		$this->staff_collapsed = false;

		//get data from feed specific to the site
		$ch = curl_init($this->feed_uri.'/'.$slug);
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
/*--- OPTION MUTATORS -----------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

	public function SetBlankUser($user){
		foreach ($this->news as $article) {
			$article->ReplaceBlankUser(strip_tags($user));
		}
	}

	public function StaffCollapsed($bool){
		if(!is_bool($bool)){
			throw new Exception("StaffCollapsed value must be a boolean value.", 1);
		}
		$this->staff_collapsed = $bool;
	}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- DATA ACCESSORS ------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

	public function GetAlerts(){
		return $this->alerts;
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

	//print an alert only if it is marked as site-wide
	public function PrintSiteAlert(){
		$output = null;
		$sitewide = null;
		if(empty($this->alerts)){
			return $output;
		}

		foreach($this->alerts as $alert){
			if($alert->GetScope() == true){
				$sitewide = $alert;
			}
		}

		if($sitewide instanceof DirectoryHelperAlert){
			$output .= $sitewide->PrintAlert();
		}

		return $output;
	}

	//all alerts
	public function PrintAlerts(){
		$output = null;
		if(!empty($this->alerts)){
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

	//current news articles
	public function PrintNews($includeBillboards = false){
		$output = null;

		if(!empty($this->news)){
			foreach($this->news as $article){
				if($includeBillboards || !$article->HasBillboard()){
					$output .= $article->PrintNews();
				}
			}
		} else {
			$output .= '<p>No news articles at this time.</p>';
		}

		//news footer
		$output .= '<div class="top-b"></div>';
		$output .= '<div class="datestamp">';
		$output .= '<a href="'.$this->archive_uri.'/'.$this->slug.'">&raquo;News Archive</a>';
		$output .= '</div>';

		return $output;
	}	

	//current news articles
	public function PrintBillboard(){
		$output = null;

		if(!empty($this->news)){

			$output .= '<div id="slate_container"><div id="slate"><div id="slider">';

			foreach($this->news as $article){
				if($article->HasBillboard() != null){
					$output .= $article->PrintBillboard();
				}
			}

			$output .= '</div></div></div>';

			foreach($this->news as $article){
				if($article->HasBillboard() != null){
					$output .= $article->PrintBillboardCaptions();
				}
			}			
		}

		return $output;
	}	

	//all staff
	public function PrintStaff($headers = false){
		$output = null;

		if($this->staff_collapsed){
			$output .= '<script type="text/javascript" src="'.$this->collapse_uri.'"></script>';
		}

		if(!empty($this->roles)){
			foreach($this->roles as $role){
				$output .= $role->PrintRole($headers);
			}
		} else {
			$output .= '<p>No staff members at this time.</p>';
		}

		return $output;
	}	

	//only staff in a single role
	public function PrintRole($name){
		$output = null;
		$selected = null;

		if($this->staff_collapsed){
			$output .= '<script type="text/javascript" src="'.$this->collapse_uri.'"></script>';
		}

		if(!empty($this->roles)){
			foreach($this->roles as $role){
				if(strtolower($role->GetName()) == strtolower($name)){
					$selected = $role;
				}
			}

			if($selected instanceof DirectoryHelperRole){
				$output .= $selected->PrintRole();
			} else {
				$output .= '<p>No role by this name.</p>';
			}

		} else {
			$output .= '<p>No staff members in this role.</p>';
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

	public function GetAlert(){
		return [$this->title, $this->message, $this->url, $this->isPlanned, $this->isSiteWide];
	}

	public function GetScope(){
		return $this->isSiteWide;
	}

	public function PrintAlert(){
		$output = null;

		if($this->id == null){
			return $output;
		}

		$output .= $this->isPlanned ? '<div class="cautionbar">' : '<div class="alertbar">';
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
	private $id;
	private $user;
	private $title;
	private $strapline;
	private $summary;
	private $extended;
	private $thumb;
	private $billboard;
	private $url;
	private $created;
	private $modified;

	public function __construct($json){
		parent::__construct();

		//populate properties with json values
		if(!empty($json)){
			$this->id           = $json['id'];
			$this->title        = strip_tags($json['title']);
			$this->strapline    = strip_tags($json['strapline']);
			$this->summary      = strip_tags($json['summary'], $this->allowed_html);
			$this->extended     = strip_tags($json['extended'], $this->allowed_html);
			$this->thumb        = $json['thumb'];
			$this->billboard    = $json['billboard'];
			$this->url          = $json['url'];
			$this->created      = $json['posted'];
			$this->modified     = $json['modified'];

			if($this->thumb == null){
				$this->thumb = $this->blank_img;
			} else {
				$this->thumb = $this->directory_uri.$this->thumb;
			}

			if($this->user == null){
				$this->user = $this->blank_user;
			}

			if(substr($this->url, 0, 6) == '/file/' || substr($this->url, 0, 9) == '/article/'){
				$this->url = $this->directory_uri.$this->url;
			}
		}
	}

	public function ReplaceBlankUser($user){
		if($this->user == $this->blank_user){
			$this->user = $user;
		}
	}

	public function HasBillboard(){
		return $this->billboard != null;
	}

	public function PrintNews(){
		$output = null;

		if($this->id == null){
			return $output;
		}

		//start news block, image
		$output .= '<div class="news">';
		$output .= '<img src="'.$this->thumb.'" alt="thumb" />';
		
		//news content
		$output .= '<div class="news-content">';

		//title with or without link
		if($this->url != null){
			$output .= '<div class="news-title bullets">';
			$output .= '<a href="'.$this->url.'">'.$this->title.'</a>';
			$output .= '</div>';
		} else {
			$output .= '<div class="news-title">'.$this->title.'</div>';
		}

		//strapline
		$output .= '<div class="news-strapline">'.$this->strapline.'</div>';
		
		//datestamp and authorship
		$output .= '<div class="datestamp">';
		$output .= date("l, F jS, Y @ g:ia", strtotime($this->created)).' by '.$this->user;
		$output .= '</div>';

		//news body
		$output .= '<p class="news-summary">'.nl2br($this->summary).'</p>';
		
		//extended article link
		if($this->extended != null){
			$output .= '<p><a href="'.$this->url.'">[Read More]</p>';
		}
		
		//end news block
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="hr-blank"></div>';

		return $output;
	}

	public function PrintBillboard(){
		$output = null;

		if($this->billboard == null){
			return $output;
		}

		$image_tag = '<img src="'.$this->directory_uri.$this->billboard.'" alt="thumb" title="#news'.$this->id.'" />';

		//title with or without link
		if($this->url != null){
			$output .= '<a href="'.$this->url.'">'.$image_tag.'</a>';
		} else {
			$output .= $image_tag;
		}

		return $output;
	}

	public function PrintBillboardCaptions(){
		$output = null;

		if($this->billboard == null){
			return $output;
		}

		$output .= '<div id="news'.$this->id.'" class="nivo-html-caption">';
		$output .= '<div class="nivo-padding">';
		$output .= '<div class="nivo-title">'.$this->title.'</div>';
		$output .= '<div class="nivo-strapline">'.$this->strapline.'</div>';
		$output .= '</div></div>';

		return $output;
	}
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE ROLE CLASS ---------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperRole extends DirectoryHelperConfig{
	private $id;
	private $name;
	private $staff = [];

	public function __construct($json){
		parent::__construct();

		//populate properties with json values
		if(!empty($json)){
			$this->id = $json['id'];
			$this->name = strip_tags($json['name']);

			//create child objects
			foreach ($json['staff'] as $member) {
				$this->staff[] = new DirectoryHelperStaff($member);
			}
		}
	}

	public function GetName(){
		return $this->name;
	}

	public function PrintRole($headers = false){
		$output = null;

		if($this->id == null){
			return $output;
		}

		if($headers){
			$output .= '<div class="staff-role">'.$this->name.'</div>';
		}

		foreach($this->staff as $member){
			$output .= $member->PrintStaff();
		}		

		return $output;
	}	
}

/*-------------------------------------------------------------------------------------------------------------------*/
/*--- SINGLE STAFF CLASS --------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/

class DirectoryHelperStaff extends DirectoryHelperConfig{
	private $id;
	private $fname;
	private $lname;
	private $prefix;
	private $suffix;
	private $name;
	private $email;
	private $phone;
	private $title;
	private $details;
	private $isPrimary;
	private $image;

	public function __construct($json){
		parent::__construct();

		//populate properties with json values
		if(!empty($json)){
			$this->id           = $json['id'];
			$this->fname        = $json['fname'];
			$this->lname        = $json['lname'];
			$this->prefix       = $json['prefix'];
			$this->suffix       = $json['suffix'];
			$this->email        = $json['email'];
			$this->phone        = $json['phone'];
			$this->title        = $json['title'];
			$this->details      = strip_tags($json['details'], $this->allowed_html);
			$this->isPrimary    = $json['isPrimary'];
			$this->image        = $json['image'];

			if($this->image == null){
				$this->image = $this->blank_img;
			} else {
				$this->image = $this->directory_uri.$this->image;
			}

			//construct full name
			$this->name = $this->fname.' '.$this->lname;
			if($this->prefix != null){
				$this->name = $this->prefix.' '.$this->name;
			}
			if($this->suffix != null){
				$this->name = $this->name.', '.$this->suffix;
			}
		}
	}

	public function PrintStaff(){
		$output = null;

		if($this->id == null){
			return $output;
		}

		//start staff block, image
		$output .= '<div class="staff">';
		$output .= '<img src="'.$this->image.'" alt="thumb" />';
		
		//staff content
		$output .= '<div class="staff-content">';
		$output .= '<div class="staff-name">'.$this->name.'</div>';
		if($this->title != null){
			$output .= '<div class="staff-title">'.$this->title.'</div>';
		}
		if($this->email != null){
			$output .= '<div class="staff-email"><a href="mailto:'.$this->email.'">'.$this->email.'</a></div>';
		}
		if($this->phone != null){
			$output .= '<div class="staff-phone">'.$this->phone.'</div>';
		}

		//staff body
		if(strlen($this->details) != strlen(strip_tags($this->details))){
			$output .= '<div class="staff-details">'.$this->details.'</div>';
		} else {
			$output .= '<div class="staff-details"><p>'.nl2br($this->details).'</p></div>';
		}
		
		//end staff block
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="hr-blank"></div>';

		return $output;
	}
}

?>