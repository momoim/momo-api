<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  通知模板
 * id 唯一性 模板ＩＤ
 * auto 通知是否自动设置为己阅
 * 备注：改变状态后的模板ＩＤ一律为 (原ID.0.*N) (N为第几种状态)
 * AUTO_INCREMENT=12
 */
$config = array();
/*
 * @好友模块
 */
//好友请求
$config['friendReq']['id']    = 1;
$config['friendReq']['auto']  = false;
$config['friendReq']['title'] = '请求加你为好友。[friend_apply]';
$config['friendReq']['body']  = '附言:{explain}';

//同意请求后的模板
$config['friendReq1']['id']    = 101;
$config['friendReq1']['auto']  = false;
$config['friendReq1']['title'] = '请求加你为好友。你已同意了该请求！[friend_apply]';
$config['friendReq1']['body']  = '附言:{explain}';

//忽略请求后的模板
$config['friendReq2']['id']    = 102;
$config['friendReq2']['auto']  = false;
$config['friendReq2']['title'] = '请求加你为好友。你已忽略了该请求！[friend_apply]';
$config['friendReq2']['body']  = '附言:{explain}';

//应邀加入自动成为好友
$config['autoFriend']['id']    = 2;
$config['autoFriend']['auto']  = true;
$config['autoFriend']['title'] = '应你的邀请加入了MOMMO，你们自动成为好友。';
$config['autoFriend']['body']  = '';

//已通过好友请求模板
$config['agreeFriendReq']['id']    = 3;
$config['agreeFriendReq']['auto']  = true;
$config['agreeFriendReq']['title'] = '同意了你的好友请求.[friend_apply]';
$config['agreeFriendReq']['body']  = '';

//好友介绍
$config['offerFriend']['id']    = 4;
$config['offerFriend']['auto']  = true;
$config['offerFriend']['title'] = "介绍 [recommend_friend] 给你认识!";
$config['offerFriend']['body']  = '附言:{explain}';

/*
 * @群组模块
 */
//申请加入群
$config['applyJoin']['id']    = 5;
$config['applyJoin']['auto']  = false;
$config['applyJoin']['title'] = '申请加入群 [group_apply]';
$config['applyJoin']['body']  = '';

//同意入群申请
$config['applyJoin1']['id']    = 501;
$config['applyJoin1']['auto']  = false;
$config['applyJoin1']['title'] = '申请加入群[group_apply]，你已通过该申请';
$config['applyJoin1']['body']  = '';

//忽略入群申请
$config['applyJoin2']['id']    = 502;
$config['applyJoin2']['auto']  = false;
$config['applyJoin2']['title'] = '申请加入群[group_apply]，你已忽略该申请';
$config['applyJoin2']['body']  = '';

//忽略入群申请
$config['applyJoin3']['id']    = 503;
$config['applyJoin3']['auto']  = false;
$config['applyJoin3']['title'] = '申请加入群[group_apply]，其它管理员己处理';
$config['applyJoin3']['body']  = '';

//忽略入群申请
$config['applyJoin4']['id']    = 504;
$config['applyJoin4']['auto']  = false;
$config['applyJoin4']['title'] = '申请加入群[group_apply]，群人数超限制';
$config['applyJoin4']['body']  = '';

//忽略入群申请
$config['applyJoin5']['id']    = 505;
$config['applyJoin5']['auto']  = false;
$config['applyJoin5']['title'] = '申请加入群[group_apply]，你已不是该群管理员';
$config['applyJoin5']['body']  = '';

//忽略入群申请
$config['applyJoin6']['id']    = 506;
$config['applyJoin6']['auto']  = false;
$config['applyJoin6']['title'] = '申请加入群[group_apply]，群已不存在';
$config['applyJoin6']['body']  = '';

//同意入群申请
$config['agreeJoinGroup']['id']   =6;
$config['agreeJoinGroup']['auto'] =true;
$config['agreeJoinGroup']['title']='管理员同意了你加入群[group_apply]';
$config['agreeJoinGroup']['body'] ='';

