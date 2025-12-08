<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
<table class="wp-list-table widefat fixed striped table-view-excerpt roles">
    <thead>
        <tr>
            <th><?php print __('Name', 'coder_sandbox') ?></th>        
            <th><?php print __('Title', 'coder_sandbox') ?></th>        
            <th><?php print __('Endpoint', 'coder_sandbox') ?></th>        
            <th><?php print __('Tier', 'coder_sandbox') ?></th>        
            <th><?php print __('State', 'coder_sandbox') ?></th>        
            <th><?php print __('Created', 'coder_sandbox') ?></th>
        </tr>        
    </thead>
    <tbody>
        <?php foreach ($this->list_boxes() as $box) : ?>
            <tr>
                <td><a href="<?php
                    print $this->action_sandbox(array('context'=>'sandbox','id'=>$box->id)) ?>" target="_self"><?php
                    print $box->name ?></a>
                </td>
                <td><?php print $box->title ?></td>
                <td><?php print $box->endpoint ?></td>
                <td><?php print $box->tier ?></td>
                <td>
                    <?php if( $this->is_ready($box->name) ) : ?>
                    <a class="button-primary" href="<?php
                        print $this->get_url($box->name) ?>" target="_blank"><?php
                        print __('Open','coder_sandbox') ?></a>
                    <?php else : ?>
                    <a class="button" href="<?php
                        print $this->action_activate(array('box'=>$box->name)) ?>" target="_self" class="state <?php
                        print $box->state ?>"><?php
                        print __('Install','coder_sandbox')  ?></a>
                    <?php endif; ?>
                </td>
                <td><?php print $box->created ?></td>
            </tr>
        <?php endforeach; ?>        
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">
                <a class="button button-primary right" target="_self" href="<?php
                    print $this->action_sandbox ?>"><?php
                    print __('New Sandbox','coder_sandbox') ?></a>
            </td>
        </tr>
    </tfoot>
</table>

