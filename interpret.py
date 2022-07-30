#Ivana Colnikova, xcolni00 

# importy
import xml.parsers.expat as XML
import getopt
import sys
import re

# globalne premenne
source_value = sys.stdin;
input_value = sys.stdin;

is_source = False
WE_HAVE_INPUT = False
INPUT_VALUES = []
is_stats = False
where_send_stats = ""
is_insts = False
is_vars = False
is_hot = False
number_executed_instructions = 0
global_counter_variables = 0
dictionary = {}
hot = 0

# funkcia pre kontrolu zadanych argumentov
def check_arguments():
	global source_value
	global input_value
	global is_source
	global WE_HAVE_INPUT
	global is_stats
	global where_send_stats
	global is_hot
	global is_vars
	global is_insts

	# kontrola parametrov s rozsirenim stats
	try:
		option, argc = getopt.getopt(sys.argv[1:], '',['help', 'source=', 'input=', 'stats=', 'insts', 'vars', 'hot'])
	except:
		exit(10)

	for opt, value in option:
		if opt == '--help':
			print("python3.8 interpret.py --help --source=file --input=file [--stats=file --insts --vars --hot]")
			exit(0)
		elif opt == '--source':
			source_value = value
			is_source = True
		elif opt == '--input':
			WE_HAVE_INPUT = True
			input_value = value
		elif opt == '--stats':
			is_stats = True;
			where_send_stats = value
		elif opt == '--insts':
			is_insts = True;
		elif opt == '--vars':
			is_vars = True;
		elif opt == '--hot':
			is_hot = True;	
		elif((opt != '--source') and (opt != '--input')):
			exit(10)
	if not option:
		exit(10)
	if not is_stats and (is_hot or is_vars or is_insts):
		exit(10)				

# ci sme dostali spravny pocet parametrov
def check_number_parameter(list_of_parameters, how_many_parameters_want):
	if how_many_parameters_want != len(list_of_parameters):
		exit(32)
	for index, x in enumerate(list_of_parameters):
		if str(index+1) not in list_of_parameters[index].order:
			exit(32) 

# triedy na jednoduchsie spracovanie xml suboru
class Argument:
	def __init__(self, arg_type, order):
		self.type = arg_type
		self.value = ''
		self.order = order 

class Instruction:
	def __init__(self, name, order):
		self.name = name
		self.order = order
		self.arguments = []

instructions = []
orders = []

# kontrola hlavicky spravnosti orderu, spravnost instrukcii(elementu); 
# spracovanie instrukcii z xml
def handle_instructions(name, attributes):
	global instructions
	global orders
	if name == 'program':
		if attributes['language'] != 'IPPcode21':
			exit(32)
	elif name == 'instruction': 
		#print('ORDER', int(attributes['order']))
		if int(attributes['order']) < 1 or int(attributes['order']) in orders:
			exit(32)
		orders.append(int(attributes['order']))
		instructions.append(Instruction(attributes['opcode'].upper(), int(attributes['order'])))
	elif 'arg' in name:
		instructions[-1].arguments.append(Argument(attributes['type'], name))
	else:
		exit(32)

# spracovanie hodnot argumentov z xml
def handle_argument_value(arg_value):
	global instructions
	if not arg_value.isspace(): 
		instructions[-1].arguments[-1].value += arg_value

# nacitanie suboru zo standardneho vstupu
def load_stdin():
	input_xml = ''
	for line in sys.stdin:
		input_xml += line
	return input_xml

# pomocna trieda na ulozenie informacii o nacitanej premennej
class FrameVariable:
	def __init__(self, var_type, var_value):
		self.variable_type = var_type
		self.variable_value = var_value

# pomocne ramce
GLOBAL_FRAME = {}
LOCAL_FRAME = []
TEMPORARY_FRAME = {}
FRAME_CREATED = False
stack = []

# vracia prislusny ramec premennej
def get_frame(frame):
	global GLOBAL_FRAME
	global LOCAL_FRAME
	global TEMPORARY_FRAME
	global FRAME_CREATED
	if "GF" == frame: return GLOBAL_FRAME
	if "TF" == frame: 
		if FRAME_CREATED:
			return TEMPORARY_FRAME
		else:
			exit(55)
	if "LF" == frame: 
		if LOCAL_FRAME:
			return LOCAL_FRAME[-1] 
		else:
			exit(55)