//私密群邀请入群
$config['groupInvite']['id']    = 7;
$config['groupInvite']['auto']  = false;
$config['groupInvite']['title'] = '邀请你加入群 [group_invite]';
$config['groupInvite']['body']  = '';

//同意入私密群邀请
$config['groupInvite1']['id']    = 701;
$config['groupInvite1']['auto']  = false;
$config['groupInvite1']['title'] = '邀请你加入群 [group_invite], 你已同意加入该群';
$config['groupInvite1']['body']  = '';

//忽略入私密群邀请
$config['groupInvite2']['id']    = 702;
$config['groupInvite2']['auto']  = false;
$config['groupInvite2']['title'] = '邀请你加入群 [group_invite], 你已忽略该邀请';
$config['groupInvite2']['body']  = '';

//同意入私密群邀请,但超出群最高人数限制，忽略请求
$config['groupInvite3']['id']    = 703;
$config['groupInvite3']['auto']  = false;
$config['groupInvite3']['title'] = '邀请你加入群 [group_invite], 群成员人数已满';
$config['groupInvite3']['body']  = '';

//处理私密群邀请时，群组已经被删除，忽略请求
$config['groupInvite4']['id']    = 704;
$config['groupInvite4']['auto']  = false;
$config['groupInvite4']['title'] = '邀请你加入群 [group_invite],群已不存在';
$config['groupInvite4']['body']  = '';

/*
 * @用户信息模块
 */
//个人资料变更
$config['user_information_update']['id']    = 8;
$config['user_information_update']['auto']  = true;
$config['user_information_update']['title'] = '{caption}';
$config['user_information_update']['body']  = "{foreach summary as val} \n {val['describe']}设置为:\"{val['info']}\" {endforeach}";

/*
 * @活动信息模块
 */
//邀请参加活动
$config['actionInvite']['id']    = 9;
$config['actionInvite']['auto']  = false;
$config['actionInvite']['title'] = '邀请你参加活动[action_info]';
$config['actionInvite']['body']  = '';

//同意邀请参加活动
$config['actionInvite1']['id']    = 901;
$config['actionInvite1']['auto']  = false;
$config['actionInvite1']['title'] = '邀请你参加活动[action_info],你已确定参加该活动';
$config['actionInvite1']['body']  = '';

//拒绝邀请参加活动
$config['actionInvite2']['id']    = 902;
$config['actionInvite2']['auto']  = false;
$config['actionInvite2']['title'] = '邀请你参加活动[action_info],你已确定不参加该活动';
$config['actionInvite2']['body']  = '';

//对邀请参加活动感兴趣
$config['actionInvite3']['id']    = 903;
$config['actionInvite3']['auto']  = false;
$config['actionInvite3']['title'] = '邀请你参加活动[action_info],你已选择对活动感兴趣';
$config['actionInvite3']['body']  = '';

//参加活动的报名时间己结束
$config['actionInvite4']['id']    = 904;
$config['actionInvite4']['auto']  = false;
$config['actionInvite4']['title'] = '邀请你参加活动[action_info],报名时间已结束';
$config['actionInvite4']['body']  = '';

//活动已被删除
$config['actionInvite5']['id']    = 905;
$config['actionInvite5']['auto']  = false;
$config['actionInvite5']['title'] = '邀请你参加活动[action_info],活动已解散';
$config['actionInvite5']['body']  = '';

//参加或感兴趣的人会收到的活动变更
$config['actionUpdate']['id']    = 10;
$config['actionUpdate']['auto']  = true;
$config['actionUpdate']['title'] = '修改了活动[action_info_modify]';
$config['actionUpdate']['body']  = "{foreach summary as val} \n {val['describe']}<span class=\"colon\">:</span><span class=\"summary\">{val['info']}</span><br />\n {endforeach}";

