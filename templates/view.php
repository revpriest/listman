<div class="messageText">
  <h2>From: <?php print $list->getTitle(); ?></h2>
  <h2>To: All Subscribers</h2>
  <h1>Subject: <?php print $message->getSubject(); ?></h1>
  <div class="messageBody">
    <?php print $message->getBody(); ?>
  </div>
</div>
