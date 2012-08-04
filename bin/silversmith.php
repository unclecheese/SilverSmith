<?php
/**
 * Initializes SilverSmith from a command-line interface.
 */


// Turn off timezone warning
date_default_timezone_set(@date_default_timezone_get());

// Require dependencies
$script_dir = dirname(dirname(__FILE__));
require_once("{$script_dir}/code/lib/bedrock/Bedrock.php");
require_once("{$script_dir}/code/SilverSmithNode.php");
require_once("{$script_dir}/code/BedrockDataRecord.php");
foreach(glob("{$script_dir}/code/*.php") as $class) {
    require_once($class);
}
require_once("{$script_dir}/code/lib/thirdparty/TextDiff.php");


// Bootstrap the SilverSmith class
SilverSmith::set_cli(true);
SilverSmith::set_script_dir($script_dir);
SilverSmith::set_git_path(shell_exec("which git"));
SilverSmithDefaults::load(SilverSmith::get_script_dir() . "/code/lib/_defaults.yml");
SilverSmithSpec::load(SilverSmith::get_script_dir() . "/code/lib/_spec.yml");


// Validation for the CLI commands
$commands = new BedrockYAML(SilverSmith::get_script_dir() . "/code/lib/_cli.yml");
$allowed_actions = $commands->getAllowedActions();
$PARAMS = SilverSmithUtil::parse_parameters();
if (!isset($PARAMS[1])) {
    fail("Usage: silversmith <command> [-args]. Type 'silversmith help' for more information.");
}
$action = $PARAMS[1];
if (!$allowed_actions->get($action)) {
    say(error("'$action' is not an allowed command."));
    say("Available commands:\n " . implode("\n", array_keys($allowed_actions->toArray())));
    die();
}
$allowed_options = $allowed_actions->get($action)->getOptions();
foreach ($PARAMS as $k => $v) {
    if (!is_numeric($k) && !$allowed_options->get($k)) {
        say(error("Option '$k' is not allowed."));
        say("Available options for $action:\n" . implode("\n", array_keys($allowed_options->toArray())));
        die();
    }
}


if ($allowed_actions->get($action)->getProjectRequired()) {
    if (!SilverSmith::switch_to_project_root())
        fail("You must run this command from within a SilverStripe project.");
    
    if (!isset($_SERVER['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = "";
    }
    if (!isset($_SERVER['SERVER_PROTOCOL'])) {
        $_SERVER['SERVER_PROTOCOL'] = "";
    }
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        $_SERVER['REQUEST_METHOD'] = "";
    }
    define('BASE_PATH', getcwd());
    global $databaseConfig;
    $_SESSION = null;
    state("Including SilverStripe core...");
    if(file_exists("sapphire/core/Core.php")) {
        require_once("sapphire/core/Core.php");
        require_once("sapphire/model/DB.php");
    }
    elseif(file_exists("framework/core/Core.php")) {
        require_once("framework/core/Core.php");
        require_once("framework/model/DB.php");        
    }
    else {
        fail("Could not find framework directory!");
    }
    
    say("done.");

    state("Connecting to database...");
    DB::connect($databaseConfig);
    say("done");
    
    $project_dir = isset($PARAMS['module']) ? $PARAMS['module'] : project();
    SilverSmith::set_project_dir($project_dir);
    if ($action != "init") {
        $yml_file = isset($PARAMS['file']) ? $PARAMS['file'] : "_project.yml";
        $yml_path = SilverSmith::get_project_dir()."/$yml_file";        
        if (!file_exists($yml_path)) {
            fail("File $yml_path does not exist. Use 'silversmith init' to create it.");
        }
        state("Bootstrapping SilverSmith...");        
        SilverSmith::set_yaml_path($yml_path);
        SilverSmithProject::load($yml_path);
        SilverSmith::load_field_manifest();
        SilverSmith::load_class_manifest();
        SilverSmith::load_interface_manifest();
        
        // Check for an upgrade every hour
        $time = time();
        $stamp = @file_get_contents($script_dir."/upgrade");
        if(!$stamp) $stamp = $time;
        $diff = $time - (int) $stamp;
        if($diff > 3600) {
            say("Checking for upgrade...");
            SilverSmith::upgrade();
        }        
        say("done");        
    }
}
// if (SilverSmith::is_upgrade_available() && $action != "upgrade") {
//     warn("*** An upgrade is available ***");
//     state(" Run 'silversmith upgrade' to install.\n");
//     sleep(2);
// }


// Hand off execution to the SilverSmith static class
say("Executing CLI command\n\n");
line();
call_user_func("SilverSmith::{$action}",$PARAMS);       







