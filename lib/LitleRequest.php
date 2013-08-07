<?php
set_include_path(dirname(__FILE__) . '/../resources/');

include('Net/SFTP.php');

class LitleRequest{
	
	# file name that holds the batch requests once added
	private $batches_file;
	
	private $request_file;
	
	private $config;
	
	
	private $num_batch_requests = 0;
	# note that a single litle request cannot hold more than 500,000 transactions
	private $total_transactions = 0;
	
	private $closed = false;
	/*
	 * Creates the intermediate request file and preps it to have batches added
	 */
	public function __construct($overrides=array()){
		$config = Obj2xml::getConfig($overrides);
		
		$this->config= $config;
		$request_dir = $config['litle_requests_path'];
		
		if(substr($request_dir, -1, 1) != DIRECTORY_SEPARATOR){
			$request_dir = $request_dir . DIRECTORY_SEPARATOR;
		}
		
		$ts = str_replace(" ", "", substr(microtime(), 2));
		$batches_filename = $request_dir . "request_" . $ts . "_batches";
		$request_filename = $request_dir . "request_" . $ts;
		
		// if either file already exists, let's try again!
		if(file_exists($batches_filename) || file_exists($request_filename)){
			$this->__construct();
		}
		
		// if we were unable to write the file
		if(file_put_contents($batches_filename, "") === FALSE){
			throw new RuntimeException("A request file could not be written at $batches_filename. Please check your privilege.");
		}
		$this->batches_file = $batches_filename;
		
		// if we were unable to write the file
		if(file_put_contents($request_filename, "") === FALSE){
			throw new RuntimeException("A request file could not be written at $request_filename. Please check your privilege.");
		}
		$this->request_file = $request_filename;
	}

	public function wouldFill($addl_txns_count){
		return ($this->total_transactions + $addl_txns_count) > MAX_TXNS_PER_REQUEST;
	}
	
	/* 
	 * Adds a closed batch request to the Litle Request. This entails copying the completed batch file into the intermediary
	 * request file
	 */
	public function addBatchRequest($batch_request){
		if($this->wouldFill($batch_request->total_txns)){
			throw new RuntimeException("Couldn't add the batch to the Litle Request. The total number of transactions would exceed the maximum allowed for a request.");
		}
		
		if($this->closed){
			throw new RuntimeException("Could not add the batchRequest. This litleRequest is closed.");
		}
		
		if(!$batch_request->closed){
			$batch_request->closeRequest();
		}
		$handle = @fopen($batch_request->batch_file,"r");
		if($handle){
			while(($buffer = fgets($handle, 4096)) !== false){
				file_put_contents($this->batches_file, $buffer, FILE_APPEND);
			}
			if(!feof($handle)){
				throw new RuntimeException("Error when reading batch file at $batch_request->batch_file. Please check your privilege.");
			}
			fclose($handle);
			
			unlink($batch_request->batch_file);
			unset($batch_request->batch_file);
			$this->num_batch_requests += 1;
			$this->total_transactions += $batch_request->total_txns;
		}
		else{
			throw new RuntimeException("Could not open batch file at $batch_request->batch_file. Please check your privilege.");
		}
	}
	
	public function createRFRRequest($hash_in){
		if($this->num_batch_requests > 0){
			throw new RuntimeException("Could not add the RFR Request. A single Litle Request cannot have both an RFR request and batch requests together.");
		}
		
		if($this->closed){
			throw new RuntimeException("Could not add the RFR Request. This litleRequest is closed.");
		}
		$RFRXml = Obj2xml::rfrRequestToXml($hash_in);
		file_put_contents($this->request_file, Obj2xml::generateRequestHeader($this->config, $this->num_batch_requests), FILE_APPEND);
		file_put_contents($this->request_file, $RFRXml, FILE_APPEND);
		file_put_contents($this->request_file, "</litleRequest>", FILE_APPEND);
		unlink($this->batches_file);
		unset($this->batches_file);
		$this->closed = true;
	}
	/*
	 * Fleshes out the XML needed for the Litle Request. Returns the file name of the completed request file
	 */
	public function closeRequest(){
		$handle = @fopen($this->batches_file,"r");
		if($handle){
			file_put_contents($this->request_file, Obj2xml::generateRequestHeader($this->config, $this->num_batch_requests), FILE_APPEND);
			while(($buffer = fgets($handle, 4096)) !== false){
				file_put_contents($this->request_file, $buffer, FILE_APPEND);
			}
			if(!feof($handle)){
				throw new RuntimeException("Error when reading batches file at $this->batches_file. Please check your privilege.");
			}
			fclose($handle);
			file_put_contents($this->request_file, "</litleRequest>", FILE_APPEND);
			
			unlink($this->batches_file);
			unset($this->batches_file);
			$this->closed = true;
		}
		else{
			throw new RuntimeException("Could not open batches file at $this->batches_file. Please check your privilege.");
		}
	}	
	