# kontrola redefinicie premennych
def check_for_errors_in_variable(variable):
	global GLOBAL_FRAME
	global LOCAL_FRAME
	global TEMPORARY_FRAME
	global FRAME_CREATED


	frame, variable_name = variable.split("@")

	if not variable_name in get_frame(frame):
		exit(54)

# pomocou split rozdelenie argumentov
def split_instruction(argument):
	return argument.split("@")

# vracia typ nacitaneho symbolu
def get_symbol_type(symbol):
	if symbol.type == "var": 
		check_for_errors_in_variable(symbol.value)
		frame, name = split_instruction(symbol.value)
		return get_frame(frame)[name].variable_type
	else:
		return symbol.type

# kontrola aky typ symbolu mame
def check_symbol_type(symbol, var_type):
	if symbol.type == "var":
		if get_symbol_type(symbol) != var_type:
			exit(53)
	else:
		if symbol.type != var_type:
			exit(53)	

# vracia hodnotu symbolu
def get_symbol_value(symbol):
	if symbol.type == "var": 
		check_for_errors_in_variable(symbol.value)
		var_type = get_symbol_type(symbol)
		frame, name = split_instruction(symbol.value)
		if var_type == "int": return int(get_frame(frame)[name].variable_value)
		elif var_type == "string" or var_type == "bool" or var_type == "nil": return str(get_frame(frame)[name].variable_value)
		else: 
			exit(32)
	else:
		var_type = get_symbol_type(symbol)
		if var_type == "int": return int(symbol.value)
		elif var_type in ["string", "bool", "nil", "type"]: return str(symbol.value)
		else: 
			exit(32)

# funkcia push na stack s rozkladom argumentu
def push_to_stack_argument(argument):
	global stack

	vtype = get_symbol_type(argument)

	if vtype == "undef":
		exit(56)

	value = get_symbol_value(argument)

	if vtype == "int":
		stack.append(FrameVariable(vtype,int(value)))
	else:	
		stack.append(FrameVariable(vtype,value))

# funkcia push na stack hned pridavam parametre
def push_to_stack_parameters(type,value):
	global stack

	if type == "int":
		stack.append(FrameVariable(type,int(value)))
	else:	
		stack.append(FrameVariable(type,value))

# funkcia pop zo stack
def pop_from_stack():
	global stack

	if not stack:
		exit(56)

	return stack.pop()

def check_stack_type(arg, var_type):
	if arg.variable_type != var_type:
		exit(53)

def check_for_undef_value(argument):
	argtype = get_symbol_type(argument)
	if argtype == "undef":
		exit(56)

