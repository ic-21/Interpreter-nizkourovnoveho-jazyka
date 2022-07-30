<?php
// Ivana Colnikova, xcolni00

ini_set('display_errors', 'stderr');

//definicia premennych

// zoznam src suborov
$src_file_list = [];

//ci sa jedna o rekurziu
$recursion = 0;

// subor pre parser
$parse_file = "./parse.php";
$parse_only = 0;

// subor pre interpret
$interpret_file = "./interpret.py";
$int_only = 0; 

// subory xml
$jexamxml_file = "/pub/courses/ipp/jexamxml/jexamxml.jar";
$jexamcfg_file = "/pub/courses/ipp/jexamxml/options";

// premenne pre pocty testov
$test_ok = 0;
$test_bad = 0;

// echa testov
$string_echo = "";

// funkcia na kontrolu zadavanych argumentov
function check_arguments()
{
	global $recursion;
	global $src_file_list;
	global $parse_file;
	global $parse_only;
	global $interpret_file;
	global $int_only;
	global $jexamxml_file;
	global $jexamcfg_file;
	global $testlist_bonus;
	$match_bonus = '';
	$is_match = False;

	// volby s ktorymi sa moze nas skript spustat
	$option = getopt("" ,["help","directory:", "recursive", "parse-script:", "int-script:", "parse-only", "int-only", "jexamxml:", "jexamcfg:", "testlist:", "match:"]);

	if(array_key_exists('help', $option)){
		echo("php7.4 test.php --help --directory=path --recursive --parse-script=file --int-script=file --parse-only --int-only --jexamxml=file --jexamcfg=file [--testlist --match]\n");
		exit(0);
	}

	// RECURSIVE
	if(array_key_exists('recursive', $option)){
		$recursion = 1;
	}

	// DIRECTORY
	if(array_key_exists('directory', $option))
		$actual_file = $option["directory"];
	else
		$actual_file = "./";

	$directory = new RecursiveDirectoryIterator($actual_file);

	// pri rekurzii
	if($recursion == 1)
		$iterator = new RecursiveIteratorIterator($directory);
	else
		$iterator = new IteratorIterator($directory);

	$files_in_dir = new RegexIterator($iterator, '/^.+\.src/', RecursiveRegexIterator::GET_MATCH);

	foreach($files_in_dir as $file) {
		array_push($src_file_list, $file[0]);	
	}
		
	// PARSE-SCRIPT
	if(array_key_exists('parse-script', $option)){
		$parse_file = $option["parse-script"];
	}

	// INT-SCRIPT
	if(array_key_exists('int-script', $option)){
		$interpret_file = $option["int-script"];
	}

	// PARSE-ONLY
	if(array_key_exists('parse-only', $option)){
		$parse_only = 1;
	}

	// INT-ONLY
	if(array_key_exists('int-only', $option)){
		$int_only = 1;
	}

	// JEXAMXML
	if(array_key_exists('jexamxml', $option)){
		$jexamxml_file = $option["jexamxml"];
	}

	// JEXAMCFG
	if(array_key_exists('jexamcfg', $option)){
		$jexamcfg_file = $option["jexamcfg"];
	}

	// MATCH
	if(array_key_exists('match', $option)){
		$is_match = true;
		$match_bonus = $option["match"];
		if(!preg_match("/^\/.+\/[a-zA-Z]*$/i", $match_bonus)){
			exit(11);
		}
	}

	// TESTLIST
	if(array_key_exists('testlist', $option)){
		$actual_file = $option["testlist"];
		$src_file_list = [];
	
		if(!file_exists($actual_file)) {
			exit(41);
		}

		if(!($filelist = fopen($actual_file, "r"))) {
			exit(41);
		}

		while(!feof($filelist)) {
			$line = rtrim(fgets($filelist));

			if(is_dir($line)) {
				$dir = new RecursiveDirectoryIterator($line);
				$iterator = new IteratorIterator($dir);
				$files_in_dir = new RegexIterator($iterator, '/^.+\.src/', RecursiveRegexIterator::GET_MATCH);

				foreach($files_in_dir as $file) {
					
					if($is_match){
						if(preg_match($match_bonus, $file[0]))
							array_push($src_file_list, $file[0]);
					}
					else{
						array_push($src_file_list, $file[0]);	
					}
				}
			}
			else if(file_exists($line)){
				if($is_match){
					if(preg_match($match_bonus, $line))
						array_push($src_file_list, $line);
				}
				else {
					array_push($src_file_list, $line);
				}
			}
			else{
				exit(41);
			}

		}

		fclose($filelist);

	}

	if((array_key_exists('directory', $option)) && (array_key_exists('testlist', $option))){
		echo "Badly combined parameters";
		exit(10);
	}
}
 
check_arguments();

echo "<!doctype html>\n";
echo "<html lang=\"sk\">\n";
echo "<head>\n";
echo "<meta charset=\"utf-8\">\n";
echo "<title>Štatistika testov</title>\n";
echo "</head>\n";
echo "<body>\n";

