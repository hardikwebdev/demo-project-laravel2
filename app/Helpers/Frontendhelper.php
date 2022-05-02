<?php

function active_campaign_api($url=null,$post=[]){

    $url = env('ACTIVECAMPAIGN_API_URL').'/api/3/'.$url;
    $token = env('ACTIVECAMPAIGN_API_KEY');
    
    $ch = curl_init($url);

    if(count($post) > 0){
    	$post = json_encode($post);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }else{
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Api-Token: '.$token));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    return $result;
}

function front_asset($url){
	return asset('public/frontend/'.$url);
}
function admin_asset($url){
	return asset('public/backend/assets/'.$url);
}
function web_asset($url){
	return asset('public/frontend/assets/'.$url);
}
function set_php_config(){
	ini_set('post_max_size', '52428800'); /*# 50MB*/
	ini_set('upload_max_filesize', '52428800'); /*# 50MB*/
	ini_set('max_execution_time', '300');  /*# 5 minutes*/
}
function bytesToHuman($bytes){
	$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

	for ($i = 0; $bytes > 1024; $i++) {
		$bytes /= 1024;
	}

	return round($bytes, 2) . ' ' . $units[$i];
}
function get_time_ago( $time )
{
	$time_difference = time() - $time;

	if( $time_difference < 1 ) { return 'less than 1 second ago'; }
	$condition = array( 12 * 30 * 24 * 60 * 60 =>  'year',
		30 * 24 * 60 * 60       =>  'month',
		24 * 60 * 60            =>  'day',
		60 * 60                 =>  'hour',
		60                      =>  'minute',
		1                       =>  'second'
	);

	foreach( $condition as $secs => $str )
	{
		$d = $time_difference / $secs;

		if( $d >= 1 )
		{
			$t = round( $d );
			return $t . ' ' . $str . ( $t > 1 ? 's' : '' ) . ' ago';
		}
	}
}

function get_job_days_ago($created_at){
	$now = \Carbon\Carbon::now();
	$hours= $now->diffInHours($created_at);
	if($hours > 24)
	{

		$days=$hours/24;

		$time=intval(round($days)).' Days Ago';
	}
	elseif($hours < 1)
	{
		$min = $now->diffInMinutes($created_at);
		$time =$min.' Minutes Ago';
	}
	else
	{
		$time =$hours.' Hours Ago';
	}
	return $time;
}

function display_expire_on($expire_on){
	$now = strtotime(date('Y-m-d')); 
	$your_date = strtotime($expire_on);
	$datediff = $your_date - $now;

	$diff_date = round($datediff / (60 * 60 * 24));

	if($diff_date == 1){
		return 'Expires Today';
	}else{
		return 'Expires in '.$diff_date.' days';
	}
}


function displayRating($seller_rating = 0 ,$showFiveStar = 0){
	$seller_rating_p =  ($seller_rating*100)/5;
	
	if($showFiveStar == 1){
		$rating_star ='<div class="star-ratings-sprite"><span style="width:'.$seller_rating_p.'%" class="star-ratings-sprite-rating"></span></div>';
	}else if($showFiveStar == 2){
		$rating_star ='<div class="star-ratings-sprite"><span style="width:'.$seller_rating_p.'%" class="star-ratings-sprite-rating"></span></div>';
	}else{
		$rating_star ='<div class="star-ratings-sprite"><span style="width:'.$seller_rating_p.'%" class="star-ratings-sprite-rating"></span></div>';
	}
	return $rating_star;
}

function displayCourseUserRating($seller_rating = 0){
	$seller_rating_p =  ($seller_rating*100)/5;
	return '<div class="star-ratings-sprite yellow-star"><span style="width:'.$seller_rating_p.'%" class="star-ratings-sprite-rating yellow-star"></span></div>';
}

function display_subtitle($subtitle="", $description="",$length="80"){
	$subtitle = preg_replace('/[^A-Za-z0-9\-]/', ' ', $subtitle);
	if ($subtitle){
		if (strlen(strip_tags($subtitle)) > $length) {
			return substr(strip_tags($subtitle), 0, $length)."...";
		}else{
			return substr(strip_tags($subtitle), 0, $length);
		}
	}else{
		if (strlen(strip_tags($description)) > $length) {
			return substr(strip_tags($description), 0, $length)."...";
		}else{
			return substr(strip_tags($description), 0, $length);
		}
	}
}
function display_username($username="", $length="8"){
	if (strlen(strip_tags($username)) > $length) {
		return substr(strip_tags($username), 0, $length)."...";
	}else{
		return substr(strip_tags($username), 0, $length);
	}
}
function display_title($title="", $length="80"){
	$title = str_replace(',', ' ', $title); 
	$title = str_replace('<', ' ', $title); 
	$title = str_replace('>', ' ', $title); 
	if (strlen(strip_tags($title)) > $length) {
		return substr(strip_tags($title), 0, $length)."...";
	}else{
		return substr(strip_tags($title), 0, $length);
	}
}

function display_title_for_api($title="", $length="80"){
	$title = \Str::limit($title,$length);
	$title = str_replace(',', ' ', $title); 
	$title = str_replace('<', ' ', $title); 
	$title = str_replace('>', ' ', $title); 
	return $title;
}

function display_content($content="", $length="80"){
	$content = str_replace("’" ,"'", $content);
	if (strlen(strip_tags($content)) > $length) {
		return substr(strip_tags($content), 0, $length)."...";
	}else{
		return substr(strip_tags($content), 0, $length);
	}
}
function display_title_predict_search($title="", $length="50"){
	$title = str_replace("’" ,"'", $title);
	$title = preg_replace('/[^A-Za-z0-9_\-(),]/', ' ', $title); 
	if (strlen(strip_tags($title)) > $length) {
		return substr(strip_tags($title), 0, $length);
	}else{
		return substr(strip_tags($title), 0, $length);
	}
}

function dispay_money_format($money=""){
	$rounded = floor($money*100)/100;
	return number_format($rounded, 2, '.', ',');
}

function remove_timestamp_from_filename($string){
	return implode('_', array_slice( explode( '_', $string ),1 ) );
}

function array_to_date_list($dates_array,$saparator=null){
	$list = [];
	foreach ($dates_array as $date_value) {
		$list[] = date('jS M Y',strtotime($date_value));
	}
	if($saparator == null){
		return implode(', ',$list);
	}else{
		return implode($saparator,$list);
	}
}
function date_range_to_list($from_date,$to_date,$saparator=null){
	$list = [];
	while ($from_date <= $to_date) {
		$list[] = date('jS M Y',strtotime($from_date));
		$from_date = date('Y-m-d', strtotime($from_date . "+1 days"));
	}
	if($saparator == null){
		return implode(', ',$list);
	}else{
		return implode($saparator,$list);
	}
}

function country_code_list(){

	$defaultCountry = ['44' => 'UK (+44)','1' => 'USA (+1)'];

	$countryArray = [
		'355' => 'Albania (+355)',
		'213' => 'Algeria (+213)',
		'376' => 'Andorra (+376)',
		'244' => 'Angola (+244)',
		'1264' => 'Anguilla (+1264)',
		'1268' => 'Antigua & Barbuda (+1268)',
		'54' => 'Argentina (+54)',
		'374' => 'Armenia (+374)',
		'297' => 'Aruba (+297)',
		'61' => 'Australia (+61)',
		'43' => 'Austria (+43)',
		'994' => 'Azerbaijan (+994)',
		'1242' => 'Bahamas (+1242)',
		'973' => 'Bahrain (+973)',
		'880' => 'Bangladesh (+880)',
		'1246' => 'Barbados (+1246)',
		'375' => 'Belarus (+375)',
		'32' => 'Belgium (+32)',
		'501' => 'Belize (+501)',
		'229' => 'Benin (+229)',
		'1441' => 'Bermuda (+1441)',
		'975' => 'Bhutan (+975)',
		'591' => 'Bolivia (+591)',
		'387' => 'Bosnia Herzegovina (+387)',
		'267' => 'Botswana (+267)',
		'55' => 'Brazil (+55)',
		'673' => 'Brunei (+673)',
		'359' => 'Bulgaria (+359)',
		'226' => 'Burkina Faso (+226)',
		'257' => 'Burundi (+257)',
		'855' => 'Cambodia (+855)',
		'237' => 'Cameroon (+237)',
		'238' => 'Cape Verde Islands (+238)',
		'1345' => 'Cayman Islands (+1345)',
		'236' => 'Central African Republic (+236)',
		'56' => 'Chile (+56)',
		'86' => 'China (+86)',
		'57' => 'Colombia (+57)',
		'269' => 'Comoros (+269)',
		'242' => 'Congo (+242)',
		'682' => 'Cook Islands (+682)',
		'506' => 'Costa Rica (+506)',
		'385' => 'Croatia (+385)',
		'53' => 'Cuba (+53)',
		'599' => 'Curacao (+599)',
		'90392' => 'Cyprus North (+90392)',
		'357' => 'Cyprus South (+357)',
		'420' => 'Czech Republic (+420)',
		'45' => 'Denmark (+45)',
		'253' => 'Djibouti (+253)',
		'1809' => 'Dominican Republic (+1809)',
		'1829' => 'Dominican Republic (+1829)',
		'1849' => 'Dominican Republic (+1849)',
		'593' => 'Ecuador (+593)',
		'20' => 'Egypt (+20)',
		'503' => 'El Salvador (+503)',
		'240' => 'Equatorial Guinea (+240)',
		'291' => 'Eritrea (+291)',
		'372' => 'Estonia (+372)',
		'251' => 'Ethiopia (+251)',
		'500' => 'Falkland Islands (+500)',
		'298' => 'Faroe Islands (+298)',
		'679' => 'Fiji (+679)',
		'358' => 'Finland (+358)',
		'33' => 'France (+33)',
		'594' => 'French Guiana (+594)',
		'689' => 'French Polynesia (+689)',
		'241' => 'Gabon (+241)',
		'220' => 'Gambia (+220)',
		'995' => 'Georgia (+995)',
		'49' => 'Germany (+49)',
		'233' => 'Ghana (+233)',
		'350' => 'Gibraltar (+350)',
		'30' => 'Greece (+30)',
		'299' => 'Greenland (+299)',
		'1473' => 'Grenada (+1473)',
		'590' => 'Guadeloupe (+590)',
		'671' => 'Guam (+671)',
		'502' => 'Guatemala (+502)',
		'224' => 'Guinea (+224)',
		'245' => 'Guinea - Bissau (+245)',
		'592' => 'Guyana (+592)',
		'509' => 'Haiti (+509)',
		'504' => 'Honduras (+504)',
		'852' => 'Hong Kong (+852)',
		'36' => 'Hungary (+36)',
		'354' => 'Iceland (+354)',
		'91' => 'India (+91)',
		'62' => 'Indonesia (+62)',
		'98' => 'Iran (+98)',
		'964' => 'Iraq (+964)',
		'353' => 'Ireland (+353)',
		'972' => 'Israel (+972)',
		'39' => 'Italy (+39)',
		'1876' => 'Jamaica (+1876)',
		'81' => 'Japan (+81)',
		'962' => 'Jordan (+962)',
		'7' => 'Kazakhstan (+7)',
		'254' => 'Kenya (+254)',
		'686' => 'Kiribati (+686)',
		'850' => 'Korea North (+850)',
		'82' => 'Korea South (+82)',
		'965' => 'Kuwait (+965)',
		'996' => 'Kyrgyzstan (+996)',
		'856' => 'Laos (+856)',
		'371' => 'Latvia (+371)',
		'961' => 'Lebanon (+961)',
		'266' => 'Lesotho (+266)',
		'231' => 'Liberia (+231)',
		'218' => 'Libya (+218)',
		'417' => 'Liechtenstein (+417)',
		'370' => 'Lithuania (+370)',
		'352' => 'Luxembourg (+352)',
		'853' => 'Macao (+853)',
		'389' => 'Macedonia (+389)',
		'261' => 'Madagascar (+261)',
		'265' => 'Malawi (+265)',
		'60' => 'Malaysia (+60)',
		'960' => 'Maldives (+960)',
		'223' => 'Mali (+223)',
		'356' => 'Malta (+356)',
		'692' => 'Marshall Islands (+692)',
		'596' => 'Martinique (+596)',
		'222' => 'Mauritania (+222)',
		'230' => 'Mauritius (+230)',
		'269' => 'Mayotte (+269)',
		'52' => 'Mexico (+52)',
		'691' => 'Micronesia (+691)',
		'373' => 'Moldova (+373)',
		'377' => 'Monaco (+377)',
		'976' => 'Mongolia (+976)',
		'1664' => 'Montserrat (+1664)',
		'212' => 'Morocco (+212)',
		'258' => 'Mozambique (+258)',
		'95' => 'Myanmar (+95)',
		'264' => 'Namibia (+264)',
		'674' => 'Nauru (+674)',
		'977' => 'Nepal (+977)',
		'31' => 'Netherlands (+31)',
		'687' => 'New Caledonia (+687)',
		'64' => 'New Zealand (+64)',
		'505' => 'Nicaragua (+505)',
		'227' => 'Niger (+227)',
		'234' => 'Nigeria (+234)',
		'683' => 'Niue (+683)',
		'672' => 'Norfolk Islands (+672)',
		'670' => 'Northern Marianas (+670)',
		'47' => 'Norway (+47)',
		'968' => 'Oman (+968)',
		'92' => 'Pakistan(+92)',
		'680' => 'Palau (+680)',
		'507' => 'Panama (+507)',
		'675' => 'Papua New Guinea (+675)',
		'595' => 'Paraguay (+595)',
		'51' => 'Peru (+51)',
		'63' => 'Philippines (+63)',
		'48' => 'Poland (+48)',
		'351' => 'Portugal (+351)',
		'1787' => 'Puerto Rico (+1787)',
		'974' => 'Qatar (+974)',
		'262' => 'Reunion (+262)',
		'40' => 'Romania (+40)',
		'7' => 'Russia (+7)',
		'250' => 'Rwanda (+250)',
		'378' => 'San Marino (+378)',
		'239' => 'Sao Tome & Principe (+239)',
		'966' => 'Saudi Arabia (+966)',
		'221' => 'Senegal (+221)',
		'381' => 'Serbia (+381)',
		'248' => 'Seychelles (+248)',
		'232' => 'Sierra Leone (+232)',
		'65' => 'Singapore (+65)',
		'421' => 'Slovak Republic (+421)',
		'386' => 'Slovenia (+386)',
		'677' => 'Solomon Islands (+677)',
		'252' => 'Somalia (+252)',
		'27' => 'South Africa (+27)',
		'34' => 'Spain (+34)',
		'94' => 'Sri Lanka (+94)',
		'290' => 'St. Helena (+290)',
		'1869' => 'St. Kitts (+1869)',
		'1758' => 'St. Lucia (+1758)',
		'249' => 'Sudan (+249)',
		'597' => 'Suriname (+597)',
		'268' => 'Swaziland (+268)',
		'46' => 'Sweden (+46)',
		'41' => 'Switzerland (+41)',
		'963' => 'Syria (+963)',
		'255' => 'Tanzania (+255)',
		'886' => 'Taiwan (+886)',
		//'7' => 'Tajikstan (+7)',
		'66' => 'Thailand (+66)',
		'228' => 'Togo (+228)',
		'676' => 'Tonga (+676)',
		'1868' => 'Trinidad & Tobago (+1868)',
		'216' => 'Tunisia (+216)',
		'90' => 'Turkey (+90)',
		//'7' => 'Turkmenistan (+7)',
		'993' => 'Turkmenistan (+993)',
		'1649' => 'Turks & Caicos Islands (+1649)',
		'688' => 'Tuvalu (+688)',
		'256' => 'Uganda (+256)',
		'380' => 'Ukraine (+380)',
		'971' => 'United Arab Emirates (+971)',
		'598' => 'Uruguay (+598)',
		//'7' => 'Uzbekistan (+7)',
		'678' => 'Vanuatu (+678)',
		'379' => 'Vatican City (+379)',
		'58' => 'Venezuela (+58)',
		'84' => 'Vietnam (+84)',
		//'84' => 'Virgin Islands - British (+1284)',
		//'84' => 'Virgin Islands - US (+1340)',
		'681' => 'Wallis & Futuna (+681)',
		'969' => 'Yemen (North)(+969)',
		'967' => 'Yemen (South)(+967)',
		'260' => 'Zambia (+260)',
		'263' => 'Zimbabwe (+263)',
	];
	asort($countryArray);
	return $defaultCountry + $countryArray;
}

function br2newline( $input ) {
	$out = str_replace( "<br>", "\n", $input );
	$out = str_replace( "<br/>", "\n", $out );
	$out = str_replace( "<br />", "\n", $out );
	$out = str_replace( "<BR>", "\n", $out );
	$out = str_replace( "<BR/>", "\n", $out );
	$out = str_replace( "<BR />", "\n", $out );
	return $out;
}

function round_price($price){
	if($price){
		return number_format($price, 2, '.', '');
	}else{
		return 0;
	}
}

/*Get admin fee charge*/
function get_service_change($value,$is_new_order){
	if($is_new_order == 1){
		return ($value * env('ADMIN_CHARGE_PER')) / 100;
	}else{
		return ($value * env('OLD_ORDER_ADMIN_CHARGE_PER')) / 100;
	}
}
function isMobileDevice() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

/*Get jim fee charge*/
function get_jim_service_change($value,$is_new_order){
	if($is_new_order == 1){
		$serviceCharge = ($value * env('ADMIN_CHARGE_PER')) / 100;
		return ($serviceCharge * env('JIM_CHARGE_PER')) / 100;
	}else{
		$serviceCharge = ($value * env('OLD_ORDER_ADMIN_CHARGE_PER')) / 100;
		return ($serviceCharge * env('OLD_ORDER_JIM_CHARGE_PER')) / 100;
	}
}

function block_email_list(){
	return ['yandex.com'];
}

function convertToEmoji($message) {
	$shortNames = [
		"<3" => ":heart:",
		"</3" => ":broken_heart:",
		":')" => ":joy:",
		":'-)" => ":joy:",
		":D" => ":smiley:",
		":-D" => ":smiley:",
		"=D" => ":smiley:",
		":)" => ":slight_smile:",
		":-)" => ":slight_smile:",
		"=]" => ":slight_smile:",
		"=)" => ":slight_smile:",
		":]" => ":slight_smile:",
		"':)" => ":sweat_smile:",
		"':-)" => ":sweat_smile:",
		"'=)" => ":sweat_smile:",
		"':D" => ":sweat_smile:",
		"':-D" => ":sweat_smile:",
		"'=D" => ":sweat_smile:",
		">:)" => ":laughing:",
		">;)" => ":laughing:",
		">=)" => ":laughing:",
		";)" => ":wink:",
		"':(" => ":sweat:",
		":*" => ":kissing_heart:",
		">:P" => ":stuck_out_tongue_winking_eye:",
		":(" => ":disappointed:",
		">:(" => ":angry:",
		":@" => ":angry:",
		";(" => ":cry:",
		">.<" => ":persevere:",
		"D:" => ":fearful:",
		":$" => ":flushed:",
		"O:-)" => ":innocent:",
		"B)" => ":sunglasses:",
		"-_-" => ":expressionless:",
		"=/" => ":confused:",
		":P" => ":stuck_out_tongue:",
		":p" => ":stuck_out_tongue:",
		":O" => ":open_mouth:",
		":o" => ":open_mouth:",
		":X" => ":no_mouth:",
		":x" => ":no_mouth:"
	];
	$newMessage = $message;
	foreach ($shortNames as $key => $value) {
		if (strpos($newMessage, $key) !== false) {
			$newMessage = str_replace($key, $value, $newMessage);
		}
	}
	return $newMessage;
}

function remove_links_from_string($string = ""){
	if($string != ""){
		/*$pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
		$replacement = "";
		$string = preg_replace($pattern, $replacement, $string);*/

		$string = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "", $string);

		/*$regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@";
		$string = preg_replace($regex, ' ', $string);

		$regex = "@(http?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@";
		$string = preg_replace($regex, ' ', $string);*/
		return $string;

	}
	return $string;
}

