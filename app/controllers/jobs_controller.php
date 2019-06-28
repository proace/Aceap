<? ob_start();
//error_reporting(E_PARSE  ^ E_ERROR );
class JobsController extends AppController
{
	//To avoid possible PHP4 problemfss
	var $name = "JobsController";

	var $uses = array('User', 'Item', 'OrderType', 'ItemCategory', 'OrderType', 'OrderTypeQuestions', 'JobCategory', 'OrderTypeItems');

	var $helpers = array('Time','Javascript','Common');
	var $components = array('HtmlAssist','RequestHandler','Common','Jpgraph', 'Lists');
	var $itemsToShow = 20;
	var $pagesToDisplay = 10;
	
	function index()
	{
		$this->layout="list";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		
		$sort = $_GET['sort'];
		$order = $_GET['order'];
		if (!$order) $order = 'id asc';
		
		$conditions = "where flagactive=1";
		$ShowInactive = $_GET['ShowInactive'];
		if ($ShowInactive) $conditions = "";

		$query = "select *
								from ace_rp_order_types i
							 $conditions
							 order by ".$order.' '.$sort;
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		
		$this->set('ShowInactive', $ShowInactive);
		$this->set('items', $items);
		$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
		
	}

	function copyJobtype() {
		$this->layout = "blank";
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$action = $_GET['action'];		
		
		if(isset($action)) {
			if($action == "copy") {
				$new_copy = $_POST['jobtype_name'];
				$jobtype_id = $_POST['jobtype_id'];
				
				if(!isset($new_copy) || !isset($jobtype_id)) $this->redirect("jobs");
				
				$query = "
					INSERT INTO ace_rp_order_types(category_id, name)
					SELECT category_id, '$new_copy'
					FROM ace_rp_order_types
					WHERE id = $jobtype_id
				";
				$result = $db->_execute($query);
				
				$query = "
					SELECT LAST_INSERT_ID() id
				";
				
				$result = $db->_execute($query);
				
				if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					$copyid = $row['id'];
				}
				
			} else if($action == "copyquestions") {
				$jobtype_id_from = $_POST['jobtype_id_from'];
				$jobtype_id_to = $_POST['jobtype_id_to'];
				
				if(!isset($jobtype_id_from) || !isset($jobtype_id_to)) $this->redirect("jobs");
				
				$jobtype_id = $jobtype_id_from;
				$copyid = $jobtype_id_to;
			}			
			
			 $item_id = $jobtype_id;
			//start
				$query = "
				SELECT * 
				FROM ace_rp_questions 
				WHERE order_type_id = $item_id 
				order by rank, value
			";
			
			$questions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $questions[$row['id']][$k] = $v;
			}
			
			$query = "
				SELECT r.* 
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				WHERE q.order_type_id = $item_id
			";
					
