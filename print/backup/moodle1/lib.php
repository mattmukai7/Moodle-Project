<?php
defined('MOODLE_INTERNAL') || die();

class moodle1_mod_print_handler extends moodle1_resource_successor_handler {

    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data
     * Called by moodle1_mod_resource_handler::process_resource()
     */
    public function process_legacy_resource(array $data, array $raw = null) {

        // get the course module id and context id
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid, 'resource');
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // convert the legacy data onto the new print record
        $print                       = array();
        $print['id']                 = $data['id'];
        $print['name']               = $data['name'];
        $print['intro']              = $data['intro'];
        $print['introformat']        = $data['introformat'];
        $print['content']            = $data['alltext'];

        if ($data['type'] === 'html') {
            // legacy Resource of the type Web print
            $print['contentformat'] = FORMAT_HTML;

        } else {
            // legacy Resource of the type Plain text print
            $print['contentformat'] = (int)$data['reference'];

            if ($print['contentformat'] < 0 or $print['contentformat'] > 4) {
                $print['contentformat'] = FORMAT_MOODLE;
            }
        }

        $print['legacyfiles']        = RESOURCELIB_LEGACYFILES_ACTIVE;
        $print['legacyfileslast']    = null;
        $print['revision']           = 1;
        $print['timemodified']       = $data['timemodified'];

        // populate display and displayoptions fields
        $options = array('printheading' => 1, 'printintro' => 0);
        if ($data['popup']) {
            $print['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                list($name, $value) = explode('=', trim($rawoption), 2);
                if ($value > 0 and ($name == 'width' or $name == 'height')) {
                    $options['popup'.$name] = $value;
                    continue;
                }
            }
        } else {
            $print['display'] = RESOURCELIB_DISPLAY_OPEN;
        }
        $print['displayoptions'] = serialize($options);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_print');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $print['intro'] = moodle1_converter::migrate_referenced_files($print['intro'], $this->fileman);

        // convert course files embedded into the content
        $this->fileman->filearea = 'content';
        $this->fileman->itemid   = 0;
        $print['content'] = moodle1_converter::migrate_referenced_files($print['content'], $this->fileman);

        // write print.xml
        $this->open_xml_writer("activities/print_{$moduleid}/print.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'print', 'contextid' => $contextid));
        $this->write_xml('print', $print, array('/print/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml for migrated resource file.
        $this->open_xml_writer("activities/print_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
