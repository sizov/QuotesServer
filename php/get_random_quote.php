<?php
	/* CONSTANTS */
	$AMOUNT_QUOTES_IN_SET = 6;
	$AMOUNT_ORIGINS_TO_CHOOSE = 4;
	
	/* info & error codes */
	$INFO_CODE_SET_ENDED = 0;
	$INFO_CODE_NO_MORE_UNIQUE_QUOTES = 1;
	
	session_start(); 	

	/* get quote type*/
	$origin_type_id = urldecode($_GET["origin_type_id"]);
	
	/* connect to the db */
	$link = mysql_connect('localhost','root','281182') or die('Cannot connect to the DB');
	
	/* select data base */
	mysql_select_db('quotes',$link) or die('Cannot select the DB');

	/* if user was NOT already asked any questions or restart was launched*/
	if (!isset($_SESSION['asked_quotes_IDs']) || isset($_GET['restart']) && $_GET["restart"] === "1"){		
		$_SESSION['asked_quotes_IDs']= array();
		$query = "	
				SELECT *
				FROM `quotes`
				WHERE origin_id	IN (
										SELECT id
										FROM `quote_origins`
										WHERE type_id = '".$origin_type_id."'
									);
				";			
	}	
	
	/* if user did not answered any questions, but we sent him them OR if he did not answer
	to last asked question - ask same again.*/			
	else if(isset($_SESSION['last_asked_quote_text']) &&
		isset($_SESSION['last_asked_origins_to_choose_from']) &&
		isset($_SESSION['asked_quotes_IDs']) &&
		(!isset($_SESSION['last_answered_quote_text']) ||
		$_SESSION['last_answered_quote_text'] <> $_SESSION['last_asked_quote_text']	)){				
		
		sendQuestionSet($_SESSION['last_asked_quote_text'],
						$_SESSION['last_asked_origins_to_choose_from'],
						$_SESSION['asked_quotes_IDs'],
						$AMOUNT_QUOTES_IN_SET);
									
		stopExecution();
	}		
	
	/* if user was already asked some questions */
	else{
		$asked_quotes_IDs = $_SESSION['asked_quotes_IDs'];
		
		/* if set of quotes questions has ended - exit with info message */
		if(count($asked_quotes_IDs) == $AMOUNT_QUOTES_IN_SET){		
			//require('fbmain.php');
			
		
			echo json_encode(array('info'=>$INFO_CODE_SET_ENDED));
			stopExecution();
		}
		
		$asked_quotes_IDs_string = "(".implode(',', $asked_quotes_IDs).")";
		$query = "
				SELECT *
				FROM `quotes`
				WHERE id NOT IN ".$asked_quotes_IDs_string."
				AND
				origin_id IN (
								SELECT id
								FROM `quote_origins`
								WHERE type_id = '".$origin_type_id."'
							);
				";					
	}
									
	$quotes = mysql_query($query,$link) or die('Errant query:  '.$query);
	
	/* construct quotes array */
	$quotes_list = array();
	if(mysql_num_rows($quotes)) {
		while($quote = mysql_fetch_assoc($quotes)) {		
			$quotes_list[] = $quote;
		}
	}	
	/* if there are no more unique quotes to show - exit with appropriate error */
	else{
		echo json_encode(array('info'=>$ERROR_CODE_NO_MORE_UNIQUE_QUOTES));
		stopExecution();
	}
	
	/* take random key and random quote text */
	$rand_key = array_rand($quotes_list);		
	$random_quote_text = $quotes_list[$rand_key]["quote_text"];
	
	/* take correct answer (origin) */
	$query = "SELECT `origin_text` FROM `quote_origins` WHERE id = '".$quotes_list[$rand_key]["origin_id"]."';";			
	$correct_origins = mysql_query($query,$link) or die('Errant query:  '.$query);
	$correct_origin_text = mysql_fetch_array($correct_origins, MYSQL_NUM);
	
	/* select origins by provided type*/
	$query = "SELECT `origin_text` FROM `quote_origins` WHERE type_id = '".$origin_type_id."';";			
	$origins = mysql_query($query,$link) or die('Errant query:  '.$query);	
	
	/* construct all origins array */
	$all_origins_array = array();
	if(mysql_num_rows($origins)) {
		while($origin = mysql_fetch_array($origins, MYSQL_NUM)) {		
			$all_origins_array[] = $origin[0];
		}
	}	
	
	/* get random elements */
	$origins_to_choose_from = array();
	if(count($all_origins_array) >= $AMOUNT_ORIGINS_TO_CHOOSE){
		$rand_keys = array_rand($all_origins_array, $AMOUNT_ORIGINS_TO_CHOOSE);
		for($i = 0; $i < count($rand_keys); $i++){
			$origins_to_choose_from[] = $all_origins_array[$rand_keys[$i]];
		}
	}	
	else{
		$origins_to_choose_from = $all_origins_array;		
	}
		
	//put correct answer to set	
	if(!in_array($correct_origin_text[0], $origins_to_choose_from)) {
		$origins_to_choose_from[0] = $correct_origin_text[0];
	}
	shuffle($origins_to_choose_from);
			
	$_SESSION['last_asked_quote_text'] = $random_quote_text;		
	$_SESSION['last_asked_origins_to_choose_from'] = $origins_to_choose_from;		
	$_SESSION['asked_quotes_IDs'][] = $quotes_list[$rand_key]["id"];
	
	sendQuestionSet($_SESSION['last_asked_quote_text'],
					$_SESSION['last_asked_origins_to_choose_from'],
					$_SESSION['asked_quotes_IDs'],
					$AMOUNT_QUOTES_IN_SET);
	
	stopExecution();
	
	/* =========================================================================================================== */
	/* =========================================================================================================== */
	
	function sendQuestionSet($random_quote_text, $origins_to_choose_from, $asked_quotes_IDs, $AMOUNT_QUOTES_IN_SET){
		$json_data = array ('quote'=>$random_quote_text,
							'origins'=>$origins_to_choose_from,
							'quotesAsked'=>count($asked_quotes_IDs),
							'quotesInSet'=>$AMOUNT_QUOTES_IN_SET);
		
		/* output to JSON */
		header('Content-type: application/json');		
		echo json_encode($json_data);	
	}	
	
	function stopExecution(){
		/* disconnect from the db */
		@mysql_close($link);
		
		exit();
	}
	
?>