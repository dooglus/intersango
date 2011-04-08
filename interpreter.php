<?php

/* Run an sql script from the given file. Code taken adapted from elgg 1.7 */
function run_sql_script($scriptlocation) {
        if ($script = file_get_contents($scriptlocation)) {
                // global $CONFIG;

                $errors = array();

                $script = preg_replace('/\-\-.*\n/', '', $script);
                $sql_statements =  preg_split('/;[\n\r]+/', $script);
                foreach($sql_statements as $statement) {
                        $statement = trim($statement);
                        // $statement = str_replace("prefix_",$CONFIG->dbprefix,$statement);
                        if (!empty($statement)) {
                                try {
                                        $result = do_query($statement);
                                } catch (Exception $e) {
                                        $errors[] = $e->getMessage();
                                }
                        }
                }
                if (!empty($errors)) {
                        $errortxt = "";
                        foreach($errors as $error)
                                $errortxt .= " {$error};";
                        throw new Exception('error running script: ' . $scriptlocation . ":" . $errortxt);
                }
        } else {
                throw new Exception('ScriptNotFound:'.$scriptlocation);
        }
}
?>
