<?php

# Define necessary constant
define("ERR_MSG_MISSING_ARG", "Missing mandatory arguments");
define("ERR_MSG_MISSING_FIELDS", "Missing reuqired fields");
define("ERR_MSG_NO_INPUT", "Missing input filename or invalid input filename");
define("ERR_MSG_INCORRECT_INPUT_FORMAT", "The content of input file is incorrect");
define("ERR_MSG_OUTPUT_SAVE_PROBLEM", "There is error in saving the output file");
define("ERR_MSG_UNSUPPORT_FORMAT", "The input format or output format are not supported");
$SUPPORTED_FORMAT=array('csv');

# Function to print the error information and command usage guideline
function show_help($command, $err_msg="") {
    if ($err_msg) {
        echo "Error: ".$err_msg.PHP_EOL.PHP_EOL;
    }
    echo "Usage: php ".$command." --file <filename> --out <output_filename>".PHP_EOL;
    echo "\t--file <file_name>\t(Mandatory Field) specify the input filename".PHP_EOL;
    echo "\t--out <file_name>\t(Optional Field) specify the output filename".PHP_EOL;
    exit();
}

# An abstract class to define the functions required to be implemented for each supported format
abstract class Parser {
    abstract protected static function count_combination($input);
    abstract protected static function save_to_file($output, $comb);
}

# A class implementing functions in Parser class for the supported csv format
class CsvParser {
    public static function count_combination($input) {
        $handle = fopen($input, 'r');

        $cols=fgetcsv($handle);
        if (!$cols) {
            return null;
        }

        # Construct the output column names
        $cols_num=count($cols);
        array_push($cols, "count");
        $comb=array("COLUMN"=>$cols);

        while (($data=fgetcsv($handle))!==FALSE) {
            if (count($data)!=$cols_num) {
                return null;
            }

            $key=join($data);
            if (array_key_exists($key, $comb)) {
                # if the combination is existed in the combination array
                $comb[$key][$cols_num]++;
            } else {
                # if the combination is not existed in the combination array
                $data[$cols_num]=1;
                $comb[$key]=$data;
            }
        }

        fclose($handle);

        return $comb;
    }

    public static function save_to_file($output, $comb) {
        try {
            $handle = fopen($output, 'w');

            foreach ($comb as $fields) {
                fputcsv($handle, $fields);
            }

            fclose($handle);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}

# Show help menu
if ($argc>=2 && in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    show_help($argv[0]);
}

# Insufficient number of arguments
if ($argc<3) {
    show_help($argv[0], ERR_MSG_MISSING_ARG);
}

$input="";
$output="";
$in_format="";
$out_format="";

# Retrieve the required arguments
for ($i=1; $i<$argc; $i++) {
    switch($argv[$i]) {
        case "--file":
            if ($i+1<$argc && file_exists($argv[$i+1])) {
                $input=$argv[++$i];
            } else {
                show_help($argv[0], ERR_MSG_NO_INPUT);
            }
            break;
        case "--out":
            if ($i+1<$argc) {
                $output=$argv[++$i];
            } else {
                show_help($argv[0], ERR_MSG_MISSING_FIELDS);
            }
            break;
    }
    
    if ($input && $output) {
        break;
    }
}

$pp=pathinfo($input);
$in_format=$pp['extension'];

# If output filename is not defined, append "-combination" to the input filename.
if (!$output) {
    $output=$pp['dirname'].DIRECTORY_SEPARATOR.$pp['filename']."-combination.".$pp['extension'];
    $out_format=$pp['extension'];
} else {
    $out_format=pathinfo($output)['extension'];
}

# Check if the format is supported
if (!in_array($in_format, $SUPPORTED_FORMAT) || !in_array($out_format, $SUPPORTED_FORMAT)) {
    show_help($argv[0], ERR_MSG_UNSUPPORT_FORMAT);
}

$comb;

# Read input file and count the combination
switch (strtolower($in_format)) {
    case "csv":
        echo "[INFO] Start reading input file '".$input."' and counting combinations.".PHP_EOL;
        $comb=CsvParser::count_combination($input);
        if (!$comb) {
            show_help($argv[0], ERR_MSG_INCORRECT_INPUT_FORMAT);
        }
        break;
    default:
        show_help($argv[0], ERR_MSG_UNSUPPORT_FORMAT);
}

# Write the results to the output file
echo "[INFO] Start writing to output file '".$output."'.".PHP_EOL;
switch (strtolower($out_format)) {
    case "csv":
        if (CsvParser::save_to_file($output, $comb)) {
            echo "[INFO] The combination counts were saved in the output file '".$output."'.".PHP_EOL;
        } else {
            show_help($argv[0], ERR_MSG_OUTPUT_SAVE_PROBLEM);
        }
        break;
    default:
        show_help($argv[0], ERR_MSG_UNSUPPORT_FORMAT);
}

?>