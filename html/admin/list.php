<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
<?php $this->show_log() ?>
<div class="coder-sandbox">
    <ul class="collection container">
        <?php foreach ($this->list_boxes() as $box) : ?>
            <li class="item box <?php print $box->status ?>">
                <a href="<?php print $this->action_sandbox(array('id' => $box->id)) ?>" target="_self"><?php print $box->title ?></a>

                <a class="button right" href="<?php print $this->link_sandbox(array($box->name)) ?>" target="_blank"><?php print __('Open', 'coder_sandbox') ?></a>
                <p class="tags">
                    <span class="tag"><?php print $box->created ?></span>
                    <span class="tag"><?php print $box->endpoint ?></span>
                    <span class="tag"><?php print $box->tier ?></span>
                </p>
            </li>
<?php endforeach; ?>
        <li class="item create">
            <a class="button button-primary right" target="_self" href="<?php print $this->action_sandbox ?>"><?php print __('New Sandbox', 'coder_sandbox') ?></a>  
        </li>
    </ul>
</div>