# INSTRUKCIE
def process_instruction(current_instruction):
	global GLOBAL_FRAME
	global LOCAL_FRAME
	global TEMPORARY_FRAME
	global FRAME_CREATED
	global WE_HAVE_INPUT
	global INPUT_VALUES
	global number_executed_instructions
	global stack

	if current_instruction.name == "CREATEFRAME":
		FRAME_CREATED = True
		TEMPORARY_FRAME = {}

	elif current_instruction.name == "PUSHFRAME":
		if FRAME_CREATED == True:
			LOCAL_FRAME.append(TEMPORARY_FRAME.copy())
			FRAME_CREATED = False
		else:
			exit(55) 

	elif current_instruction.name == "DEFVAR":
		check_number_parameter(current_instruction.arguments, 1)
		arg1 = current_instruction.arguments[0]
		frame, var_name = split_instruction(arg1.value)
		
		if var_name in get_frame(frame):
			exit(52)

		get_frame(frame)[var_name] = FrameVariable("undef", "nil")

	elif current_instruction.name == "POPFRAME":
		if LOCAL_FRAME:
			FRAME_CREATED = True
			TEMPORARY_FRAME = LOCAL_FRAME.pop()
		else:
			exit(55)

	elif current_instruction.name == "MOVE":
		check_number_parameter(current_instruction.arguments, 2)
		arg1 = current_instruction.arguments[0]
		arg2 = current_instruction.arguments[1]

		check_for_undef_value(arg2)

		frame, variable_name = split_instruction(arg1.value)

		check_for_errors_in_variable(arg1.value)
		if arg2.type == "var":
			check_for_errors_in_variable(arg2.value)
			frame2, variable_name2 = split_instruction(arg2.value)
			get_frame(frame)[variable_name] = get_frame(frame2)[variable_name2]
		else:
			get_frame(frame)[variable_name] = FrameVariable(arg2.type, get_symbol_value(arg2))

	elif current_instruction.name in ["ADD", "SUB", "MUL", "IDIV"]:
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		var_frame, var_name = split_instruction(var.value)

		# vkladam tam prvykrat hodnotu
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "int"


		check_symbol_type(var, "int")
		check_symbol_type(symb1, "int")
		check_symbol_type(symb2, "int")

		try:
			s1_value = int(get_symbol_value(symb1))
			s2_value = int(get_symbol_value(symb2))
		except:
			exit(32)
		
		if current_instruction.name == "ADD":
			get_frame(var_frame)[var_name].variable_value = int(s1_value+s2_value)
		elif current_instruction.name == "SUB":
			get_frame(var_frame)[var_name].variable_value = int(s1_value-s2_value)
		elif current_instruction.name == "MUL":
			get_frame(var_frame)[var_name].variable_value = int(s1_value*s2_value)
		elif current_instruction.name == "IDIV":
			if s2_value == 0:
				exit(57)
			get_frame(var_frame)[var_name].variable_value = int(s1_value/s2_value)

	elif current_instruction.name == "PUSHS":
		check_number_parameter(current_instruction.arguments, 1)

		var = current_instruction.arguments[0]
		
		push_to_stack_argument(var)

	elif current_instruction.name == "POPS":
		check_number_parameter(current_instruction.arguments, 1)

		argument = current_instruction.arguments[0]
		var_frame,var_name = split_instruction(argument.value)

		this_is_where_we_pop = pop_from_stack()

		# vkladam tam prvykrat hodnotu
		if get_symbol_type(argument) == "undef":
			get_frame(var_frame)[var_name].variable_type = this_is_where_we_pop.variable_type

		get_frame(var_frame)[var_name] = this_is_where_we_pop


	elif current_instruction.name in ["ADDS", "SUBS", "MULS", "IDIVS"]:
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		check_stack_type(symb1, "int")
		check_stack_type(symb2, "int")

		try:
			s1_value = int(symb1.variable_value)
			s2_value = int(symb2.variable_value)
		except:
			exit(32)
		
		if current_instruction.name == "ADDS":
			push_to_stack_parameters("int",int(s1_value+s2_value))
		elif current_instruction.name == "SUBS":
			push_to_stack_parameters("int",int(s1_value-s2_value))
		elif current_instruction.name == "MULS":
			push_to_stack_parameters("int",int(s1_value*s2_value))
		elif current_instruction.name == "IDIVS":
			if s2_value == 0:
				exit(57)
			push_to_stack_parameters("int",int(s1_value/s2_value))	

	elif current_instruction.name in ["LTS", "GTS", "EQS"]:
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		if current_instruction.name == "LTS":
			if symb1.variable_type != symb2.variable_type: 
				exit(53)
			if symb1.variable_type == "string" or symb1.variable_type == "bool":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb1.variable_value)
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb2.variable_value) 
				if regex < regex2:
					push_to_stack_parameters("bool","true")
				else:
					push_to_stack_parameters("bool","false")

			elif int(symb1.variable_value) < int(symb2.variable_value):
				push_to_stack_parameters("bool","true")
			else:
				push_to_stack_parameters("bool","false")
		elif current_instruction.name == "GTS":
			if symb1.variable_type != symb2.variable_type: 
				exit(53)
			if symb1.variable_type == "string" or symb1.variable_type == "bool":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb1.variable_value)
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb2.variable_value)
				if regex > regex2:
					push_to_stack_parameters("bool","true")
				else:
					push_to_stack_parameters("bool","false")

			elif int(symb1.variable_value) > int(symb2.variable_value):
				push_to_stack_parameters("bool","true")
			else:
				push_to_stack_parameters("bool","false")
		elif current_instruction.name == "EQS":
			if symb1.variable_type != symb2.variable_type and (symb1.variable_type != "nil" and symb2.variable_type != "nil"):
				exit(53)
			try:
				if symb1.variable_type == "string" or symb1.variable_type == "bool":
					regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb1.variable_value)
					regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb2.variable_value)
					if regex == regex2:
						push_to_stack_parameters("bool","true")
					else:
						push_to_stack_parameters("bool","false")

				elif symb1.variable_type == "nil" and symb2.variable_type == "nil":
					push_to_stack_parameters("bool","true")
				elif int(symb1.variable_value) == int(symb2.variable_value):
					push_to_stack_parameters("bool","true")
				else:
					push_to_stack_parameters("bool","false")
			except:
				push_to_stack_parameters("bool","false")

	elif current_instruction.name == "CLEARS":
		stack = []			

	elif current_instruction.name in ["LT", "GT", "EQ"]:
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "bool"

		if get_symbol_type(var) != "bool":
			exit(53)

		if current_instruction.name == "LT":
			if get_symbol_type(symb1) != get_symbol_type(symb2):
				exit(53)
			try:
				if get_symbol_type(symb1) == "string" or get_symbol_type(symb1) == "bool":
					regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
					regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2)) 
					if regex < regex2:
						get_frame(var_frame)[var_name].variable_value = "true"
					else:
						get_frame(var_frame)[var_name].variable_value = "false"

			
				elif int(get_symbol_value(symb1)) < int(get_symbol_value(symb2)):
					get_frame(var_frame)[var_name].variable_value = "true"
				else:
					get_frame(var_frame)[var_name].variable_value = "false"
			except:
				exit(53)

		elif current_instruction.name == "GT":
			if get_symbol_type(symb1) != get_symbol_type(symb2):
				exit(53)

			try:
				if get_symbol_type(symb1) == "string" or get_symbol_type(symb1) == "bool":
					regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
					regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2)) 
					if regex > regex2:
						get_frame(var_frame)[var_name].variable_value = "true"
					else:
						get_frame(var_frame)[var_name].variable_value = "false"

				elif int(get_symbol_value(symb1)) > int(get_symbol_value(symb2)):
					get_frame(var_frame)[var_name].variable_value = "true"
				else:
					get_frame(var_frame)[var_name].variable_value = "false"
			except:
				exit(53)
		elif current_instruction.name == "EQ":

			if get_symbol_type(symb1) != get_symbol_type(symb2) and get_symbol_type(symb1) != "nil" and get_symbol_type(symb2) != "nil":
				exit(53)

			if get_symbol_type(symb1) == "string" or get_symbol_type(symb1) == "bool":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2)) 
				if regex == regex2:
					get_frame(var_frame)[var_name].variable_value = "true"
				else:
					get_frame(var_frame)[var_name].variable_value = "false"
			
			elif get_symbol_type(symb1) == "nil" or get_symbol_type(symb2) == "nil":
				if get_symbol_type(symb1) == get_symbol_type(symb2):
					get_frame(var_frame)[var_name].variable_value = "true"
				else:
					get_frame(var_frame)[var_name].variable_value = "false"
			elif int(get_symbol_value(symb1)) == int(get_symbol_value(symb2)):
				get_frame(var_frame)[var_name].variable_value = "true"
			else:
				get_frame(var_frame)[var_name].variable_value = "false"

				
	elif current_instruction.name == "NOT":
		check_number_parameter(current_instruction.arguments, 2)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]

		check_for_undef_value(symb1)
		
		var_frame, var_name = split_instruction(var.value)
		get_symbol_type(var)
		get_frame(var_frame)[var_name].variable_type = "bool"

		if get_symbol_type(symb1) != "bool":
			exit(53)
	
		if get_symbol_value(symb1) == "true":
			get_frame(var_frame)[var_name].variable_value = "false"
		else:
			get_frame(var_frame)[var_name].variable_value = "true"

	elif current_instruction.name == "NOTS":
		symb1 = pop_from_stack()

		check_stack_type(symb1,"bool")
	
		if symb1.variable_value == "true":
			push_to_stack_parameters("bool", "false")
		else:
			push_to_stack_parameters("bool", "true")		

	elif current_instruction.name in ["AND", "OR"]:
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "bool"

		if get_symbol_type(var) != "bool" or get_symbol_type(symb1) != "bool" or get_symbol_type(symb2) != "bool":
			exit(53)

		if current_instruction.name == "AND":
			if get_symbol_value(symb1) == "true" and get_symbol_value(symb2) == "true":
				get_frame(var_frame)[var_name].variable_value = "true"
			else:
				get_frame(var_frame)[var_name].variable_value = "false"	

		if current_instruction.name == "OR":
			if get_symbol_value(symb1) == "true" or get_symbol_value(symb2) == "true":
				get_frame(var_frame)[var_name].variable_value = "true"
			else:
				get_frame(var_frame)[var_name].variable_value = "false"

	elif current_instruction.name in ["ANDS", "ORS"]:
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		if symb1.variable_type not in ["bool", "nil"] or symb2.variable_type not in ["bool", "nil"]:
			exit(53)

		if current_instruction.name == "ANDS":
			if symb1.variable_value == "true" and symb2.variable_value == "true":
				push_to_stack_parameters("bool", "true")
			else:
				push_to_stack_parameters("bool", "false")	

		if current_instruction.name == "ORS":
			if symb1.variable_value == "true" or symb2.variable_value == "true":
				push_to_stack_parameters("bool", "true")
			else:
				push_to_stack_parameters("bool", "false")

	elif current_instruction.name == "INT2CHAR":
		check_number_parameter(current_instruction.arguments, 2)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]

		check_for_undef_value(symb1)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "string"

		if get_symbol_type(var) != "string" or get_symbol_type(symb1) != "int":
			exit(53)

		try:
			 result = chr(int(get_symbol_value(symb1)))
		except:
			exit(58)

		get_frame(var_frame)[var_name].variable_value = result

	elif current_instruction.name == "INT2CHARS":
		symb1 = pop_from_stack()

		if symb1.variable_type != "int":
			exit(53)

		try:
			 result = chr(int(symb1.variable_value))
		except:
			exit(58)

		push_to_stack_parameters("string", result)

	elif current_instruction.name == "STRI2INTS":
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		if symb1.variable_type != "string" or symb2.variable_type != "int":
			exit(53)

		try:
			result = ord(symb1.variable_value[int(symb2.variable_value)])
		except:
			exit(58)

		push_to_stack_parameters("int", result)

	elif current_instruction.name == "STRI2INT":
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "int"

		if get_symbol_type(var) != "int" or get_symbol_type(symb1) != "string" or get_symbol_type(symb2) != "int":
			exit(53)

		try:
			result = ord(get_symbol_value(symb1)[int(get_symbol_value(symb2))])
		except:
			exit(58)

		get_frame(var_frame)[var_name].variable_value = result

	elif current_instruction.name == "READ":
		check_number_parameter(current_instruction.arguments, 2)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]

		check_for_undef_value(symb1)
		
		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = get_symbol_value(symb1)

		input_type = get_symbol_value(symb1)

		if WE_HAVE_INPUT:
			if not INPUT_VALUES:
				value = "nil"
			else:
				value = INPUT_VALUES.pop(0)
		else:
			value = sys.stdin.readline()

		if value == "nil":
			get_frame(var_frame)[var_name] = FrameVariable("nil", "nil")
		elif input_type == "int":
			try:
				result = int(value)
				get_frame(var_frame)[var_name] = FrameVariable("int", result)
			except:
				get_frame(var_frame)[var_name] = FrameVariable("nil", "nil")

		elif input_type == "string":
			try:
				result = str(value)
				get_frame(var_frame)[var_name] = FrameVariable("string", result)
			except:
				get_frame(var_frame)[var_name] = FrameVariable("nil", "nil")

		elif input_type == "bool":
			if value.lower() == "true":
				get_frame(var_frame)[var_name] = FrameVariable("bool", "true")
			else:
				get_frame(var_frame)[var_name] = FrameVariable("bool","false")
		else:
			get_frame(var_frame)[var_name] = FrameVariable("nil", "nil")

	elif current_instruction.name == "WRITE":
		check_number_parameter(current_instruction.arguments, 1)
		symb1 = current_instruction.arguments[0]

		check_for_undef_value(symb1)

		if get_symbol_type(symb1) == "bool":
			print(get_symbol_value(symb1), end='')

		elif get_symbol_type(symb1) == "string":
			regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
			print(regex, end='')

		elif get_symbol_type(symb1) == "nil":
			print(end='')

		elif get_symbol_type(symb1) == "int":
			print(get_symbol_value(symb1), end='')

	elif current_instruction.name == "DPRINT":
		number_executed_instructions -= 1
		check_number_parameter(current_instruction.arguments, 1)
		symb1 = current_instruction.arguments[0]

		check_for_undef_value(symb1)

		if get_symbol_type(symb1) == "bool":
			print(get_symbol_value(symb1), end='', file=sys.stderr)

		elif get_symbol_type(symb1) == "string":
			regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
			print(regex, end='', file=sys.stderr)

		elif get_symbol_type(symb1) == "nil":
			print(end='', file=sys.stderr)

		elif get_symbol_type(symb1) == "int":
			print(get_symbol_value(symb1), end='', file=sys.stderr)

	elif current_instruction.name == "CONCAT":
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]			

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "string"

		if get_symbol_type(symb1) == "string" and get_symbol_type(symb2) == "string" and get_symbol_type(var) == "string":
			get_frame(var_frame)[var_name].variable_value = get_symbol_value(symb1) + get_symbol_value(symb2)
		else:
			exit(53)	

	elif current_instruction.name == "STRLEN":
		check_number_parameter(current_instruction.arguments, 2)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]

		check_for_undef_value(symb1)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "int"

		if get_symbol_type(symb1) == "string" and get_symbol_type(var) == "int":
			get_frame(var_frame)[var_name].variable_value = len(get_symbol_value(symb1))
		else:
			exit(53)

	elif current_instruction.name == "GETCHAR":
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			get_frame(var_frame)[var_name].variable_type = "string"

		if get_symbol_type(symb1) == "string" and get_symbol_type(symb2) == "int" and get_symbol_type(var) == "string":
			if get_symbol_value(symb2) < len(get_symbol_value(symb1)) and get_symbol_value(symb2) > -1:
				get_frame(var_frame)[var_name].variable_value = get_symbol_value(symb1)[get_symbol_value(symb2)]
			else:
				exit(58)	

	elif current_instruction.name == "SETCHAR":
		check_number_parameter(current_instruction.arguments, 3)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		# prvykrat sa pracuje s danou premennou
		var_frame, var_name = split_instruction(var.value)
		if get_symbol_type(var) == "undef":
			exit(56)

		
		if get_symbol_type(symb2) == "string" and get_symbol_type(symb1) == "int" and get_symbol_type(var) == "string":
			if get_symbol_value(symb1) < len(get_symbol_value(var)) and get_symbol_value(symb1) > -1 and get_symbol_value(symb2):
				value_of_var = list(get_symbol_value(var))

				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2))

				value_of_var[get_symbol_value(symb1)] = list(regex)[0]
				get_frame(var_frame)[var_name].variable_value = ''.join(value_of_var)
			else:
				exit(58)	
		else:
			exit(53)
		
	elif current_instruction.name == "TYPE":
		check_number_parameter(current_instruction.arguments, 2)
		var = current_instruction.arguments[0]
		symb1 = current_instruction.arguments[1]

		get_symbol_type(var)

		if get_symbol_type(symb1) == "undef":
			var_frame, var_name = split_instruction(var.value)
			get_frame(var_frame)[var_name].variable_value = ""
		else:
			var_frame, var_name = split_instruction(var.value)
			get_frame(var_frame)[var_name].variable_value = get_symbol_type(symb1)

		get_frame(var_frame)[var_name].variable_type = "string"
	else:
		exit(32)											

