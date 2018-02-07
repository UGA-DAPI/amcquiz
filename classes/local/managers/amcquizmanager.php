<?php

namespace mod_amcquiz\local\managers;

class amcquizmanager
{
    const TABLE_AMCQUIZ = 'amcquiz';
    const TABLE_PARAMETERS = 'amcquiz_parameter';

    const RAND_MINI = 1000;
    const RAND_MAXI = 100000;

    const DISPLAY_POINTS_BEFORE = 1;
    const DISPLAY_POINTS_AFTER = 2;

    const TAB_1 = "\t";
    const TAB_2 = "\t\t";
    const TAB_3 = "\t\t\t";

    private $groupmanager;
    private $questionmanager;

    public function __construct() {
        $this->groupmanager = new \mod_amcquiz\local\managers\groupmanager();
        $this->questionmanager = new \mod_amcquiz\local\managers\questionmanager();
    }

    /**
     * Get an amcquiz with all relevant data
     * @param  int    $id   amcquiz id
     * @param  int    $cmid course module id (needed for getting proper context)
     * @return \stdClass an amcquiz
     */
    public function get_amcquiz_record($id, $cmid)
    {
        global $DB;
        // get amcquiz from db
        $amcquiz = $DB->get_record(self::TABLE_AMCQUIZ, ['id' => $id]);
        $amcquiz->parameters = $this->get_amcquiz_parameters_record($id);
        $amcquiz->groups = $this->groupmanager->get_quiz_groups($id);

        $nbquestions = 0;
        $scoresum = 0;
        // get all questions by groups
        foreach ($amcquiz->groups as $group) {
            if ($group->description_question_id) {
                // get question content and set it to group
                $questionInstance = \question_bank::load_question($group->description_question_id);
                $context = \context_module::instance($cmid);
                // will call mod/amcquiz/lib.php->amcquiz_question_preview_pluginfile
                $content = \question_rewrite_question_preview_urls(
                    $questionInstance->questiontext,
                    $questionInstance->id,
                    $questionInstance->contextid,
                    'question',
                    'questiontext',
                    $questionInstance->id,
                    $context->id,
                    'amcquiz'
                );
                $group->description = format_text($content, $questionInstance->questiontextformat);
            }
            // get questions
            $group->questions = $this->questionmanager->get_group_questions($group->id, $cmid);
            $nbquestions += count($group->questions);
            foreach ($group->questions as $question) {
                $scoresum += $question->score;
            }
        }
        // add usefull data to quiz
        $amcquiz->nbquestions = $nbquestions;
        $amcquiz->scoresum = $scoresum;

        return $amcquiz;

    }

    /**
     * Get amcquiz paramters
     * @param  int    $id amcquiz id
     * @return \stdClass  amcquiz parameters
     */
    public function get_amcquiz_parameters_record(int $id)
    {
        global $DB;
        return $DB->get_record(self::TABLE_PARAMETERS, ['amcquiz_id' => $id]);
    }

    /**
     * Create a quiz based on form data
     * @param  \stdClass $data form data
     * @return \stdClass the new amc quiz
     */
    public function create_quiz_from_form(\stdClass $data)
    {
        global $DB, $USER;

        $amcquiz = new \stdClass(); // \mod_amcquiz\local\entity\amcquiz();
        $amcquiz->name = $data->name;
        $amcquiz->course_id = $data->course;
        $amcquiz->author_id = $USER->id;
        $amcquiz->timecreated = time();
        $amcquiz->timemodified = time();
        $amcquiz->anonymous = (boolean)$data->anonymous;
        $amcquiz->studentcorrectionaccess = (boolean)$data->studentcorrectionaccess;
        $amcquiz->studentannotatedaccess = (boolean)$data->studentannotatedaccess;
        // save in order to have the id
        $amcquiz->id = $DB->insert_record(self::TABLE_AMCQUIZ, $amcquiz);

        // create default group
        $amcquiz->groups[] = $this->groupmanager->add_group($amcquiz->id);
        return $amcquiz;
    }

