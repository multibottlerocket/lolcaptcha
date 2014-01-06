<?
include('letterz.php');

function int2rgba($int) { 
	return array(
		'r' => ($int >> 16) & 0xFF,
		'g' => ($int >> 8) & 0xFF,
		'b' => $int & 0xFF,
		'a' => ($int >> 24) & 0xFF
		);
} 

function redline(&$m, $x){
	$red = imagecolorallocate($m, 255, 0, 0);
	
	for($y = 0; $y < imagesy($m); $y++){
		imagesetpixel($m, $x, $y, $red);
	}
}

function is_col_white(&$m, $x, $heur=0){
	$cc = 0;
	
	for($y = 0; $y < imagesy($m); $y++){
		$c = int2rgba(imagecolorat($m, $x, $y));
		
		if($c['r']+$c['g']+$c['b'] == 255*3) $cc++;
	}
	
	if(!$heur && $cc != imagesy($m)) return false;
	else if($heur && $cc <= imagesy($m)-$heur) return false;
	
	return true;
}

function is_row_white($m, $y, $heur=0){
	$cc = 0;
	
	for($x = 0; $x < imagesx($m); $x++){
		$c = int2rgba(imagecolorat($m, $x, $y));
		
		if($c['r']+$c['g']+$c['b'] == 255*3) $cc++;
	}
	
	if(!$heur && $cc != imagesx($m)) return false;
	else if($heur && $cc <= imagesx($m)-$heur) return false;
	
	return true;
}

function png2str(&$m){
	ob_start();
	imagepng($m);
	$s = ob_get_contents();
	ob_end_clean();
	return $s;
}

function img_crop_topbot($m){
	//mozna to zoptymalizowac wykrywjac pusta gore & dol a potem ucinajac 1 raz
	
	$ix = imagesx($m);
	$iy = imagesy($m);
	
	$o = imagecreatetruecolor($ix, $iy);
	
	imagecopy($o, $m, 0, 0, 0, 0, $ix, $iy);
	
	//top
	while(is_row_white($o, 0)){
		$new = imagecreatetruecolor($ix, imagesy($o)-1);
		imagecopy($new, $o, 0, 0, 0, 1, $ix, $iy-1);
		$o = $new;
	}
	
	//bottom
	while(is_row_white($o, imagesy($o)-1)){
		$new = imagecreatetruecolor($ix, imagesy($o)-1);
		imagecopy($new, $o, 0, 0, 0, 0, $ix, imagesy($o)-1);
		$o = $new;
	}
	
	return $o;
}

function imgcut($m){
	$outs = array();
	
	//szukanie liter
	$letter = 0;
	
	for($x = 0; $x < imagesx($m); $x++){
		if(is_col_white($m, $x) || $x == imagesx($m)){
			if($letter){
				if($x-$letter_from >= 10){ //min 10px
					$letters[] = array($letter_from, $x);
				}
				
				$letter = 0;
			}
		} else {
			//letter end
			if($letter == 0){
				$letter = 1;
				$letter_from = $x;
			}
		}
	}
	
	if($letter) $letters[] = array($letter_from, $x);
	//--
	
	for($i = 0; $i < count($letters); $i++){
		$width = $letters[$i][1]-$letters[$i][0];
		$mt = imagecreatetruecolor($width, imagesy($m));
		imagecopy($mt, $m, 0, 0, $letters[$i][0], 0, $width, imagesy($m));
		
		if($width >= 40){ //zlepione 2 litery
			$white_cols = array();
			
			$wc_heur = 1;
			while(count($white_cols) < 1){
				for($x = floor($width/2*0.5); $x < floor($width/2*1.5); $x++){ //apgrejt, cuttujemy wszystkie znaki
					if(is_col_white($mt, $x, $wc_heur)) $white_cols[] = $x;
				}
				
				$wc_heur += 1;
			}
			
			//najblizej srodka
			$col_most = -1;
			foreach($white_cols as $col) if(abs(($width/2)-$col) < $col_most || $col_most == -1) $col_most = $col;
			
			if($col_most > -1){
				//ucinamy, pierwsza litera do nowego obrazu
				$mt2 = imagecreatetruecolor($col_most, imagesy($m));
				imagecopy($mt2, $mt, 0, 0, 0, 0, $col_most, imagesy($m));
				
				$outs[] = $mt2;
				
				//druga litera
				$tmp = imagecreatetruecolor(imagesx($mt)-$col_most, imagesy($m));
				imagecopy($tmp, $mt, 0, 0, $col_most, 0, imagesx($mt)-$col_most, imagesy($mt));
				
				$outs[] = $tmp;
			}
		} else {
			$outs[] = $mt;
		}
	}
	
	return $outs;
}

function extract_chars($m){
	$outs = array($m);
	
	$check = 1;
	while($check){
		$check = 0;
		
		$tmp = $outs;
		$outs = array();
		
		foreach($tmp as $out){
			$r = imgcut($out);
			
			if(count($r) > 0){
				if(count($r) > 1) $check = 1;
				
				foreach($r as $img) $outs[] = $img;
			} else {
				$outs[] = $out;
			}
		}
	}
	
	for($i = 0; $i < count($outs); $i++){
		$outs[$i] = img_crop_topbot($outs[$i]);
	}
	
	return $outs;
}

