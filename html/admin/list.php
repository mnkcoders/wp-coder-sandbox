<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
<div class="coder-sandbox wrap">
    <div class="container">
        <a class="button button-primary right" target="_self" href="<?php print $this->action_sandbox ?>"><?php print __('New Sandbox', 'coder_sandbox') ?></a>  
    </div>
    <?php $this->show_log() ?>
    <ul class="collection">
    <?php foreach ($this->list_boxes() as $box) : ?>
            <li class="item box <?php print $box->status ?>">
                <a class="title" href="<?php print $this->action_sandbox(array('id' => $box->id)) ?>" target="_self"><?php print $box->title ?></a>
                <div class="actions">
                    <a class="button right" href="<?php print $this->link_sandbox(array($box->name)) ?>" target="_blank"><?php print __('Open', 'coder_sandbox') ?></a>
                </div>
                <p><?php print $box->description ?></p>
                <div class="tags">
                    <span class="tag"><?php print $box->created ?></span>
                    <span class="tag"><?php print $box->endpoint ?></span>
                    <span class="tag"><?php print $box->tier ?></span>
                </div>
            </li>
    <?php endforeach; ?>
    </ul>
</div>