# funkcia pre vars v stats
def count_every_variables():
	global GLOBAL_FRAME
	global LOCAL_FRAME
	global TEMPORARY_FRAME
	global FRAME_CREATED
	global global_counter_variables

	local_counter_variables = 0

	for x in GLOBAL_FRAME:
		if GLOBAL_FRAME[x].variable_type != 'undef':
			local_counter_variables += 1
	if local_counter_variables > global_counter_variables:
		global_counter_variables = local_counter_variables
	
	local_counter_variables = 0

	if LOCAL_FRAME:
		for x in LOCAL_FRAME[-1]:
			if LOCAL_FRAME[-1][x].variable_type != 'undef':
				local_counter_variables += 1
	if local_counter_variables > global_counter_variables:
		global_counter_variables = local_counter_variables

	local_counter_variables = 0

	if FRAME_CREATED:
		for x in TEMPORARY_FRAME:
			if TEMPORARY_FRAME[x].variable_type != 'undef':
				local_counter_variables += 1
	if local_counter_variables > global_counter_variables:
		global_counter_variables = local_counter_variables
		
# stastistika '''
def statistic():
	global is_stats
	global where_send_stats
	global is_hot
	global is_vars
	global is_insts
	global global_counter_variables
	global number_executed_instructions
	global hot

	if (is_stats):
		with open(where_send_stats, 'w') as statsfile:
			if is_insts:
				statsfile.write(str(number_executed_instructions)+ '\n')
			if is_vars:
				statsfile.write(str(global_counter_variables)+ '\n')
			if is_hot:
				statsfile.write(str(hot)+ '\n')

