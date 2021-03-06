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

global $CFG;

require_once($CFG->libdir.'/form/editor.php');

class mod_surveypro_mform_editor extends MoodleQuickForm_editor {

    /**
     * All types must have this constructor implemented.
     */
    public function mod_surveypro_mform_editor($elementName=null, $elementLabel=null, $attributes=null, $options=null) {
        parent::MoodleQuickForm_editor($elementName, $elementLabel, $attributes, $options);
    }

    /**
     * Returns type of editor element
     *
     * @return string
     */
    public function getElementTemplateType() {
        return 'default';
    }

    /**
     * Returns HTML for editor form element.
     *
     * @return string
     */
    public function toHtml() {
        // the core code is ONLY MISSING the class in the first <div>
        // I add it with a simple relace
        $output = parent::toHtml(); // core code

        // My intervention only replaces <div> with <div class="indent-x"> AT THE BEGINNING of $output
        // I force the beginning (^) to avoid to replace different <div> eventually found into $output
        // I use the output of parent::toHtml() to get advantages of future updates to core mform class
        // I search for simple <div> without attributes so that if moodle HQ will fix this issue in the core code,
        // my intervention will result in nothing without adding useless or dangerous modifications
        $tabs = $this->_getTabs();
        $pattern = '~^'.$tabs.'<div>~';
        $class = empty($this->_attributes['class']) ? 'indent-0' : $this->_attributes['class'];
        $replacement = $tabs.'<div class="'.$class.'">';
        $output = preg_replace($pattern, $replacement, $output);

        return $output;
    }

    /**
     * What to display when element is frozen.
     *
     * @return empty string
     */
    public function getFrozenHtml() {
        $class = empty($this->_attributes['class']) ? 'indent-0' : $this->_attributes['class'];
        $output = $this->_getTabs().'<div class="'.$class.'">';

        $value = $this->_values['text'];
        $output .= strlen($value) ? $value : '&nbsp;';

        $output .= '</div>';
        $output .= $this->_getPersistantData();

        return $output;
    }
}
