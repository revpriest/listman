<div class="row">
	<div class="messageForm">
		<h1><?php echo $list->getTitle(); ?></h1>
		<h3>Success</h3>
		<hr/>
		<table>
			<tr><th>Name:</th><td><?php echo $member->getName(); ?></td></tr>
			<tr><th>Email:</th><td><?php echo $member->getEmail(); ?></td></tr>
			<tr><th>Subscription: </th><td> <?php 
				switch($member->getState()){
					case -1: echo "blocked";break;
					case 0: echo "unconfirmed";break;
					case 1: echo "subscribed";break;
				}
			?></td></tr>
		</table>
		<div class="btns">
			<a class="btn" href="<?php echo $sub; ?>">Subscribe</a> 
			<a class="btn" href="<?php echo $unsub; ?>">Block</a> 
		</div>
	</div>
</div>


