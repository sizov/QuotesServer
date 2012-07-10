<?php
	/* get result type*/
	$quote_text = urldecode($_POST["quote_text"]); 
	$origin_answer =  urldecode($_POST["origin"]);
	
	session_start(); 	
	
	/* connect to the db */
	$link = mysql_connect('localhost','root','281182') or die('Cannot connect to the DB');
	
	/* select data base */
	mysql_select_db('quotes',$link) or die('Cannot select the DB');
	
	/* select quotes list by provided type*/
	$query = "SELECT `origin_text` FROM `quote_origins` WHERE id IN (SELECT `origin_id` FROM `quotes` WHERE quote_text = '".$quote_text."');";				
	$origins = mysql_query($query,$link) or die('Errant query:  '.$query);
	
	/* array of answers */
	$answers_array = array();
	if(mysql_num_rows($origins)) {
		$_SESSION['last_answered_quote_text'] = $quote_text;
	
		while($origin = mysql_fetch_array($origins, MYSQL_NUM)) {		
			$answers_array[] = $origin[0];
		}
		if(in_array($origin_answer, $answers_array)){			
			echo json_encode(array('isUserAnswerCorrect'=>true, 'allCorrectAnswers'=>$answers_array));
		}
		else{
			echo json_encode(array('isUserAnswerCorrect'=>false, 'allCorrectAnswers'=>$answers_array));
		}
	}	
	else{
		echo json_encode(array('error'=>'no correct anwser in database'));
	}
	
	/* disconnect from the db */
	@mysql_close($link);	
?>