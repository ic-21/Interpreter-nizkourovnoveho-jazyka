<?php
// Ivana Colnikova, xcolni00

// nastavenie zo zadania
ini_set('display_errors', 'stderr');

// define chybove stavy
const NO_HEADER = 21;
const WRONG_CODE = 22;
const ELSE_SYN_SEM_ERROR = 23;

// nastavenie pre vypis
$write_xml_output = xmlwriter_open_memory();
xmlwriter_set_indent($write_xml_output, 1);
$end = xmlwriter_set_indent_string($write_xml_output, ' ');

// zakladny vypis xml hlavicky
xmlwriter_start_document($write_xml_output, '1.0', 'UTF-8');

// kontrola zadanych argumentov
if($argc > 1)
{
	// ak spustame s HELP
	if($argv[1] == "--help" || $argv[1] == "-h")
	{
		echo("Usage: parser.php [--help | -h] < name_file\n");
		exit(0);
	}
	else
	{
		exit(10);
	}
}

// volanie funkcie pre kontrolu hlavicky
if(header_good())
{
	// udaje na vypis hlavicky
	xmlwriter_start_element($write_xml_output, 'program');
	xmlwriter_start_attribute($write_xml_output, 'language');
	xmlwriter_text($write_xml_output, 'IPPcode21');
	xmlwriter_end_attribute($write_xml_output);
}
else {
	exit(NO_HEADER);
}

// pocitadlo pouzite vo switch
$counter = 0;