function remove_emoji($text){
	return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text);
}

function select_Timezone($selected = '') { 
  
    // Create a list of timezone 
    $OptionsArray = timezone_identifiers_list(); 
        $select= '<select name="user_timezone" class="form-control" name="timezone" id="timezone"> 
                    <option disabled selected> 
                        Please Select Timezone 
                    </option>'; 
          
        foreach($OptionsArray as $row ){ 
            $select .='<option value="'.$row.'"'; 
            $select .= ($row == $selected ? 'selected' : ''); 
            $select .= '>'.$row.'</option>'; 
        }  
          
        $select.='</select>'; 
    return $select; 
} 
  
function convert_timezone($time='', $toTz='', $fromTz='') {
	//$new_date = new DateTime($time, new DateTimeZone($fromTz));
	$new_date = $time->setTimezone(new DateTimeZone($toTz));
	return $new_date;
}

function getProfileImageURL($username) {
	$url = front_asset('images/profile-default-image.jpg');
	$user = App\User::select('id','profile_photo','photo_s3_key')->where('username',$username)->first();
	/* User Profile Picture */
	$url = get_user_profile_image_url($user);
	/* END User Profile Picture */
	return $url;
}

function services_with_restricted_cat_subcat($services = array()) {
	$message = "";
	if(sizeof($services) > 0) {
		$data = App\Service::with('seller_categories.category','seller_categories.subcategory')
							->whereIn('id',$services)->get();
	
		foreach ($data as $key => $value) {
			if(sizeof($value->seller_categories) > 0) {
				foreach ($value->seller_categories as $index => $row) {
					if($row->is_default == 0) {
						if($row->category->restrict_for_bluesnap == 1) {
							$message = "The Service having secondary category <b>".$row->category->category_name."</b> has been restricted for Credit Card payment.";
							return array("status" => "error", "message" => $message);
						} else if($row->subcategory->restrict_for_bluesnap) {
							$message = "The Service having secondary sub category <b>".$row->subcategory->subcategory_name."</b> has been restricted for Credit Card payment.";
							return array("status" => "error", "message" => $message);
						}
					} else if($row->is_default == 1) {
						if($row->category->restrict_for_bluesnap == 1) {
							$message = "The Service having primary category <b>".$row->category->category_name."</b> has been restricted for Credit Card payment.";
							return array("status" => "error", "message" => $message);
						} else if($row->subcategory->restrict_for_bluesnap == 1) {
							$message = "The Service having primary sub category <b>".$row->subcategory->subcategory_name."</b> has been restricted for Credit Card payment.";
							return array("status" => "error", "message" => $message);
						}
					}
				}
			}
		}
    }
	return array("status" => "success", "message" => $message);;
}

