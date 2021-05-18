<div style="background:white;text:black;border:1px solid blue;border-radius:1em">
<h1>Success</h1>
<style>
  td{
   border: 1px solid black;
    padding: 0.3em 1em;
  }
  table{
    margin: auto;
    padding: 0.3em 1em;
  }
</style>
<table>
  <tr><td>Name:</td><td><?php echo $member->getName(); ?></td></tr>
  <tr><td>Email:</td><td><?php echo $member->getEmail(); ?></td></tr>
  <tr><td>Subscription:</td><td><?php 
    switch($member->getState()){
      case -1: echo "blocked";break;
      case 0: echo "unconfirmed";break;
      case 1: echo "subscribed";break;
    }
  ?></td></tr>
</table>
</div>