//删除活动，所有活动参加和感兴趣成员受到系统消息提示
$config['actionDelete']['id']   =13;
$config['actionDelete']['auto'] =true;
$config['actionDelete']['title']='删除了活动[action_info]';
$config['actionDelete']['body'] ='';

/*
//发起群活动，通知每个群成员
$config['actionGroupNotice']['id']   =14;
$config['actionGroupNotice']['auto'] =true;
$config['actionGroupNotice']['title']='<p class="s-info"><a href="user/{uid}" class="s-name" target="_blank">{name}</a>在"{group[\'name\']}"群发起一个活动"<a href="action/{action[0][\'id\']}" target="_blank">{action[0][\'name\']}</a>"<span class="s-time"> %s </span></p>';
$config['actionGroupNotice']['body'] ='';
*/

/*
//管理员拒绝入群申请
$config['rejectJoinGroup']['id']   =11;
$config['rejectJoinGroup']['auto'] =true;
$config['rejectJoinGroup']['title']='<p class="s-info">管理员<a href="user/{uid}" class="s-name" target="_blank">{name}</a>拒绝了您加入群"{group[0][\'name\']}"的申请<span class="s-time"> %s </span></p>';
$config['rejectJoinGroup']['body'] ='';
*/

//普通成员邀请好友加入群
$config['inviteApplyJoin']['id']    = 12;
$config['inviteApplyJoin']['auto']  = false;
$config['inviteApplyJoin']['title'] = '邀请你加入群[group_invite]';
$config['inviteApplyJoin']['body']  = '';

//同意入群申请，并成功加入群
$config['inviteApplyJoin1']['id']    = 1201;
$config['inviteApplyJoin1']['auto']  = false;
$config['inviteApplyJoin1']['title'] = '邀请你加入群 [group_invite], 你已加入该群';
$config['inviteApplyJoin1']['body']  = '';

//同意入群申请
$config['inviteApplyJoin2']['id']    = 1202;
$config['inviteApplyJoin2']['auto']  = false;
$config['inviteApplyJoin2']['title'] = '邀请你加入群 [group_invite], 你已提交申请';
$config['inviteApplyJoin2']['body']  = '';

//拒绝入群申请
$config['inviteApplyJoin3']['id']    = 1203;
$config['inviteApplyJoin3']['auto']  = false;
$config['inviteApplyJoin3']['title'] = '邀请你加入群 [group_invite], 你已拒绝加入该群';
$config['inviteApplyJoin3']['body']  = '';

//同意入群申请,但超出群最高人数限制，忽略请求
$config['inviteApplyJoin4']['id']    = 1204;
$config['inviteApplyJoin4']['auto']  = false;
$config['inviteApplyJoin4']['title'] = '邀请你加入群 [group_invite], 群成员人数已满，忽略该邀请';
$config['inviteApplyJoin4']['body']  = '';

//同意入群申请，群组已经被删除，忽略请求
$config['inviteApplyJoin5']['id']    = 1205;
$config['inviteApplyJoin5']['auto']  = false;
$config['inviteApplyJoin5']['title'] = '邀请你加入群 [group_invite], 群已删除，忽略该邀请';
$config['inviteApplyJoin5']['body']  = '';

//新人通过群或者活动邀请注册进来时消息提示
$config['messageTip']['id']   =15;
$config['messageTip']['auto'] =true;
$config['messageTip']['title']='<p class="s-info">{title}<span class="s-time"> %s </span></p>';
$config['messageTip']['body'] ='';

//公开群管理员邀请入群
$config['groupInviteJoin']['id']    = 16;
$config['groupInviteJoin']['auto']  = false;
$config['groupInviteJoin']['title'] = '邀请你加入群[group_invite]';
$config['groupInviteJoin']['body']  = '';

//同意入群邀请
$config['groupInviteJoin1']['id']    = 1601;
$config['groupInviteJoin1']['auto']  = false;
$config['groupInviteJoin1']['title'] = '邀请你加入群 [group_invite], 你已同意加入该群';
$config['groupInviteJoin1']['body']  = '';