# najviackrat pouzita instrukcia HOT
def most_use_instruction(current_instruction):
	global dictionary
	global hot

	# instrukciu dame do slovnika alebo pripocitame vyskyt
	if not current_instruction.name in dictionary:
		dictionary[current_instruction.name] = (1,current_instruction.order)

	if current_instruction.name in dictionary:
		number, order = dictionary[current_instruction.name]
		if order > current_instruction.order:
			order = current_instruction.order
			order = current_instruction.order
		dictionary[current_instruction.name] = (number + 1,order)	

	# zisti najviac pouzitu instrukciu
	biggest_number = 0
	smallest_order = 0

	for name in dictionary:
		n, o = dictionary[name]
		if n > biggest_number:
			biggest_number = n
			smallest_order = o
		elif n == biggest_number:
			if o < smallest_order:
				smallest_order = o
	hot = smallest_order			

# zavolame kontrolu argumentov
check_arguments()

# spracujeme vstupny xml subor
xml_parser = XML.ParserCreate()
xml_parser.StartElementHandler = handle_instructions
xml_parser.CharacterDataHandler = handle_argument_value

# odkial mam nacitat
try:
	if is_source:
		with open(source_value, "rb") as file:
			xml_parser.ParseFile(file)
	else:
		source_value = load_stdin()
		xml_parser.Parse(source_value)
