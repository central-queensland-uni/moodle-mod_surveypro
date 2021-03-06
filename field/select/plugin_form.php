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
 * @package    mod_surveypro
 * @copyright  2013 onwards kordan <kordan@mclink.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/surveypro/forms/items/itembase_form.php');
require_once($CFG->dirroot.'/mod/surveypro/field/select/lib.php');

class mod_surveypro_pluginform extends mod_surveypro_itembaseform {

    /*
     * definition
     *
     * @param none
     * @return none
     */
    public function definition() {
        // ----------------------------------------
        // start with common section of the form
        parent::definition();

        // ----------------------------------------
        $mform = $this->_form;

        // ----------------------------------------
        // get _customdata
        $item = $this->_customdata->item;
        // $surveypro = $this->_customdata->surveypro;

        // ----------------------------------------
        // item: options
        // ----------------------------------------
        $fieldname = 'options';
        $mform->addElement('textarea', $fieldname, get_string($fieldname, 'surveyprofield_select'), array('wrap' => 'virtual', 'rows' => '10', 'cols' => '65'));
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_select');
        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
        $mform->setType($fieldname, PARAM_RAW); // PARAM_RAW and not PARAM_TEXT otherwise '<' is not accepted

        // ----------------------------------------
        // item: labelother
        // ----------------------------------------
        $fieldname = 'labelother';
        $mform->addElement('text', $fieldname, get_string($fieldname, 'surveyprofield_select'), array('maxlength' => '64', 'size' => '50'));
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_select');
        $mform->setType($fieldname, PARAM_TEXT);

        // ----------------------------------------
        // item: defaultoption
        // ----------------------------------------
        $fieldname = 'defaultoption';
        $elementgroup = array();
        $elementgroup[] = $mform->createElement('radio', 'defaultoption', '', get_string('customdefault', 'surveyprofield_select'), SURVEYPRO_CUSTOMDEFAULT);
        $elementgroup[] = $mform->createElement('radio', 'defaultoption', '', get_string('invitationdefault', 'surveypro'), SURVEYPRO_INVITATIONDEFAULT);
        $elementgroup[] = $mform->createElement('radio', 'defaultoption', '', get_string('noanswer', 'surveypro'), SURVEYPRO_NOANSWERDEFAULT);
        $mform->addGroup($elementgroup, $fieldname.'_group', get_string($fieldname, 'surveyprofield_select'), ' ', false);
        $mform->setDefault($fieldname, SURVEYPRO_INVITATIONDEFAULT);
        $mform->addHelpButton($fieldname.'_group', $fieldname, 'surveyprofield_select');

        // ----------------------------------------
        // item: defaultvalue
        // ----------------------------------------
        $fieldname = 'defaultvalue';
        $elementgroup = array();
        $mform->addElement('text', $fieldname, null);
        $mform->setType($fieldname, PARAM_RAW);
        $mform->disabledIf($fieldname, 'defaultoption', 'neq', SURVEYPRO_CUSTOMDEFAULT);

        // ----------------------------------------
        // item: downloadformat
        // ----------------------------------------
        $fieldname = 'downloadformat';
        $options = array(SURVEYPRO_ITEMSRETURNSVALUES => get_string('returnvalues', 'surveyprofield_select'),
                         SURVEYPRO_ITEMRETURNSLABELS => get_string('returnlabels', 'surveyprofield_select'),
                         SURVEYPRO_ITEMRETURNSPOSITION => get_string('returnposition', 'surveyprofield_select'));
        $mform->addElement('select', $fieldname, get_string($fieldname, 'surveyprofield_select'), $options);
        $mform->setDefault($fieldname, $item->item_get_friendlyformat());
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_radiobutton');
        $mform->setType($fieldname, PARAM_INT);

        $this->add_item_buttons();
    }

    /*
     * validation
     *
     * @param $data
     * @param $files
     * @return $errors
     */
    public function validation($data, $files) {
        // ----------------------------------------
        // $item = $this->_customdata->item;

        $errors = parent::validation($data, $files);

        // clean inputs
        $cleanoptions = surveypro_textarea_to_array($data['options']);
        $cleanlabelother = trim($data['labelother']);
        $cleandefaultvalue = isset($data['defaultvalue']) ? trim($data['defaultvalue']) : '';

        // build $value and $label arrays starting from $cleanoptions and $cleanlabelother
        $values = array();
        $labels = array();

        foreach ($cleanoptions as $option) {
            if (strpos($option, SURVEYPRO_VALUELABELSEPARATOR) === false) {
                $values[] = trim($option);
                $labels[] = trim($option);
            } else {
                $pair = explode(SURVEYPRO_VALUELABELSEPARATOR, $option);
                $values[] = $pair[0];
                $labels[] = $pair[1];
            }
        }
        if (!empty($cleanlabelother)) {
            if (strpos($cleanlabelother, SURVEYPRO_OTHERSEPARATOR) === false) {
                $values[] = $cleanlabelother;
                $labels[] = $cleanlabelother;
            } else {
                $pair = explode(SURVEYPRO_OTHERSEPARATOR, $cleanlabelother);
                $values[] = $pair[0];
                $labels[] = $pair[1];
            }
        }

        // if (default == noanswer but the item is mandatory) then => error
        if ( ($data['defaultoption'] == SURVEYPRO_NOANSWERDEFAULT) && isset($data['required']) ) {
            $a = get_string('noanswer', 'surveypro');
            $errors['defaultvalue_group'] = get_string('notalloweddefault', 'surveypro', $a);
        }

        if ($data['defaultoption'] == SURVEYPRO_CUSTOMDEFAULT) {
            if (empty($data['defaultvalue'])) {
                // -----------------------------
                // first check
                // user asks for SURVEYPRO_CUSTOMDEFAULT but doesn't provide it
                // -----------------------------
                $a = get_string('standarddefault', 'surveyprofield_select');
                $errors['defaultvalue'] = get_string('default_missing', 'surveyprofield_select', $a);
            } else {
                // -----------------------------
                // second check
                // each item of default has to be among options item OR has to be == to otherlabel value
                // -----------------------------
                if (!in_array($cleandefaultvalue, $labels)) {
                    $errors['defaultvalue'] = get_string('defaultvalue_err', 'surveyprofield_select', $cleandefaultvalue);
                }

                // -----------------------------
                // third check
                // each single option item has to be unique
                // -----------------------------
                $arrayunique = array_unique($cleanoptions);
                if (count($cleanoptions) != count($arrayunique)) {
                    $errors['options'] = get_string('options_err', 'surveypro', $default);
                }
            }
        }

        return $errors;
    }
}