//忽略入群邀请
$config['groupInviteJoin2']['id']    = 1602;
$config['groupInviteJoin2']['auto']  = false;
$config['groupInviteJoin2']['title'] = '邀请你加入群 [group_invite], 你已忽略加入该群';
$config['groupInviteJoin2']['body']  = '';

//同意入群邀请,但超出群最高人数限制，忽略请求
$config['groupInviteJoin3']['id']    = 1603;
$config['groupInviteJoin3']['auto']  = false;
$config['groupInviteJoin3']['title'] = '邀请你加入群 [group_invite], 群成员人数已满，忽略该邀请';
$config['groupInviteJoin3']['body']  = '';

//处理群邀请时，群组已经被删除，忽略请求
$config['groupInviteJoin4']['id']    = 1604;
$config['groupInviteJoin4']['auto']  = false;
$config['groupInviteJoin4']['title'] = '邀请你加入群 [group_invite], 群已删除，忽略该邀请';
$config['groupInviteJoin4']['body']  = '';

//处理群邀请时，群邀请已经失效，忽略请求
$config['groupInviteJoin5']['id']    = 1605;
$config['groupInviteJoin5']['auto']  = false;
$config['groupInviteJoin5']['title'] = '邀请你加入群 [group_invite], 邀请已失效';
$config['groupInviteJoin5']['body']  = '';

//活动发起人设置我为组织者
$config['actionSetOrganizer']['id']   =17;
$config['actionSetOrganizer']['auto'] =true;
$config['actionSetOrganizer']['title']='设置你为活动[action_info]的组织者';
$config['actionSetOrganizer']['body'] ='';

//活动发起人取消我的组织者权限
$config['actionUnsetOrganizer']['id']   =18;
$config['actionUnsetOrganizer']['auto'] =true;
$config['actionUnsetOrganizer']['title']='取消了你在活动[action_info]的组织者身份';
$config['actionUnsetOrganizer']['body'] ='';

//申请活动组织者
$config['actionApplyOrganizer']['id']    = 19;
$config['actionApplyOrganizer']['auto']  = false;
$config['actionApplyOrganizer']['title'] = '申请为活动[action_info]的组织者';
$config['actionApplyOrganizer']['body']  = '';

//同意活动组织者申请
$config['actionApplyOrganizer1']['id']    = 1901;
$config['actionApplyOrganizer1']['auto']  = false;
$config['actionApplyOrganizer1']['title'] = '申请为活动[action_info]的组织者, 你已通过该申请';
$config['actionApplyOrganizer1']['body']  = '';

//忽略活动组织者申请
$config['actionApplyOrganizer2']['id']    = 1902;
$config['actionApplyOrganizer2']['auto']  = false;
$config['actionApplyOrganizer2']['title'] = '申请为活动[action_info]的组织者, 你已忽略该申请';
$config['actionApplyOrganizer2']['body']  = '';

//活动组织者人数已满
$config['actionApplyOrganizer3']['id']    = 1903;
$config['actionApplyOrganizer3']['auto']  = false;
$config['actionApplyOrganizer3']['title'] = '申请为活动[action_info]的组织者, 活动组织者人数已满';
$config['actionApplyOrganizer3']['body']  = '';

//活动已解散
$config['actionApplyOrganizer4']['id']    = 1904;
$config['actionApplyOrganizer4']['auto']  = false;
$config['actionApplyOrganizer4']['title'] = '申请为活动[action_info]的组织者, 活动已解散';
$config['actionApplyOrganizer4']['body']  = '';

//申请人已报名不参加活动
$config['actionApplyOrganizer5']['id']    = 1905;
$config['actionApplyOrganizer5']['auto']  = false;
$config['actionApplyOrganizer5']['title'] = '申请为活动[action_info]的组织者, 申请人已报名不参加活动';
$config['actionApplyOrganizer5']['body']  = '';