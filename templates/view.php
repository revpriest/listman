<div class="row">

	<div class="shareIcons">
		<p>🌎 Share</p>
		<ul>
			<li><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>" class="btn">Facebook</a></li>
			<li><a target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>" class="btn">Twitter</a></li>
			<li><a target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo $url; ?>" class="btn">Pinterest</a></li>
			<li><a target="_blank" href="https://t.me/share/url?url=<?php echo $url; ?>" class="btn">Telegram</a></li>
			<li><a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $url; ?>" class="btn">LinkedIn</a></li>
			<li><a target="_blank" href="mailto:?&subject=&cc=&bcc=&body=<?php echo $url; ?>" class="btn">Email</a></li>
			<li><a id="clipcopy" class="btn">Clipboard</a></li>
		</ul>
	</div>


	<div class="messageText">
		<div class="messageBody">
			<?php print $body; ?>
		</div>
	</div>

  <div class="react">
    <ul>
      <?php foreach($react as $r): ?>
        <li><?php echo $r->getSymbol().$r->getCount(); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

</div>

