<?php defined('SYSPATH') or die('No direct access allowed.');?>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="application/vnd.wap.xhtml+xml;charset=UTF-8"/>
<title>MOMO - 查看日记</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; minimum-scale=1.0; maximum-scale=2.0"/>
</head>
<body>
<div class="detail-show">
<?php
if('allow' == $seepower || $uid==$row['uid']):
    if($row['uid']==$row['aid']):
        $from_who =  '发表于：' . date('Y-m-d',$row['addtime']);
        $presubject = '';
    else:
        $from_who = date('Y-m-d',$row['addtime']) . '<span class="from">转自：</span><a class="username head-tip" href="' . url::base() . 'user/' . $row['aid'] . '" target="_blank">' . sns::getrealname($row['aid']) . '</a>' ;
        $presubject =  '<span class="presubject">[转]</span>';
    endif;
?>
<h3><?php echo $presubject,$row['subject']?></h3>
<div class="detail-show-tip"> 
<span class="time"><?php echo $from_who?></span>
<span class="type">分类：<?php echo htmlspecialchars($cateName)?></span>
<?php
if (isset($privacys[$row['privacy']])):
    echo '<span class="privi">权限：',$privacys[$row['privacy']],'</span>';
endif;
?>
</div>
<div style="border-bottom: 1px dotted #66676B; clear: both; margin: 3px;"></div>
<div class="detail-show-info">
<?php
echo $content;
if($row['quoturl']) {
    echo "<br />(本文来源：<a href=\"{$row['quoturl']}\" target=\"_blank\">{$row['quoturl']}</a>)";
}
?>
</div>
<?php
else:
?>
<div class="tip-0"><?php echo $denyCal;?></div>
<?php
endif;
?>  
</div>
</body>
</html>