// nacitavam po riadkoch
while($line = fgets(STDIN))
{
	// regex pre komentare
	$comments_out = "/^#|^(\s)+$/";
	// preskoc komentare a nove riadky
	if(preg_match($comments_out, $line)) continue;

	// najdeme odstranime komentar na riadku
	$hashtag = strpos($line, "#");
	if($hashtag != false) 
	{
			// chceme od zaciatku po #
		$line = substr($line, 0, $hashtag);	
	}

	// odstranenie konca riadku, a prazdych retazcov
	$word_from_string = explode(' ', trim($line, "\n"));
	$word_from_string = array_values(array_filter($word_from_string));

	// regex pre neterminal znaciaci premennu
	$regex_var = "/^(LF|TF|GF)@([a-zA-Z\-$!?_%&*][0-9a-zA-Z\-$!?_%&*]*)$/";

	// regex pre navestie
	$regex_label ="/^([a-zA-Z\-$!?_%&*][0-9a-zA-Z\-$!?_%&*]*)$/";

	// regex pre konstantu alebo premennu
	$regex_symb = "/^(LF|TF|GF)@([a-zA-Z\-$!?_%&*][0-9a-zA-Z\-$!?_%&*]*)$|^string@([^\s#\\\\]|(\\\\\d\d\d))*$|^int@([0-9]+|[+-][0-9]+)$|^bool@(true|false)$|^nil@nil$/";

	// regex pre typ
	$regex_type = "/^string$|^int$|^bool$|^nil$/";

	// pre pripad kedy su instrukcie pisane malym psimenom -> zmenim na velke
	$word_from_string[0] = strtoupper($word_from_string[0]);

	// instrukcie
	switch($word_from_string[0])
	{
		case 'MOVE':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}

		break;

		case 'CREATEFRAME':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako jeden
			if($numbers_parameters > 1)
			{
				if(preg_match($comments_out, $word_from_string[1]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			xmlwriter_start_element($write_xml_output, 'instruction');
			xmlwriter_start_attribute($write_xml_output, 'order');
			xmlwriter_text($write_xml_output, ++$counter);
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_start_attribute($write_xml_output, 'opcode');
			xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_end_element($write_xml_output);
		break;

		case 'PUSHFRAME':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako jeden
			if($numbers_parameters > 1)
			{
				if(preg_match($comments_out, $word_from_string[1]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			xmlwriter_start_element($write_xml_output, 'instruction');
			xmlwriter_start_attribute($write_xml_output, 'order');
			xmlwriter_text($write_xml_output, ++$counter);
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_start_attribute($write_xml_output, 'opcode');
			xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_end_element($write_xml_output);
		break;

		case 'POPFRAME':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako jeden
			if($numbers_parameters > 1)
			{
				if(preg_match($comments_out, $word_from_string[1]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			xmlwriter_start_element($write_xml_output, 'instruction');
			xmlwriter_start_attribute($write_xml_output, 'order');
			xmlwriter_text($write_xml_output, ++$counter);
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_start_attribute($write_xml_output, 'opcode');
			xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_end_element($write_xml_output);
		break;

		case 'DEFVAR':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'CALL':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <label>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_label, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "label");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'RETURN':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako jeden
			if($numbers_parameters > 1)
			{
				if(preg_match($comments_out, $word_from_string[1]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 1) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			xmlwriter_start_element($write_xml_output, 'instruction');
			xmlwriter_start_attribute($write_xml_output, 'order');
			xmlwriter_text($write_xml_output, ++$counter);
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_start_attribute($write_xml_output, 'opcode');
			xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_end_element($write_xml_output);
		break;

		case 'BREAK':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako jeden
			if($numbers_parameters > 1)
			{
				if(preg_match($comments_out, $word_from_string[1]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 1) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			xmlwriter_start_element($write_xml_output, 'instruction');
			xmlwriter_start_attribute($write_xml_output, 'order');
			xmlwriter_text($write_xml_output, ++$counter);
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_start_attribute($write_xml_output, 'opcode');
			xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
			xmlwriter_end_attribute($write_xml_output);
			xmlwriter_end_element($write_xml_output);
		break;

		case 'PUSHS':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <symb>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_symb, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[1]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'POPS':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'ADD':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;
			
		case 'SUB':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'MUL':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'IDIV':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'LT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'GT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'EQ':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;
			
		case 'AND':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'OR':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1);
				exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'NOT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'INT2CHAR':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'STRI2INT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'READ':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <type>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_type, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				
				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "type");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[2]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'WRITE':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <symb>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_symb, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[1]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'CONCAT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'STRLEN':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'GETCHAR':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'SETCHAR':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'TYPE':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <var> <symb>
			if($numbers_parameters > 3)
			{
				if(preg_match($comments_out, $word_from_string[3]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 3) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_var, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "var");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'LABEL':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <label>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_label, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "label");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'JUMP':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <label>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_label, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "label");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'JUMPIFEQ':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <label> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_label, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "label");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'JUMPIFNEQ':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <label> <symb1> <symb2>
			if($numbers_parameters > 4)
			{
				if(preg_match($comments_out, $word_from_string[4]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 4) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_label, $word_from_string[1]) && preg_match($regex_symb, $word_from_string[2]) && preg_match($regex_symb, $word_from_string[3]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				xmlwriter_text($write_xml_output, "label");
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $word_from_string[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg2');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[2]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg3');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[3]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'EXIT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <symb>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_symb, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[1]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		case 'DPRINT':
			// pocet parametrov v riadku
			$numbers_parameters = count($word_from_string);

			// ak je parametrov viac ako <symb>
			if($numbers_parameters > 2)
			{
				if(preg_match($comments_out, $word_from_string[2]) != 1)
					exit(ELSE_SYN_SEM_ERROR);
			}

			// ak tam je menej nez zadany pocet argumentov
			if($numbers_parameters < 2) 
			{
				exit(ELSE_SYN_SEM_ERROR);
			}

			if(preg_match($regex_symb, $word_from_string[1]))
			{
				xmlwriter_start_element($write_xml_output, 'instruction');
				xmlwriter_start_attribute($write_xml_output, 'order');
				xmlwriter_text($write_xml_output, ++$counter);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_start_attribute($write_xml_output, 'opcode');
				xmlwriter_text($write_xml_output, strtoupper($word_from_string[0]));
				xmlwriter_end_attribute($write_xml_output);

				xmlwriter_start_element($write_xml_output, 'arg1');
				xmlwriter_start_attribute($write_xml_output, 'type');
				$type_symb_output = what_type_of_symbol($word_from_string[1]);
				xmlwriter_text($write_xml_output, $type_symb_output[0]);
				xmlwriter_end_attribute($write_xml_output);
				xmlwriter_text($write_xml_output, $type_symb_output[1]);
				xmlwriter_end_element($write_xml_output);
				xmlwriter_end_element($write_xml_output);
			}
			else {
				exit(ELSE_SYN_SEM_ERROR);
			}
		break;

		default: exit(WRONG_CODE);
	}
}

// kontrola hlavicky
function header_good()
{
	// ak sa podari nacitat riadok	
	while(($line = fgets(STDIN)) != false)
	{
		// preskoc komentare a nove riadky
		$comments_out = "/^#|^(\s)+$/";
		if(preg_match($comments_out, $line)) continue;

		// najdeme odstranime komentar na riadku
		$hashtag = strpos($line, "#");
		if($hashtag != false) 
		{
			// chceme od zaciatku po #
			$line = substr($line, 0, $hashtag);	
		}
		
		// odstranime biele znaky z lava aj z prava
		$line = rtrim($line);
		$line = ltrim($line);

		// kontrola hlavicky 
		if($line == ".IPPcode21")
		{
			return true;
		}
		return false;
	}
	return false;
}

// funkcia na zistenie o aky symbol sa jedna
function what_type_of_symbol($word_from_string)
{
	// rozdelim maximalne na dve veci
	$type_symbol = explode('@', $word_from_string, 2); 

	// porovnanie ci ide o premennu
	if($type_symbol[0] == "GF" || $type_symbol[0] == "LF" || $type_symbol[0] == "TF")
	{
		$type_symbol[0] ="var";
		$type_symbol[1]=$word_from_string;
	}
	return $type_symbol;
}

// vysledny vypis
xmlwriter_end_element($write_xml_output);
echo xmlwriter_output_memory($write_xml_output);
exit(0);
?>