<html>
<head>
	<script language="javascript">
		function onJsAlerta(){
			document.getElementById("result").innerHTML ="你已报名参加活动";
		}

		function clickJoinAction() {
			//apply(1);
			document.getElementById("join").style.display = "none";
		}

		function clickNotJoinAction() {
			//apply(2);
			document.getElementById("notJoin").style.display = "none";
		}

		function clickInterestAction() {
			//apply(3);
			document.getElementById("interest").style.display = "none";
		}

	</script>
</head>


<style>
#status{
	margin-top: 6px;
	width:120px;
	float:left;
}

#finish{
	margin-top: 6px;
	width:120px;
	float:left;
}

.left-float{
	float:left;
	width:60px;
	margin-right: 3px;
}
</style>


<body>
<table>
	<tbody>
	<tr>
		<th><label for="">活动标题：</label></th>
		<td><span><?php echo html::specialchars($activity['title']); ?></span></td>
	</tr>
	<tr>
		<th><label for="">开始时间：</label></th>
		<td><span><?php echo date('Y-m-d H:i', $activity['start_time']); ?></span></td>
	</tr>
	<tr>
		<th><label for="">结束时间：</label></th>
		<td><span><?php echo date('Y-m-d H:i', $activity['end_time']); ?></span></td>
	</tr>
	<tr>
		<th><label for="">活动地点：</label></th>
		<td><span><?php echo html::specialchars($activity['spot']); ?></span></td>
	</tr>
	<tr>
		<th><label for="">活动类型：</label></th>
		<td><span><?php echo $type; ?></span></td>
	</tr>
	<tr>
		<th><label for="">发&nbsp;&nbsp;起&nbsp;&nbsp;人：</label></th>
		<td>
			<span class="connect">
				<?php echo $activity['user']['name']; ?>&nbsp;&nbsp;<label for=""><?php echo $activity['user']['mobile']; ?></label>
			</span>
		</td>
	</tr>
	<?php foreach($activity['organizer'] as $key=>$value): ?>
	<tr>
		<th>
			<?php if(!$key): ?>
				<label for="">组&nbsp;&nbsp;织&nbsp;&nbsp;者：</label>
			<?php endif; ?>
		</th>
			<td>
				<span class="connect">
					<?php echo $value['name']; ?>&nbsp;&nbsp;<label for=""><?php echo $value['mobile']; ?></label>
				</span>
			</td>
	</tr>
	<?php endforeach; ?>
	 <tr>
		<th><label for="">活动说明：</label></th>
		<td><p><?php echo nl2br(html::specialchars($activity['content']));?></p></td>

	</tr>
</tbody>
</table>
<br/>
<div id="test" style="display:none;">
<input type="button" onClick="window.demo.clickTestAction()" value="测试"/>
</div>

<div>

		<?php if($webRequest == 1 || ($webRequest > 1 && $this->user_id == $activity['creator_id'] || $activity['end_time'] < time())): ?>
			<?php if($apply_type == Kohana::config('activity.apply_type.join')): ?>
			<span id="status">你已报名参加&nbsp;&nbsp;</span>
			<?php elseif($apply_type == Kohana::config('activity.apply_type.not_join')): ?>
			<span id="status">你已报名不参加&nbsp;&nbsp;</span>
			<?php elseif($apply_type == Kohana::config('activity.apply_type.interest')): ?>
			<span id="status">你已报名感兴趣&nbsp;&nbsp;</span>
			<?php endif;?>
			<?php if($activity['end_time'] < time()): ?>
				<span id="finish">&nbsp;&nbsp;活动已结束</span>
			<?php endif;?>
		<?php elseif($webRequest > 1): ?>
			
			<?php if($apply_type == Kohana::config('activity.apply_type.join')): ?>
				<span id="status" class="left-float">你已报名参加</span>&nbsp;&nbsp;
				<span id="join" style="display:none;">
				<input type="submit" onClick="clickJoinAction()" value="参加"/>
				</span>&nbsp;&nbsp;
				<span id="notJoin" class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="2" />

					<input type="submit" value="不参加"/>
				</form>
				</span>&nbsp;&nbsp;
				<span id="interest"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="3" />

					<input type="submit" value="感兴趣"/>

				</form>
				</span>
			<?php elseif($apply_type == Kohana::config('activity.apply_type.not_join')): ?>
				<span id="status"  class="left-float">你已报名不参加</span>&nbsp;&nbsp;
				<span id="join"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;width:50px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="1" />

					<input type="submit" value="参　加"/>

				</form>
				</span>&nbsp;&nbsp;
				<span id="interest"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="3" />
					<input type="submit" value="感兴趣"/>
				</form>
				</span>
			<?php elseif($apply_type == Kohana::config('activity.apply_type.interest')): ?>
				<span id="status"  class="left-float">你已报名感兴趣</span>&nbsp;&nbsp;
				<span id="join"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="1" />

					<input type="submit" value="参　加"/>
				</form>
				</span>&nbsp;&nbsp;
				<span id="notJoin"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="2" />

					<input type="submit" value="不参加"/>
				</form>
				</span>
			<?php else: ?>
				<span id="join"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="1" />

					<input type="submit" value="参　加"/>
				</form>
				</span>&nbsp;&nbsp;
				<span id="notJoin"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="2" />

					<input type="submit"  value="不参加"/>
				</form>
				</span>&nbsp;&nbsp;
				<span id="interest"  class="left-float">
				<form method="post" action="apiserver.php" style="margin-bottom:0px;">
					<input type="hidden" name="web" value="2" />
					<input type="hidden" name="type" value="POST" />
					<input type="hidden" name="class" value="701" />
					<input type="hidden" name="method" value="2" />
					<input type="hidden" name="id" value="<?php echo $activity['aid']; ?>" />
					<input type="hidden" id="applyType" name="applyType" value="3" />

					<input type="submit" value="感兴趣"/>
				</form>
				</span>
			<?php endif;?>
		<?php endif; ?>
</div>
<div id="result">
</div>
</body>
</html>
