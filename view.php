<?php

/* FRONT CTRL */

require_once(__DIR__ . '/locallib.php');

global $OUTPUT, $PAGE, $USER, $DB;

$service = new \mod_amcquiz\shared_service();
$service->parse_request();
$cm = $service->cm;
$course = $service->course;
$amcquiz = $service->amcquiz;
$current_view = $service->current_view;

$PAGE->set_url('/mod/amcquiz/view.php', ['id' => $cm->id, 'current' => $current_view]);

$viewcontext = context_module::instance($cm->id);
require_capability('mod/amcquiz:view', $viewcontext);
require_login($course, true, $cm);

$renderer = $PAGE->get_renderer('mod_amcquiz');
$renderer->render_from_template('mod_amcquiz/noscript', []);
$PAGE->requires->js_call_amd('mod_amcquiz/common', 'init', []);

echo $renderer->render_header($amcquiz, $viewcontext);

if (!has_capability('mod/amcquiz:update', $viewcontext)) {
    $studentview = new \mod_amcquiz\output\view_student($amcquiz, $USER);
    echo $renderer->render_student_view($studentview);
} else {
    $tabs = new \mod_amcquiz\output\tabs($amcquiz, $viewcontext, $cm, $current_view);
    echo $renderer->render_tabs($tabs);

    if (isset($_POST['action'])) {
        $postmanager = new \mod_amcquiz\local\managers\postmanager();
        $postmanager->handle_post_request($_POST);
        // update amcquiz object after post actions
        $amcquiz = $service->amcquizmanager->get_amcquiz_record($amcquiz->id, $cm->id);
    }
    // render desired view with proper data
    switch ($current_view) {
        case 'questions':
            $PAGE->requires->js_call_amd('mod_amcquiz/questions', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_questions renderer
            $data = [
                'cmid' => $cm->id,
                'courseid' => $course->id,
                'pageurl' => '/mod/amcquiz/view.php?id=' . $cm->id . '&current=' . $current_view
            ];
            $content = new \mod_amcquiz\output\view_questions($amcquiz, $data);
            echo $renderer->render_questions_view($content);
            break;
        case 'subjects':
            $PAGE->requires->js_call_amd('mod_amcquiz/documents', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_documents renderer
            $data = [];
            $content = new \mod_amcquiz\output\view_documents($amcquiz, $data);
            echo $renderer->render_documents_view($content);
            break;
        case 'sheets':
            $PAGE->requires->js_call_amd('mod_amcquiz/sheets', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_sheets renderer
            $data = [];
            $content = new \mod_amcquiz\output\view_sheets($amcquiz, $data);
            echo $renderer->render_sheets_view($content);
            break;
        case 'associate':
            $PAGE->requires->js_call_amd('mod_amcquiz/associate', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_associate renderer
            $data = [];
            $content = new \mod_amcquiz\output\view_associate($amcquiz, $data);
            echo $renderer->render_associate_view($content);
            break;
        case 'annotate':
            $PAGE->requires->js_call_amd('mod_amcquiz/annotate', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_annotate renderer
            $data = [
              'cm' => $cm
            ];
            $content = new \mod_amcquiz\output\view_annotate($amcquiz, $data);
            echo $renderer->render_annotate_view($content);
            break;
        case 'correction':
            $PAGE->requires->js_call_amd('mod_amcquiz/correction', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_correction renderer
            $data = [];
            $content = new \mod_amcquiz\output\view_correction($amcquiz, $data);
            echo $renderer->render_correction_view($content);
            break;
        default:
            $PAGE->requires->js_call_amd('mod_amcquiz/questions', 'init', [$amcquiz->id, $course->id, $cm->id]);
            // additional data to pass to view_questions renderer
            $data = [
                'cmid' => $cm->id,
                'courseid' => $course->id,
                'pageurl' => '/mod/amcquiz/view.php?id=' . $cm->id . '&current=' . $current_view
            ];
            $content = new \mod_amcquiz\output\view_questions($amcquiz, $data);
            echo $renderer->render_questions_view($content);
    }
}

echo $OUTPUT->footer();
