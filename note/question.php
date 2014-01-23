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
 * This file defines question and grading strategy classes for music theory
 * question subtypes related to key signatures.
 *
 * @package    qtype
 * @subpackage musictheory
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * The music theory note writing question subtype.
 *
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musictheory_note_write extends qtype_musictheory_question implements qtype_musictheory_subtype {

    public function get_supported_grading_strategies() {
        return array(
            'qtype_musictheory_strategy_note_allornothing'
        );
    }

    public function get_expected_data() {
        return array('answer' => PARAM_CLEANHTML);
    }

    public function grade_response(array $response) {
        $correctresponse = $this->get_correct_response();
        $params = array();
        $params['considerregister'] = $this->musictheory_considerregister;

        return $this->gradingstrategy->grade($response, $correctresponse, $params);
    }

    public function get_correct_response() {
        $ltr = $this->musictheory_givennoteletter;
        $acc = $this->musictheory_givennoteaccidental;
        if ($this->musictheory_considerregister) {
            $reg = $this->musictheory_givennoteregister;
        } else {
            $reg = 4;
        }
        return array('answer' => $ltr . $acc . $reg);
    }

    public function is_complete_response(array $response) {
        if (!isset($response['answer'])) {
            return false;
        }
        if ($this->musictheory_considerregister) {
            $regex = '/^([A-G](n|\#|b|x|bb)[1-6]){1}$/';
        } else {
            $regex = '/^([A-G](n|\#|b|x|bb)[1-6]?){1}$/';
        }
        return preg_match($regex, $response['answer']);
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                        $prevresponse, $newresponse, 'answer');
    }

    public function summarise_response(array $response) {
        if (!array_key_exists('answer', $response)) {
            return null;
        } else {
            $ans = str_replace(' ', '', $response['answer']);
            $ans = ($this->musictheory_considerregister) ? $ans : substr($ans, 0, strlen($ans) - 1);
            return $ans;
        }
    }

    public function get_validation_error(array $response) {
        if (empty($response['answer'])) {
            return get_string('validationerror_empty', 'qtype_musictheory');
        } else if (preg_match('/\s/', $response['answer'])) {
            return get_string('validationerror_whitespace', 'qtype_musictheory');
        }
        global $OUTPUT;
        $help = $OUTPUT->help_icon('note_write_questionastext', 'qtype_musictheory', true);
        return get_string('validationerror_invalidsyntax', 'qtype_musictheory') . $help;
    }

    public function get_question_text() {
        $qtext = get_string('questiontext_note_write', 'qtype_musictheory');
        switch ($this->musictheory_givennoteaccidental) {
            case 'n':
                $acc = '';
                break;
            case 'b':
            case 'x':
            case 'bb':
                $acc = get_string('acc_' . $this->musictheory_givennoteaccidental, 'qtype_musictheory');
                break;
            case '#':
                $acc = get_string('acc_sharp', 'qtype_musictheory');
                break;
        }
        $note = get_string('note' . $this->musictheory_givennoteletter, 'qtype_musictheory');
        if ($this->musictheory_considerregister) {
            $note .= $acc . $this->musictheory_givennoteregister;
        } else {
            $note .= $acc;
        }

        return $qtext . ': <b>' . $note . '</b>';
    }

}