    /**
     * Update a quiz based on form data
     * @param  \stdClass $data form data
     * @return \stdClass the new amc quiz
     */
    public function update_quiz_from_form(\stdClass $data)
    {
        global $DB;
        $updated = new \stdClass();
        $updated->id = $data->instance;
        $updated->name = $data->name;
        $updated->timemodified = time();
        $updated->anonymous = (boolean)$data->anonymous;
        $updated->studentcorrectionaccess = (boolean)$data->studentcorrectionaccess;
        $updated->studentannotatedaccess = (boolean)$data->studentannotatedaccess;
        $DB->update_record(self::TABLE_AMCQUIZ, $updated);
        return $updated;
    }

    /**
     * Create parameters for a new quiz
     * @param  \stdClass $amcquiz the quiz
     * @param  array $data form parameters data
     * @return \stdClass the new amc quiz
     */
    public function create_amcquiz_parameters(\stdClass $amcquiz, array $data)
    {
        global $DB;
        $parameters = new \stdClass();
        $parameters->amcquiz_id = $amcquiz->id;
        $parameters->globalinstructions = $data['globalinstructions']['text'];
        $parameters->globalinstructionsformat = $data['globalinstructions']['format'];
        $parameters->studentnumberinstructions = $data['studentnumberinstructions'];
        $parameters->studentnameinstructions = $data['studentnameinstructions'];
        $parameters->grademax = (int)$data['grademax'];
        $parameters->gradegranularity = (float)$data['gradegranularity'];
        $parameters->graderounding = $data['graderounding'];
        $parameters->scoringset = $data['scoringset'];
        $parameters->versions = (int)$data['versions'];
        $parameters->shuffleq = (boolean)$data['shuffleq'];
        $parameters->shufflea = (boolean)$data['shufflea'];
        $parameters->qcolumns = (int)$data['qcolumns'];
        $parameters->acolumns = (int)$data['acolumns'];
        $parameters->separatesheet = (boolean)$data['separatesheet'];
        $parameters->displaypoints = (boolean)$data['displaypoints'];
        $parameters->markmulti = (boolean)$data['markmulti'];
        $parameters->showscoringset = (boolean)$data['showscoringset'];
        $parameters->customlayout = $data['customlayout'] ? $data['customlayout'] : null;
        $parameters->randomseed = rand(self::RAND_MINI, self::RAND_MAXI);
        $parameters->id = $DB->insert_record(self::TABLE_PARAMETERS, $parameters);

        $amcquiz->parameters = $parameters;
        return $amcquiz;
    }

    /**
     * Update parameters for a new quiz
     * @param  \stdClass $amcquiz the quiz
     * @param  array $data form parameters data
     * @return \stdClass the updated parameters
     */
    public function update_amcquiz_parameters(\stdClass $amcquiz, array $data)
    {
        global $DB;
        // we need to retrieve parameters id...
        $paramrecord = $this->get_amcquiz_parameters_record($amcquiz->id);
        $parameters = new \stdClass();
        $parameters->id = $paramrecord->id;
        $parameters->globalinstructions = $data['globalinstructions']['text'];
        $parameters->globalinstructionsformat = $data['globalinstructions']['format'];
        $parameters->studentnumberinstructions = $data['studentnumberinstructions'];
        $parameters->studentnameinstructions = $data['studentnameinstructions'];
        $parameters->grademax = (int)$data['grademax'];
        $parameters->gradegranularity = (float)$data['gradegranularity'];
        $parameters->graderounding = $data['graderounding'];
        $parameters->scoringset = $data['scoringset'];
        $parameters->versions = (int)$data['versions'];
        $parameters->shuffleq = (boolean)$data['shuffleq'];
        $parameters->shufflea = (boolean)$data['shufflea'];
        $parameters->qcolumns = (int)$data['qcolumns'];
        $parameters->acolumns = (int)$data['acolumns'];
        $parameters->separatesheet = (boolean)$data['separatesheet'];
        $parameters->displaypoints = (boolean)$data['displaypoints'];
        $parameters->markmulti = (boolean)$data['markmulti'];
        $parameters->showscoringset = (boolean)$data['showscoringset'];
        $parameters->customlayout = $data['customlayout'] ? $data['customlayout'] : null;

        $DB->update_record(self::TABLE_PARAMETERS, $parameters);
        return $parameters;
    }

