<?php

namespace IxTheo;

class Utility {

    const USER_TYPE_MAP = [ 'bibstudies' => 'ixtheo',
                            'ixtheo2'    => 'ixtheo'];

    public static function getUserTypeFromUsedEnvironment() {
        $vufind_local_dir = getenv('VUFIND_LOCAL_DIR');
        $instance_type = basename($vufind_local_dir);
        if (isset(self::USER_TYPE_MAP[$instance_type])) {
            $instance_type = self::USER_TYPE_MAP[$instance_type];
        }
        return $instance_type;
    }

    public static function printToConsole($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);
        echo "<script>console.log('" . $output . "');</script>";
    }
}