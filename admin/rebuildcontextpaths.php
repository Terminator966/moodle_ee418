<?php require_once(dirname(__FILE__) . '/../config.php');

require_login(); require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$strRebuildContextPath = "Rebuild Context Path";

$PAGE->set_url('/admin/rebuildcontexpath.php'); $PAGE->set_title($strRebuildContextPath); $PAGE->set_heading($strRebuildContextPath); $PAGE->navbar->add($strRebuildContextPath);

echo $OUTPUT->header();

echo '

Rebuilding context paths ...

';

context_helper::build_all_paths(True);

echo '

Done

';

echo $OUTPUT->footer(); ?>