    // NEED API
    public function send_latex_file(\stdClass $amcquiz, \stdClass $data, \mod_amcquiz_mod_form $form)
    {
        if (isset($data->latexfile) && !empty($data->latexfile)) {
            $filename = $form->get_new_filename('latexfile');
            // @TODO file content should be sent to API https://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms#filepicker
            $content = $form->get_file_content('latexfile');
            /*$uploadsuccess = $form->save_file(
                'latexfile',
                $this->getDirName(true).'/'.$filename,
                true
            );*/
            $amcquiz->latexfile = $filename;
            return true;
        }

        return false;
    }



    // need API should read grades from amc csv
    protected function read_amc_csv(\stdClass $amcquiz) {
        return [];
        /*$input = $this->fopenRead($this->workdir . self::PATH_AMC_CSV);
        if (!$input) {
            return false;
        }
        $header = fgetcsv($input, 0, self::CSV_SEPARATOR);
        if (!$header) {
            return false;
        }
        $getCol = array_flip($header);
        $grades = array();

        while (($data = fgetcsv($input, 0, self::CSV_SEPARATOR)) !== false) {
            $idnumber = $data[$getCol['student.number']];
            $userid = null;
            $userid = $data[$getCol['moodleid']];
            if ($userid) {
                $this->usersknown++;
            } else {
                $this->usersunknown++;
            }
            $grades[] = (object) array(
                'userid' => $userid,
                'rawgrade' => str_replace(',', '.', $data[6])
            );
        }
        fclose($input);
        return $grades;*/
    }


    public function get_grades(array $amcgradesdata = []) {
        $grades = [];
        foreach ($amcgradesdata as $grade) {
            if ($grade->userid) {
                $grades[$grade->userid] = (object) array(
                    'id' => $grade->userid,
                    'userid' => $grade->userid,
                    'rawgrade' => $grade->rawgrade,
                );
            }
        }
        return $grades;
    }


