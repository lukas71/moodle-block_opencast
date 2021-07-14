<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Create series form.
 *
 * @package    block_opencast
 * @copyright  2018 Tamara Gunkel
 * @author     Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_opencast\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Series form.
 *
 * @package    block_opencast
 * @copyright  2018 Tamara Gunkel
 * @author     Tamara Gunkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class series_form extends \moodleform
{

    /**
     * Form definition.
     */
    public function definition()
    {
        global $USER;
        $mform = $this->_form;

        $ocinstanceid = $this->_customdata['ocinstanceid'];

        $apibridge = apibridge::get_instance($ocinstanceid);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'seriesid', $this->_customdata['seriesid']);
        $mform->setType('seriesid', PARAM_ALPHANUMEXT);


        $settitle = true;
        foreach ($this->_customdata['metadata_catalog'] as $field) {
            $param = array();
            $attributes = array();
            if ($field->name == 'title') {
                if ($field->required) {
                    $settitle = false;
                } else {
                    continue;
                }
            }

            if ($field->param_json) {
                $param = $field->datatype == 'static' ? $field->param_json : (array)json_decode($field->param_json);
            }
            if ($field->datatype == 'autocomplete') {
                $attributes = [
                    'multiple' => true,
                    'placeholder' => get_string('metadata_autocomplete_placeholder', 'block_opencast',
                        $this->try_get_string($field->name, 'block_opencast')),
                    'showsuggestions' => true, // If true, admin is able to add suggestion via admin page. Otherwise no suggestions!
                    'noselectionstring' => get_string('metadata_autocomplete_noselectionstring', 'block_opencast',
                        $this->try_get_string($field->name, 'block_opencast')),
                    'tags' => true
                ];
            }

            $mform->addElement($field->datatype, $field->name, $this->try_get_string($field->name, 'block_opencast'),
                $param, $attributes);

            if ($field->name == 'title') {
                if (!empty($this->_customdata['seriesid'])) {
                    $ocseries = $apibridge->get_series_by_identifier($this->_customdata['seriesid']);
                    $mform->setDefault('title', $ocseries->title, $USER->id);
                } else {
                    $mform->setDefault('title', $apibridge->get_default_seriestitle($this->_customdata['courseid'], $USER->id));
                }
            }

            if ($field->datatype == 'text') {
                $mform->setType($field->name, PARAM_TEXT);
            }

            if ($field->required) {
                if ($field->datatype == 'autocomplete') {
                    $mform->addRule($field->name, get_string('required'), 'required', null, 'client');
                } else {
                    $mform->addRule($field->name, get_string('required'), 'required');
                }
            }
        }

        if ($settitle) {
            $mform->addElement('text', 'title', get_string('title', 'block_opencast'));
            $mform->addRule('title', get_string('required'), 'required');
            $mform->setType('title', PARAM_TEXT);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Tries to get the string for identifier and component.
     * As a fallback it outputs the identifier itself with the first letter being uppercase.
     * @param string $identifier The key identifier for the localized string
     * @param string $component The module where the key identifier is stored,
     *      usually expressed as the filename in the language pack without the
     *      .php on the end but can also be written as mod/forum or grade/export/xls.
     *      If none is specified then moodle.php is used.
     * @param string|object|array $a An object, string or number that can be used
     *      within translation strings
     * @return string
     * @throws \coding_exception
     */
    protected function try_get_string($identifier, $component = '', $a = null)
    {
        if (!get_string_manager()->string_exists($identifier, $component)) {
            return ucfirst($identifier);
        } else {
            return get_string($identifier, $component, $a);
        }
    }
}