			$responses = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $responses[$row['question_id']][$row['id']][$k] = $v;
			}
			
			$query = "
				SELECT s.* 
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				WHERE q.order_type_id = $item_id
			";
			$suggestions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $suggestions[$row['response_id']][$row['id']][$k] = $v;
			}
			
			$query = "
				SELECT d.* 
				FROM ace_rp_questions q
				LEFT JOIN ace_rp_responses r
				ON q.id = r.question_id
				LEFT JOIN ace_rp_suggestions s
				ON r.id = s.response_id
				LEFT JOIN ace_rp_decisions d
				ON s.id = d.suggestion_id
				WHERE q.order_type_id = $item_id
			";
			
			$decisions = array();
			$result = $db->_execute($query);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				foreach ($row as $k => $v)
				  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
			}
			//end
			
			foreach($questions as $question_id => $question) {
				$query = "
					INSERT INTO ace_rp_questions(value, type, order_type_id, rank, for_print, for_office, for_tech)
					VALUES('".$question['value']."', '".$question['type']."', $copyid, ".$question['rank'].", ".$question['for_print'].", ".$question['for_office'].",".$question['for_tech'].")					
				";
				
				$result = $db->_execute($query);
				
				$query = "
					SELECT LAST_INSERT_ID() id
				";
				
				$result = $db->_execute($query);
				
				if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					$new_question_id = $row['id'];
				}
				
				foreach($responses[$question_id] as $response_id => $response) {
					//echo "<pre>";
					//print_r($responses);
					//echo "</pre>";
					//echo "start response $response_id value (".$response['value'].")";			
					if(isset($response['value'])) {						
						$query = "
							INSERT INTO ace_rp_responses(question_id, value, operation_id, which_id)
							VALUES($new_question_id, '".$response['value']."', ".$response['operation_id'].", ".$response['which_id'].")
						";
						
						$result = $db->_execute($query);
						
						$query = "
							SELECT LAST_INSERT_ID() id
						";
						
						$result = $db->_execute($query);
						
						if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
							$new_response_id = $row['id'];
						}
					
					foreach($suggestions[$response_id] as $suggestion_id => $suggestion) {
						if(isset($suggestion['value'])) {
							$query = "
								INSERT INTO ace_rp_suggestions(response_id, value)
								VALUES($new_response_id, '".$suggestion['value']."')
							";
							
							$result = $db->_execute($query);
							
							$query = "
								SELECT LAST_INSERT_ID() id
							";
							
							$result = $db->_execute($query);
							
							if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
								$new_suggestion_id = $row['id'];
							}
						
						foreach($decisions[$suggestion_id] as $decision) {
							if(isset($decision['value'])) {
								$query = "
									INSERT INTO ace_rp_decisions(suggestion_id, value, points, notify)
									VALUES($new_suggestion_id, '".$decision['value']."', ".$decision['points'].", ".$decision['notify'].")
								";
								
								$result = $db->_execute($query);	
							}//end if
						}//end decisions
						}//end if
					}//end suggestions
					}//end if					
				}//end responses
			}//end questions
		}
		
		$this->redirect("jobs");
	}

	function editItem()
	{
		$this->layout="edit";
		$item_id = $_GET['item_id'];
		$this->OrderType->id = $item_id;
		$this->data = $this->OrderType->read();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "select * from ace_rp_order_types_questions where order_type_id='".$item_id."' order by question_number";
		
		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $questions[$row['id']][$k] = $v;
		}
		
		$query = "select * from ace_rp_order_types_items where order_type_id='$item_id'";
		
		$items = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $items[$row['id']][$k] = $v;
		}
		
		$this->set('items', $items);
		$this->set('questions', $questions);
		$this->set('jobtypes', $this->OrderType->findAll());
		$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
	}

	function saveItem()
	{
		$this->OrderType->id = $this->data['OrderType']['id'];
		$this->OrderType->save($this->data);
		if ($this->OrderType->id)
			$cur_id = $this->OrderType->id;
		else
			$cur_id = $this->OrderType->getLastInsertId();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		//Save job type's questions
		$query = "delete from ace_rp_order_types_questions where order_type_id=".$cur_id;
		$db->_execute($query);
		foreach ($this->data['OrderType']['OrderTypeQuestions'] as $dat)
		{
			$dat['id'] = '';
			$dat['order_type_id'] = $cur_id;
			if ($dat['for_office']=='on') $dat['for_office']=1; else $dat['for_office']=0;
			if ($dat['for_tech']=='on') $dat['for_tech']=1; else $dat['for_tech']=0;
			if ($dat['for_print']=='on') $dat['for_print']=1; else $dat['for_print']=0;
			$this->OrderTypeQuestions->save($dat);
		}
		
		//Save job type's default items		
		$query = "delete from ace_rp_order_types_items where order_type_id=".$cur_id;
		$db->_execute($query);
		foreach ($this->data['OrderType']['OrderTypeItems'] as $dat)
		{
			$dat['id'] = '';
			$dat['order_type_id'] = $cur_id;
			$this->OrderTypeItems->save($dat);
		}
		
		$query = "INSERT INTO ace_rp_estimation_template(job_type_id,template)
			VALUES($cur_id, '".$this->data['template']['templateform']."')";

		$result = $db->_execute($query);

		//Forward user where they need to be - if this is a single action per view
		/*$this->redirect('/jobs/editItem?item_id='.$cur_id);*/
		$this->redirect("jobs/index");
	}

	// AJAX method to get the job's category
	function getCategory()
	{
		$item_id = $_GET['job_type'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "select category_id from ace_rp_order_types where id=".$item_id;
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		
		echo $row['category_id'];
		exit;
	}

	// AJAX method for activation/deactivation of an item
	function changeActive()
	{
		$item_id = $_GET['item_id'];
		$is_active = $_GET['is_active'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_order_types set flagactive='".$is_active."' where id=".$item_id);

		exit;
	}

  function getDefaultItems()
	{
		$job_type = $_GET['job_type'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "select * from ace_rp_order_types_items where order_type_id='".$job_type."'";
		
		$ret = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$ar = array();
			foreach ($row as $k => $v)
			  $ar[$k] = $v;
			$ret[] = $ar;
		}
		
		echo json_encode($ret);
		exit;
	}

	function editTemplate()
	{	
		$this->layout="blank";
		$item_id = $_GET['item_id'];
		$this->OrderType->id = $item_id;
		$this->data = $this->OrderType->read();

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT * 
			FROM ace_rp_estimation_template where job_type_id = ".$item_id;
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		
		/*echo "<pre>"; print_r($row); die;*/

		$this->set('template', $row['template']);
		$this->set('typeId', $item_id);
		
	}

	function saveEditedTemplateForJobType()
	{
		$this->layout="blank";
		$type_id = $_POST['typeId'];				
		$template = $_POST['template'];	
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$query = "
			SELECT * 
			FROM ace_rp_estimation_template where job_type_id = ".$type_id;
		$result = $db->_execute($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);

		if(!empty($row['template'])){
			$query = "
			UPDATE ace_rp_estimation_template 
			SET template = '".$template."'  
			WHERE job_type_id = ".$type_id;
			$db->_execute($query);
		}else{
			$query = "INSERT INTO ace_rp_estimation_template(job_type_id,template)
			VALUES(".$type_id.", '".$template."')";
			$result = $db->_execute($query);
		}

		$this->redirect("jobs/index");
	}
	
	function editQuestionsTemplate()
	{
		$this->layout="blank";
		$item_id = $_GET['item_id'];
		$this->OrderType->id = $item_id;
		$this->data = $this->OrderType->read();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT * 
			FROM ace_rp_order_type_categories 			
		";
		
		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $categories[$row['id']][$k] = $v;
		}
		
		$this->set('categories', $categories);
		
		$query = "
			SELECT * 
			FROM ace_rp_order_types 
			WHERE id = $item_id 			
		";
		
		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $order_type[$k] = $v;
		}
		
		$this->set('order_type', $order_type);
		
		$query = "
			SELECT * 
			FROM ace_rp_questions 
			WHERE order_type_id = $item_id 
			order by rank, value
		";
		
		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $questions[$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT r.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			WHERE q.order_type_id = $item_id
		";
				
		$responses = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $responses[$row['question_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT s.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			WHERE q.order_type_id = $item_id
		";
		$suggestions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $suggestions[$row['response_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT d.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
			WHERE q.order_type_id = $item_id
		";
		
		$decisions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT * 
			FROM ace_rp_suggestion_operations			
		";
		
		$operations = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $operations[$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT * 
			FROM ace_rp_suggestion_which
		";
		
		$which = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $which[$row['id']][$k] = $v;
		}
		
		 $query = "
			SELECT m.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
            LEFT JOIN ace_rp_reminders m
			ON d.id = m.decision_id
			WHERE q.order_type_id = $item_id
		";
		
		$reminders = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			$reminders[$row['decision_id']][$k] = $v;
			 //$reminders[$row['id']][$k] = $v;
			  //$reminders[$row['decision_id']]=$row['value'];
		}
		
	
		
		
		$this->set('questions', $questions);
		$this->set('responses', $responses);
		$this->set('suggestions', $suggestions);		
		$this->set('decisions', $decisions);
		$this->set('item_id', $item_id);
		$this->set('operations', $operations);
		$this->set('which', $which);
		$this->set('reminders', $reminders);
		
		$this->set('jobtypes', $this->OrderType->findAll());
		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
		
		if(isset($this->data)) $this->set('template', $this->data);
		
	}
	
	function saveQuestionsTemplate() { 
		$this->layout = "blank";

		ini_set('max_execution_time', 300);

		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);

		$this->set('item_id', $_GET['item_id']);
		
		
		if(isset($this->data)) $this->set('template', $this->data);
		
		
		$ordertype_name = $this->data['OrderType']['name'];
		$category_id = $this->data['OrderType']['category_id'];
		$ordertype_id = $_GET['item_id'];
		
		$query = "
			UPDATE ace_rp_order_types 
			SET name = '$ordertype_name',
			category_id = $category_id
			WHERE id = $ordertype_id
		";
		$db->_execute($query);
		
		foreach($this->data['questions'] as $question_id => $question) {
			//set defaults
			if(!isset($question['for_print'])) $question['for_print'] = 0;
			if(!isset($question['for_office'])) $question['for_office'] = 0;
			if(!isset($question['for_tech'])) $question['for_tech'] = 0;
			if(!isset($question['for_estimate'])) $question['for_estimate'] = 0;
			if(!isset($question['is_permanent'])) $question['is_permanent'] = 0;
			
			if(isset($question['delete_this']) && $question['delete_this'] == 1) {
				if($question_id[0] == 'n') {
					unset($question['responses']);
				} else {
					$query = "
						DELETE FROM ace_rp_questions
						WHERE id = $question_id
					";
					$db->_execute($query);
				}
				$last_question_id = 0;
			} else {
				if($question_id[0] == 'n') {
					$query = "
						INSERT INTO ace_rp_questions(value, type, order_type_id, rank, for_print, for_office, for_tech, for_estimate, is_permanent)
						VALUES('".$question['value']."', '".$question['type']."', '".$question['order_type_id']."', '".$question['rank']."', '".$question['for_print']."', '".$question['for_office']."', '".$question['for_tech']."', '".$question['for_estimate']."', '".$question['is_permanent']."')
					";
					$db->_execute($query);
					$query = "
						SELECT LAST_INSERT_ID() AS last_id
					";
					$result = $db->_execute($query);
					$row = mysql_fetch_array($result, MYSQL_ASSOC);
					$last_question_id = $row['last_id'];					
				} else {
					$query = "
						UPDATE ace_rp_questions
						SET value = '".$question['value']."', 
						type = '".$question['type']."', 
						order_type_id = '".$question['order_type_id']."', 
						rank = '".$question['rank']."', 
						for_print = '".$question['for_print']."', 
						for_office = '".$question['for_office']."', 
						for_tech = '".$question['for_tech']."',
						for_estimate = '".$question['for_estimate']."',
						is_permanent = '".$question['is_permanent']."'
						WHERE id = $question_id
					";
					$db->_execute($query);
					$last_question_id = $question_id;
				}
			}
			
			foreach($question['responses'] as $response_id => $response) {
				if(!isset($response['operation_id'])) $response['operation_id'] = 0;
				if(!isset($response['which_id'])) $response['which_id'] = 0;
				
				if((isset($response['delete_this']) && $response['delete_this'] == 1) || $last_question_id == 0) {
					if($response_id[0] == 'n') {
						unset($response['suggestions']);
					} else {
						$query = "
							DELETE FROM ace_rp_responses
							WHERE id = $response_id
						";
						$db->_execute($query);
					}
					$last_response_id = 0;
				} else {
					if($response_id[0] == 'n') {				
						$query = "
							INSERT INTO ace_rp_responses(value, question_id, operation_id, which_id)
							VALUES('".$response['value']."', '".$last_question_id."', '".$response['operation_id']."', '".$response['which_id']."')
						";
						$db->_execute($query);
						$query = "
							SELECT LAST_INSERT_ID() AS last_id
						";
						$result = $db->_execute($query);
						$row = mysql_fetch_array($result, MYSQL_ASSOC);
						$last_response_id = $row['last_id'];
					} else {
						$query = "
							UPDATE ace_rp_responses
							SET value = '".$response['value']."',
							question_id = '$last_question_id',
							operation_id = '".$response['operation_id']."',
							which_id = '".$response['which_id']."'
							WHERE id = $response_id
						";
						$db->_execute($query);
						$last_response_id = $response_id;
					}
				}
				foreach($response['suggestions'] as $suggestion_id => $suggestion) {					
					if((isset($suggestion['delete_this']) && $suggestion['delete_this'] == 1) || $last_response_id == 0) {
						if($suggestion_id[0] == 'n') {
							unset($suggestion['decisions']);
						} else {
							$query = "
								DELETE FROM ace_rp_suggestions
								WHERE id = $suggestion_id
							";
							$db->_execute($query);
						}
						$last_suggestion_id = 0;
					} else {
						if($suggestion_id[0] == 'n') {				
							$query = "
								INSERT INTO ace_rp_suggestions(value, response_id)
								VALUES('".$suggestion['value']."', '$last_response_id')
							";
							$db->_execute($query);
							$query = "
								SELECT LAST_INSERT_ID() AS last_id
							";
							$result = $db->_execute($query);
							$row = mysql_fetch_array($result, MYSQL_ASSOC);
							$last_suggestion_id = $row['last_id'];
						} else {
							$query = "
								UPDATE ace_rp_suggestions
								SET value = '".$suggestion['value']."',
								response_id = '$last_response_id'							
								WHERE id = $suggestion_id
							";
							$db->_execute($query);
							$last_suggestion_id = $suggestion_id;
						}
					}
					
					foreach($suggestion['decisions'] as $decision_id => $decision) {
						if(!isset($decision['points'])) $decision['points'] = 0;
						if(!isset($decision['notify'])) $decision['notify'] = 0;
						
						if((isset($decision['delete_this']) && $decision['delete_this'] == 1) || $last_suggestion_id == 0) {
							if($decision_id[0] == 'n') {
								//do nothing
							} else {
								$query = "
									DELETE FROM ace_rp_decisions
									WHERE id = $decision_id
								";
								$db->_execute($query);
							}
							$last_decision_id = 0;
						} else {						
							if($decision_id[0] == 'n') {				
								$query = "
									INSERT INTO ace_rp_decisions(value, suggestion_id, points, notify)
									VALUES('".$decision['value']."', '$last_suggestion_id', '".$decision['points']."', '".$decision['notify']."')
								";
								$db->_execute($query);
								$query = "
									SELECT LAST_INSERT_ID() AS last_id
								";
								$result = $db->_execute($query);
								$row = mysql_fetch_array($result, MYSQL_ASSOC);
								$last_decision_id = $row['last_id'];
							} else {
								$query = "
									UPDATE ace_rp_decisions
									SET value = '".$decision['value']."',
									suggestion_id = '$last_suggestion_id',
									points = '".$decision['points']."',
									notify = '".$decision['notify']."'
									WHERE id = $decision_id
								";
								$db->_execute($query);
								$last_decision_id = $decision_id;
							}
						}
						
						/***************************/
					if(isset($decision['reminders'])){	
				foreach($decision['reminders'] as $reminder_id => $reminder) {
					if((isset($reminder['delete_this']) && $reminder['delete_this'] == 1) || $last_response_id == 0) {
						if($reminder[0] == 'n') {
							unset($reminder['decision']);
						} else {
							$query = "
								DELETE FROM ace_rp_reminders
								WHERE id = $reminder_id
							";
							$db->_execute($query);
						}
						$last_reminder_id = 0;
					} else {
						if($reminder_id[0] == 'n') {				
							$query = "
								INSERT INTO ace_rp_reminders(value, decision_id)
								VALUES('".$reminder['value']."', '$last_decision_id')
							";
							$db->_execute($query);
							$query = "
								SELECT LAST_INSERT_ID() AS last_id
							";
							$result = $db->_execute($query);
							$row = mysql_fetch_array($result, MYSQL_ASSOC);
							$last_reminder_id = $row['last_id'];
						} else {
							$query = "
								UPDATE ace_rp_reminders
								SET value = '".$reminder['value']."',
								decision_id = '$last_decision_id'							
								WHERE id = $reminder_id
							";
							$db->_execute($query);
							$last_reminder_id = $reminder_id;
						}
					}
					}//END foreach($decision['reminder'] as $reminder_id => $reminder)
				  }
						/***************************/
						
					} //END foreach($suggestion['decisions'] as $decision_id => $decision)
				} //END foreach($response['suggestions'] as $suggestion_id => $suggestion) {
			} //END foreach($question['responses'] as $response_id => $response)
		} //END foreach($this->data['questions'] as $question_id => $question)
		
		
		if(isset($_GET['preview']) && $_GET['preview'] == 1) {
			$this->redirect("jobs/previewQuestionsTemplate?item_id=".$_GET['item_id']);
		} else {
			$this->redirect("jobs/editQuestionsTemplate?item_id=".$_GET['item_id']);
		}
		
		exit;
		
	}
	
	function previewQuestionsTemplate()
	{
		$this->layout="blank";
		$item_id = $_GET['item_id'];
		$this->OrderType->id = $item_id;
		$this->data = $this->OrderType->read();
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT * 
			FROM ace_rp_questions 
			WHERE order_type_id = $item_id 
			order by rank, value
		";
		
		$questions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $questions[$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT r.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			WHERE q.order_type_id = $item_id
		";
				
		$responses = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $responses[$row['question_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT s.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			WHERE q.order_type_id = $item_id
		";
		$suggestions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $suggestions[$row['response_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT d.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
			WHERE q.order_type_id = $item_id
		";
		
		$decisions = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $decisions[$row['suggestion_id']][$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT * 
			FROM ace_rp_suggestion_operations			
		";
		
		$operations = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $operations[$row['id']][$k] = $v;
		}
		
		$query = "
			SELECT * 
			FROM ace_rp_suggestion_which
		";
		
		$which = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $which[$row['id']][$k] = $v;
		}
		
		 $query = "
			SELECT m.* 
			FROM ace_rp_questions q
			LEFT JOIN ace_rp_responses r
			ON q.id = r.question_id
			LEFT JOIN ace_rp_suggestions s
			ON r.id = s.response_id
			LEFT JOIN ace_rp_decisions d
			ON s.id = d.suggestion_id
            LEFT JOIN ace_rp_reminders m
			ON d.id = m.decision_id
			WHERE q.order_type_id = $item_id
		";
		
		$reminders = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			 $reminders[$row['decision_id']][$k] = $v;
			 //$reminders[$row['id']][$k] = $v;
			  //$reminders[$row['decision_id']]=$row['value'];
		}
		
		
		$this->set('questions', $questions);
		$this->set('responses', $responses);
		$this->set('suggestions', $suggestions);		
		$this->set('decisions', $decisions);
		$this->set('item_id', $item_id);
		$this->set('operations', $operations);
		$this->set('which', $which);
		$this->set('reminders', $reminders);
		
		$this->set('jobtypes', $this->OrderType->findAll());
		//$this->set('jobcategories', $this->Lists->ListTable('ace_rp_order_type_categories'));
		
		if(isset($this->data)) $this->set('template', $this->data);
		
	}
	
	function itemCategories() {
		$this->layout="blank";
		$id = $_GET['id'];	
		$this->set('job_type', $id);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT c.*, IFNULL(i.job_type_id, 0) flag
			FROM ace_iv_categories c
			LEFT JOIN (SELECT * FROM ace_rp_item_job_categories WHERE job_type_id = $id) i
			ON c.id = i.item_category_id
		";
		
		$categories = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $categories[$row['id']][$k] = $v;
		}
		
		$this->set('categories', $categories);
	}
	
	function saveItemCategories() {
		$approval = $this->data['Approval'];
		$job_type = $_POST['job_type'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "DELETE FROM ace_rp_item_job_categories WHERE job_type_id = $job_type";
		$db->_execute($query);
		
		foreach($approval as $id => $i) {		
			$query = "
				INSERT INTO ace_rp_item_job_categories(item_category_id, job_type_id)
				VALUES($id, $i)
			";	
			$db->_execute($query);
		}
		echo "<script>window.close()</script>";
		//print_r($approval);
		//print_r($noapproval);
	}
	
	function scheduleDisplay() {
		$this->layout="blank";
		$id = $_GET['id'];	
		$this->set('job_type', $id);
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "
			SELECT c.*, IFNULL(i.job_type_id, 0) flag
			FROM ace_rp_schedules c
			LEFT JOIN (SELECT * FROM ace_rp_orders_schedule_display WHERE job_type_id = $id) i
			ON c.id = i.schedule_id
		";
		
		$categories = array();
		$result = $db->_execute($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			foreach ($row as $k => $v)
			  $categories[$row['id']][$k] = $v;
		}
		
		$this->set('schedules', $categories);
	}
	
	function saveScheduleDisplay() {
		$approval = $this->data['Approval'];
		$job_type = $_POST['job_type'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		
		$query = "DELETE FROM ace_rp_orders_schedule_display WHERE job_type_id = $job_type";
		$db->_execute($query);
		
		foreach($approval as $id => $i) {		
			$query = "
				INSERT INTO ace_rp_orders_schedule_display(schedule_id, job_type_id)
				VALUES($id, $i)
			";	
			$db->_execute($query);
		}
		echo "<script>window.close()</script>";
		//print_r($approval);
		//print_r($noapproval);
	}

	// #Loki - Active/Inactive commission

	function changeCommissionActive()
	{
		$jobtype_id = $_GET['jobtype_id'];
		$is_active = $_GET['is_active'];
		
		$db =& ConnectionManager::getDataSource($this->User->useDbConfig);
		$db->_execute("update ace_rp_order_types set show_commission='".$is_active."' where id=".$jobtype_id);
		exit;
	}

}
?>
