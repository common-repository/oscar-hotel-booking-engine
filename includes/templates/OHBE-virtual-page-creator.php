<?php

/**
 * Redforts Hotel Booking Engine settings class
 * Create plugin's pages
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */
class OHBE_Virtual_Page_Creator {

    /**
     * Set up needed actions/filters for the pages to initialize.
     *
     * @return void
     */
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_filter('query_vars', array($this, 'queryVars'));
        add_action('template_include', array($this, 'getTemplate'));
    }

    /**
     * Activate the pages.
     *
     * @return void
     */
    public function activate() {
        set_transient('ohbe_flush', 1, 60);
    }

    /**
     * Set the title for the Booking Engine pages.
     *
     * @return array Title.
     */
    public function assignTitle() {
        $title_parts['title'] = __("Booking engine", 'ohbe') . ' ';
        return $title_parts;
    }

    /**
     * Return theme template if exists or plugin template by default.
     *
     * @return string Page template.
     */
    public function getTemplate($template) {
        $this->setTemplate(OHBE_BOOKING_PAGE, $template);

        return $template;
    }

    /**
     * Add template names to a list.
     *
     * @return array List of template names.
     */
    public function queryVars($vars) {
        $vars[] = OHBE_BOOKING_PAGE;

        return $vars;
    }

    /**
     * Set theme template.
     *
     * @return void
     */
    private function setTemplate($name, &$template) {
        if (isset($_GET['ohbe']) && $_GET['ohbe'] == $name) {
            $theme_template = get_template_directory()
                . '/'
                . $name
                . '.php';
            file_exists($theme_template)
                ? $template = $theme_template
                : $template = OHBE_PATH . "includes/templates/pages/{$name}.php";
            add_filter('document_title_parts', array($this, 'assignTitle'));
            add_filter('wp_title', array($this, 'setWpTitle'), 9, 2);
            add_filter('wpseo_title', array($this, 'setWpTitle'), 9, 2);
        }
    }

    /**
     * Assign title.
     *
     * @return array Title.
     */
    public function setWpTitle() {
        $parts = $this->assignTitle();
        return $parts['title'];
    }
}

new OHBE_Virtual_Page_Creator;
