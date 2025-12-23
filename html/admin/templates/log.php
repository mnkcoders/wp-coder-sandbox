<?php defined('ABSPATH') ||die ?>
<div class="messages">
<?php foreach( $this->list_messages() as $message ):  ?>
    <div class="is-dismissible message <?php
        print $message['type'] ?? 'info' ?>"><?php
        print $message['content'] ?? '' ?></div>
<?php endforeach; ?>
</div>

