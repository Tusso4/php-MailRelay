<?php

class MailRelay {

	private $apiKey = "";
	private $username = "";

	public function __construct(String $username, String $apiKey) {
		if(!empty($apiKey)) {
			$this->apiKey = $apiKey;
		}

		if(!$this->testConnection()) {
			die("No se pudo conectar con la API.");
		}
	}

	public function _mergeArray(&$array1, $array2) {
		foreach($array2 as $key => $value) {
			if(!empty($array1[$key])) {
				$array1[$key + "_1"] = $value;
			} else {
				$array1[$key] = $value;
			}
		}

		return true;
	}

	public function _curl($apiFunction, $params = []) {
		$curl = curl_init('https://'.($this->username).'.ip-zone.com/ccm/admin/api/version/2/&type=json');
 
		$postData = array(
		    'function' => $apiFunction,
		    'apiKey' => $this->apiKey,
		);

		$this->_mergeArray($postData, $params);

		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 
		$json = curl_exec($curl);


		if ($json === false) {
			return false;
		}
		 
		$result = json_decode($json);
		if ($result->status == 0) {
			return false;
		}

		return $result;
	}

	public function testConnection() {
		if($this->_curl('getStats') == false) {
			return false;
		} else {
			return true;
		}
	}

	public function getSuscribers($email, $params = [])  {
		$fixedParams = [
			"email" => $email
		];

		$this->_mergeArray($fixedParams, $params);

		return $this->_curl('getSubscribers', $fixedParams);
	}

	public function enableSuscriber($email) {
		if($client = $this->getSuscribers($email)) {
			$fixedParams = $client->data[0];
			$fixedParams->activated = true;

			return $this->_curl('updateSubscriber', $fixedParams);
		}
	}

	public function disableSuscriber($email) {
		if($client = $this->getSuscribers($email)) {
			$fixedParams = $client->data[0];
			$fixedParams->activated = false;

			return $this->_curl('updateSubscriber', $fixedParams);
		}
	}

	public function masiveActiveByEmail($activated, $emails = [], $groups = []) {
		$clients = [];
		$fixedGroups = [];
		$ids = [];

		if(!empty($groups)) {
			if(is_array($groups)) {
				$fixedGroups = $groups;
			} else {
				$fixedGroups[] = $groups;
			}
		}

		$tmpVal = null;
		if(!empty($emails) && is_array($emails)) {
			foreach($emails as $key => $value) {
				$tmpVal = $this->getSuscribers($value, [ 'groups' => $fixedGroups ])->data[0];
				$ids[] = $tmpVal->id;
			}
		} else {
			$tmpVal = $this->getSuscribers($emails, [ 'groups' => $fixedGroups ])->data[0];
			$ids[] = $tmpVal->id;
		}

		return $this->_curl('updateSubscribers', [
			'ids' => $ids,
		 	'activated' => $activated
		]);
	}

	public function masiveActiveByGroup($activated, $groups = []) {
		$clients = [];
		if(!empty($groups) && is_array($groups)) {
			$clients = $this->getSuscribers(null, [
				"groups" => $groups
			]);
		} else {
			$clients = $this->getSuscribers(null, [
				"groups" => array($groups)
			]);
		}

		$ids = [];

		foreach ($clients->data as $key => $value) {
			$ids[] = $value->id;
		}

		return $this->_curl('updateSubscribers', [
			'ids' => $ids,
			'activated' => $activated
		]);
	}

	public function test() {


	}
}
