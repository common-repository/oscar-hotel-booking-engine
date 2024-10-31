<?php

class OHBE_Search_Widget extends WP_Widget {

    /**
     * Add hooks to initialize the class.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(
            'ohbe_search_widget',
            __("Redforts Hotel: Create booking", 'ohbe'),
            array('description' =>
                __("Booking engine widget", 'ohbe')
            )
        );
    }

    /**
     * Add hook to display the widget form.
     *
     * @return void
     */
    public function form($instance) {
        // Title input
        printf(
            '<p><label for=%1$s>%2$s</label>'
            . '<input class=widefat id=%1$s name=%3$s value="%4$s"></p>',
            $this->get_field_id('title'),
            __("Title", 'ohbe'),
            $this->get_field_name('title'),
            esc_attr(isset($instance['title']) ? $instance['title'] : ''
            )
        );
        // Show promocode checkbox
        printf(
            '<p><input type=checkbox id=%1$s class=checkbox '
            . 'name="%2$s" %3$s><label for=%1$s>%4$s</label>',
            $this->get_field_id('ispr'),
            $this->get_field_name('ispr'),
            isset($instance['ispr']) && isset($instance['ispr']) == 'on'
                ? 'checked'
                : '',
            __("Show promo code", 'ohbe')
        );
        // Account
        printf(
            '<p><label for=%1$s>%2$s</label>'
            . '<input class=widefat id=%1$s name=%3$s value="%4$s"></p>',
            $this->get_field_id('account'),
            __("Account", 'ohbe'),
            $this->get_field_name('account'),
            esc_attr(isset($instance['account']) ? $instance['account'] : ''
            )
        );
        // Show account selector
        printf(
            '<p><input type=checkbox id=%1$s class=checkbox '
            . 'name="%2$s" %3$s><label for=%1$s>%4$s</label>',
            $this->get_field_id('accounts_selector'),
            $this->get_field_name('accounts_selector'),
            isset($instance['accounts_selector'])
                && isset($instance['accounts_selector']) == 'on' ? 'checked' : '',
            __("Account selector", 'ohbe')
        );
    }

    /**
     * Add hook to update the widget.
     *
     * @return array Instance properties.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title'])
            ? strip_tags($new_instance['title'])
            : '';
        $instance['ispr'] = $new_instance['ispr'];
        $instance['account'] = !empty($new_instance['account'])
            ? strip_tags($new_instance['account'])
            : '';
        $instance['accounts_selector'] = $new_instance['accounts_selector'];
        return $instance;
    }

    /**
     * Add hook to display the widget.
     *
     * @return void
     */
    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        ohbeSearchFormOutput($instance, true);
        echo $args['after_widget'];
    }
}

/**
 * Add hook to register the widget.
 *
 * @return void
 */
function loadWidget() {
    register_widget('ohbe_search_widget');
}

add_action('widgets_init', 'loadWidget');
