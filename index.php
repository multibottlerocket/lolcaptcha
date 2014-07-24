<?
include('captcha.php');
include('curl.php');

function rand_str($len=6){
	$r = '';
	for($i = 0; $i < $len; $i++) $r .= rand(0, 3)==0?rand(0, 9):chr(rand(ord('a'), ord('z')));
	return $r;
}

function l($arg){
	echo date('H:i:s').'> '.$arg.'<br>';
}

if(!is_writable('.')) die('set chmod so i can write cookie files');

$uname = rand_str(4).rand(1, 9).chr(rand(ord('a'), ord('z')));
$pwd = $uname;
$email = $uname.'@'.rand_str().'.info';
?>

<!doctype html>
<html>
<head>
	<link rel="stylesheet" href="assets/css/demo.css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script src="assets/js/jquery.formalize.js"></script>
</head>
<body>

<style>
td:first-child {
	width: 100px;
	text-align: right;
	padding-right: 10px;
	padding-top: 4px;
}

td {
	padding: 2px 0px;
}

select, input {
	width: 220px;
}
</style>

<div style="width: 800px; margin: auto; padding-top: 40px;">
	<h1>LoL Account Maker</h1>
	<hr>
	
	<table width="100%">
		<form method="POST">
			<tr><td>Reflink Code:</td><td><input type="text" name="reflink"></td></tr>
			<tr><td>Server:</td><td>
				<select name="realm">
					<option value="na">North America</option>
			</td></tr>
			<tr><td>Username:</td><td><input type="text" name="username" value="<?=$uname?>"> 4-24 chars (only letters and numbers)</td></tr>
			<tr><td>Password:</td><td><input type="password" name="password" value="<?=$pwd?>"> 6-16 chars, at least 1 letter and 1 number</td></tr>
			<tr><td>Email Address:</td><td><input type="text" name="email" value="<?=$email?>"></td></tr>
			<tr><td>Date of Birth:</td><td>
				<input type="number" name="dob_dd" min="1" max="30" placeholder="DD" value="1" style="width: 66px;">
				<input type="number" name="dob_mm" min="1" max="12" placeholder="MM" value="1" style="width: 66px;">
				<input type="number" name="dob_yyyy" min="1940" max="2013" placeholder="YYYY" value="2000" style="width: 80px;">
			</td></tr>
			<tr><td>Captcha:</td><td style="padding-top: 4px;"><i>bypassed</i></td></tr>
			<tr><td>&nbsp;</td><td><input type="submit" value="Create Account"></td></tr>
		</form>
	</table>
	
<?
if(isset($_POST['username'])){
	echo '<div style="margin-top: 40px;"><h1>Creating account...</h1><hr>';
	
	l('creating account (username: '.$_POST['username'].', password: '.$_POST['password'].')...');
	
	$reflink_code = $_POST['reflink'];
	if(empty($reflink_code)) {
		$reflink = 'http://signup.leagueoflegends.com/en/signup/index';
	} else {
		$reflink = 'https://signup.leagueoflegends.com/en/signup/index?ref=' . $reflink_code;
	}

	l($reflink);
	$tries = 0;
	
	while($tries < 10){
		$tries++;
		
		l('try #'.$tries);
		
		// $f = curl_get('https://signup.leagueoflegends.com/en/signup/index');
		$f = curl_get($reflink);
		preg_match('#name\=\"data\[\_Token\]\[key\]\" value\=\"(.*)\"#siU', $f, $token_key);
		preg_match('#name\=\"data\[\_Token\]\[fields\]\" value\=\"(.*)\"#siU', $f, $token_fields);
		preg_match('#\<img src\=\"\/en\/signup\/captcha\/(.*)\" id\=\"CaptchaImg\"#siU', $f, $captcha);
		
		if($img = curl_get('http://signup.leagueoflegends.com/en/signup/captcha/'.$captcha[1])){
			l($img)
			if($img = @imagecreatefromstring($img)){
				if($hax = hax_captcha($img)){
					l('captcha: <img src="data:image/png;base64,'.base64_encode(png2str($img)).'" style="border: 1px solid red;"> = '.$hax);
					
					$post = array(
						'_method' => 'POST',
						'data[_Token][key]' => $token_key[1],
						'data[PvpnetAccount][name]' => $_POST['username'],
						'data[PvpnetAccount][password]' => $_POST['password'],
						'data[PvpnetAccount][confirm_password]' => $_POST['password'],
						'data[PvpnetAccount][email_address]' => $_POST['email'],
						'data[PvpnetAccount][date_of_birth_day]' => $_POST['dob_dd'],
						'data[PvpnetAccount][date_of_birth_month]' => $_POST['dob_mm'],
						'data[PvpnetAccount][date_of_birth_year]' => $_POST['dob_yyyy'],
						'data[PvpnetAccount][realm]' => $_POST['realm'],
						'data[PvpnetAccount][tou_agree]' => '0',
						'data[PvpnetAccount][tou_agree]' => '1',
						'data[PvpnetAccount][newsletter]' => '0',
						'data[PvpnetAccount][captcha]' => $hax,
						'data[_Token][fields]' => $token_fields[1]
						);
					
					$xhr = array(
						'X-Requested-With' => 'XMLHttpRequest'
						);
					
					// if($f = curl_post('https://signup.leagueoflegends.com/en/signup/index', $post, $xhr)){
					if($f = curl_post($reflink, $post, $xhr)){
						$success = 0;
						
						foreach(explode("\n", $f) as $e){
							$e = explode(': ', $e);
							
							if(trim($e[0]) == 'Location' && trim($e[1]) == 'https://signup.leagueoflegends.com/en/signup/download'){
								$success = 1;
								
								break;
							}
						}
						
						if($success){
							l('account created');
							
							l('username: <b>'.$_POST['username'].'</b>');
							l('password: <b>'.$_POST['password'].'</b>');
							
							break;
						} else {
							l('could not create account');
						}
					}
				} else {
					l('could not read captcha image (too hard to read)');
				}
			} else {
				l('could not read captcha image (image invalid)');
			}
		}
	}

	echo '</div>';
}

if(file_exists('cookiefile.tmp')) @unlink('cookiefile.tmp');
?>

	<div style="margin-top: 60px; text-align: center;">
		<hr>
		
		LoL Account Maker created by <a href="http://www.elitepvpers.com/forum/members/5239042-wintechz.html"><b>wintechz</b></a>
	</div>
</div>

</body>
</html>