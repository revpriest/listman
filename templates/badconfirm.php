<div class="row">
	<div class="messageForm">
		<h1><?php echo $list==null?"Unknown List":$list->getTitle(); ?></h1>
		<h3>Error</h3>
    <p><?php echo $message; ?></p>
    <?php if($sub!=null): ?>
      <hr/>
      <p>Maybe try the subscribe form again?</p>
      <div class="btns">
        <a class="btn" href="<?php echo $sub; ?>">Subscribe</a> 
      </div>
    <?php endif; ?>
	</div>
</div>


