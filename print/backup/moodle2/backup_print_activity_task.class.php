<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/print/backup/moodle2/backup_print_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the print instance
 */
class backup_print_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the print.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_print_activity_structure_step('print_structure', 'print.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of prints
        $search="/(".$base."\/mod\/print\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@printINDEX*$2@$', $content);

        // Link to print view by moduleid
        $search="/(".$base."\/mod\/print\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@printVIEWBYID*$2@$', $content);

        return $content;
    }
}
