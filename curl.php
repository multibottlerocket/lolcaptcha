<?
function curl_get($url){
	$c = curl_init();
	
	curl_setopt_array($c, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_COOKIEJAR => getcwd().'/cookiefile.tmp',
		CURLOPT_COOKIEFILE => getcwd().'/cookiefile.tmp',
		//CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31'
		));
	
	$e = curl_exec($c);
	curl_close($c);
	
	return $e;
}

function curl_post($url, $post, $header=false){
	$c = curl_init();
	
	curl_setopt_array($c, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_COOKIEJAR => getcwd().'/cookiefile.tmp',
		CURLOPT_COOKIEFILE => getcwd().'/cookiefile.tmp',
		//CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31',
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query($post),
		CURLOPT_HEADER => 1
		));
	
	if($header){
		curl_setopt($c, CURLOPT_HTTPHEADER, $header);
	}
	
	$e = curl_exec($c);
	curl_close($c);
	
	return $e;
}
?>