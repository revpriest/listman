<div class="messageText">
	<?php echo $style; ?>
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
