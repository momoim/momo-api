<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人语言文件
 */
$lang = array(
	//公共错误
	'resource_not_exist'                  => '请求的资源不存在',
	'method_not_exist'                    => '请求的方法不存在',
	'no_permission'                       => '您没有权限',
	'service_unavailable'                 => '服务暂时不可用',
	//用户错误
	'user_id_empty'                       => '400201:用户ID为空',
	//联系人分组错误
	'group_id_empty'                      => '400231:分组ID为空',
	'group_name_empty'                    => '400232:分组名为空',
	'group_name_exist'                    => '400233:分组名已存在',
	'group_not_exist'                     => '400234:分组不存在',
	'group_ids_empty'                     => '400235:分组ids为空',
	'group_ids_not_complete'              => '400236:分组ids不完整',
	'group_name_too_long'                 => '400237:分组名太长',
	'group_id_and_name_not_set'           => '400237:分组ID和分组名不能同时设置',
	//联系人错误
	'contact_id_empty'                    => '400211:联系人ID为空',
	'contact_ids_empty'                   => '400212:联系人ids为空',
	'contact_ids_exceed_limit'            => '400213:联系人ids超过上限(100个)',
	'contact_info_incorrect'              => '400214:联系人信息有误',
	'contact_not_exist'                   => '400215:联系人不存在',
	'contact_exceed_limit'                => '400216:联系人超过上限(100个)',
	'contact_has_saved'                   => '400217:联系人已保存',
	'contact_name_empty'                  => '400218:姓名为空',
	'contact_tel_no_allow'                => '400219:手机号码为空或非法',
	//分页、时光机错误
	'page_limit'                          => '400221:页数不合法',
	'page_size_limit'                     => '400222:页大小不合法',
	'dateline_empty'                      => '400223:时间为空',
	'history_not_exist'                   => '400224:变更历史不存在',
	'snapshot_data_empty'                 => '400225:时光机内容为空',
	'recover_history_fail'                => '400226:还原历史失败',
	'operation_not_enable'                => '400227:功能暂时不可用',
	'info_limit'                          => '400228:info参数不合法',
	'query_limit'                         => '400229:关键字不能为空',
	//其他错误
	'contact_update_fail'                 => '修改联系人失败',
	'contact_info_conflict'               => '联系人信息冲突',
	'operation_fail'                      => '操作失败',
	'param_limit'                         => '400241:参数不合法',
	//时光机操作类型
	'add'                                 => '新增联系人',
	'import'                              => '导入联系人',
	'update'                              => '修改联系人',
	'merge'                               => '合并联系人',
	'delete'                              => '删除联系人',
	'recover'                             => '还原联系人',
	'auto_merge'                          => '自动合并联系人',
	'save'                                => '保存名片',
	'sync'                                => '同步联系人',
	'complete'                            => '完善信息',
	'recover_snapshot'                    => '还原快照',
	'set_category'                        => '设置分组',
	'update_category'                     => '更新分组',
	'add_category'                        => '加入分组',
	'remove_category'                     => '移出分组',
	'contact_update_mobile'               => '联系人更换手机号',
	'manual_backup'                       => '备份联系人',
);

