<?php

class SimCode {

	/**
	 * 简单对称加密算法之加密
	 * @param String $string 需要加密的字串
	 * @param String $skey 加密EKY
	 * @return String
	 */
	public static function encode($string = '', $skey = 'abc123') {
		$strArr = str_split ( base64_encode ( $string ) );
		$strCount = count ( $strArr );
		foreach ( str_split ( $skey ) as $key => $value )
			$key < $strCount && $strArr [$key] .= $value;
		return str_replace ( array (
				'=',
				'+',
				'/' 
		), array (
				'O0O0O',
				'o000o',
				'oo00o' 
		), join ( '', $strArr ) );
	}
	/**
	 * 简单对称加密算法之解密
	 * @param String $string 需要解密的字串
	 * @param String $skey 解密KEY
	 * @return String
	 */
	public static function decode($string = '', $skey = 'abc123') {
		$strArr = str_split ( str_replace ( array (
				'O0O0O',
				'o000o',
				'oo00o' 
		), array (
				'=',
				'+',
				'/' 
		), $string ), 2 );
		$strCount = count ( $strArr );
		foreach ( str_split ( $skey ) as $key => $value )
			$key <= $strCount && $strArr [$key] [1] === $value && $strArr [$key] = $strArr [$key] [0];
		return base64_decode ( join ( '', $strArr ) );
	}
}
/* 	
	
	
echo '<pre>';
$str = '56,15123365247,54,四大古典风格';
echo "string : " . $str . " <br />";
echo "encode : " . ($enstring = encode ( $str )) . '<br />';
echo "decode : " . decode ( $enstring );
die (); */
