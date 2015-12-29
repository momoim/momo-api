<?php
define('WORD_WIDTH',9);
define('WORD_HIGHT',13);
define('OFFSET_X',8);
define('OFFSET_X2',9);
define('OFFSET_Y',4);
define('OFFSET_Y2',3);
define('WORD_SPACING',4);

class valite
{
	public function setImage($Image)
	{
		$this->ImagePath = $Image;
	}
	public function getData()
	{
		return $data;
	}
	public function getResult()
	{
		return $DataArray;
	}
	public function getHec()
	{
		$res = imagecreatefromjpeg($this->ImagePath);
		//$res = imagecreatefromgif($this->ImagePath);
		$size = getimagesize($this->ImagePath);
		$data = array();
		for($i=0; $i < $size[1]; ++$i)
		{
			for($j=0; $j < $size[0]; ++$j)
			{
				$rgb = imagecolorat($res,$j,$i);
				$rgbarray = imagecolorsforindex($res, $rgb);
				if($rgbarray['red'] < 125 || $rgbarray['green']<125
				|| $rgbarray['blue'] < 125)
				{
					$data[$i][$j]=1;
				}else{
					$data[$i][$j]=0;
				}
			}
		}
		$this->DataArray = $data;
		$this->ImageSize = $size;
	}
	public function run()
	{
		$result="";
		$data = array("","","","");
		for($i=0;$i<4;++$i)
		{
			if($i==0) {
				$x = 9;
				$y = 4;
			}elseif($i==1) {
				$x = 21;
				$y = 2;
			}elseif($i==2) {
				$x = 36;
				$y = 5;
			}elseif($i==3) {
				$x = 46;
				$y = 2;
			}
			
			/*
			if($i==2) {
				$x = ($i*(WORD_WIDTH+WORD_SPACING))+OFFSET_X2;
			}elseif($i==3) {
				$x = ($i*(WORD_WIDTH+WORD_SPACING))+OFFSET_X-1;
			} else {
				$x = ($i*(WORD_WIDTH+WORD_SPACING))+OFFSET_X;
			}
			if($i%2==0) {
				$y = OFFSET_Y;
			} else {
				$y = OFFSET_Y2;
			}
			*/
			
			for($h = $y; $h < (OFFSET_Y+WORD_HIGHT); ++ $h)
			{
				for($w = $x; $w < ($x+WORD_WIDTH); ++$w)
				{
					$data[$i].=$this->DataArray[$h][$w];
				}
			}
			
		}
		/*
		foreach($data as $k=>$v) {
			for($i=0;$i<strlen($v);$i++) {
				if($v[$i] == 0)
				{
					echo '_';
				}
				else
				{
					echo ''.$v[$i];
				}
				
				if($i%9==0) {
					echo '<br/>';
				}
			}
			echo '<br/>*********<br/>';
		}*/

		foreach($data as $numKey => $numString)
		{
			$max=0.0;
			$num = 0;
			foreach($this->Keys as $key => $value)
			{
				$percent=0.0;
				similar_text($value, $numString,$percent);
				if(intval($percent) > $max)
				{
					$max = $percent;
					$num = $key;
					if(intval($percent) > 95)
						break;
				}
			}
			$result.=$num;
		}
		$this->data = $result;
		return $result;
	}

	public function Draw()
	{
		for($i=0; $i<$this->ImageSize[1]; ++$i)
		{
	        for($j=0; $j<$this->ImageSize[0]; ++$j)
		    {
			    echo $this->DataArray[$i][$j];
	        }
		    echo "\n";
		}
	}
	public function __construct()
	{
		$this->Keys = array(
		'0'=>'000111000011111110011000110110000011110000011110000011110000011110000011110000011110000011011000110011111110000111000',
		'1'=>'000111000011111000011111000000011000000011000000011000000011000000011000000011000000011000000011000011111111011111111',
		'2'=>'011111000111111100100000110000000111000000110000001100000011000000110000001100000011000000110000000011111110111111110',
		'3'=>'011111000111111110100000110000000110000001100011111000011111100000001110000000111000000110100001110111111100011111000',
		'4'=>'000001100000011100000011100000111100001101100001101100011001100011001100111111111111111111000001100000001100000001100',
		'5'=>'111111110111111110110000000110000000110000000111110000111111100000001110000000111000000110100001110111111100011111000',
		'6'=>'000111100001111110011000010011000000110000000110111100111111110111000111110000011110000011011000111011111110000111100',
		'7'=>'011111111011111111000000011000000010000000110000001100000001000000011000000010000000110000000110000001100000001100000',
		'8'=>'001111100011111110011000110011000110011101110001111100001111100011101110110000011110000011111000111011111110001111100',
		'9'=>'001111000011111110111000111110000011110000011111000111011111111001111011000000011000000110010000110011111100001111000',
	);
	}
	protected $ImagePath;
	protected $DataArray;
	protected $ImageSize;
	protected $data;
	protected $Keys;
	protected $NumStringArray;

}
?>