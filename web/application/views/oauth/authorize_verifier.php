<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo html::specialchars($title) ?></title>

	<style type="text/css">
	html { background: #69ad0f 50% 0 no-repeat; }
	body { width: 52em; margin: 200px auto 2em; font-size: 76%; font-family: Arial, sans-serif; color: #273907; line-height: 1.5; text-align: center; }
	h1 { font-size: 3em; font-weight: normal; text-transform: uppercase; color: #fff; }
	a { color: inherit; }
	code { font-size: 1.3em; }
	ul { list-style: none; padding: 2em 0; }
	ul li { display: inline; padding-right: 1em; text-transform: uppercase; }
	ul li a { padding: 0.5em 1em; background: #69ad0f; border: 1px solid #569f09; color: #fff; text-decoration: none; }
	ul li a:hover { background: #569f09; }
	.box { padding: 2em; background: #98cc2b; border: 1px solid #569f09; }
	.copyright { font-size: 0.9em; text-transform: uppercase; color: #557d10; }
        .form_wrap{padding:4px 30px 1px;position:relative;width:160px;margin:0 auto;}
        .lable_fm{line-height:24px;}
        .row_fm{width:100%;padding-bottom:10px;text-align:left;}
        .error{color:#ff0000;}
       </style>

</head>
<body>

	<h1><?php echo html::specialchars($title) ?></h1>
	<div class="box">
            获得的授权码：<b><?php echo $verifier; ?></b>
        </div>
<ul>
</ul>

	<p class="copyright">
		Copyright 2010-2011 © momo.im 闽ICP证 B2-20050038
	</p>

</body>
</html>