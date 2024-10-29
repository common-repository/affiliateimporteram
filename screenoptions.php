<?php
add_action('load-amimporter_page_aidn-add', function () {
    $option = 'columns';

    $args = array(
        'label' => ''
    );

    add_screen_option($option, $args);
});

add_action('load-amimporter_page_aidn-stats', function () {
    $option = 'columns';

    $args = array(
        'label' => ''
    );

    add_screen_option($option, $args);
});