function send_push_notification($data) {
	$idSite = env('NOTIFICATION_IDSITE');
	$apiKey = env('NOTIFICATION_APIKEY');
	$url = env('NOTIFICATION_APIURL');
	
	$postData = array(
		 "subscriber_uids"=> $data['subscriber_id'],
		  "push_ttl"=> 604800000000000,
		  "auto_hide"=> 100000000,
		  "idSite"=> $idSite,
		  "title"=> $data['title'],
		  "body"=> $data['message'],
		  "link"=> $data['link']
		  //"link"=> '<a href="'.$data['link'].'" target="blank">Try it</a>'
	);
	$headerData = array(
		'content-type: application/json',
		'x-authorization-api-key: '.$apiKey
	);

	try {
		$handle = curl_init();
		curl_setopt_array($handle,
			array(
				CURLOPT_URL => $url,
				CURLOPT_POST       => true,
				CURLOPT_POSTFIELDS => json_encode($postData),
				CURLOPT_RETURNTRANSFER     => true,
			)
		);
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headerData);
		$data = curl_exec($handle);
		curl_close($handle);
	} catch(\Exception $e) {
		\Log::channel('notificationlog')->info(' Curl failure ');
	}

	\Log::channel('notificationlog')->info('Response  '.json_encode($data));
	return true;
}

function sendFCMNotification($firebaseToken, $title, $body)
{ // testing purpose, not using anywhere
	$data = [
		"registration_ids" => $firebaseToken,
		"notification" => [
			"title" => $title,
			"body" => $body,  
		]
	];
	$dataString = json_encode($data);
	\Log::channel('notificationlog')->info('fcm payload: '.$dataString);
	$headers = [
		'Authorization: key=' . env('NOTIFICATION_SERVER_KEY'),
		'Content-Type: application/json',
	];

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			
	$response = curl_exec($ch);

	\Log::channel('notificationlog')->info('fcm response: '.json_encode($response));
}

function makeClickableLinks($string) {
	if($string){
		$old_string = $string;
		/* replace img tag in tem data */
		preg_match_all('/(<img[^>]+>)/i', $string, $getImgTags);
		$temp_img_data_array = array();
		$j = 100;
		if(isset($getImgTags[1])){
			foreach ($getImgTags[1] as $value) {
				if($value && !in_array($value,$temp_img_data_array)){
					/* Replace anchor tag link to temp data */
					$img_temp_item = 'IMG_TEMP'.$j.time().md5(rand());
					$string = str_replace($value,$img_temp_item,$string); 
					$temp_img_data_array[$img_temp_item] = $value;
					$j++;
				}
			}
		}

		/* replace anchor tag if url is already in tag */
		preg_match_all('/<\/?a( [^>]*)?>/i', $string, $anchortags);
		if(isset($anchortags[1])){
			foreach ($anchortags[1] as $value) {
				if($value && !in_array($value,$temp_data_array)){
					/* Replace anchor tag link to temp data */
					$temp = $value.' target="_blank"';
					$string = str_replace($value,$temp,$string);
				}
			}
		}
		
		preg_match_all("/<a.*? href=(\"|')(.*?)(\"|').*?>(.*?)<\/a>/i", $string, $anchortags1);
		$temp_data_array = array();
		$i = 200;
		if(isset($anchortags1[0])){
			foreach ($anchortags1[0] as $value) {
				if($value && !in_array($value,$temp_data_array)){
					/* Replace anchor tag link to temp data */
					$temp_item = 'TEMP'.$i.time().md5(rand());
					$string = str_replace($value,$temp_item,$string); 
					$temp_data_array[$temp_item] = $value;
					$i++;
				}
			}
		}
		
		// add a space between link and &nbsp; other wise it will include with link 
		$string = str_replace('&nbsp;',' &nbsp;',$string);
		//taking all links in array from string
		preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $string, $match);
		//remove duplicate value from array
		$match[0] = array_unique($match[0]);
		//replace all links with anchour tags
		foreach ($match[0] as $value) {
			$url = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
			$string = str_replace($value,$url,$string);
		}

		/* Revert back to anchor tag*/
		foreach ($temp_data_array as $key => $value) {
			/* Replace anchor tag link to temp data */
			$string = str_replace($key,$value,$string);
		}

		/* Revert back to img tag*/
		foreach ($temp_img_data_array as $key => $value) {
			/* Replace img tag link to temp data */
			$string = str_replace($key,$value,$string);
		}
		return $string;
	}
	return $string;
}

function review_in_percentage($service_id,$star,$total_rating,$get_count=0) {
	$no_of_review = App\Order::where(['service_id' => $service_id,  'seller_rating' => $star])
	->whereIn('status',['cancelled','completed'])
	//->whereRaw('(completed_note is not null OR seller_rating > 0)')
	->where('seller_rating', '>', 0)
	->count();
	// whereIn('status' , ['completed','cancelled'])
	// return $no_of_review;
	if($get_count==1){
		return $no_of_review;
	}
	$per = ($no_of_review * 100) / $total_rating;
	return number_format($per,1, '.', '');
}

