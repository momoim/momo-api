<?php defined('SYSPATH') OR die('No direct access allowed.');
//事件模板
//AUTO_INCREMENT=31

$config['all'] = array(
  array('typeid'=>1,'typename'=>'user_face_update','appid'=>1,'icon'=>'user','title_tpl'=>'<span class="type-desc">更新了头像</span>','body_tpl'=>'<div class="multi"><ul><li><img data-box="true" pid="{pid}" aid="{aid}" width="120" height="120" border="0" alt="{name}" src="{image}"></li></ul><div class="clear"></div></div>'),
  array('typeid'=>3,'typename'=>'user_intro_update','appid'=>1,'icon'=>'modify','title_tpl'=>'<span class="type-desc">变更了个人描述</span><br />{summary}','body_tpl'=>''),
  array('typeid'=>4,'typename'=>'user_edu_add','appid'=>1,'icon'=>'modify','title_tpl'=>'<span class="type-desc">变更了教育情况</span><br />{summary}','body_tpl'=>''),
  array('typeid'=>5,'typename'=>'user_work_add','appid'=>1,'icon'=>'modify','title_tpl'=>'<span class="type-desc">变更了工作情况</span><br />{summary}','body_tpl'=>''),
  array('typeid'=>6,'typename'=>'user_information_update','appid'=>1,'icon'=>'modify','title_tpl'=>'<span class="type-desc">修改了个人资料</span><br />{summary}','body_tpl'=>''),
  array('typeid'=>7,'typename'=>'user_contact_update','appid'=>1,'icon'=>'modify','title_tpl'=>'<span class="type-desc">把联系方式改为</span><br />{summary}','body_tpl'=>''),
  array('typeid'=>17,'typename'=>'user_sign_update','appid'=>1,'icon'=>'sign','title_tpl'=>'<span class="type-desc">更新了签名</span><span class="colon">:</span><span class="summary">{summary}</span>','body_tpl'=>''),
  array('typeid'=>2,'typename'=>'friend_mutual','appid'=>2,'icon'=>'friend','title_tpl'=>'<a href="user/{fuid}" class="username" target="_blank">{fname}</a>和<a href="user/{tuid}" class="username" target="_blank">{tname}</a>成为了好友','body_tpl'=>''),
  array('typeid'=>9,'typename'=>'recommend_ad','appid'=>2,'icon'=>'recommend_ad','title_tpl'=>' <span class="type-desc">推荐一位{sex}给大家认识</span>','body_tpl'=>'<div class="single">
  <div class="photo f-l-1"> <a href="user/{tuid}"><img src="{avatar}" width="130" height="130" alt="{tname}" /></a></div>
  <div class="sub-detail f-l">
  <h6><a href="user/{tuid}" class="username" target="_blank">{tname}</a></h6>
  <p>{summary}</p>
  <div class="to-detail"><a href="#{tuid}">加入好友</a></div>
  </div>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>12,'typename'=>'photo_upload','appid'=>3,'icon'=>'photo','title_tpl'=>'<span class="type-desc">上传了{num}张照片到相册</span><span class="quot">“</span><a href="photo/listPhoto/{album_id}#p=0">{album_name}</a><span class="quot">”</span>','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" pid="{var[pid]}" aid="{var[aid]}" rel="http://uap19.91.com/photo/thumb/{var[pid]}_780.jpg" />
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>13,'typename'=>'photo_comment','appid'=>3,'icon'=>'photo','title_tpl'=>'<span class="type-desc">照片收到评论</span>','body_tpl'=>'<div class="multi">
  <ul>
  <li><img data-box="true" src="{image}" width="{width}" height="{height}" pid="{pid}" aid="{aid}"/></li>
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>21,'typename'=>'album','appid'=>3,'icon'=>'album','title_tpl'=>'<span class="type-desc">上传了{num}张照片到相册</span><span class="quot">“</span><a href="photo/listPhoto/{album_id}#p=0">{album_name}</a><span class="quot">”</span>','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" pid="{var[pid]}" aid="{var[aid]}" rel="http://uap19.91.com/photo/thumb/{var[pid]}_780.jpg" />
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>8,'typename'=>'record_add','appid'=>4,'icon'=>'record','title_tpl'=>'<span id="content_{rec_id}" class="summary">{summary}</span>','body_tpl'=>'<div class="record-photo">
  {foreach array as var}
  <img data-box="true" src="{var[src]}" {var[size]}  rel="{var[rel]}" class="{var[classname]}"/>
  {endforeach}
  </div>'),
  array('typeid'=>10,'typename'=>'diary_add','appid'=>5,'icon'=>'diary','title_tpl'=>'<span class="type-desc">{dtype}了一篇日志</span><span class="quot">“</span><a data-box="true" href="blog/showblogbox/{blogid}">{title}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
<p>{summary}</p>
</div>'),
  array('typeid'=>14,'typename'=>'vote_do','appid'=>6,'icon'=>'vote','title_tpl'=>'<span class="type-desc">参与了</span><span class="quot">“</span><a href="vote/showbox/{voteid}/{gid}">{subject}</a><span class="quot">”</span><span class="type-desc">的投票</span>','body_tpl'=>'<div class="detail">
  <p class="intro">{summary}</p>
  <ol>
  {foreach votes as val}
  <li>
  <input type="{checkbox}" disabled="disabled" class="v" {val[checked]} />
  <span class="v">{val[name]}</span></li>
  {endforeach}
  </ol>
  </div>'),
  array('typeid'=>16,'typename'=>'vote_add','appid'=>6,'icon'=>'vote','title_tpl'=>'<span class="type-desc">发起了投票</span><span class="quot">“</span><a data-box="true" href="vote/showbox/{voteid}">{subject}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
  <p class="intro">{summary}</p>
  <ol>
  {foreach votes as val}
  <li>
  <input type="{checkbox}" disabled="disabled" class="v" />
  <span class="v">{val}</span></li>
  {endforeach}
  </ol>
  </div>'),
  array('typeid'=>11,'typename'=>'group_join','appid'=>7,'icon'=>'group','title_tpl'=>'<span class="type-desc">加入了</span> <span class="quot">“</span>{groupname}<span class="quot">”</span><span class="type-desc">群</span>','body_tpl'=>''),
  array('typeid'=>19,'typename'=>'group_album','appid'=>7,'icon'=>'album','title_tpl'=>'<span class="type-desc">上传了{num}张照片到群相册</span><span class="quot">“</span><a href="group/photolist/{group_id}/{album_id}#p=0">{album}</a><span class="quot">”</span>','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" aid="{var[aid]}" pid="{var[photo_id]}" rel="http://uap19.91.com/photo/thumb/{var[photo_id]}_780.jpg" />
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>20,'typename'=>'group_photo','appid'=>7,'icon'=>'photo','title_tpl'=>'<span class="type-desc">上传了{num}张照片到群相册</span><span class="quot">“</span><a href="group/photolist/{group_id}/{album_id}#p=0">{album}</a><span class="quot">”</span>','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" aid="{var[aid]}" pid="{var[photo_id]}" rel="http://uap19.91.com/photo/thumb/{var[photo_id]}_780.jpg" />
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>22,'typename'=>'group_photo_comment','appid'=>7,'icon'=>'photo','title_tpl'=>'<span class="type-desc">照片收到评论</span>','body_tpl'=>'<div class="multi">
  <ul>
  <li><img data-box="true" src="{image}" width="{width}" height="{height}" alt="{photo_name}" pid="{pid}" aid="{aid}"/></li>
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>18,'typename'=>'index_leave','appid'=>8,'icon'=>'message','title_tpl'=>'对<a href="user/{tuid}" class="username head-tip" target="_blank">{tname}</a>说<span class="colon">:</span><span class="summary">{content}</span>','body_tpl'=>''),
  array('typeid'=>23,'typename'=>'diary_quot','appid'=>5,'icon'=>'diary','title_tpl'=>'<span class="type-desc">转载</span><a href="user/{fuid}" class="username" target="_blank">{fname}</a><span class="type-desc">的日志</span><span class="quot">“</span><a data-box="true" href="blog/showblogbox/{blogid}">{title}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
<p>{summary}</p>
</div>'),
  array('typeid'=>24,'typename'=>'diary_syn','appid'=>7,'icon'=>'diary','title_tpl'=>'<span class="type-desc">{dtype}了一篇日志</span><span class="quot">“</span><a data-box="true" href="blog/showblogbox/{blogid}">{title}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
<p>{summary}</p>
</div>'),  
  array('typeid'=>25,'typename'=>'vote_syn','appid'=>7,'icon'=>'vote','title_tpl'=>'<span class="type-desc">发起了投票</span><span class="quot">“</span><a data-box="true" href="vote/showbox/{voteid}/{gid}">{subject}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
  <p class="intro">{summary}</p>
  <ol>
  {foreach votes as val}
  <li>
  <input type="{checkbox}" disabled="disabled" class="v" />
  <span class="v">{val}</span></li>
  {endforeach}
  </ol>
  </div>'),
  array('typeid'=>26,'typename'=>'photo_desc','appid'=>3,'icon'=>'photo','title_tpl'=>'<span class="type-desc">上传了{num}张照片</span>{desc}','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img data-box="true" src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" pid="{var[pid]}" aid="{var[aid]}"/>
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
    array('typeid'=>27,'typename'=>'photo_desc_group','appid'=>7,'icon'=>'photo','title_tpl'=>'<span class="type-desc">上传了{num}张照片</span>{desc}','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img data-box="true" src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" pid="{var[pid]}" aid="{var[aid]}"/>
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>28,'typename'=>'action_add','appid'=>14,'icon'=>'action','title_tpl'=>'<span class="type-desc">发起了一个活动</span><span class="quot">“</span><a data-box="true" href="action/showblogbox/{id}">{title}</a><span class="quot">”</span>','body_tpl'=>'<div class="detail">
<p>{summary}</p>
</div>'),
    array('typeid'=>29,'typename'=>'photo_desc_action','appid'=>15,'icon'=>'photo','title_tpl'=>'<span class="type-desc">上传了{num}张照片</span>{desc}','body_tpl'=>'<div class="multi">
  <ul>
  {foreach array as var}
  <li>
  <img data-box="true" src="{var[image]}" width="{var[width]}" height="{var[height]}" alt="{var[title]}" pid="{var[pid]}" aid="{var[aid]}"/>
  </li>
  {endforeach}
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>30,'typename'=>'action_photo_comment','appid'=>15,'icon'=>'photo','title_tpl'=>'<span class="type-desc">照片收到评论</span>','body_tpl'=>'<div class="multi">
  <ul>
  <li><img data-box="true" src="{image}" width="{width}" height="{height}" alt="{photo_name}" pid="{pid}" aid="{aid}"/></li>
  </ul>
  <div class="clear"></div>
  </div>'),
  array('typeid'=>31,'typename'=>'action_attend','appid'=>1,'icon'=>'user','title_tpl'=>'','body_tpl'=>''),
  array('typeid'=>32,'typename'=>'group_attend','appid'=>1,'icon'=>'user','title_tpl'=>'','body_tpl'=>''),
  array('typeid'=>33,'typename'=>'group_add','appid'=>1,'icon'=>'user','title_tpl'=>'','body_tpl'=>''),
 
);

