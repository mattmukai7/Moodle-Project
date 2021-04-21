<?php
defined('MOODLE_INTERNAL') || die;

/**
 * Define all the backup steps that will be used by the backup_print_activity_task
 */

/**
 * Define the complete print structure for backup, with file and id annotations
 */
class backup_print_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $print = new backup_nested_element('print', array('id'), array(
            'name', 'intro', 'introformat', 'content', 'contentformat',
            'legacyfiles', 'legacyfileslast', 'display', 'displayoptions',
            'revision', 'timemodified'));

        // Build the tree
        // (love this)

        // Define sources
        $print->set_source_table('print', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Define file annotations
        $print->annotate_files('mod_print', 'intro', null); // This file areas haven't itemid
        $print->annotate_files('mod_print', 'content', null); // This file areas haven't itemid

        // Return the root element (print), wrapped into standard activity structure
        return $this->prepare_activity_structure($print);
    }
}
