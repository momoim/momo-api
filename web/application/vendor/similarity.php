<?php
class similarity
{
    public static function ld($str1,$str2)
    {
        $str1len = strlen($str1);
        $str2len = strlen($str2);

        if($str1len == 0)
        {
            return $str2len;
        }

        if($str2len == 0)
        {
            return $str1len;
        }


        $distance = array();

        for($i = 0; $i <= $str1len;$i++)
        {
            $distance[$i][0] = $i;
        }

        for($j = 0; $j <= $str2len;$j++)
        {
            $distance[0][$j] = $j;
        }

        for($i = 1; $i <= $str1len;$i++)
        {
            $char1 = $str1[$i-1];

            for($j = 1;$j <= $str2len;$j++)
            {
                $char2 = $str2[$j-1];
                if($char1 == $char2)
                {
                    $temp = 0;
                }
                else
                {
                    $temp = 1;
                }

                $distance[$i][$j] = min($distance[$i-1][$j]+1,$distance[$i][$j-1]+1,$distance[$i-1][$j-1]+$temp);
            }

        }
        //print_r($distance);
        // 可以注释掉，下面table的内容，为了测试思路，直观显示数组内容，添加上去的
//        echo '<table border=1>';
//        foreach ($distance as $key=>$value)
//        {
//            echo '<tr>';
//            foreach ($value as $h)
//            {
//                echo '<td>'.$h.'</td>';
//            }
//            echo '</tr>';
//        }
//        echo '</table>';

        return $distance[$str1len][$str2len];
    }

    public static function sim($str1,$str2)
    {
        $ld = self::ld($str1,$str2);

        return 1 - ($ld / max(strlen($str1),strlen($str2)));
    }

}

//$similarity = new similarity();
//$str1 = '吴颖';
//$str2 = '吴云帆';
//echo $similarity->sim($str1,$str1);
//echo $similarity->sim($str1,$str2);
