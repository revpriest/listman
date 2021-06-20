<?php echo $style; ?>
<div class="row">
	<div class="shareIcons">
		<p>Share with:</p>
		<ul class="shareIconsList">
			<li><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>" class="sbtn">Facebook</a></li>
			<li><a target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>" class="sbtn">Twitter</a></li>
			<li><a target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo $url; ?>" class="sbtn">Pinterest</a></li>
			<li><a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $url; ?>" class="sbtn">Linked In</a></li>
			<li><a target="_blank" href="mailto:?&subject=&cc=&bcc=&body=<?php echo $url; ?>" class="sbtn">Email</a></li>
		</ul>
	</div>
	<div class="messageText">
		<h1><?php print $message->getSubject(); ?></h1>
		<h2><b>From:</b> <?php print $list->getTitle(); ?></h2>
		<h2><b>To:</b> All Subscribers</h2>
		<h2><b>Date:</b> <?php print $message->getCreatedAt(); ?></h2>
		<div class="react">
			<ul>
				<?php foreach($react as $r): ?>
					<li><?php echo $r->getSymbol().$r->getCount(); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="messageBody">
			<?php print $body; ?>
			<?php print $buttons; ?>
		</div>
	</div>
	<div class="shareIcons">
		<p>Share with:</p>
		<ul class="shareIconsList">
			<li><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>" class="sbtn">Facebook</a></li>
			<li><a target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>" class="sbtn">Twitter</a></li>
			<li><a target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo $url; ?>" class="sbtn">Pinterest</a></li>
			<li><a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $url; ?>" class="sbtn">Linked In</a></li>
			<li><a target="_blank" href="mailto:?&subject=&cc=&bcc=&body=<?php echo $url; ?>" class="sbtn">Email</a></li>
		</ul>
	</div>
</div>