function get_interested_in($id){
	
	$value = '';

	if($id == 1){
		$value = 'Buying services to grow my business';
	}elseif($id == 2){
		$value = 'Selling my services';
	}elseif($id == 3){
		$value = 'Both buying and selling';
	}

	return $value;
}

function amount_to_show($amount) {
	if(is_numeric($amount) && floor($amount) != $amount) {
		return number_format($amount,2, '.', '');
	}
	return $amount;
}

function country_code_array(){

	$defaultCountry = '[
		  {
		    "id": 210,
		    "phonecode": "44",
		    "name": "UK"
		  },
		  {
		    "id": 211,
		    "phonecode": "1",
		    "name": "USA"
		  }
		]';

	$countryArray = '[
		  {
		    "id": 1,
		    "phonecode": "355",
		    "name": "Albania"
		  },
		  {
		    "id": 2,
		    "phonecode": "213",
		    "name": "Algeria"
		  },
		  {
		    "id": 3,
		    "phonecode": "376",
		    "name": "Andorra"
		  },
		  {
		    "id": 4,
		    "phonecode": "244",
		    "name": "Angola"
		  },
		  {
		    "id": 5,
		    "phonecode": "1264",
		    "name": "Anguilla"
		  },
		  {
		    "id": 6,
		    "phonecode": "1268",
		    "name": "Antigua & Barbuda"
		  },
		  {
		    "id": 7,
		    "phonecode": "54",
		    "name": "Argentina"
		  },
		  {
		    "id": 8,
		    "phonecode": "374",
		    "name": "Armenia"
		  },
		  {
		    "id": 9,
		    "phonecode": "297",
		    "name": "Aruba"
		  },
		  {
		    "id": 9,
		    "phonecode": "61",
		    "name": "Australia"
		  },
		  {
		    "id": 10,
		    "phonecode": "43",
		    "name": "Austria"
		  },
		  {
		    "id": 11,
		    "phonecode": "994",
		    "name": "Azerbaijan"
		  },
		  {
		    "id": 12,
		    "phonecode": "1242",
		    "name": "Bahamas"
		  },
		  {
		    "id": 13,
		    "phonecode": "973",
		    "name": "Bahrain"
		  },
		  {
		    "id": 14,
		    "phonecode": "880",
		    "name": "Bangladesh"
		  },
		  {
		    "id": 15,
		    "phonecode": "1246",
		    "name": "Barbados"
		  },
		  {
		    "id": 16,
		    "phonecode": "375",
		    "name": "Belarus"
		  },
		  {
		    "id": 17,
		    "phonecode": "32",
		    "name": "Belgium"
		  },
		  {
		    "id": 18,
		    "phonecode": "501",
		    "name": "Belize"
		  },
		  {
		    "id": 19,
		    "phonecode": "229",
		    "name": "Benin"
		  },
		  {
		    "id": 20,
		    "phonecode": "1441",
		    "name": "Bermuda"
		  },
		  {
		    "id": 21,
		    "phonecode": "975",
		    "name": "Bhutan"
		  },
		  {
		    "id": 22,
		    "phonecode": "591",
		    "name": "Bolivia"
		  },
		  {
		    "id": 23,
		    "phonecode": "387",
		    "name": "Bosnia Herzegovina"
		  },
		  {
		    "id": 24,
		    "phonecode": "267",
		    "name": "Botswana"
		  },
		  {
		    "id": 25,
		    "phonecode": "55",
		    "name": "Brazil"
		  },
		  {
		    "id": 26,
		    "phonecode": "673",
		    "name": "Brunei"
		  },
		  {
		    "id": 27,
		    "phonecode": "359",
		    "name": "Bulgaria"
		  },
		  {
		    "id": 28,
		    "phonecode": "226",
		    "name": "Burkina Faso"
		  },
		  {
		    "id": 29,
		    "phonecode": "257",
		    "name": "Burundi"
		  },
		  {
		    "id": 30,
		    "phonecode": "855",
		    "name": "Cambodia"
		  },
		  {
		    "id": 31,
		    "phonecode": "237",
		    "name": "Cameroon"
		  },
		  {
		    "id": 32,
		    "phonecode": "238",
		    "name": "Cape Verde Islands"
		  },
		  {
		    "id": 33,
		    "phonecode": "1345",
		    "name": "Cayman Islands"
		  },
		  {
		    "id": 34,
		    "phonecode": "236",
		    "name": "Central African Republic"
		  },
		  {
		    "id": 35,
		    "phonecode": "56",
		    "name": "Chile"
		  },
		  {
		    "id": 36,
		    "phonecode": "86",
		    "name": "China"
		  },
		  {
		    "id": 37,
		    "phonecode": "57",
		    "name": "Colombia"
		  },
		  {
		    "id": 38,
		    "phonecode": "269",
		    "name": "Comoros"
		  },
		  {
		    "id": 39,
		    "phonecode": "242",
		    "name": "Congo"
		  },
		  {
		    "id": 40,
		    "phonecode": "682",
		    "name": "Cook Islands"
		  },
		  {
		    "id": 41,
		    "phonecode": "506",
		    "name": "Costa Rica"
		  },
		  {
		    "id": 42,
		    "phonecode": "385",
		    "name": "Croatia"
		  },
		  {
		    "id": 43,
		    "phonecode": "53",
		    "name": "Cuba"
		  },
		  {
		    "id": 44,
		    "phonecode": "599",
		    "name": "Curacao"
		  },
		  {
		    "id": 45,
		    "phonecode": "90392",
		    "name": "Cyprus North"
		  },
		  {
		    "id": 46,
		    "phonecode": "357",
		    "name": "Cyprus South"
		  },
		  {
		    "id": 47,
		    "phonecode": "420",
		    "name": "Czech Republic"
		  },
		  {
		    "id": 48,
		    "phonecode": "45",
		    "name": "Denmark"
		  },
		  {
		    "id": 49,
		    "phonecode": "253",
		    "name": "Djibouti"
		  },
		  {
		    "id": 50,
		    "phonecode": "1809",
		    "name": "Dominican Republic"
		  },
		  {
		    "id": 51,
		    "phonecode": "1829",
		    "name": "Dominican Republic"
		  },
		  {
		    "id": 52,
		    "phonecode": "1849",
		    "name": "Dominican Republic"
		  },
		  {
		    "id": 53,
		    "phonecode": "593",
		    "name": "Ecuador"
		  },
		  {
		    "id": 54,
		    "phonecode": "20",
		    "name": "Egypt"
		  },
		  {
		    "id": 55,
		    "phonecode": "503",
		    "name": "El Salvador"
		  },
		  {
		    "id": 56,
		    "phonecode": "240",
		    "name": "Equatorial Guinea"
		  },
		  {
		    "id": 57,
		    "phonecode": "291",
		    "name": "Eritrea"
		  },
		  {
		    "id": 58,
		    "phonecode": "372",
		    "name": "Estonia"
		  },
		  {
		    "id": 59,
		    "phonecode": "251",
		    "name": "Ethiopia"
		  },
		  {
		    "id": 60,
		    "phonecode": "500",
		    "name": "Falkland Islands"
		  },
		  {
		    "id": 61,
		    "phonecode": "298",
		    "name": "Faroe Islands"
		  },
		  {
		    "id": 62,
		    "phonecode": "679",
		    "name": "Fiji"
		  },
		  {
		    "id": 63,
		    "phonecode": "358",
		    "name": "Finland"
		  },
		  {
		    "id": 64,
		    "phonecode": "594",
		    "name": "French Guiana"
		  },
		  {
		    "id": 65,
		    "phonecode": "689",
		    "name": "French Polynesia"
		  },
		  {
		    "id": 66,
		    "phonecode": "241",
		    "name": "Gabon"
		  },
		  {
		    "id": 67,
		    "phonecode": "220",
		    "name": "Gambia"
		  },
		  {
		    "id": 68,
		    "phonecode": "995",
		    "name": "Georgia"
		  },
		  {
		    "id": 69,
		    "phonecode": "49",
		    "name": "Germany"
		  },
		  {
		    "id": 70,
		    "phonecode": "233",
		    "name": "Ghana"
		  },
		  {
		    "id": 71,
		    "phonecode": "350",
		    "name": "Gibraltar"
		  },
		  {
		    "id": 72,
		    "phonecode": "30",
		    "name": "Greece"
		  },
		  {
		    "id": 73,
		    "phonecode": "299",
		    "name": "Greenland"
		  },
		  {
		    "id": 74,
		    "phonecode": "1473",
		    "name": "Grenada"
		  },
		  {
		    "id": 75,
		    "phonecode": "590",
		    "name": "Guadeloupe"
		  },
		  {
		    "id": 76,
		    "phonecode": "671",
		    "name": "Guam"
		  },
		  {
		    "id": 77,
		    "phonecode": "502",
		    "name": "Guatemala"
		  },
		  {
		    "id": 78,
		    "phonecode": "224",
		    "name": "Guinea"
		  },
		  {
		    "id": 79,
		    "phonecode": "245",
		    "name": "Guinea - Bissau"
		  },
		  {
		    "id": 80,
		    "phonecode": "592",
		    "name": "Guyana"
		  },
		  {
		    "id": 81,
		    "phonecode": "509",
		    "name": "Haiti"
		  },
		  {
		    "id": 82,
		    "phonecode": "504",
		    "name": "Honduras"
		  },
		  {
		    "id": 83,
		    "phonecode": "852",
		    "name": "Hong Kong"
		  },
		  {
		    "id": 84,
		    "phonecode": "36",
		    "name": "Hungary"
		  },
		  {
		    "id": 85,
		    "phonecode": "354",
		    "name": "Iceland"
		  },
		  {
		    "id": 86,
		    "phonecode": "91",
		    "name": "India"
		  },
		  {
		    "id": 87,
		    "phonecode": "62",
		    "name": "Indonesia"
		  },
		  {
		    "id": 88,
		    "phonecode": "98",
		    "name": "Iran"
		  },
		  {
		    "id": 89,
		    "phonecode": "964",
		    "name": "Iraq"
		  },
		  {
		    "id": 90,
		    "phonecode": "353",
		    "name": "Ireland"
		  },
		  {
		    "id": 91,
		    "phonecode": "972",
		    "name": "Israel"
		  },
		  {
		    "id": 92,
		    "phonecode": "39",
		    "name": "Italy"
		  },
		  {
		    "id": 93,
		    "phonecode": "1876",
		    "name": "Jamaica"
		  },
		  {
		    "id": 94,
		    "phonecode": "81",
		    "name": "Japan"
		  },
		  {
		    "id": 95,
		    "phonecode": "962",
		    "name": "Jordan"
		  },
		  {
		    "id": 96,
		    "phonecode": "7",
		    "name": "Kazakhstan"
		  },
		  {
		    "id": 97,
		    "phonecode": "254",
		    "name": "Kenya"
		  },
		  {
		    "id": 98,
		    "phonecode": "686",
		    "name": "Kiribati"
		  },
		  {
		    "id": 99,
		    "phonecode": "850",
		    "name": "Korea North"
		  },
		  {
		    "id": 100,
		    "phonecode": "82",
		    "name": "Korea South"
		  },
		  {
		    "id": 101,
		    "phonecode": "965",
		    "name": "Kuwait"
		  },
		  {
		    "id": 102,
		    "phonecode": "996",
		    "name": "Kyrgyzstan"
		  },
		  {
		    "id": 103,
		    "phonecode": "856",
		    "name": "Laos"
		  },
		  {
		    "id": 104,
		    "phonecode": "371",
		    "name": "Latvia"
		  },
		  {
		    "id": 105,
		    "phonecode": "961",
		    "name": "Lebanon"
		  },
		  {
		    "id": 106,
		    "phonecode": "266",
		    "name": "Lesotho"
		  },
		  {
		    "id": 107,
		    "phonecode": "231",
		    "name": "Liberia"
		  },
		  {
		    "id": 108,
		    "phonecode": "218",
		    "name": "Libya"
		  },
		  {
		    "id": 109,
		    "phonecode": "417",
		    "name": "Liechtenstein"
		  },
		  {
		    "id": 110,
		    "phonecode": "370",
		    "name": "Lithuania"
		  },
		  {
		    "id": 111,
		    "phonecode": "352",
		    "name": "Luxembourg"
		  },
		  {
		    "id": 112,
		    "phonecode": "389",
		    "name": "Macedonia"
		  },
		  {
		    "id": 113,
		    "phonecode": "261",
		    "name": "Madagascar"
		  },
		  {
		    "id": 114,
		    "phonecode": "265",
		    "name": "Malawi"
		  },
		  {
		    "id": 115,
		    "phonecode": "60",
		    "name": "Malaysia"
		  },
		  {
		    "id": 116,
		    "phonecode": "960",
		    "name": "Maldives"
		  },
		  {
		    "id": 117,
		    "phonecode": "223",
		    "name": "Mali"
		  },
		  {
		    "id": 118,
		    "phonecode": "356",
		    "name": "Malta"
		  },
		  {
		    "id": 119,
		    "phonecode": "692",
		    "name": "Marshall Islands"
		  },
		  {
		    "id": 120,
		    "phonecode": "596",
		    "name": "Martinique"
		  },
		  {
		    "id": 121,
		    "phonecode": "222",
		    "name": "Mauritania"
		  },
		  {
		    "id": 212,
		    "phonecode": "230",
		    "name": "Mauritius"
		  },
		  {
		    "id": 122,
		    "phonecode": "269",
		    "name": "Mayotte"
		  },
		  {
		    "id": 123,
		    "phonecode": "52",
		    "name": "Mexico"
		  },
		  {
		    "id": 124,
		    "phonecode": "691",
		    "name": "Micronesia"
		  },
		  {
		    "id": 125,
		    "phonecode": "373",
		    "name": "Moldova"
		  },
		  {
		    "id": 126,
		    "phonecode": "377",
		    "name": "Monaco"
		  },
		  {
		    "id": 127,
		    "phonecode": "976",
		    "name": "Mongolia"
		  },
		  {
		    "id": 128,
		    "phonecode": "1664",
		    "name": "Montserrat"
		  },
		  {
		    "id": 129,
		    "phonecode": "212",
		    "name": "Morocco"
		  },
		  {
		    "id": 130,
		    "phonecode": "258",
		    "name": "Mozambique"
		  },
		  {
		    "id": 131,
		    "phonecode": "95",
		    "name": "Myanmar"
		  },
		  {
		    "id": 132,
		    "phonecode": "264",
		    "name": "Namibia"
		  },
		  {
		    "id": 133,
		    "phonecode": "674",
		    "name": "Nauru"
		  },
		  {
		    "id": 134,
		    "phonecode": "977",
		    "name": "Nepal"
		  },
		  {
		    "id": 135,
		    "phonecode": "31",
		    "name": "Netherlands"
		  },
		  {
		    "id": 136,
		    "phonecode": "687",
		    "name": "New Caledonia"
		  },
		  {
		    "id": 137,
		    "phonecode": "64",
		    "name": "New Zealand"
		  },
		  {
		    "id": 138,
		    "phonecode": "505",
		    "name": "Nicaragua"
		  },
		  {
		    "id": 139,
		    "phonecode": "227",
		    "name": "Niger"
		  },
		  {
		    "id": 140,
		    "phonecode": "234",
		    "name": "Nigeria"
		  },
		  {
		    "id": 141,
		    "phonecode": "683",
		    "name": "Niue"
		  },
		  {
		    "id": 142,
		    "phonecode": "672",
		    "name": "Norfolk Islands"
		  },
		  {
		    "id": 143,
		    "phonecode": "670",
		    "name": "Northern Marianas"
		  },
		  {
		    "id": 144,
		    "phonecode": "670",
		    "name": "Northern Marianas"
		  },
		  {
		    "id": 145,
		    "phonecode": "47",
		    "name": "Norway"
		  },
		  {
		    "id": 146,
		    "phonecode": "968",
		    "name": "Oman"
		  },
		  {
		    "id": 147,
		    "phonecode": "92",
		    "name": "Pakistan"
		  },
		  {
		    "id": 148,
		    "phonecode": "680",
		    "name": "Palau"
		  },
		  {
		    "id": 149,
		    "phonecode": "507",
		    "name": "Panama"
		  },
		  {
		    "id": 150,
		    "phonecode": "675",
		    "name": "Papua New Guinea"
		  },
		  {
		    "id": 151,
		    "phonecode": "595",
		    "name": "Paraguay"
		  },
		  {
		    "id": 152,
		    "phonecode": "51",
		    "name": "Peru"
		  },
		  {
		    "id": 153,
		    "phonecode": "63",
		    "name": "Philippines"
		  },
		  {
		    "id": 154,
		    "phonecode": "48",
		    "name": "Poland"
		  },
		  {
		    "id": 155,
		    "phonecode": "351",
		    "name": "Portugal"
		  },
		  {
		    "id": 156,
		    "phonecode": "1787",
		    "name": "Puerto Rico"
		  },
		  {
		    "id": 157,
		    "phonecode": "974",
		    "name": "Qatar"
		  },
		  {
		    "id": 158,
		    "phonecode": "262",
		    "name": "Reunion"
		  },
		  {
		    "id": 159,
		    "phonecode": "40",
		    "name": "Romania"
		  },
		  {
		    "id": 160,
		    "phonecode": "7",
		    "name": "Russia"
		  },
		  {
		    "id": 161,
		    "phonecode": "250",
		    "name": "Rwanda"
		  },
		  {
		    "id": 162,
		    "phonecode": "378",
		    "name": "San Marino"
		  },
		  {
		    "id": 163,
		    "phonecode": "239",
		    "name": "Sao Tome & Principe"
		  },
		  {
		    "id": 164,
		    "phonecode": "966",
		    "name": "Saudi Arabia "
		  },
		  {
		    "id": 165,
		    "phonecode": "221",
		    "name": "Senegal "
		  },
		  {
		    "id": 166,
		    "phonecode": "381",
		    "name": "Serbia "
		  },
		  {
		    "id": 167,
		    "phonecode": "248",
		    "name": "Seychelles "
		  },
		  {
		    "id": 168,
		    "phonecode": "232",
		    "name": "Sierra Leone "
		  },
		  {
		    "id": 169,
		    "phonecode": "65",
		    "name": "Singapore "
		  },
		  {
		    "id": 170,
		    "phonecode": "421",
		    "name": "Slovak Republic "
		  },
		  {
		    "id": 171,
		    "phonecode": "386",
		    "name": "Slovenia "
		  },
		  {
		    "id": 172,
		    "phonecode": "677",
		    "name": "Solomon Islands "
		  },
		  {
		    "id": 173,
		    "phonecode": "252",
		    "name": "Somalia "
		  },
		  {
		    "id": 174,
		    "phonecode": "27",
		    "name": "South Africa "
		  },
		  {
		    "id": 175,
		    "phonecode": "34",
		    "name": "Spain "
		  },
		  {
		    "id": 176,
		    "phonecode": "94",
		    "name": "Sri Lanka "
		  },
		  {
		    "id": 177,
		    "phonecode": "290",
		    "name": "St. Helena "
		  },
		  {
		    "id": 178,
		    "phonecode": "1869",
		    "name": "St. Kitts "
		  },
		  {
		    "id": 179,
		    "phonecode": "1758",
		    "name": "St. Lucia "
		  },
		  {
		    "id": 180,
		    "phonecode": "249",
		    "name": "Sudan "
		  },
		  {
		    "id": 181,
		    "phonecode": "597",
		    "name": "Suriname "
		  },
		  {
		    "id": 182,
		    "phonecode": "268",
		    "name": "Swaziland "
		  },
		  {
		    "id": 183,
		    "phonecode": "46",
		    "name": "Sweden "
		  },
		  {
		    "id": 184,
		    "phonecode": "41",
		    "name": "Switzerland "
		  },
		  {
		    "id": 185,
		    "phonecode": "963",
		    "name": "Syria "
		  },
		  {
		    "id": 186,
		    "phonecode": "255",
		    "name": "Tanzania "
		  },
		  {
		    "id": 187,
		    "phonecode": "886",
		    "name": "Taiwan "
		  },
		  {
		    "id": 188,
		    "phonecode": "66",
		    "name": "Thailand "
		  },
		  {
		    "id": 189,
		    "phonecode": "228",
		    "name": "Togo "
		  },
		  {
		    "id": 190,
		    "phonecode": "676",
		    "name": "Tonga "
		  },
		  {
		    "id": 191,
		    "phonecode": "1868",
		    "name": "Trinidad & Tobago "
		  },
		  {
		    "id": 192,
		    "phonecode": "216",
		    "name": "Tunisia"
		  },
		  {
		    "id": 193,
		    "phonecode": "90",
		    "name": "Turkey"
		  },
		  {
		    "id": 194,
		    "phonecode": "993",
		    "name": "Turkmenistan"
		  },
		  {
		    "id": 195,
		    "phonecode": "1649",
		    "name": "Turks & Caicos Islands"
		  },
		  {
		    "id": 196,
		    "phonecode": "688",
		    "name": "Tuvalu"
		  },
		  {
		    "id": 197,
		    "phonecode": "256",
		    "name": "Uganda"
		  },
		  {
		    "id": 198,
		    "phonecode": "380",
		    "name": "Ukraine"
		  },
		  {
		    "id": 199,
		    "phonecode": "971",
		    "name": "United Arab Emirates"
		  },
		  {
		    "id": 200,
		    "phonecode": "598",
		    "name": "Uruguay"
		  },
		  {
		    "id": 201,
		    "phonecode": "678",
		    "name": "Vanuatu"
		  },
		  {
		    "id": 202,
		    "phonecode": "379",
		    "name": "Vatican City"
		  },
		  {
		    "id": 203,
		    "phonecode": "58",
		    "name": "Venezuela"
		  },
		  {
		    "id": 204,
		    "phonecode": "84",
		    "name": "Vietnam"
		  },
		  {
		    "id": 205,
		    "phonecode": "681",
		    "name": "Wallis & Futuna"
		  },
		  {
		    "id": 206,
		    "phonecode": "969",
		    "name": "Yemen (North)"
		  },
		  {
		    "id": 207,
		    "phonecode": "967",
		    "name": "Yemen (South)"
		  },
		  {
		    "id": 208,
		    "phonecode": "260",
		    "name": "Zambia"
		  },
		  {
		    "id": 209,
		    "phonecode": "263",
		    "name": "Zimbabwe"
		  }
		]';
		
	$country_array = array_merge(json_decode($defaultCountry,true), json_decode($countryArray,true));
	return $country_array;
}