except IndexError:
	exit(32)
except XML.ExpatError:
	exit(31)
except:
	exit(32)

# ak sme dostali subor nacitame ho
if WE_HAVE_INPUT:
	with open(input_value) as file:
		for line in file:
			INPUT_VALUES.append(line.rstrip())

# zasobnik volania
call_stack_buffer = []

# zoradenie instrukcii argumentov
instructions.sort(key=lambda x: x.order, reverse=False)

for i in instructions:
	i.arguments.sort(key=lambda d: d.order, reverse=False)

# najdeme label, najdeme duplicity
labels = [x for x in instructions if (x.name == "LABEL")]
buffer_labels = []
for label in labels:
	if label.arguments[0].value in buffer_labels:
		exit(52)
	buffer_labels.append(label.arguments[0].value)

# index prave vykonavanej instrukcie
instruction_counter = 0
# nekonecny cyklus
while True:
	count_every_variables()

	if instruction_counter >= len(instructions):
		break
		
	# zistim si dalsiu instruckciu
	current_instruction = instructions[instruction_counter]
	number_executed_instructions += 1
	most_use_instruction(current_instruction)

	# kontrola nazvu instrukcii
	if current_instruction.name == "LABEL":
		number_executed_instructions -= 1
		instruction_counter = instruction_counter + 1
		continue 

	if current_instruction.name == "CALL":
		call_stack_buffer.append(instruction_counter+1)
		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if not called:
			exit(52)
		instruction_counter = instructions.index(called[0])+1
		continue

	if current_instruction.name == "RETURN":
		if not call_stack_buffer:
			exit(56)
		instruction_counter = call_stack_buffer.pop()
		continue

	if current_instruction.name == "EXIT":
		symb1 = current_instruction.arguments[0]
		
		check_for_undef_value(symb1)
		check_symbol_type(symb1,"int")

		#try:
		if int(get_symbol_value(symb1)) > -1 and int(get_symbol_value(symb1)) < 50:
			statistic()
			exit(int(get_symbol_value(symb1)))
		else:
			exit(57)

	if current_instruction.name == "JUMP":
		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if called:
			instruction_counter = instructions.index(called[0])+1
		else:
			exit(52)
		continue

	if current_instruction.name == "JUMPIFEQ":
		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if not called:
			exit(52)

		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		if get_symbol_type(symb1) == get_symbol_type(symb2) or get_symbol_value(symb1) == "nil" or get_symbol_value(symb2) == "nil":
			if get_symbol_type(symb1) == "string":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2)) 

				if regex == regex2:
					instruction_counter = instructions.index(called[0])+1
					continue
				else:
					instruction_counter = instruction_counter + 1
					continue

			if get_symbol_value(symb1) == get_symbol_value(symb2):
				instruction_counter = instructions.index(called[0])+1
				continue
			else:
				instruction_counter = instruction_counter + 1
				continue
		else:
			exit(53)

	if current_instruction.name == "JUMPIFEQS":
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if not called:
			exit(52)

		if symb1.variable_type == symb2.variable_type or symb1.variable_value == "nil" or symb2.variable_value == "nil":
			if symb1.variable_type == "string":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb1.variable_value)
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb2.variable_value) 
				if regex == regex2:
					instruction_counter = instructions.index(called[0])+1
					continue
				else:
					instruction_counter = instruction_counter + 1
					continue

			if symb1.variable_value == symb2.variable_value:
				instruction_counter = instructions.index(called[0])+1
				continue
			else:
				instruction_counter = instruction_counter + 1
				continue
		else:
			exit(53)	

	if current_instruction.name == "JUMPIFNEQS":
		symb2 = pop_from_stack()
		symb1 = pop_from_stack()

		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if not called:
			exit(52)

		if symb1.variable_type == symb2.variable_type or symb1.variable_value == "nil" or symb2.variable_value == "nil":
			if symb1.variable_type == "string":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb1.variable_value)
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), symb2.variable_value) 
				if regex != regex2:
					instruction_counter = instructions.index(called[0])+1
					continue
				else:
					instruction_counter = instruction_counter + 1
					continue

			if symb1.variable_value != symb2.variable_value:
				instruction_counter = instructions.index(called[0])+1
				continue
			else:
				instruction_counter = instruction_counter + 1
				continue
		else:
			exit(53)	

	if current_instruction.name == "JUMPIFNEQ":
		called = [x for x in instructions if (x.name == "LABEL" and x.arguments[0].value == current_instruction.arguments[0].value)]
		if not called:
			exit(52)

		symb1 = current_instruction.arguments[1]
		symb2 = current_instruction.arguments[2]

		check_for_undef_value(symb1)
		check_for_undef_value(symb2)

		if get_symbol_type(symb1) == get_symbol_type(symb2) or get_symbol_value(symb1) == "nil" or get_symbol_value(symb2) == "nil":
			if get_symbol_type(symb1) == "string":
				regex = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb1))
				regex2 = re.sub(r'\\(\d\d\d)', lambda what_we_want_match: chr(int(what_we_want_match.group(1))), get_symbol_value(symb2)) 

				if regex != regex2:
					instruction_counter = instructions.index(called[0])+1
					continue
				else:
					instruction_counter = instruction_counter + 1
					continue

			if get_symbol_value(symb1) != get_symbol_value(symb2):
				instruction_counter = instructions.index(called[0])+1
				continue
			else:
				instruction_counter = instruction_counter + 1
				continue
		else:
			exit(53)

	if current_instruction.name == "BREAK":
		number_executed_instructions -= 1
		print("---GLOBAL_FRAME---")
		for x in GLOBAL_FRAME:
			print(x, GLOBAL_FRAME[x].variable_type, GLOBAL_FRAME[x].variable_value)

		print("---LOCAL_FRAME---")
		for x in LOCAL_FRAME:
			for i in x:
				print(i, x[i].variable_type, x[i].variable_value)

		print(FRAME_CREATED)
		print("---TEMPORARY_FRAME---")
		for x in TEMPORARY_FRAME:
			print(x, TEMPORARY_FRAME[x].variable_type, TEMPORARY_FRAME[x].variable_value)
		continue;


	# spracuj instrukciu
	process_instruction(current_instruction)

	instruction_counter = instruction_counter + 1
statistic()
