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
 * Renderer for outputting parts of a question belonging to the legacy
 * adaptive behaviour.
 *
 * @package    qbehaviour
 * @subpackage adaptive
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/behaviour/adaptive/renderer.php');

/**
 * Renderer for outputting parts of a question belonging to the legacy
 * adaptive behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_adaptiveweighted_renderer extends qbehaviour_adaptive_renderer 
{
    /**
     * Several behaviours need a submit button, so put the common code here.
     * The button is disabled if the question is displayed read-only.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    protected function submit_button(question_attempt $qa, question_display_options $options, $save_only = false) 
    {
	//if save_only is false, this is a real submit button
	if(!$save_only)
	{
		$attributes = 
		    array
		    (
			'type' => 'submit',
			'id' => $qa->get_behaviour_field_name('submit'),
			'name' => $qa->get_behaviour_field_name('submit'),
			'value' => get_string('gradenow', 'qbehaviour_adaptiveweighted'),
			'alt' => get_string('gradenow', 'qbehaviour_adaptiveweighted'),
			'class' => 'submit btn gradenow',
		    );
	} 
	//otherwise, it's just a button that saves the student work
	else
	{
		$attributes = 
		    array
		    (
			'type' => 'submit',
			'id' => $qa->get_behaviour_field_name('save'),
			'name' => $qa->get_behaviour_field_name('save'),
			'value' => get_string('savenow', 'qbehaviour_adaptiveweighted'),
			'alt' => get_string('savenow', 'qbehaviour_adaptiveweighted'),
			'class' => 'submit btn savenow',
		    );

	}

        //if the question is read-only, prevent the button from being clicked
        if ($options->readonly)
            $attributes['disabled'] = 'disabled';

        //generate a new submit button 
        $output = html_writer::empty_tag('input', $attributes);

        //if this question isn't read-only, initialize the submit button routine, which prevents multiple submissions
        if (!$options->readonly) 
            $this->page->requires->js_init_call('M.core_question_engine.init_submit_button', array($attributes['id'], $qa->get_slot()));
        
        return $output;
    }

    public function controls(question_attempt $qa, question_display_options $options) 
    {
        $output = $this->submit_button($qa, $options);
        $output .=  $this->submit_button($qa, $options, true);

	return $output;
    }

    /**
     * Create some information intended to be displayed in the left-hand bar, which summarizes grading
     * methods. (e.g. "No penalty if incorrect." or "0.30 penalty per guess".)
     */
    public function grade_method_details(question_attempt $qa, question_display_options $options) {

        // Get the definition for the active question.
        $question = $qa->get_question();

        // Since we're providing a percentage, we'll represent the value to two less decimal places
        // as to maintain the same precision.
        $penaltydp = max($options->markdp - 2, 0);

        // Get a formatted indication of the incorrect answer penalty.
        $penalty = format_float($question->penalty * 100, $penaltydp);

        // If the question has no penalty set, show a "no penalty if incorrect" message to the side;
        // otherwise, display the penalty information.
        if($question->penalty == 0) {
            return get_string('nopenalty', 'qbehaviour_adaptiveweighted');
        } else {
            return get_string('penaltyinfo', 'qbehaviour_adaptiveweighted', $penalty);
        }
    }

	/**
	* Display the information about the penalty calculations.
	* @param question_attempt $qa the question attempt.
	* @param object $mark contains information about the current mark.
	* @param question_display_options $options display options.
	*/
    protected function grading_details(qbehaviour_adaptive_mark_details $details, question_display_options $options) 
    {
		//if no penalties have been set, return an empty string
		if (!$details->totalpenalty)
			return '';
		
		$output = '';
		
		// Print details of grade adjustment due to penalties
		//if ($details->rawmark != $details->actualmark) {
            $output .= ' ' . get_string('gradingdetailsadjustment', 'qbehaviour_adaptive', $details->get_formatted_marks($options->markdp));
        //}

		// Print information about any new penalty, only relevant if the answer can be improved.
        if ($details->improvable)  {

			//calculate the maximum score the student can still achieve
			$maxpossible = $details->maxmark - ($details->maxmark * $details->totalpenalty);
			
			$lastpenalty = $details->maxmark * $details->currentpenalty;
			
			//and return that, instead of penalty information
			if(round($maxpossible, $options->markdp) > 0) {
				$output .= ' ' . get_string('gradingdetailsmaxpossible', 'qbehaviour_adaptiveweighted', array('lastpenalty' => format_float($lastpenalty, $options->markdp), 'maxpossible' => format_float(max($maxpossible, 0), $options->markdp), 'max' => format_float($details->maxmark, $options->markdp)));
			}
			else {
				$output .= ' ' . get_string('gradingdetailspenalty', 'qbehaviour_adaptiveweighted', array('lastpenalty' => format_float($lastpenalty, $options->markdp), 'max' => format_float($details->maxmark, $options->markdp)));
			}
		}
			
			//$output .= ' ' . get_string('gradingdetailspenalty', 'qbehaviour_adaptive', format_float($qa->get_last_behaviour_var('sumpenalty', 0), $options->markdp));
	
		return $output;
	}
}
