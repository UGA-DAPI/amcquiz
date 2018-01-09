<?php

// module
$string['modulename'] = 'AMC Quiz';

// plugin global settings
$string['settings_scoring_rules'] = "Groups of rules are separed by a line of at least 3 dashes.
<p>
The first line of each block will be the title displayed in the dropdown list.
Eventually, lines of description follow. They will be displayed on the main form of settings.
After a eventual blank line, each line should contain a scoring rule like: <code>M|S ; default|[points] ; [rule]</code>.
The syntax of each rule is described in <a href=\"http://home.gna.org/auto-qcm/auto-multiple-choice.fr/interface-graphique.shtml#bareme\">AMC's documentation</a>.
When the question score is not explicit, it can be written <code>SCORE</code> in the rule.
</p>

Example:
<pre>
Default
For a single choice question with one point, one point for a good answer and no points for all other cases.
For a single choice question with multiple right answer, all points for a right answer, 0 if no answer given and -1 for all other cases.
For a multiple question with multiple right answers, 1 point is substracted by wrong answer, without exceeding -1 per question.

S ;       1 ; e=0,v=0,m=0,b=1
S ; default ; e=-1,v=0,m=-1,b=SCORE
M ; default ; e=-1,m=-1,p=-1,haut=SCORE

---
All or nothing
For all question, all the points if the answer is totally right and else 0.
S ; default ; e=0,v=0,m=0,b=SCORE
M ; default ; e=0,mz=SCORE
</pre>

<p>For each question, the first rule matching on the 2 first columns will be used.</p>
";
$string['settings_scoring_rules_default'] = "All or nothing
For all question, all the points if the answer is totally right and else 0.
S ; default ; e=0,v=0,m=0,b=SCORE
M ; default ; e=0,mz=SCORE";

$string['settings_code_length_short'] = 'Code length';
$string['settings_code_length_full'] = 'Student code length for AMC display';
$string['settings_instructionslstudent_short'] = 'Instructions / student number';
$string['settings_instructionslstudent_full'] = 'Default value of the homonymous field, when creating paper questionnaires.';
$string['settings_instructionslstudent_default'] = 'Please code the student number here, and write your name below.';
$string['settings_instructionslnamestd_short'] = 'Identification area / Standard';
$string['settings_instructionslnamestd_full'] = 'Default instruction for the field when creating a new standard paper questionnaire.';
$string['settings_instructionslnamestd_default'] = 'Name et first name';
$string['settings_instructionslnameanon_short'] = 'Identification area / Anonymous';
$string['settings_instructionslnameanon_full'] = 'Default instruction for the field when creating an anonymous paper questionnaire.';
$string['settings_instructions_short'] = 'Default instructions';
$string['settings_instructions_full'] = 'Elements are separed by a line of at least 3 dashes. The first line of each block will be the title displayed in the dropdown list. Example:<pre>Exams<br/>You have 4 hours.<br/>anonymity is guaranteed.<br/>---<br/>First Test<br/>Please use a pencil and gray each selected case completely.</pre>';
$string['settings_instructions_default'] = 'Exams
You have 4 hours.
Anonymity is guaranteed.
---
First Test
Please use a pencil and gray each selected case completely.';
$string['settings_idnumberprefixes_short'] = 'Prefix for student number';
$string['settings_idnumberprefixes_full'] = '<p>Prefixes, one per row. Beware of spaces.</p><p>Each prefix will be inserted at the beginning of the student number of each sheet, until the identification of the student among the moodle users (cf LDAP import and idnumber). If the student can not be found, a no prefix identification will be attempted.</p>';


// Instance settings
$string['modform_amcquizname'] = 'Questionnaire';
$string['modform_uselatexfile'] = 'Use a Latex file';
$string['modform_uselatexfilelabel'] = 'The Latex file define AMC and some questionnaire parameters.';
$string['modform_latexfile'] = 'Latex file (*.tex).';
$string['modform_instructionsheader'] = 'Instructions';
$string['modform_top_instructions_predefined'] = 'Predefined instructions.';
$string['modform_top_instructions_predefined_help'] = 'Choose one of the predefined instruction in order to automaticaly set the top instructions field.';
$string['modform_top_instructions'] = 'Top instructions';
$string['modform_description'] = 'Description';
$string['modform_description_help'] = 'A short description for the questionnaire.';
$string['modform_anonymous'] = 'Anonymous questionnaire.';
$string['modform_studentnumber_instructions'] = 'Instructions for the student number.';
$string['modform_studentname_instructions'] = 'Instructions for the student name.';

// add those fields to the form
$string['modform_scoring_parameters_header'] = 'Scoring';
$string['modform_grademax'] = 'Maximum grade';
$string['modform_gradegranularity'] = 'Grade granularity';
$string['modform_graderounding_strategy'] = 'Grade rounding strategy.';
$string['modform_scoring_strategy'] = 'Strategy used for score compution.';
$string['grade_rounding_strategy_nearest'] = 'Nearest';
$string['grade_rounding_strategy_lower'] = 'Lower';
$string['grade_rounding_strategy_upper'] = 'Upper';


$string['modform_amc_parameters_header'] = 'AMC parameters';
$string['modform_sheets_versions'] = 'Number of versions';
$string['modform_questions_columns'] = 'Number of columns for questions';
$string['modform_shuffle_questions'] = 'Shuffle questions';
$string['modform_shuffle_answers'] = 'Shuffle answers';
$string['modform_separate_sheets'] = 'Separate answer sheet';
$string['modform_sheets_columns'] = 'Number of columns for each sheet';

$string['modform_display_scores'] = 'Display scores';
$string['modform_display_scores_no'] = 'Do not display';
$string['modform_display_scores_beginning'] = 'Display at the beginning of question';
$string['modform_display_scores_end'] = 'Display at the end of question';

$string['modform_mark_multi'] = 'Put a mark if multiple good answer';
$string['modform_mark_multi_help'] = 'If checked, a clover leaf will appear if a question has more than one right answer.';

$string['modform_display_score_rules'] = 'Display score rules';
$string['modform_display_score_rules_help'] = 'The score rule will be printed.';

$string['modform_custom_layout'] = 'Custom layout';
$string['modform_custom_layout_help'] = 'Set a custom layout for AMC';
