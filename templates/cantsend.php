<div class="row">
	<div class="messageForm">
		<h1><?php echo $list==null?"Unknown List":$list->getTitle(); ?></h1>
		<h3>Error</h3>
    <p><?php echo $message; ?></p>
    <?php if($sub!=null): ?>
      <hr/>
      <div class="center">
        <a class="btn" href="<?php echo $sub; ?>">Subscribe</a> 
      </div>
    <?php endif; ?>
	</div>
</div>