foreach($src_file_list as $test_of_tests){
	$testcase = str_replace(".src", "", $test_of_tests);
	$src_trash = $testcase.".src";
	$out_trash = $testcase.".out";
	$in_trash = $testcase.".in";
	$rc_trash = $testcase.".rc";
	$where_it_goes = "what_we_got";
	$what_number_we_get = "return_code";
	$exec_number = 0;

	if($parse_only == true){

		$string_echo.= "<p> Testing $testcase: ";
		exec("php7.4 $parse_file < $src_trash > $where_it_goes", $result, $number_of_return_code);

		exec("echo $number_of_return_code > $what_number_we_get");
		exec("diff --ignore-all-space $what_number_we_get $rc_trash", $result, $number_of_return_code_diff); 

		if(!$number_of_return_code_diff){ 
			$string_echo.= "RC OK </p>";
		}
		else{
			$string_echo.= "RC FAILED </p>";
		}

		// navratova hodnota bola 0
		if(!$number_of_return_code){
			exec("java -jar $jexamxml_file $where_it_goes $out_trash final.xml $jexamcfg_file", $result, $exec_number);

			if(!$exec_number) $string_echo.= " XML OK </p>";
			else{
				$string_echo.= " XML FAILED </p>";	
			}
			//continue;
		}

		if(!$number_of_return_code_diff){
			if(!$number_of_return_code){
				if(!$exec_number){
					$test_ok += 1;
				}
				else{
					$test_bad += 1;
				}
			}
			else{
				$test_ok += 1;
			}
		}
		else{
			$test_bad += 1;
		}
	}

	else if($int_only == true){
		$string_echo.= "<p> Testing $testcase: ";
		exec("python3.8 $interpret_file --source=\"$src_trash\" --input=\"$in_trash\" > $where_it_goes", $result, $number_of_return_code);

		exec("echo $number_of_return_code > $what_number_we_get");
		exec("diff --ignore-all-space $what_number_we_get $rc_trash", $result, $number_of_return_code_diff); 

		if(!$number_of_return_code_diff) $string_echo.= "RC OK </p>";
		else{
			$string_echo.= "RC FAILED </p>";
		}

		// navratova hodnota bola 0
		if(!$number_of_return_code && file_exists($out_trash)){
			exec("diff --ignore-all-space $out_trash $where_it_goes", $result, $exec_number);
		
			if(!$exec_number){
				$string_echo.= "Output OK </p>";
			}
			else{
				$string_echo.= "Output FAILED </p>";
			}
		}
		
		if(!$number_of_return_code_diff){
			if(!$number_of_return_code){
				if(!$exec_number){
					$test_ok += 1;
				}
				else{
					$test_bad += 1;
				}
			}
			else{
				$test_ok += 1;
			}
		}
		else{
			$test_bad += 1;
		}

	}

	else if($int_only == false && $parse_only == false){
		$string_echo.= "<p> Testing $testcase: ";
		exec("php7.4 $parse_file < $src_trash > $where_it_goes", $result, $number_of_return_code);

		$where_it_went = 'intout.out';
		// navratova hodnota bola 0
		if(!$number_of_return_code){
			if(file_exists($in_trash))
				exec("python3.8 $interpret_file --source=\"$where_it_goes\" --input=\"$in_trash\" > $where_it_went", $result, $number_of_return_code_all);
			else
				exec("python3.8 $interpret_file --source=\"$where_it_goes\" > $where_it_went", $result, $number_of_return_code_all);

			if(!$number_of_return_code_all){
				if(file_exists($out_trash))
					exec("diff --ignore-all-space $out_trash $where_it_went", $result, $number_of_return_code_diff_all);
				else{
					$string_echo.= "YOUR CODE IS OK </p>";
					$test_ok += 1;
					continue;
				}

				if(!$number_of_return_code_diff_all){
					$string_echo.= "YOUR CODE IS OK </p>";
					$test_ok += 1;
				}
				else{
					$string_echo.= "Sorry, your code is wrong </p>";
					$test_bad += 1;
				}
			}
			else{
				exec("echo $number_of_return_code_all > $what_number_we_get");
				if(file_exists($rc_trash))
					exec("diff --ignore-all-space $what_number_we_get $rc_trash", $result, $number_of_return_code_diff_all);
				else {
					$string_echo.= "YOUR CODE IS OK </p>";
					$test_ok += 1;
					continue;
				}

				if(!$number_of_return_code_diff_all){
					$string_echo.= "YOUR CODE IS OK </p>";
					$test_ok += 1;
				}
				else{
					$string_echo.= "Sorry, your code is wrong </p>";
					$test_bad += 1;
				}
			}

		}
		else{
			exec("echo $number_of_return_code > $what_number_we_get");
			exec("diff --ignore-all-space $what_number_we_get $rc_trash", $result, $number_of_return_code_diff_all);

			if(!$number_of_return_code_diff_all){
				$string_echo.= "YOUR CODE IS OK </p>";
				$test_ok += 1;
			}
			else{
				$string_echo.= "Sorry, your code is wrong </p>";
				$test_bad += 1;
			}
		}
	}
}

echo "<p> Tests OK: $test_ok </p>";
echo "<p> Tests FAILED: $test_bad </p>";
echo "<br>";
echo $string_echo;
echo "</body>\n";
echo "</html>\n";


?>