    public function amcquiz_export(int $id) {
        // get quiz and transform all its data (ie group description question content, question content and question anwer content)
        global $DB, $CFG;

        srand(microtime() * 1000000);
        $unique = str_replace('.', '', microtime(true) . '_' . rand(0, 100000));
        // quiz temp folder
        $amcquizfolder = $CFG->dataroot . "/temp/amcquiz/" . $unique;

        $result = [
            'tempfolder' => $amcquizfolder,
            'zipfile' => '',
            'errors' => [],
            'warnings' => []
        ];


        if (!check_dir_exists($amcquizfolder, true, true)) {
            print_error("Could not create data directory");
        } else {
            // get amcquiz from db
            $amcquiz = $DB->get_record(self::TABLE_AMCQUIZ, ['id' => $id]);
            $amcquiz->parameters = $this->get_amcquiz_parameters_record($id);
            $latexfilename = $amcquizfolder . DIRECTORY_SEPARATOR . 'prepare-source.tex';
            $latexcontent = file_get_contents($latexfilename);
            // build header
            $latexcontent .= $this->build_latex_header($amcquiz);

            $translator = new \mod_amcquiz\translator($amcquiz->parameters);
            $groups = $this->groupmanager->get_quiz_groups($id);
            // remove group that do not have questions
            $groups_filtered = array_filter($groups, function ($group) {
                return $this->questionmanager->count_group_questions($group->id) > 0;
            });

            // transform group data
            $groups_mapped = array_map(function ($group) use ($translator, $amcquizfolder) {
                if ($group->description_question_id) {
                    // get question content and set it to group
                    $questionInstance = \question_bank::load_question($group->description_question_id);
                    $content = format_text($questionInstance->questiontext, $questionInstance->questiontextformat);
                    $parsedhtml = $translator->html_to_tex($content, $questionInstance->contextid, 'questiontext', $questionInstance->id, $amcquizfolder, 'group-description');
                    $group->description = $parsedhtml['latex'];
                    if (count($parsedhtml['errors']) > 0) {
                        $result['errors'][] = $parsedhtml['errors'];
                    }

                    if (count($parsedhtml['warnings']) > 0) {
                        $result['warnings'][] = $parsedhtml['warnings'];
                    }
                }
                return $group;
            }, $groups_filtered);

            $morethanonegroup = count($groups_mapped) > 1;
            $nbquestioninquiz = $this->count_quiz_questions($amcquiz);

            // if more than one group
            if ($morethanonegroup) {
                $latexcontent .= '%%% preparation of the groups';
                $latexcontent .= PHP_EOL;
                $latexcontent .= '\setdefaultgroupmode{withoutreplacement}';
                $latexcontent .= PHP_EOL;
            }

            // get quiz scoring rule
            $scoringrule = $this->get_quiz_scoring_rule($amcquiz);

            foreach ($groups_mapped as $group) {

                $groupexport = $this->questionmanager->export_group_questions($group->id, $amcquizfolder, $translator);

                $groupquestions = $groupexport['questions'];
                if (count($groupexport['errors']) > 0) {
                    $result['errors'][] = $groupexport['errors'];
                }

                if (count($groupexport['warnings']) > 0) {
                    $result['warnings'][] = $groupexport['warnings'];
                }
                foreach ($groupquestions as $question) {
                    if ($question->score === round($question->score)) {
                        $points = $question->score;
                    } elseif (abs(round(10*$question->score) - 10*$question->score) < 1) {
                        $points =  sprintf('%.1f', $question->score);
                    } else {
                        $points = sprintf('%.2f', $question->score);
                    }

                    $pointstext = '(' . $points . ' pt';
                    if ($question->score > 1) {
                        $pointstext .= 's)';
                    } else {
                        $pointstext .= ')';
                    }

                    $questionrule = '';
                    foreach ($scoringrule->rules as $rule) {
                        //si toutes ces conditions sont réunies alors on peut appliquer la règle...
                        $rulematch = true;

                        if (!$question->multiple && $rule->multiple) {
                            $rulematch = false;
                        }
                        if ($rule->score && $question->score !== $rule->score) {
                            $rulematch = false;
                        }
                        if ($rulematch) {
                            if ($rule->score) {
                                $questionrule = str_replace('SCORE', $rule->score, $rule->expression);
                            } else {
                                $questionrule = str_replace('SCORE', $question->score, $rule->expression);
                            }
                            break;
                        }
                    }

                    $latexcontent .= $morethanonegroup ? '\element{'.$group->name.'}{' : '\element{default}{';
                    $latexcontent .= PHP_EOL;
                    $questionname = preg_replace('/[^a-zA-Z]+/', '', @iconv('UTF-8', 'ASCII//TRANSLIT', substr(html_entity_decode(strip_tags($question->name)), 0, 30 )));
                    $questionname .= $question->multiple ? 'mult':'';
                    $latexcontent .= self::TAB_1 . '\begin{question}{'.$questionname.'}';
                    $latexcontent .= PHP_EOL;
                    $latexcontent .= self::TAB_1 . '\scoring{' . $questionrule . '}';
                    $latexcontent .= PHP_EOL;

                    if ((int)$amcquiz->parameters->displaypoints === self::DISPLAY_POINTS_BEFORE) {
                        $latexcontent .= self::TAB_1 . $pointstext;
                        $latexcontent .= PHP_EOL;
                    }
                    $latexcontent .= self::TAB_1 . $question->questiontext;
                    $latexcontent .= PHP_EOL;

                    if ((int)$amcquiz->parameters->displaypoints === self::DISPLAY_POINTS_AFTER) {
                        $latexcontent .= self::TAB_1 . $pointstext;
                        $latexcontent .= PHP_EOL;
                    }
                    $latexcontent .= self::TAB_2 .'\begin{choices}';
                    $latexcontent .= $amcquiz->parameters->shufflea ? '':'[o]';
                    $latexcontent .= PHP_EOL;

                    foreach ($question->answers as $answer) {
                        $latexcontent .= $answer->valid ? self::TAB_3 . '\correctchoice' : self::TAB_3 . '\wrongchoice';
                        $latexcontent .= '{'. $answer->answertext .'}';
                        $latexcontent .= PHP_EOL;
                    }

                    $latexcontent .= self::TAB_2 . '\end{choices}';
                    $latexcontent .= PHP_EOL;
                    $latexcontent .= self::TAB_1 . '\end{question}';
                    $latexcontent .= PHP_EOL;
                    $latexcontent .= '}';
                    $latexcontent .= PHP_EOL;
                }
            }

            $latexcontent .= '\begin{examcopy}['.$amcquiz->parameters->versions.']';
            $latexcontent .= PHP_EOL;
            $latexcontent .= '\begin{center}\Large\bf\mytitle\end{center}';
            $latexcontent .= PHP_EOL;

            if (!$amcquiz->parameters->separatesheet) {
                $latexcontent .= $this->build_latex_student_block($amcquiz);
            }

            if ($amcquiz->parameters->globalinstructions && $amcquiz->parameters->globalinstructions !== '') {
                $latexcontent .= '\begin{instructions}';
                $latexcontent .= PHP_EOL;

                $parsedhtml = $translator->html_to_tex($amcquiz->parameters->globalinstructions);
                if (count($parsedhtml['errors']) > 0) {
                    $result['errors'][] = $parsedhtml['errors'];
                }

                if (count($parsedhtml['warnings']) > 0) {
                    $result['warnings'][] = $parsedhtml['warnings'];
                }
                $latexcontent .= $parsedhtml['latex'];
                $latexcontent .= PHP_EOL;

                $latexcontent .= '\end{instructions}';
                $latexcontent .= PHP_EOL;
            }

            $nbcolumns = $amcquiz->parameters->qcolumns;
            if ($nbcolumns === 0 && $nbquestioninquiz > 5) {
                $nbcolumns = 2;
            }

            // group data to print
            foreach ($groups_mapped as $group) {

                if ($group->description_question_id) {
                      $latexcontent .= '\begin{center}';
                      $latexcontent .= PHP_EOL;
                      $latexcontent .= self::TAB_1 . '\hrule\vspace{2mm}';
                      $latexcontent .= PHP_EOL;
                      // use DOM
                      $latexcontent .= self::TAB_1 . '\bf\Large ' . $group->description;
                      $latexcontent .= PHP_EOL;
                      $latexcontent .= self::TAB_1 . '\vspace{2mm}\hrule';
                      $latexcontent .= PHP_EOL;
                      $latexcontent .= '\end{center}';
                      $latexcontent .= PHP_EOL;
                }

                if ($nbcolumns > 1) {
                    $latexcontent .= '\begin{multicols}{'.$nbcolumns.'}';
                    $latexcontent .= PHP_EOL;
                }

                $latexcontent .= $morethanonegroup ? '\insertgroup{'.$group->name.'}' : '\insertgroup{default}';
                $latexcontent .= PHP_EOL;

                if ($amcquiz->parameters->shuffleq) {
                    $latexcontent .= $morethanonegroup ? '\shufflegroup{'.$group->name.'}' : '\shufflegroup{default}';
                    $latexcontent .= PHP_EOL;
                }

                if ($nbcolumns > 1) {
                    $latexcontent .= '\end{multicols}';
                    $latexcontent .= PHP_EOL;
                }
            }

            if ($amcquiz->parameters->separatesheet) {
                $nbanswercolumns = 0;
                if (empty($amcquiz->parameters->acolumns)) {
                    $nbanswercolumns = $nbquestioninquiz > 22 ? 2 : 0;
                } elseif ($amcquiz->parameters->acolumns === 1) {
                    $nbanswercolumns = 0;
                } else {
                    $nbanswercolumns = $amcquiz->parameters->acolumns;
                }
                $latexcontent .= '\AMCcleardoublepage';
                $latexcontent .= PHP_EOL;
                $latexcontent .= '\AMCformBegin';
                $latexcontent .= PHP_EOL;
                $latexcontent .= '\answersheet';
                $latexcontent .= PHP_EOL;
                $latexcontent .= $this->build_latex_student_block($amcquiz);
                if ($nbanswercolumns > 1) {
                    $latexcontent .= '\begin{multicols}{'.$nbanswercolumns.'}';
                    $latexcontent .= PHP_EOL;
                    $latexcontent .= '\raggedcolumns';
                    $latexcontent .= PHP_EOL;
                }
                $latexcontent .= '\AMCform';
                $latexcontent .= PHP_EOL;
                if ($nbanswercolumns > 1) {
                    $latexcontent .= '\end{multicols}';
                    $latexcontent .= PHP_EOL;
                }
                $latexcontent .= '\clearpage';
                $latexcontent .= PHP_EOL;
            }

            $latexcontent .= '\end{examcopy}';
            $latexcontent .= PHP_EOL;
            $latexcontent .= '\end{document}';
            //save content to prepare-source.tex
            file_put_contents($latexfilename, $latexcontent);

            // zip files
            $zipfile = $amcquizfolder . DIRECTORY_SEPARATOR . 'amcquiz_' . $amcquiz->id . '.zip';
            $zip = new \ZipArchive();

            if ($zip->open($zipfile, \ZipArchive::CREATE) !== true) {
                $result['errors'][] = 'could not open zip archive: ' . $zipfile;
            }

            if (file_exists($latexfilename)) {
                $zip->addFile($latexfilename, 'prepare-source.tex');
            } else {
                $result['errors'][] = 'can not add latex file';
            }

            $media = preg_grep('/^([^.])/', scandir($amcquizfolder . DIRECTORY_SEPARATOR . 'media'));

            foreach ($media as $file) {
                $success = $zip->addFile($amcquizfolder . DIRECTORY_SEPARATOR . 'media'. DIRECTORY_SEPARATOR . $file, 'media' . DIRECTORY_SEPARATOR . $file);
                if (!$success) {
                    $result['errors'][] = 'problem adding file: ' . $file . ' to zip';
                }
            }

            $zipsuccessfull = $zip->close();

            if (!$zipsuccessfull) {
                $result['errors'][] = 'problem while creating zip file: '.$zipfile;
            }

            $result['zipfile'] = $zipsuccessfull ? base64_encode(file_get_contents($zipfile)) : null;

            if ($result['zipfile'] === false) {
                $result['errors'][] = 'problem while enconding zip file: '.$zipfile;
            }

            // delete dir and all its content
            //rmdir($amcquizfolder);

            return $result;
        }
    }