/**
 * The music theory random note writing question subtype.
 *
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musictheory_note_write_random extends qtype_musictheory_note_write {

    public function start_attempt(question_attempt_step $step, $variant) {
        $this->musictheory_clef = qtype_musictheory_randomiser::get_random_field($this->musictheory_clef_random);
        $this->musictheory_givennoteletter = qtype_musictheory_randomiser::get_random_letter_name();
        $this->musictheory_givennoteaccidental = qtype_musictheory_randomiser::get_random_accidental();
        $this->musictheory_givennoteregister =
                qtype_musictheory_randomiser::get_random_register(
                        $this->musictheory_clef, $this->musictheory_givennoteletter);
        $this->musictheory_optionsxml = $this->qtype->get_options_xml($this, 'note-write');
        $this->questiontext = $this->get_question_text();
        $step->set_qt_var('_var_clef', $this->musictheory_clef);
        $step->set_qt_var('_var_considerregister', $this->musictheory_considerregister);
        $step->set_qt_var('_var_givennoteletter', $this->musictheory_givennoteletter);
        $step->set_qt_var('_var_givennoteaccidental', $this->musictheory_givennoteaccidental);
        $step->set_qt_var('_var_givennoteregister', $this->musictheory_givennoteregister);
        $step->set_qt_var('_var_optionsxml', $this->musictheory_optionsxml);
        $step->set_qt_var('_var_questiontext', $this->questiontext);
        parent::start_attempt($step, $variant);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        $this->musictheory_clef = $step->get_qt_var('_var_clef');
        $this->musictheory_considerregister = $step->get_qt_var('_var_considerregister');
        $this->musictheory_givennoteletter = $step->get_qt_var('_var_givennoteletter');
        $this->musictheory_givennoteaccidental = $step->get_qt_var('_var_givennoteaccidental');
        $this->musictheory_givennoteregister = $step->get_qt_var('_var_givennoteregister');
        $this->musictheory_optionsxml = $step->get_qt_var('_var_optionsxml');
        $this->questiontext = $step->get_qt_var('_var_questiontext');
        parent::apply_attempt_state($step);
    }

}

/*
 * The music theory note identification question subtype.
 *
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class qtype_musictheory_note_identify extends qtype_musictheory_question implements qtype_musictheory_subtype {

    public function get_supported_grading_strategies() {
        return array(
            'qtype_musictheory_strategy_all_or_nothing'
        );
    }

    public function get_expected_data() {
        if ($this->musictheory_considerregister) {
            return array(
                'musictheory_answer_ltr' => PARAM_TEXT,
                'musictheory_answer_acc' => PARAM_TEXT,
                'musictheory_answer_reg' => PARAM_TEXT
            );
        } else {
            return array(
                'musictheory_answer_ltr' => PARAM_TEXT,
                'musictheory_answer_acc' => PARAM_TEXT
            );
        }
    }

    public function grade_response(array $response) {

        $correctresponse = $this->get_correct_response();
        return $this->gradingstrategy->grade($response, $correctresponse);
    }

    public function get_correct_response() {
        if ($this->musictheory_considerregister) {
            return array(
                'musictheory_answer_ltr' => $this->musictheory_givennoteletter,
                'musictheory_answer_acc' => $this->musictheory_givennoteaccidental,
                'musictheory_answer_reg' => $this->musictheory_givennoteregister
            );
        } else {
            return array(
                'musictheory_answer_ltr' => $this->musictheory_givennoteletter,
                'musictheory_answer_acc' => $this->musictheory_givennoteaccidental
            );
        }
    }

    public function is_complete_response(array $response) {
        if (!isset($response['musictheory_answer_ltr']) ||
                !isset($response['musictheory_answer_acc'])) {
            return false;
        }

        if ($this->musictheory_considerregister) {
            if (!isset($response['musictheory_answer_reg'])) {
                return false;
            }
        }

        if ($this->musictheory_considerregister) {
            return (!empty($response['musictheory_answer_ltr']) &&
                    !empty($response['musictheory_answer_acc']) &&
                    !empty($response['musictheory_answer_reg']));
        } else {
            return (!empty($response['musictheory_answer_ltr']) &&
                    !empty($response['musictheory_answer_acc']));
        }
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        $sameltr = question_utils::arrays_same_at_key_missing_is_blank(
                        $prevresponse, $newresponse, 'musictheory_answer_ltr');
        $sameacc = question_utils::arrays_same_at_key_missing_is_blank(
                        $prevresponse, $newresponse, 'musictheory_answer_acc');
        if ($this->musictheory_considerregister) {
            $samereg = question_utils::arrays_same_at_key_missing_is_blank(
                            $prevresponse, $newresponse, 'musictheory_answer_reg');
            return ($sameltr && $sameacc && $samereg);
        } else {
            return ($sameltr && $sameacc);
        }
    }

    public function summarise_response(array $response) {
        if (!isset($response['musictheory_answer_ltr']) ||
                !isset($response['musictheory_answer_acc']) ||
                empty($response['musictheory_answer_ltr']) ||
                empty($response['musictheory_answer_acc'])) {
            return '';
        }

        if ($this->musictheory_considerregister) {
            if (!isset($response['musictheory_answer_reg']) ||
                    empty($response['musictheory_answer_reg'])) {
                return '';
            }
        }

        $note = get_string('note' . $response['musictheory_answer_ltr'], 'qtype_musictheory');
        $acckey = 'acc_' . str_replace('#', 'sharp', $response['musictheory_answer_acc']);
        $acc = get_string($acckey, 'qtype_musictheory');
        if ($this->musictheory_considerregister) {
            return $note . $acc . $response['musictheory_answer_reg'];
        } else {
            return $note . $acc;
        }
    }

    public function get_validation_error(array $response) {
        if ($this->musictheory_considerregister) {
            return get_string('validationerror_note_identify', 'qtype_musictheory');
        }
        else {
            return get_string('validationerror_note_identify_no_reg', 'qtype_musictheory');
        }
    }

    public function get_question_text() {
        $qtext = get_string('questiontext_note_identify', 'qtype_musictheory');
        return $qtext . ':';
    }

}

/**
 * The music theory random note identificastion question subtype.
 *
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musictheory_note_identify_random extends qtype_musictheory_note_identify {

    public function start_attempt(question_attempt_step $step, $variant) {
        $this->musictheory_clef = qtype_musictheory_randomiser::get_random_field($this->musictheory_clef_random);
        $this->musictheory_givennoteletter = qtype_musictheory_randomiser::get_random_letter_name();
        $this->musictheory_givennoteaccidental = qtype_musictheory_randomiser::get_random_accidental();
        $this->musictheory_givennoteregister =
                qtype_musictheory_randomiser::get_random_register(
                        $this->musictheory_clef, $this->musictheory_givennoteletter);
        $this->musictheory_optionsxml = $this->qtype->get_options_xml($this, 'note-identify');
        $this->questiontext = $this->get_question_text();
        $step->set_qt_var('_var_clef', $this->musictheory_clef);
        $step->set_qt_var('_var_considerregister', $this->musictheory_considerregister);
        $step->set_qt_var('_var_givennoteletter', $this->musictheory_givennoteletter);
        $step->set_qt_var('_var_givennoteaccidental', $this->musictheory_givennoteaccidental);
        $step->set_qt_var('_var_givennoteregister', $this->musictheory_givennoteregister);
        $step->set_qt_var('_var_optionsxml', $this->musictheory_optionsxml);
        $step->set_qt_var('_var_questiontext', $this->questiontext);
        parent::start_attempt($step, $variant);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        $this->musictheory_clef = $step->get_qt_var('_var_clef');
        $this->musictheory_considerregister = $step->get_qt_var('_var_considerregister');
        $this->musictheory_givennoteletter = $step->get_qt_var('_var_givennoteletter');
        $this->musictheory_givennoteaccidental = $step->get_qt_var('_var_givennoteaccidental');
        $this->musictheory_givennoteregister = $step->get_qt_var('_var_givennoteregister');
        $this->musictheory_optionsxml = $step->get_qt_var('_var_optionsxml');
        $this->questiontext = $step->get_qt_var('_var_questiontext');
        parent::apply_attempt_state($step);
    }

}

/**
 * A grading strategy that applies the all-or-nothing approach for a note
 * question, consider the register as appropriate.
 *
 * @copyright  2014 Eric Brisson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musictheory_strategy_note_allornothing implements qtype_musictheory_grading_strategy {

    public function grade($response, $correctresponse, $params = null) {

        $fraction = 1;
        foreach ($response as $key => $answer) {
            if ($params['considerregister']) {
                $ans = $answer;
                $resp = $correctresponse[$key];
            } else {
                $ans = substr($answer, 0, 2);
                $resp = substr($correctresponse[$key], 0, 2);
            }
            if ($ans !== $resp) {
                $fraction = 0;
            }
        }
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

}