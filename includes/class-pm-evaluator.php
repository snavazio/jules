<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'PM_Evaluator' ) ) {
    class PM_Evaluator {
        public static function evaluate($json){
            $obj = json_decode($json,true);
            $checks = ['context'=>false,'instructions'=>false,'input'=>false,'output'=>false];
            if(is_array($obj)){
                foreach(array_keys($checks) as $key){
                    $checks[$key] = array_key_exists($key, $obj);
                }
            }
            return ['checks'=>$checks,'score'=>array_sum($checks)];
        }
    }
}