    public function get_quiz_scoring_rule(\stdClass $amcquiz){
        // all scoring rules available in config
        $scoringrulesrawdata = get_config('mod_amcquiz', 'scoringrules');
        $splittedrules = preg_split('/\n-{3,}\s*\n/s', $scoringrulesrawdata, -1, PREG_SPLIT_NO_EMPTY);
        $choosenone = $splittedrules[$amcquiz->parameters->scoringset];

        $ruleslines = array_filter(explode("\n", $choosenone));
        $scoringrule = new \stdClass();
        // take the first element of the array (name of scoringrule)
        $scoringrule->name = array_shift($ruleslines);
        $scoringrule->rules = [];
        // remove all descriptions texts for the scoringrule
        while ($ruleslines && !preg_match('/^\s*[SM]\s*;/i', $ruleslines[0])) {
            array_shift($ruleslines);
        }
        // remove empty values
        $nonemptyrules = array_filter($ruleslines, function ($line) {
            return trim($line) !== '';
        });

        // build scoring rules
        foreach ($nonemptyrules as $rawrule) {
            $rule = new \stdClass();
            $rawrulesplitted = explode(';', $rawrule);
            $rule->multiple = strtoupper(trim($rawrulesplitted[0])) === 'M';
            $rule->score = (double) $rawrulesplitted[1];
            $rule->expression = trim($rawrulesplitted[2]);
            $scoringrule->rules[] = $rule;
        }
        return $scoringrule;
    }