function img_clean($m){
	$remove = array(
		array(0x80, 0x80, 0xff, 0), //smieciowe 2 paski
		array(128, 191, 255, 0), //kratka w tle
		array(227, 218, 237, 0) //tlo
		);
		
	$white = imagecolorallocate($m, 255, 255, 255);
	$black = imagecolorallocate($m, 0, 0, 0);
	
	for($x = 0; $x < imagesx($m); $x++){
		for($y = 0; $y < imagesy($m); $y++){
			$c = int2rgba(imagecolorat($m, $x, $y));
			
			foreach($remove as $r){
				if($r[0] == $c['r'] && $r[1] == $c['g'] && $r[2] == $c['b']){
					imagesetpixel($m, $x, $y, $white);
				}
			}
		}
	}
	for($x = 0; $x < imagesx($m); $x++){
		for($y = 0; $y < imagesy($m); $y++){
			$c = int2rgba(imagecolorat($m, $x, $y));
			if($c['r']+$c['g']+$c['b'] < 255*3) imagesetpixel($m, $x, $y, $black);
		}
	}
	
	return $m;
}

function image_compare_create_base($icomp){ //5ms*4
	$base = imagecreatetruecolor(50, 50);
	$white = imagecolorallocate($base, 255, 255, 255);
	$comp = imagecreatetruecolor(50, 50);
	$white = imagecolorallocate($comp, 255, 255, 255);
	imagefill($comp, 0, 0, $white);
	imagecopy($comp, $icomp, 0, 0, 0, 0, imagesx($icomp), imagesy($icomp));
	
	$r = array();
	
	for($x = 0; $x < 40; $x++){
		for($y = 0; $y < 33; $y++){
			$r[$x][$y] = imagecolorat($comp, $x, $y)==0;
		}
	}
	
	return $r;
}

function image_compare_getmost($icomp){
	global $letterz;

	$best = 0;
	$bestl = '';
	
	$org_base = image_compare_create_base($icomp);
	
	$x_c = count($org_base);
	$y_c = count($org_base[0]);
	
	foreach($letterz as $a){
		$ll = $a[1]; //-100ms
		
		$black_pixels = 0;
		$base_pixels = 0;

		for($x = 0; $x < $x_c; $x++){
			for($y = 0; $y < $y_c; $y++){
				if(!empty($ll[$y][$x]) && $org_base[$x][$y]) $black_pixels++;
				if(!empty($ll[$y][$x]) || $org_base[$x][$y]) $base_pixels++;
			}
		}
	
		$match = floor(($black_pixels/$base_pixels)*100);
		
		if($match > $best){
			$best = $match;
			$bestl = $a[0];
		}
	}
	
	return array($best, $bestl);
}

function hax_captcha($m){
	$c = extract_chars(img_clean($m));
	
	if(count($c) != 4) return false;
	
	$out = '';
	
	foreach($c as $n){
		$r = image_compare_getmost($n);
		$out .= $r[1];
	}
	
	return strtoupper($out);
}

// *** EXAMPLE OF USE ***

// $m = imagecreatefromstring(file_get_contents('http://signup.leagueoflegends.com/pl/signup/captcha/:)'));

// $_start = microtime(1);

// echo '<img src="data:image/png;base64,'.base64_encode(png2str($m)).'" style="border: 1px solid red;"><br><br>';

// $_cleaning = microtime(1);
// $m = img_clean($m);
// $_cleaning = microtime(1)-$_cleaning;

// $_extracting = microtime(1);
// $outs = extract_chars($m);
// $_extracting = microtime(1)-$_extracting;

// echo '<img src="data:image/png;base64,'.base64_encode(png2str($m)).'" style="border: 1px solid red;"><br><br>';

// foreach($outs as $m){
	// echo '<img src="data:image/png;base64,'.base64_encode(png2str($m)).'" style="border: 1px solid red;">&nbsp';
// }

// echo '<br>';

// $s = '';
// $d = '';

// $_ocring = microtime(1);

// foreach($outs as $m){
	// $r = image_compare_getmost($m);
	
	// $s .= $r[1][0].' ';
	// $d .= $r[0].'%, ';
// }
// $_ocring = microtime(1)-$_ocring;

// echo '<br>
	// <span style="font-size: 24px; font-weight: bold;">'.strtoupper(substr($s, 0, strlen($s)-1)).'</span><br>
	// <br>
	// '.substr($d, 0, strlen($d)-2).'<br>
	// <br>
	// total = '.round(microtime(1)-$_start, 4).'s<br>
	// cleaning = '.round($_cleaning, 4).'s<br>
	// extracting = '.round($_extracting, 4).'s<br>
	// reading = '.round($_ocring, 4).'s<br>';
?>