<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [UAP Portal] (C)1999-2012 ND Inc.
 * callshow配置文件
 */

//后台
$config['backend'] = array(
							'admin'=>array
								(
									285=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'template'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									9=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'template'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									43=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
										),
									15=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'template'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									353=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'template'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									482=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									27343629=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),
									188=>array
										(
											'image'=>array('read'=>1,'write'=>1),
											'ring'=>array('read'=>1,'write'=>1),
											'show'=>array('read'=>1,'write'=>1),
											'user'=>array(),
											'feedback'=>array('read'=>1,'write'=>1),
											'super'=>true,
										),										
								),
							'ip'=>array
								(
									'121.207.242.118'=>1,
									'10.1.242.118'=>1,
									'121.207.242.119'=>1,
									'10.1.242.119'=>1,
									'121.207.242.120'=>1,
									'10.1.242.120'=>1,
									'121.207.242.121'=>1,
									'10.1.242.121'=>1,
									'121.207.242.122'=>1,
									'10.1.242.122'=>1,
									'121.207.242.123'=>1,
									'10.1.242.123'=>1,
									'121.207.242.210'=>1,
									'10.1.242.210'=>1,
									'121.207.242.137'=>1,
									'10.1.242.137'=>1,
									'127.0.0.1'=>1,
								),
							'admin_imsi'=>array
								(
									"460020594516423"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
									"460000854689217"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
									"460016020382418"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
									"460009150514266"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
									"460023600306096"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
									"460010325603251"=>array
										(
											"callshow"=>array('read'=>1,'write'=>1),
										),
								),
						);

$config['event'] = array(
							'type'=>array(
											'create_show_4_me'=>array('id'=>1),
											'gift'=>array('id'=>2),
											'system'=>array('id'=>3),
											'relation_user_join'=>array('id'=>4),
											'relation_user_refresh_show'=>array('id'=>5),
                                         ),
						);

?>