    public function count_quiz_questions(\stdClass $amcquiz) {
        $count = 0;
        foreach ($amcquiz->groups as $group) {
            $count += $this->questionmanager->count_group_questions($group->id);
        }
        return $count;
    }

    public function build_latex_header(\stdClass $amcquiz) {
        $latexheader = '';
        $latexheader .= '\documentclass[a4paper]{article}';
        $latexheader .=  PHP_EOL;
        $latexheader .= '\usepackage[utf8]{inputenc}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage[T1]{fontenc}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage{amsmath,amssymb}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage{multicol}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage{environ}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage{graphicx}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\usepackage{float}';
        $latexheader .= PHP_EOL;

        // options
        $latexheader .= '\usepackage[box';
        if ($amcquiz->parameters->shuffleq) {
            $latexheader .= ',noshuffle';
        }
        if ($amcquiz->parameters->separatesheet) {
            $latexheader .= ',separateanswersheet';
        }

        $latexheader .= ']{automultiplechoice}';
        $latexheader .= PHP_EOL;

        $latexheader .= '\date{}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\author{}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\title{'.$amcquiz->name.'}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\makeatletter';
        $latexheader .= PHP_EOL;
        $latexheader .= '\let\mytitle\@title';
        $latexheader .= PHP_EOL;
        $latexheader .= '\let\myauthor\@author';
        $latexheader .= PHP_EOL;
        $latexheader .= '\let\mydate\@date';
        $latexheader .= PHP_EOL;
        $latexheader .= '\makeatother';
        $latexheader .= PHP_EOL;
        if ($amcquiz->parameters->customlayout) {
            $latexheader .= $amcquiz->parameters->customlayout;
            $latexheader .= PHP_EOL;
        }

        if ($amcquiz->parameters->acolumns > 2) {
            // def has to be on one line if we want the layout to be ok...
            $latexheader .= '\def\AMCformQuestion#1{';
            $latexheader .= '\vspace{\AMCformVSpace}';
            $latexheader .= '\par{\bf Q.#1 :}';
            $latexheader .= '}';
            $latexheader .= PHP_EOL;
            $latexheader .= '\def\AMCformAnswer#1{';
            $latexheader .= '\hspace{\AMCformHSpace}#1';
            $latexheader .= '}';
            $latexheader .= '\makeatletter';
            $latexheader .= PHP_EOL;
        }

        if ($amcquiz->parameters->markmulti) {
            $latexheader .= '\def\multiSymbole{}';
            $latexheader .= PHP_EOL;
        }

        $latexheader .= '\AMCrandomseed{' . $amcquiz->parameters->randomseed . '}';
        $latexheader .= PHP_EOL;

        $latexheader .= '\scoringDefaultS{}';
        $latexheader .= PHP_EOL;
        $latexheader .= '\scoringDefaultM{}';
        $latexheader .= PHP_EOL;

        $latexheader .= '\newenvironment{instructions}{}';
        $latexheader .= PHP_EOL;
        $latexheader .= '{';
        $latexheader .= PHP_EOL;
        $latexheader .= self::TAB_1 . '\vspace{1ex}\hrule';
        $latexheader .= PHP_EOL;
        $latexheader .= self::TAB_1 . '\vspace{2ex}';
        $latexheader .= PHP_EOL;
        $latexheader .= '}';
        $latexheader .= PHP_EOL;

        $latexheader .= '\newcommand{\answersheet}{';
        $latexheader .= PHP_EOL;
        $latexheader .= self::TAB_1 . '\begin{center}';
        $latexheader .= PHP_EOL;
        $latexheader .= self::TAB_2 . '\Large\bf\mytitle{} --- ' . get_string('document_answer_sheet_title', 'mod_amcquiz');
        $latexheader .= PHP_EOL;
        $latexheader .= self::TAB_1 . '\end{center}';
        $latexheader .= PHP_EOL;
        $latexheader .= '}';
        $latexheader .= PHP_EOL;

        $latexheader .= '\begin{document}';
        $latexheader .= PHP_EOL;

        return $latexheader;
    }

