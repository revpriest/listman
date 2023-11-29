<div class="row">
<div class="messageForm" style="background:black;color:white;padding:1em">
<h1><?php echo $list->getTitle(); ?></h1>
<p><?php echo $list->getDesc(); ?></p>
<form method="post" action="<?php echo $url; ?>">
<table>
  <tr>
    <td>Name:</td>
    <td><input placeholder="name" name="name"></td>
  </tr>
  <tr>
    <td>Email:</td>
    <td><input placeholder="email" name="email"></td>
  </tr>
  <tr>
    <td colspan="2">To show you're not a robot, what is double the number three?</td>
  </tr>
  <tr>
    <td colspan="2"><input name="hello"></td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="hidden" name="redir" value="<?php $redir ?>">
      <button name="act" value="sub">Subscribe</button>
      <button name="act" value="unsub">Block</button>
    </td>
  </tr>
</table>
</form>
</div>
</div>
