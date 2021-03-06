<?php

class Model {

	protected $db;
	public $id;

	function __construct() {
		$this->db = new \DB\SQL('mysql:host=localhost;port=3306;dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
	}

	/**
	*	Data encode JSON
	*	@param $name
	*	@param $data
	**/
	public function encode($name, $data = array()){
		header('Access-Control-Allow-Origin: *');
		header('Acces-Control-Allow-Headers: Auth-Token');
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');
		return '{"' . $name . '": ' . json_encode(array_change_key_case($data, CASE_LOWER)) . '}';
	}

	/**
	*	Get datetime
	**/
	public function datetime(){
		return date('Y-m-d H:i:s');
	}

	/**
	*	Mapper SQL Get Data
	*	@param may contain "conditions", "fields", "order"
	**/
	public function get($params = array()){
		$fields = '*';
		$conditions = '1';
		$orders = '1';

		if(!empty($params['fields'])){
			$fields = '';
			foreach($params['fields'] as $field){
				$fields.= $field . ', ';
			}
			$fields = substr($fields, 0, -2); // on enleve la virgule en trop
		}

		if(!empty($params['conditions'])){
			$conditions = '';
			foreach($params['conditions'] as $id => $condition){
				$conditions.= $id . ' = "' . $condition . '" AND ';
			}
			$conditions = substr($conditions, 0, -4); // on enleve le AND en trop
		}

		if(!empty($params['order'])){
			$orders = '';
			foreach ($params['order'] as $id => $order) {
				$orders.= $id . ' ' . $order . ', ';
			}
			$orders = substr($orders, 0, -2); // on enleve la virgule en trop
		}

		// requete automatiquement généré selon les cas
		$statement = $this->db->prepare('SELECT ' . $fields . ' FROM ' . $this->table . ' WHERE ' . $conditions . ' ORDER BY ' . $orders);
		$statement->execute();

		if(sizeof($data = $statement->fetchAll(PDO::FETCH_ASSOC)) == 1){
			return $data['0']; // on retourne directement l'indice 0 du tableau
		}else{
			return $data;
		}

	}

	/**
	*	Check data exist
	*	@param $field
	*	@param $data
	**/
	public function exists($field, $data){
		$statement = $this->db->prepare('SELECT COUNT(' . $field . ') AS "exists" FROM ' . $this->table . ' WHERE ' . $field . ' = :data');
		$statement->execute(array('data' => $data));
		$exists = $statement->fetch(PDO::FETCH_ASSOC);
		return $exists['exists'] == 1 ? true : false;
	}

	/**
	*	Mapper SQL save Data
	*	@param $datas array key => data
	**/
	public function save($datas = array()){
		$values = "";
		$fields = "";
		$restriction = "";

		if(!empty($this->id)){
			foreach($datas[$this->table] as $field => $data){
				// name = :name
				$restriction .= $field . ' = :' . $field . ', ';
			}

			$restriction = substr($restriction, 0, -2);

			$statement = $this->db->prepare('UPDATE ' . $this->table . ' SET ' . $restriction . ' WHERE id = ' . $this->id);
			return $statement->execute($datas[$this->table]);

		}else{
			$datas[$this->table]['created'] = $this->datetime();

			foreach($datas[$this->table] as $field => $data){
				$restriction .= $field . ', ';
				$fields .= ":" . $field . ", ";
			}

			$restriction = substr($restriction, 0, -2);
			$fields = substr($fields, 0, -2);

			$statement = $this->db->prepare('INSERT INTO ' . $this->table . ' (' . $restriction . ') VALUES (' . $fields . ')');
			if($statement->execute($datas[$this->table])){
				return $this->db->lastInsertId();
			}else{
				return false;
			}
		}
	}

	/**
	*	Delete by ID
	*	@param $id
	**/
	public function delete($id){
		$statement = $this->db->prepare('DELETE FROM ' . $this->table . ' WHERE id = :id');
		return $statement->execute(array('id' => $id));
	}

	public function generate_questions(){
		for($i = 0; $i < 20; $i++){
			$name = file_get_contents('http://loripsum.net/api/1/short/plaintext');
			$id = $this->db->exec('SELECT id FROM categories ORDER BY RAND() LIMIT 1')[0];
			$category_id= $id['id'];
			$this->db->exec('INSERT INTO questions(name, category_id, created) VALUES(:name, :category_id, :created)',
				array('name' => $name, 'category_id' => $category_id, 'created' => $this->datetime()));
		}
		echo $this->encode('generate_questions', $i . ' reponses générées');
	}

	/**
	*	Generate random response for test
	*	id, question_id, answer, status, created
	**/
	public function generate_responses(){
		$questions = $this->db->exec('SELECT * FROM questions WHERE id NOT IN ( SELECT question_id FROM answers )');

		// pour chaque question sans reponse
		foreach ($questions as $key => $question) {
			for($i = 0; $i < 4; $i++){
				$answer = file_get_contents('http://loripsum.net/api/1/short/plaintext');
				$status = ($i == 3) ? true: false;
				$this->db->exec('INSERT INTO answers(question_id, answer, status, created) VALUES(:question_id, :answer, :status, :created)',
					array('question_id' => $question['id'], 'answer' => $answer, 'status' => $status, 'created' => $this->datetime()));
			}
		}
		echo $this->encode('generate_responses', sizeof($questions) * 4 . ' reponses générées');
	}

	/**
	*	Generate base's categories
	**/
	public function generate_categories(){
		$this->db->exec("INSERT INTO `categories` (`name`, `sex`, `created`) VALUES
			('geek', 'all', '" . $this->datetime() . "'),
			('dragqueen', 'male', '" . $this->datetime() . "'),
			('hippie', 'all', '" . $this->datetime() . "'),
			('badboy', 'male', '" . $this->datetime() . "'),
			('keke', 'male', '" . $this->datetime() . "'),
			('barbie', 'female', '" . $this->datetime() . "'),
			('badgirl', 'female', '" . $this->datetime() . "'),
			('candide', 'female', '" . $this->datetime() . "')");
	}

	/**
	*	Upload file
	*	@param $file
	**/
	public function upload($file){

		$extension = array('mp3', 'jpg', 'jpeg', 'png');
		$name = uniqid() . '-' . str_replace(' ', '-', $file['name']);
		$DIR_PATH = 'uploads/' . strtolower(get_class($this)) . '/';

		if(!file_exists($DIR_PATH)){
			mkdir($DIR_PATH, 0775);
		}

		if(empty($file) || $file['error']){
			return false;
		}

		if($file['size'] > filesize($file['tmp_name'])){
			return false;
		}

		$pathinfo = pathinfo($file['name']);
		if(!in_array(strtolower($pathinfo['extension']), $extension)){
			return false;
		}

		if( !empty($file['category']) && !file_exists($DIR_PATH . $file['category']) ){
			mkdir($DIR_PATH . strtolower($file['category']), 0775);
		}

		$path = (!empty($file['category'])) ? $DIR_PATH . $file['category'] . '/' . $name : $DIR_PATH . $name;

		return (move_uploaded_file($file['tmp_name'], $path)) ? $path : false;
	}
}

?>