	/*
	 * Alias for the preferred method of sFTP delivery
	 */
	public function sendToLitle(){
		$this->sendToLitleSFTP();
	}
	
	/*
	 * Deliver the Litle Request over sFTP using the credentials given by the config. Returns the name of the file retrieved from the server
	 */
	public function sendToLitleSFTP(){
		if(!$this->closed){
			$this->closeRequest();
		}
		
		$sftp_url = $this->config['batch_url'];
		$sftp_username = $this->config['sftp_username'];
		$sftp_password = $this->config['sftp_password'];
		
		$session = new Net_SFTP($sftp_url);
		if(!$session->login($sftp_username, $sftp_password)){
			throw new RuntimeException("Failed to SFTP with the username $sftp_username and the password $sftp_password to the host $sftp_url. Check your credentials!");
		}
		# with extension .prg
		$session->put('/inbound/' . basename($this->request_file) . '.prg', $this->request_file, NET_SFTP_LOCAL_FILE);
		# rename when the file upload is complete
		$session->rename('/inbound/' . basename($this->request_file) . '.prg', '/inbound/' . basename($this->request_file) . '.asc');
		
		echo "Response File " . $this->retrieveFromLitleSFTP();
	}
	
	private function retrieveFromLitleSFTP(){
		$sftp_url = $this->config['batch_url'];
		$sftp_username = $this->config['sftp_username'];
		$sftp_password = $this->config['sftp_password'];
		$sftp_timeout = 3600; // in seconds
		$session = new Net_SFTP($sftp_url);
		if(!$session->login($sftp_username, $sftp_password)){
			throw new RuntimeException("Failed to SFTP with the username $sftp_username and the password $sftp_password to the host $sftp_url. Check your credentials!");
		}
		$time_spent = 0;
		while($time_spent < $sftp_timeout){	
			$files = $session->nlist('/outbound');
			echo "Content of /outbound";
			echo print_r($files);
			if(in_array(basename($this->request_file) . '.asc', $files)){
				# TODO: the replacement needs to be tighter...
				$session->get('/outbound/' . basename($this->request_file) . '.asc', str_replace("request", "response", $this->request_file));
				return str_replace("request", "response", $this->request_file);
			}
			else{
				$time_spent += 15;
				sleep(15);
			}
		}
		
	}
	
	/*
	 * Deliver the Litle Request over a TCP stream. Returns the name of the file retrieved from the server
	 */
	public function sendToLitleStream(){
		if(!$this->closed){
			$this->closeRequest();
		}
		
		$tcp_url = $this->config['batch_url'];
		$tcp_port = $this->config['tcp_port'];
		$tcp_ssl = INT_CAST($this->config['tcp_ssl']);
		$tcp_timeout = $this->config['tcp_timeout'];;
		
		if($tcp_ssl){
			$tcp_url = 'ssl://' . $tcp_url;
		}
		
		$sock = fsockopen($tcp_url, $tcp_port, $err_no, $err_str, $tcp_timeout);
		
		if(!$sock){
			throw new RuntimeException("Error when opening socket at $tcp_url : $tcp_port. Error number: $err_no Error message: $err_str");
		}
		else{
			$handle = @fopen($this->request_file,"r");
			if($handle){
				while(($buffer = fgets($handle, 4096)) !== false){
					fwrite($sock, $buffer);
				}
				if(!feof($handle)){
					throw new RuntimeException("Error when reading request file at $this->request_file. Please check your privilege.");
				}
				fclose($handle);
			}
			else{
				throw new RuntimeException("Could not open request file at $this->request_file. Please check your privilege.");
			}
			$response_file = str_replace("request", "response", $this->request_file);
			# read from the response socket while there's data
			while (!feof($sock)) {
				file_put_contents($response_file, fgets($sock, 128), FILE_APPEND);
    		}
			fclose($sock);
			return $response_file;
		}
	}
}




