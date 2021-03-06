<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人语言文件
 */
$lang = array_filter(array(
	//区域1 -- 北美洲号码计划区
	'1' => 
	array(
		//美国
		'美国阿拉巴马州' => '205，251，256，334，659，938',
		'美国阿拉斯加' => '907，250',
		'美国亚利桑那州' => '480，520，602，623，928',
		'美国阿肯色州' => '327，479，501，870',
		'美国加州' => '209，213，310，323，341，369，408，415，424，442，510，530，559，562，619，626，627，628，650，657，661，669，707，714，747 ，760，764，805，818，831，858，909，916，925，935，949，951',
		'美国科罗拉多州' => '303，719，720，970',
		'美国康涅狄格' => '203，475，860，959',
		'美国特拉华州' => '302',
		'美国哥伦比亚' => '202',
		'美国佛罗里达州' => '239，305，321，352，386，407，561，689，727，754，772，786，813，850，863，904，941，954',
		'美国格鲁吉亚' => '229，404，470，478，678，706，762，770，912',
		'美国夏威夷' => '808',
		'美国爱达荷州' => '208',
		'美国伊利诺伊州' => '217，224，309，312，331，447，464，618，630，708，730，773，779，815，847，872',
		'美国印第安纳州' => '219，260，317，574，765，812',
		'美国爱荷华州' => '319，515，563，641，712',
		'美国堪萨斯州' => '316，620，785，913',
		'美国肯塔基州' => '270，364，502，606，859',
		'美国路易斯安那州' => '225，318，337，504，985',
		'美国缅因州' => '207',
		'美国马里兰州' => '227，240，301，410，443，667',
		'美国马萨诸塞州' => '339，351，413，508，617，774，781，857，978',
		'美国密歇根州' => '231，248，269，313，517，586，616，679，734，810，906，947，989',
		'美国明尼苏达州' => '218，320，507，612，651，763，952',
		'美国密西西比州' => '228，601，662，769',
		'美国密苏里州' => '314，417，557，573，636，660，816，975',
		'美国蒙大拿州' => '406',
		'美国内布拉斯加州' => '308，402，531',
		'美国内华达州' => '702，775',
		'美国新汉普郡' => '603',
		'美国新泽西州' => '201，551，609，732，848，856，862，908，973',
		'美国新墨西哥' => '505，575',
		'美国纽约' => '212，315，347，516，518，585，607，631，646，716，718，845，914，917，929',
		'美国北卡罗莱纳州' => '252，336，704，828，910，919，980，984',
		'美国北达科他州' => '701',
		'美国俄亥俄州' => '216，234，283，330，380，419，440，513，567，614，740，937',
		'美国俄克拉何马州' => '405，539，580，918',
		'美国俄勒冈州' => '458，503，541，971',
		'美国宾夕法尼亚州' => '215，267，272，412，445，484，570，582，610，717，724，814，835，878',
		'美国罗德岛' => '401',
		'美国南卡罗来纳州' => '803，843，864',
		'美国南达科他州' => '605',
		'美国田纳西州' => '423，615，731，865，901，931',
		'美国德州' => '210，214，254，281，325，361，409，430，432，469，512，682，713，737，806，817，830，832，903，915，936，940，956，972，979',
		'美国犹他州' => '385，435，801',
		'美国佛蒙特州' => '802',
		'美国弗吉尼亚州' => '276，434，540，571，703，757，804',
		'美国华盛顿' => '206，253，360，425，509，564',
		'美国西弗吉尼亚州' => '304，681',
		'美国威斯康星州' => '262，274，414，534，608，715，920',
		'美国怀俄明州' => '307',
		//加拿大
		'加拿大阿尔伯塔省' => '403，587，780，825',
		'加拿大不列颠哥伦比亚省' => '236，250，604，672，778',
		'加拿大马尼托巴省' => '204，431',
		'加拿大新不伦瑞克省' => '506',
		'加拿大纽芬兰和拉布拉多' => '709',
		'加拿大新斯科舍省' => '902',
		'加拿大安大略省' => '226，249，289，343，365，416，437，519，613，647，705，807，905',
		'加拿大爱德华王子岛' => '902',
		'加拿大魁北克' => '418，438，450，514，579，581，819，873',
		'加拿大萨斯喀彻温省' => '306，639',
		'加拿大育空地区，西北地区和努纳武特地区' => '867',
		//加勒比和百慕大
		'安圭拉岛' => '264',
		'安提瓜和巴布达' => '268',
		'巴哈马' => '242',
		'巴巴多斯' => '246',
		'百慕达' => '441',
		'英属维京群岛' => '28​​4',
		'开曼群岛' => '345',
		'多米尼加' => '767',
		'多米尼加共和国' => '809，829，849',
		'格林纳达' => '473',
		'牙买加' => '876',
		'蒙特塞拉特' => '664',
		'波多黎各' => '787，939',
		'圣基茨和尼维斯' => '869',
		'圣卢西亚' => '758',
		'圣文森特和格林纳丁斯' => '784',
		'圣马丁岛' => '721',
		'特里尼达和多巴哥' => '868',
		'特克斯和凯科斯群岛' => '649',
		'美属维尔京群岛' => '340',
		//美国太平洋领土
		'美国萨摩亚' => '684',
		'关岛' => '671',
		'北马里亚纳群岛' => '670',
		// NANP非地理
		'加拿大特殊服务' => '600',
		'入境国际' => '456',
		'局间载波服务' => '700',
		'个人通讯服务' => '500，522，533，544，566，577，588',
		'高级呼叫服务' => '900',
		'免费' => '800，822，833，844，855，866，877，880，881，882，888',
		'美国政府' => '710',
	),
	//区域2 -- 大部分是非洲地区
	'20' => '埃及',
	'210' => '',//拟分配西撒哈拉
	'211' => '南苏丹',
	'212' => '摩洛哥',
	'213' => '阿尔及利亚',
	'214' => '',//未分配。此号码原先留给阿尔及利亚
	'215' => '',//未分配。此号码原先留给阿尔及利亚
	'216' => '突尼斯',
	'217' => '',//未分配。此号码原先留给突尼斯
	'218' => '利比亚',
	'219' => '',//未分配。此号码原先留给利比亚
	'220' => '冈比亚',
	'221' => '塞内加尔',
	'222' => '毛里塔尼亚',
	'223' => '马里',
	'224' => '几内亚',
	'225' => '科特迪瓦',
	'226' => '布基纳法索',
	'227' => '尼日尔',
	'228' => '多哥',
	'229' => '贝宁',
	'230' => '毛里求斯',
	'231' => '利比里亚',
	'232' => '塞拉利昂',
	'233' => '加纳',
	'234' => '尼日利亚',
	'235' => '乍得',
	'236' => '中非共和国',
	'237' => '喀麦隆',
	'238' => '佛得角',
	'239' => '圣多美和普林西比',
	'240' => '赤道几内亚',
	'241' => '加蓬',
	'242' => '刚果共和国（布）',
	'243' => '刚果民主共和国（金）（即前扎伊尔）',
	'244' => '安哥拉',
	'245' => '几内亚比绍',
	'246' => '迪戈加西亚岛',
	'247' => '阿森松岛',
	'248' => '塞舌尔',
	'249' => '苏丹',
	'250' => '卢旺达',
	'251' => '埃塞俄比亚',
	'252' => '索马里',
	'253' => '吉布提',
	'254' => '肯尼亚',
	'255' => '坦桑尼亚',
	'256' => '乌干达',
	'257' => '布隆迪',
	'258' => '莫桑比克',
	'259' => '', //桑西巴 - 从未使用――参见255坦桑尼亚
	'260' => '赞比亚',
	'261' => '马达加斯加',
	'262' => '留尼汪和马约特',
	'263' => '津巴布韦',
	'264' => '纳米比亚',
	'265' => '马拉维',
	'266' => '莱索托',
	'267' => '博茨瓦纳',
	'268' => '斯威士兰',
	'269' => '科摩罗',
	'27' => '南非',
	'28' => '', //未分配
	'290' => '圣赫勒拿',
	'291' => '厄立特里亚',
	'292' => '', //未分配
	'293' => '', //未分配
	'294' => '', //未分配
	'295' => '', //中止（原先分配给圣马力诺，参见+378）
	'296' => '', //未分配
	'297' => '阿鲁巴',
	'298' => '法罗群岛',
	'299' => '格陵兰',
	//区域3 -- 欧洲
	
	'30' => '希腊',
	'31' => '荷兰',
	'32' => '比利时',
	'33' => '法国',
	'34' => '西班牙',
	'350' => '直布罗陀',
	'351' => '葡萄牙',
	'352' => '卢森堡',
	'353' => '爱尔兰',
	'354' => '冰岛',
	'355' => '阿尔巴尼亚',
	'356' => '马耳他',
	'357' => '塞浦路斯',
	'358' => '芬兰',
	'359' => '保加利亚',
	'36' => '匈牙利',
	'37' => '', //曾经是德意志民主共和国（东德）的区号，合并后的德国区号为49
	'370' => '立陶宛',
	'371' => '拉脱维亚',
	'372' => '爱沙尼亚',
	'373' => '摩尔多瓦',
	'374' => '亚美尼亚',
	'375' => '白俄罗斯',
	'376' => '安道尔',
	'377' => '摩纳哥',
	'378' => '圣马力诺',
	'379' => '', //保留给梵蒂冈
	'38' => '', //前南斯拉夫分裂前的区号
	'380' => '乌克兰',
	'381' => '塞尔维亚',
	'382' => '黑山',
	'383' => '', //未分配
	'384' => '', //拟分配科索沃
	'385' => '克罗地亚',
	'386' => '斯洛文尼亚',
	'387' => '波黑',
	'388' => '欧洲电话号码空间――环欧洲服务',
	'389' => '马其顿', //（前南斯拉夫马其顿共和国, FYROM）
	'39' => '意大利',
	//区域4 欧洲
	'40' => '罗马尼亚',
	'41' => '瑞士',
	'42' => '', //曾经是捷克斯洛伐克的区号
	'420' => '捷克',
	'421' => '斯洛伐克',
	'422' => '', //未分配
	'423' => '列支敦士登',
	'424' => '', //未分配
	'425' => '', //未分配
	'426' => '', //未分配
	'427' => '', //未分配
	'428' => '', //未分配
	'429' => '', //未分配
	'43' => '奥地利',
	'44' => '英国',
	'45' => '丹麦',
	'46' => '瑞典',
	'47' => '挪威',
	'48' => '波兰',
	'49' => '德国',
	//区域5 墨西哥和中南美洲
	'500' => '福克兰群岛',
	'501' => '伯利兹',
	'502' => '危地马拉',
	'503' => '萨尔瓦多',
	'504' => '洪都拉斯',
	'505' => '尼加拉瓜',
	'506' => '哥斯达黎加',
	'507' => '巴拿马',
	'508' => '圣皮埃尔和密克隆群岛',
	'509' => '海地',
	'51' => '秘鲁',
	'52' => '墨西哥',
	'53' => '古巴', //（本应属于北美区，由于历史原因分在5区）
	'54' => '阿根廷',
	'55' => '巴西',
	'56' => '智利',
	'57' => '哥伦比亚',
	'58' => '委内瑞拉',
	'590' => '瓜德罗普',
	'591' => '玻利维亚',
	'592' => '圭亚那',
	'593' => '厄瓜多尔',
	'594' => '法属圭亚那',
	'595' => '巴拉圭',
	'596' => '马提尼克',
	'597' => '苏里南',
	'598' => '乌拉圭',
	'599' => '荷属安的列斯',
	//区域6 -- 东南亚及大洋洲
	'60' => '马来西亚',
	'61' => '澳大利亚',
	'62' => '印度尼西亚',
	'63' => '菲律宾',
	'64' => '新西兰',
	'65' => '新加坡',
	'66' => '泰国',
	'670' => '东帝汶', // 曾经是北马里亚纳群岛（现在是1）
	'671' => '', // 曾经是关岛 (现在是1)
	'672' => '澳大利亚海外领地：南极洲、圣诞岛、可可斯群岛、和诺福克岛',
	'673' => '文莱',
	'674' => '瑙鲁',
	'675' => '巴布亚新几内亚',
	'676' => '汤加',
	'677' => '所罗门群岛',
	'678' => '瓦努阿图',
	'679' => '斐济',
	'680' => '帕劳',
	'681' => '瓦利斯和富图纳群岛',
	'682' => '库克群岛',
	'683' => '纽埃',
	'684' => '美属萨摩亚',
	'685' => '萨摩亚',
	'686' => '基里巴斯，吉尔伯特群岛',
	'687' => '新喀里多尼亚',
	'688' => '图瓦卢，埃利斯群岛',
	'689' => '法属波利尼西亚',
	'690' => '托克劳群岛',
	'691' => '密克罗尼西亚联邦',
	'692' => '马绍尔群岛',
	'693' => '', //未分配
	'694' => '', //未分配
	'695' => '', //未分配
	'696' => '', //未分配
	'697' => '', //未分配
	'698' => '', //未分配
	'699' => '', //未分配
	//区域7 - 俄罗斯及附近地区 (前苏联)
	'7' => '俄罗斯、哈萨克斯坦',
// 	'7 6' => '哈萨克斯坦',
// 	'7 7' => '哈萨克斯坦',
// 	'7 840' => '阿布哈兹',
// 	'7 940' => '阿布哈兹', // 另见+995 44
	//区域8 -- 东亚以及特殊服务
	'800' => '国际免费电话',
	'801' => '', //未分配
	'802' => '', //未分配
	'803' => '', //未分配
	'804' => '', //未分配
	'805' => '', //未分配
	'806' => '', //未分配
	'807' => '', //未分配
	'808' => '国际分摊费用服务',
	'809' => '', //未分配
	'81' => '日本',
	'82' => '大韩民国',
	'83' => '', //未分配
	'84' => '越南',
	'850' => '朝鲜民主主义人民共和国',
	'851' => '', //测试专用
	'852' => '香港',
	'853' => '澳门',
	'854' => '', //未分配
	'855' => '柬埔寨',
	'856' => '老挝',
	'857' => '', //未分配
	'858' => '', //未分配
	'859' => '', //未分配
	'86' => '中华人民共和国',
	'870' => '海事卫星电话',
	'875' => '', //预留给海洋移动通讯服务
	'876' => '', //预留给海洋移动通讯服务
	'877' => '', //预留给海洋移动通讯服务
	'878' => '环球个人通讯服务',
	'879' => '', //预留给国家移动/海洋使用
	'880' => '孟加拉人民共和国',
	'881' => '移动卫星系统',
	'882' => '国际网络',
	'883' => '', //未分配
	'884' => '', //未分配
	'885' => '', //未分配
	'886' => '台湾',
	'887' => '', //未分配
	'888' => '', //未分配
	'889' => '', //未分配
	'89' => '', //未分配
	//区域9 - 西亚及南亚、中东
	'90' => '土耳其',
	'91' => '印度',
	'92' => '巴基斯坦',
	'93' => '阿富汗',
	'94' => '斯里兰卡',
	'95' => '缅甸',
	'960' => '马尔代夫',
	'961' => '黎巴嫩',
	'962' => '约旦',
	'963' => '叙利亚',
	'964' => '伊拉克',
	'965' => '科威特',
	'966' => '沙特阿拉伯',
	'967' => '也门',
	'968' => '阿曼',
	'969' => '', //曾经是也门民主人民共和国（南也门）的区号，合并后的也门统一使用967区号
	'970' => '', //预留给巴勒斯坦
	'971' => '阿拉伯联合酋长国',
	'972' => '以色列',
	'973' => '巴林',
	'974' => '卡塔尔',
	'975' => '不丹',
	'976' => '蒙古',
	'977' => '尼泊尔',
	'978' => '', //未分配
	'979' => '国际费率服务',
	'98' => '伊朗',
	'990' => '', //未分配
	'991' => '国际电信个人通讯服务',
	'992' => '塔吉克斯坦',
	'993' => '土库曼斯坦',
	'994' => '阿塞拜疆',
	'995' => '格鲁吉亚',
	//+995 44 
	//+995 xx 南奥塞梯
	'996' => '吉尔吉斯斯坦',
	'997' => '', //未分配
	'998' => '乌兹别克斯坦',
	'999' => '', //保留，可能移作紧急救援
	));
