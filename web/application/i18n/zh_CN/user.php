<?php defined('SYSPATH') or die('No direct access allowed.');
/**
*用户语言包文件
*/

$lang = array(
    'name_length_limit' => '400127:姓名长度不合法',
    'name_cn_limit' => '400128:姓名不是中文',
	'family_name_limit' => '400129:您的姓不合法',
	'name_sensitive_limit' => '400130:姓名包含非法关键字',
    'name_reviewed' => '400131:姓名已通过审核，不能修改',
    'name_cn_en_limit' => '400139:姓名只允许为中文或英文',

	'gender_limit' => '400132:性别不合法',
	'gender_reviewed' => '400133:性别已通过审核，不能修改',

	'birthday_no_allow' => '400134:生日大于当前日期',
	'birthday_limit' => '400135:不存在该日期',
    'birthday_reviewed' => '400136:生日已通过审核，不能修改',

	'organization_strlen_limit' => '400137:公司/学校超过%s字限制',
    'note_strlen_limit' => '400138:个人说明超过%s字限制',
	
	'mobile_empty' => '400101:手机号码为空',
	'mobile_limit' => '400102:手机号码格式不对',
	'mobile_not_register' => '400103:手机号码未注册',
	'mobile_exceed_max' => '400104:手机号码超过上限',
	
	'username' => Array
    (
        'required' => '* 请输入用户名',
        'default' => '* 4-70位，英文小写字母a-z和数字',
        'error' =>'* 用户名不合法',
        'exist' => '* 用户名已存在',
        'notexist'=>'用户名尚未注册'
    )
);