function in_arrayi($needle)
{
	$haystack = ['Admin','demo','Support','Team'];
	return in_array(strtolower($needle), array_map('strtolower', $haystack));

}

function string_limit($string,$limit){
	
	if($string){
		return \Illuminate\Support\Str::limit($string, $limit, $end = '...');
	}

	return $string;
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function check_upgrade_plan_status($current_plan,$upgrade_plan) {
	$return = false;
	if($current_plan == 'basic' && ($upgrade_plan == 'standard' || $upgrade_plan == 'premium')) {
		$return = true;
	} else if($current_plan == 'standard' && $upgrade_plan == 'premium') {
		$return = true;
	}
	return $return;
}

function allow_to_upgrade_order($order) {
	$return = 'no';
	$allow_order_status = ['new','active'];
	if(
		in_array($order->status,$allow_order_status) && 
		$order->is_dispute == 0 && 
		count(App\Service::getServiceAllPlanWithGreaterPrice($order->service_id,$order->plan->price,$order->plan->id)) > 0 && 
		$order->is_job == 0 && 
		$order->is_recurring == 0 && 
		$order->is_custom_order == 0 && 
		$order->uid == \Auth::id() && 
		$order->plan_type != 'premium' && 
		\Auth::user()->is_sub_user() == false && 
		$order->service->three_plan == 1) {
			//Check for service is active
			$service_count = App\Service::where('id',$order->service_id)->select('id')
			->where('status', 'active')->where('is_delete', 0)->where('is_approved', 1)->where('three_plan',1)
			->count();
			if($service_count > 0){
				$return = 'yes';
			}
	}
	return $return;
}

function check_for_share_service($msg) {
	$result = false;
	if(substr($msg,0,14) == '[{@SERVICE_ID=' && substr($msg,strlen($msg) - 3,strlen($msg)) == '@}]') {
		$result = true;
	}
	return $result;
}

function get_total_cycle($order_id) {
	$cycle = 0;
	$sub_data = App\OrderSubscription::where('order_id',$order_id)->select('id','receipt')->first();
	if(!is_null($sub_data)) {
		$receipt = json_decode($sub_data->receipt);
		$cycle = (int)$receipt->NUMCYCLESCOMPLETED;
		if($cycle == 0) {
			$cycle = $cycle + 1;
		}
	}
	$text = ' Cycles';
	if($cycle < 2) {
		$text = ' Cycle';
	}
	return $cycle . $text;
}

function get_client_ip() {
    $ipaddress = '';
	if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
        $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
    else if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function check_invalid_two_factor_auth_attempt($user_id) {
	return \DB::transaction(function() {
		$invalid = false;
		$ip_address = get_client_ip();
		$check_for_inactive =  App\UserTwoFactorAuthDetails::select('id')->where('ip_address',$ip_address)->where('status',1)->sharedLock()->count();
		if($check_for_inactive > 0) {
			$invalid = true;
		} else {
			$last_try = \Carbon\Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s');

			$get_attempts = App\UserTwoFactorAuthDetails::select('id')->where('ip_address',$ip_address)->where('created_at','>=',$last_try)->count();
			if($get_attempts >= 3) {
				App\UserTwoFactorAuthDetails::where('ip_address',$ip_address)->update(['status'=>1]);
				$invalid = true;
			}
		}
		return array('status'=>$invalid,'message'=>"You have exceeded max attempt limit. Please try after 4 hours.");
	});
}

function clear_two_factor_auth_attempt_details($user_id) {
	$ip_address = get_client_ip();
	App\UserTwoFactorAuthDetails::where('ip_address',$ip_address)->delete();
	return true;
}

function get_current_chat_tab($service_id,$order_id) {
	$type = 'users';
	if($service_id != 0 && $order_id != 0) {
		$type = 'orders';
	} else if($service_id != 0 && $order_id == 0) {
		$type = 'services';
	}
	return $type;
}

function can_show_hidden_pizza() {
	$return = false;
	$pizza_setting = App\GeneralSetting::where('settingkey','hidden_pizza')->first();
	if($pizza_setting->settingvalue == '1') {
		return $return;
	}

	$today = Carbon\Carbon::today()->format('Y-m-d');
	$current_route_name = \Route::currentRouteName();
	$check_for_applied = App\PizzaAppliedHistory::whereDate('date',$today)->first();
	if(is_null($check_for_applied)) { //means still not applied on any one
		//get today's random page
		$today_page = get_today_random_page();

		// add pizza history
		try {
			$random_token = md5(microtime());
			$history = new App\PizzaAppliedHistory;
			$history->pizza_page_id = $today_page->id;
			$history->user_id = 0;
			$history->date = Carbon\Carbon::today()->format('Y-m-d')." 00:00:00";
			$history->amount = env('HIDDEN_PIZZA_AMOUNT');
			$history->pizza_page_url = get_pizza_page_url($today_page->route_name,$today_page->slug);
			$history->verification_token = $random_token;
			$history->save();
		} catch (\Throwable $th) {
			//no need to do if already there is a entry in database
		}

		if(in_array($current_route_name,['services_view','services_details','show.job_detail','viewuserservices'])) {
			if($today_page->route_name == $current_route_name && $history->pizza_page_url == url()->current()) {
				$return = true;
			}
		} else if($today_page->route_name == $current_route_name) {
			$return = true;
		}
	} else if($check_for_applied->user_id == 0) {
		if(in_array($current_route_name,['services_view','services_details','show.job_detail','viewuserservices'])) {
			if($check_for_applied->demoPage->route_name == $current_route_name && $check_for_applied->pizza_page_url == url()->current()) {
				$return = true;
			}
		} else if($check_for_applied->demoPage->route_name == $current_route_name) {
			$return = true;
		}
	}
	return $return;
}

function get_pizza_page_url($route_name,$slug) {
	$url = "";
	if($route_name == 'services_view') {
		if($slug == 'category_page') {
			$category = App\Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->inRandomOrder()->first();
			$url = route($route_name,[$category->seo_url]);
		} else if($slug == 'subcategory_page') {
			$category = App\Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->inRandomOrder()->first();
			$sub_category = App\Subcategory::select('id','seo_url')->where('category_id',$category->id)->inRandomOrder()->first();
			$url = route($route_name,[$category->seo_url,$sub_category->seo_url]);
		}
	} else if($route_name == 'services_details') {
		$service = App\Service::statusof('service')->select('id','uid','seo_url')->inRandomOrder()->first();
		$url = route($route_name,[$service->user->username,$service->seo_url]);
	} else if($route_name == 'show.job_detail') {
		$job = App\Service::statusof('job')->where('expire_on','>=',Carbon\Carbon::now()->format('Y-m-d H:i:s'))->select('id','seo_url')->inRandomOrder()->first();
		$url = route($route_name,[$job->seo_url]);
	} else if($route_name == 'viewuserservices') {
		$user = App\User::where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id','username')->inRandomOrder()->first();
		$url = route($route_name,[$user->username]);
	} else {
		$url = route($route_name);
	}
	return $url;
}

function get_today_random_page() {
	$priority = [];
	for($i=1; $i<=100; $i++) { 
		if($i <= 70) {
			$priority[$i] = 'service_details';
		} else if($i > 70 && $i <= 85) {
			$priority[$i] = 'category';
		} else {
			$priority[$i] = 'others';
		}
	}
	$random = \Arr::random($priority);

	if($random == 'service_details') {
		$today_page = App\demoPage::select('id','route_name','slug')->where('route_name','services_details')->first();
	} else if($random == 'category') {
		$today_page = App\demoPage::select('id','route_name','slug')->where('route_name','services_view')->inRandomOrder()->first();
	} else {
		$today_page = App\demoPage::select('id','route_name','slug')->whereNotIn('route_name',['services_details','services_view'])->inRandomOrder()->first();
	}
	return $today_page;
}

function get_service_image_url($service) {
	$image_url = url('public/frontend/assets/img/No-image-found.jpg');
	if(isset($service->images[0])) {
		if(!is_null($service->images[0]->thumbnail_media_url)) {
			$image_url = $service->images[0]->thumbnail_media_url; 
		} else if($service->images[0]->photo_s3_key != '') {
			$image_url = $service->images[0]->media_url; 
		} else {
			$image_url = url('public/services/images/'.$service->images[0]->media_url); 
		}
	}
	return $image_url;
}

function get_user_profile_image_url($user) {
	$image_url = url('public/frontend/images/profile-default-image.jpg');
	if($user) {
		if(isset($user->profile_photo) && $user->profile_photo != ''){
			if($user->photo_s3_key != '') {
				$image_url = $user->profile_photo;
			} else if($user->profile_photo != '') {
				$image_url = url('public/seller/profile/'.$user->profile_photo);
			}
		}
	}
	return $image_url;
}

function can_show_upgrade_premium_promo_section() {
	$return = false;
	$usrObj = new App\User;
	if($usrObj->is_premium_seller() == false && \Auth::user()->parent_id == 0) {
		$activeService = App\Service::where('uid',\Auth::user()->id)->where('status','active')->select('id')->first();
		if($activeService){
			$return = true;
		}
	}
	return $return;
}

function send_order_data_to_wicked_report($order) {
	//call wicked report API to send new user info
	$SourceSystem = 'Web';
	$order_info = [];
	$order_info["SourceSystem"] = $SourceSystem;
	$order_info["SourceID"] = $order->order_no;
	$order_info["CreateDate"] = convert_timezone($order->created_at,'UTC');
	$order_info["ContactID"] = $order->user->username;
	$order_info["OrderTotal"] = $order->order_total_amount;
	$order_info["OrderCurrency"] = 'USD';
	if($order->is_recurring == 1) {
		$order_info["SubscriptionID"] = $order->txn_id;
	}
	$order_info["ContactEmail"] = $order->user->email;
	$order_info["IP_Address"] = $order->user->ip_adress ?? '';

	$all_order_item = $order_item = [];
	$order_item['SourceSystem'] = $SourceSystem;
	$order_item['SourceID'] = $order->order_no;
	$order_item['OrderID'] = $order->order_no;
	$order_item['ProductID'] = $order->service->secret;
	$order_item['Qty'] = $order->qty;
	$order_item['PPU'] = $order->price;
	array_push($all_order_item,$order_item);

	foreach ($order->extra as $key => $value) {
		$order_item = [];
		$order_item['SourceSystem'] = $SourceSystem;
		$order_item['SourceID'] = $value->secret;
		$order_item['OrderID'] = $order->order_no;
		$order_item['ProductID'] = $order->service->secret;
		$order_item['Qty'] = $value->qty;
		$order_item['PPU'] = $value->price;
		array_push($all_order_item,$order_item);
	}

	$order_payment = [];
	$order_payment['SourceSystem'] = ucfirst($order->payment_by);
	$order_payment['OrderID'] = $order->order_no;
	$order_payment['PaymentDate'] = convert_timezone($order->created_at,'UTC');
	$order_payment['Amount'] = $order->order_total_amount;
	$order_payment['Status'] = $order->payment_status;

	//set queue data
	$data = [];
	$data['type'] = 'create_order';
	$data['orders'] = $order_info;
	$data['order_payments'] = $order_payment;
	$data['order_items'] = $all_order_item;
	
	App\Jobs\sendDataToWickedReports::dispatch($data);

	//update order in db
	$order->sent_to_wickedreport = 1;
	$order->save();

	return true;
}

function formatBytes($size, $precision = 2)
{
    if ($size > 0) {
        $size = (int) $size;
        $base = log($size) / log(1024);
        $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    } else {
        return $size;
    }
}

function get_microtime(){
	return round(microtime(true) * 1000);
}

function get_video_player_img(){
	return url('public/frontend/images/play.png');
}

function check_file_type($filename) {
	$return = "other";
	$position = strrpos($filename,'.');
	$index = $position + 1;
	$ext = substr($filename,$index);
	if(in_array($ext,['jpg','jpeg','png'])) {
		$return = "image";
	} else if(in_array($ext,['txt','sql','docx'])) {
		$return = "file";
	}
	return $return;
}

function updateActiveCampaign($userName, $userEmail) {

	$url = env('ACTIVECAMPAIGN_API_URL');
    $api_key = env('ACTIVECAMPAIGN_API_KEY');
	$params = array(
		'api_key' => $api_key,
		'api_action' => 'contact_add',
		'api_output' => 'serialize',
	);

	$post = array(
		'email' => $userEmail,
		'first_name' => $userName,
		'tags' => 'api',
		'p[1]' => 6,
		'status[1]' => 1,
		'instantresponders[1]' => 1,
	);

	// This section takes the input fields and converts them to the proper format
	$query = "";
	foreach ($params as $key => $value)
		$query .= urlencode($key) . '=' . urlencode($value) . '&';
	$query = rtrim($query, '& ');

	// This section takes the input data and converts it to the proper format
	$data = "";
	foreach ($post as $key => $value)
		$data .= urlencode($key) . '=' . urlencode($value) . '&';
	$data = rtrim($data, '& ');

	// clean up the url
	$url = rtrim($url, '/ ');

	// This sample code uses the CURL library for php to establish a connection,
	// submit your request, and show (print out) the response.
	if (!function_exists('curl_init'))
		die('CURL not supported. (introduced in PHP 4.0.2)');

	// If JSON is used, check if json_decode is present (PHP 5.2.0+)
	if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
		die('JSON not supported. (introduced in PHP 5.2.0)');
	}

	// define a final API request - GET
	$api = $url . '/admin/api.php?' . $query;

	$request = curl_init($api); // initiate curl object
	curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
	curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
	curl_setopt($request, CURLOPT_POSTFIELDS, $data); // use HTTP POST to send form data
	//curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
	curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

	$response = (string) curl_exec($request); // execute curl post and store results in $response
	// additional options may be required depending upon your server configuration
	// you can find documentation on curl options at http://www.php.net/curl_setopt
	curl_close($request); // close curl object

	if (!$response) {
		return 0;
		// die('Nothing was returned. Do you have a connection to Email Marketing server?');
	}

	// This line takes the response and breaks it into an array using:
	// unserializer
	$result = unserialize($response);


	// Result info that is always returned
	// echo 'Result: ' . ( $result['result_code'] ? 'SUCCESS' : 'FAILED' ) . '<br />';
	return $result['result_code'];
	// The entire result printed out
	// echo 'The entire result printed out:<br />';
	// echo '<pre>';
	// print_r($result);
}

function get_order_progress_status($title,$type,$is_last=0){
	if($type == 'completed'){
		$line = ($is_last == 0) ? 'vertical-line' : '';
		$color = ($is_last == 0) ? 'text-color-4' : 'text-color-1';
		$html = '<div class="d-flex '.$line.'"> <div class="completed-node"></div> <p class="font-16 '.$color.' ml-3">'. $title .'</p> </div>';
	}elseif($type == 'progress'){
		$html = '<div class="d-flex"> <div class="in-progress-node"></div> <p class="font-16 text-color-1 ml-3">'. $title .'</p> </div>';
	}elseif($type == 'cancelled'){
		$html = '<div class="d-flex"> <div class="cancelled-node"></div> <p class="font-16 text-color-4 ml-3">'. $title .'</p> </div>';
	}else{
		$html = '<div class="d-flex"> <div class="pending-node"></div> <p class="font-16 text-color-4 ml-3">'. $title .'</p> </div>';
	}
	return $html;
}

function get_blog_description($description,$length=100) {
	$description = strip_tags($description);
	$description = html_entity_decode($description);
	$description = \Str::limit($description,$length);
	return $description;
}

function get_blog_image($post) {
	$image_url = url('public/frontend/assets/img/No-image-found.jpg');
	if(isset($post->media_images[0])) {
		if(!is_null($post->media_images[0]->featured_image)) {
			$image_url = $post->media_images[0]->featured_image; 
		} else if($post->media_images[0]->photo_s3_key != '') {
			$image_url = env('ADMIN_PANEL_BASE_URL').'/public/services/images/'.$post->media_images[0]->photo_s3_key; 
		}
	}
	return $image_url;
}

function get_blog_media($media) {
	$image_url = url('public/frontend/assets/img/No-image-found.jpg');
	if(isset($media)) {
		if(!is_null($media->featured_image)) {
			$image_url = $media->featured_image; 
		} else if($media->photo_s3_key != '') {
			$image_url = env('ADMIN_PANEL_BASE_URL').'/public/services/images/'.$media->photo_s3_key; 
		}
	}
	return $image_url;
}

function get_buyer_transaction_note($note) {
	$note = str_replace('Deposit from', 'Debit from', $note);
	$note = str_replace('promotional amount', 'demo Bucks', $note);
	return $note;
}

function calculate_payment_processing_fee($amount) {
	$processing_fee_per = env('PAYMENT_PROCESSING_FEE');
	$processing_fee_amt = ($amount * $processing_fee_per) / 100;
	$processing_fee_amt = number_format($processing_fee_amt, 2, '.', '');
	$processing_fee_amt = (float)$processing_fee_amt;
	return $processing_fee_amt;
}

function get_utm_term_for_demo_lead_category($slug) {
	$slug = str_replace('_', '-', $slug);
	return $slug;
}

function get_user_id() {
	if(Auth::check()) {
		if(Auth::user()->parent_id != 0){
			$uid = Auth::user()->parent_id;
		} else {
			$uid = Auth::user()->id;
		}
		return $uid;
	} else {
		return '';
	}
}

function get_user_softban_message(){
	return "Your account has been disabled! Contact administrator for further details!";
}

function show_service_status($status){
	return ucwords(str_replace("_"," ", $status));
}

function xmlToArray($xmlstring){
	$xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
	$json = json_encode($xml);
	$array = json_decode($json,TRUE);
	return $array;
}

function skip_highlight_js($route = array()){
	if(in_array(\Route::currentRouteName(),$route)){
		return false;
	}
	return true;
}

function get_duration($times){
	if(empty($times)){
		return '';
	}
	//return  gmdate($format, $times);
	$hours = floor($times / 3600);
	$mins = floor($times / 60 % 60);
	$secs = floor($times % 60);
	$timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
	return $timeFormat;
}

function get_duration_heading($times){
	if(empty($times)){
		return '';
	}
	//return  gmdate($format, $times);
	$hours = floor($times / 3600);
	$mins = floor($times / 60 % 60);
	$secs = floor($times % 60);
	$timeFormat = sprintf('%2dh %2dmin', $hours, $mins);
	return $timeFormat;
}

function thousandsCurrencyFormat($num) {
	if($num>1000) {
		$x = round($num);
		$x_number_format = number_format($x);
		$x_array = explode(',', $x_number_format);
		$x_parts = array('k', 'm', 'b', 't');
		$x_count_parts = count($x_array) - 1;
		$x_display = $x;
		$x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
		$x_display .= $x_parts[$x_count_parts - 1];
		return $x_display;
	}
	return $num;
  }

function convert_reading_time($time,$type='i'){
	if($type == 'i'){
		$seconds = ($time * 60);
	}else{
		$seconds =  ($time * 3600);
	}
	return $seconds;
}

/* Generate table of contents */
function table_of_contents($html) {
	preg_match_all("/<h([1-6])>(.*)<\/h[1-6]>/", $html, $matches, PREG_SET_ORDER);
	$LI = 0; // List Item Count
	$HL = 2; // Heading Level
	$SubHeading = false;
	foreach ($matches as $val) {
	  	$LI++;
		if ($val[1] == $HL) { // If the heading level didn’t change.
			$List[$LI] = '<li><a href="#section'.$LI.'" class="text-color-2">' .strip_tags($val[2]) . '</a></li>';
		} else if ($val[1] > $HL) { // If bigger then last heading level, create a nested list.
			$List[$LI] = '<li><ul><li><a href="#section'.$LI.'" class="text-color-2">'. strip_tags($val[2]) . '</a></li>';
			if ($SubHeading === true) {
				$SubHeading = false;
			} else { 
				$SubHeading = true; 
			}
		} else if ($val[1] < $HL) { // If less then last Heading Level, end nested list.
			if($List != ""){
				$List[$LI] = '</ul></li><li><a href="#section'.$LI.'" class="text-color-2">'. strip_tags($val[2]) . '</a></li>';
			}else{
				$List[$LI] = '<li><a href="#section'.$LI.'" class="text-color-2">'. strip_tags($val[2]) . '</a></li>';
			}
		}
		$Sections[$LI]    =  '<h' . $val[1].">".$val[2]; // Original heading to be Replaced.
		$SectionWIDs[$LI] = '<h' . $val[1] . ' id="section'.$LI.'">' .$val[2]; // This is the new Heading.
	  	$HL = $val[1];
	}
	switch ($HL) { // Final markup fix, used if the list ended on a subheading, such as h3, h4. Etc.
	  	case 3:
	   		$List[$LI] = $List[$LI] . '</ul></li>';
	  	break;
	  		case 4:
	   		$List[$LI] = $List[$LI] . '</ul></li></ul></li>';
	  	break;
	  	case 5:
	   		$List[$LI] = $List[$LI] . '</ul></li></ul></li></ul></li>';
	  	break;
		case 6:
	   		$List[$LI] = $List[$LI] . '</ul></li></ul></li></ul></li></ul></li>';
	  	break;
	}
	
	$toc_list = "";
	foreach ($List as $val) { // Puts together the list.
		$toc_list .= $val;
	}

	if(count($List) > 0){
		return '<div id="toc" class="border p-2 profile-sidebar afilliate-content-bg"> <p class="font-18">Table Of Contents:</p><ul>' . $toc_list . '</ul></div> <div class="toc-html">' . str_replace($Sections, $SectionWIDs, $html)."</div>"; // Returns the content
	}else{
		return $html;
	}
}

function IPtoLocation($ip){ 
	$result = null;
	if($ip != "" || $ip != "UNKNOWN"){
		//Check for envoronment 
		$url = 'https://ipwhois.app/json/'.$ip; /* Free plan for development server */ 
		if(env('IP_WHOIS_KEY') != null) {
			$url = 'https://ipwhois.pro/json/'.$ip."/?key=".env('IP_WHOIS_KEY'); /* Paid plan for live server */ 
		}
		// Make HTTP GET request using PHP function 
		$location_info = file_get_contents($url);
		$result = json_decode($location_info);
		// Return geolocation data 
	}
    return $result;
}

function verifyIpForSMS($user_ip,$country_code){
	$ip_address = get_client_ip();
	/* Verify IP */
	if($ip_address == "" || $ip_address == 'UNKNOWN' || $ip_address != $user_ip){ /* check ip address */
		return ['status'=>false,'message'=>'Something went wrong.'];
	}
	return ['status'=>true,'message'=>'Success'];
	
}

function get_affiliation_percentage($total_amount=0,$affiliate_amount=0){
	$difference = ($total_amount - $affiliate_amount) / $total_amount;
	$difference = 100 - ($difference * 100);
	return  round($difference);
} 

function get_category_menu_url($category){
	if($category->is_custom == 1){
		return '#';
	}else{
		return route('services_view',[$category->seo_url]);
	}
}
function get_subcategory_menu_url($category,$subcategory){
	if($subcategory->is_custom == 1){
		return $subcategory->slug_or_url;
	}else{
		return route('services_view',[$category->seo_url, $subcategory->slug_or_url]);
	}
}

function unsubscribeEmailList(){
	$data[] = ['id'=>1,'name'=>'Order'];
	$data[] = ['id'=>2,'name'=>'Service'];
	$data[] = ['id'=>3,'name'=>'Job'];
	$data[] = ['id'=>4,'name'=>'Transaction'];
	$data[] = ['id'=>5,'name'=>'User Subscription'];
	$data[] = ['id'=>6,'name'=>'Chat Message'];
	$data[] = ['id'=>7,'name'=>'Other'];
	return $data;
}