    public function build_latex_student_block(\stdClass $amcquiz) {
        $studentblock = '';
        $codelength = get_config('mod_amcquiz', 'amccodelength');
        $studentblock .= '{';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_1 . '\setlength{\parindent}{0pt}';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_1 . '\begin{multicols}{2}';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '\raggedcolumns';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '\AMCcode{student.number}{' . $codelength . '}';
        $studentblock .= PHP_EOL;
        // need two EOL if we want the arrow to be placed at the right place... oO
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '\columnbreak';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '$\longleftarrow{}$\hspace{0pt plus 1cm}';
        $studentblock .= $amcquiz->parameters->studentnumberinstructions;
        $studentblock .= '\\\\[3ex]';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '\hfill{}';
        $studentblock .= PHP_EOL;

        $studentblock .= self::TAB_1 . '\namefield{';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '\fbox{';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_3 . '\begin{minipage}{.9\linewidth}';
        $studentblock .= PHP_EOL;
        if ($amcquiz->parameters->studentnameinstructions) {
            $studentblock .= $amcquiz->parameters->studentnameinstructions;
            $studentblock .= '\\\\[3ex]';
            $studentblock .= PHP_EOL;
        }
        $studentblock .= self::TAB_3 .'\null\dotfill\\\\[2.5ex]';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_3 . '\null\dotfill\vspace*{3mm}';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_3 . '\end{minipage}';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_2 . '}';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_1 . '}';
        $studentblock .= PHP_EOL;

        $studentblock .= self::TAB_1 . '\hfill\\\\';
        $studentblock .= PHP_EOL;
        $studentblock .= self::TAB_1 . '\end{multicols}';
        $studentblock .= PHP_EOL;
        $studentblock .= '}';
        $studentblock .= PHP_EOL;


        return $studentblock;
    }


}
