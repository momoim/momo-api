<?php
//Xhprof 性能分析 Hook by avenger

if (!function_exists('xhprof_enable')) return;

class Xhprof {
    public function test(){
        Event::$data = Event::$data.'<!-- Powered by Kohana-->';
    }

    public function start() {
        //xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_NO_BUILTINS);
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    }

    public function stop() {
        // stop profiler
        $xhprof_data = xhprof_disable();

        // display raw xhprof data for the profiler run
        //print_r($xhprof_data);

        include_once APPPATH."vendor/xhprof/utils/xhprof_lib.php";
        include_once APPPATH."vendor/xhprof/utils/xhprof_runs.php";

        // save raw data for this profiler run using default
        // implementation of iXHProfRuns.
        $xhprof_runs = new XHProfRuns_Default();

        // save the run under a namespace "xhprof_foo"
        $run_id = $xhprof_runs->save_run($xhprof_data, "uap_sns");

        //if (!isset($_REQUEST['ajax'])) echo "\r\n".'<!-- Profiler URL:'. Kohana::config('config.xhprof_url') .'?run='.$run_id.'&source=uap_sns -->';
        return $run_id;
    }
}

//http://docs.kohanaphp.com/general/events
//Event::add('system.display', array('Xhprof', 'test'));
//Event::add('system.routing', array('Xhprof', 'start'));

//Event::add('system.pre_controller', array('Xhprof', 'start'));
//Event::add('system.shutdown', array('Xhprof', 'stop'));
