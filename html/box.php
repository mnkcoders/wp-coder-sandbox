<?php defined('ABSPATH') or die;?>
<?php var_dump($this) ?>
    <?php if( $this->is_new ) : ?>
    <h1 class="wp-heading-inline"><?php print __('New Sandbox','coder_sanbox'); ?></h1>
    <?php else : ?>
    <h1 class="wp-heading-inline"><?php print sprintf('%s - %s',get_admin_page_title() , $this->get_title); ?></h1>
    <?php endif; ?>

<div class="container wrap">
    <form name="sandbox" method="post" action="<?php print $this->get_form() ?>">
        <div class="widefat fixed ">
        <?php if( $this->is_new ): ?>
            <p><input type="text" name="name" value="<?php
                print $this->get_name
                ?>" placeholder="<?php
                print __('App name','coder_sandbox') ?>"/></p>
        <?php else: ?>
            <div class="container app">
            <p>
                <a class="button" target="_blank" href="<?php
                    print $this->get_url ?>"><?php
                    print $this->get_name ?></a>
                <i>[ <?php print $this->get_id ?> ]</i>
            </p>        
            <p><span class="date"><?php print $this->get_created ?></span></p>
            </div>
        <?php endif; ?>
            
            <p><input class="widefat" type="text" name="title" value="<?php
                print $this->get_title
                ?>" placeholder="<?php
                print __('App title','coder_sandbox') ?>"/></p>
            
            <p><input class="widefat" type="text" name="endpoint" value="<?php
                print $this->get_endpoint
                ?>" placeholder="<?php
                print __('Endpoint','coder_sandbox') ?>"/></p>
            
            <p><select name="tier" size="1" class="widefat">
                <option><?php print __('Select a tier','coder_sandbox') ?></option>
                <?php foreach($this->list_tiers() as $tier => $label ) : ?>
                    <option <?php
                        print $tier === $this->get_tier ? 'selected' : ''?>value="<?php
                        print $tier ?>"><?php
                        print $label ?></option>
                <?php endforeach; ?>
            </select></p>

            <fieldset>
                <legend><?php print __('Options','coder_sandbox') ?></legend>
            <ul class="metadata">
                <?php foreach( $this->list_metadata as $key => $val ) : ?>
                    <li><label><?php
                        print $key ?></label><input type="text" name="<?php
                        print $key ?>" value="<?php
                        print $val ?>"></li>
                <?php endforeach; ?>
            </ul>
            </fieldset>
        
        <p><button class="button-primary right" type="submit" name="action" value="save"><?php
            print __('Save','coder_sandbox');
        ?></button></p>
        </div>
    </form>
</div>


