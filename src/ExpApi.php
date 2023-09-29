<?php

namespace Exprus\ExpApi;

use Exception;

/**
 * Class ExpApi
 *
 * Example:
 * $expApi = new ExpApi([
 *   "token" => "masdkfsdkjkdsjefndjsbjjedksfnjxjkNJKFDKjkjznJNvDNV",
 *   "title" => "defoultTitle",
 *   "source_id" => "201"
 * ]);
 *
 * $result = $expApi->newLead([
 *   "title" => "titleName",
 *   "name" => "тест",
 *   "second_name" => "тест",
 *   "last_name" => "тест",
 *   "email" => "value@test.ru",
 *   "phone" => "54345314",
 *   "assigned_by_id" => "50",
 *   "address" => "не указан",
 *   "utm_source" => "garant"
 * ]);
 *
 * echo $result->status; // ok or error
 * echo $result->data; // message
 *
 */
class ExpApi {
	/**
	 * @var object
	 */
	private $default;
	/**
	 * @var string
	 */
	private $token;

	/**
	 * ExpApi constructor.
	 *
	 * array['config'] array of config (required)
	 * array['config']['token'] string token for api (required)
	 * array['config']['title'] string default title for lead
	 * array['config']['source_id'] string default source_id for lead
	 *
	 * @param array $config (See above)
	 * @throws Exception
	 * @return void
	 */
	public function __construct($config) {
		if (empty($config)) throw new Exception("config is empty");
		if (empty($config["token"])) throw new Exception("token is empty");
		if (empty($config["source_id"])) throw new Exception("source_id is empty");

		$this->token = $config["token"];
		$this->default = (object) [
			"lead" => (object) []
		];
		if (!empty($config["title"])) $this->default->lead->title = $config["title"];
		$this->default->lead->source_id = $config["source_id"];
	}

	/**
	 * Create new lead
	 *
	 * array['payload'] array of lead data (required)
	 * array['payload']['title'] string title for lead (replaces the source from the config)
	 * array['payload']['source_id'] string source id for lead (replaces the source from the config)
	 * array['payload']['name'] string client name for lead
	 * array['payload']['second_name'] string client second name for lead
	 * array['payload']['last_name'] string client last name for lead
	 * array['payload']['email'] string client email for lead
	 * array['payload']['phone'] string client phone for lead
	 * array['payload']['assigned_by_id'] string id of user who will be assigned to lead
	 * array['payload']['address'] string client address for lead
	 * array['payload']['utc'] string time zone in +3 format
     * array['payload']['utm_source'] string utm source (take from utm_source get parameter) example: garant
	 * array['payload']['comments'] string comments for lead
	 *
	 * @param array $payload (See above)
	 * @throws Exception
	 * @return object
	 */
	public function newLead($payload){
		$sentData = [];

		if (!empty($payload["title"]))
			$sentData["title"] = $payload["title"];
		else
			$sentData["title"] = $this->default->lead->title;

		if (!empty($payload["source_id"]))
			$sentData["source_id"] = $payload["source_id"];
		else
			if (!empty($this->default->lead->source_id))
				$sentData["source_id"] = $this->default->lead->source_id;

		if (!empty($payload["name"])) $sentData["name"] = $payload["name"];
		if (!empty($payload["second_name"])) $sentData["second_name"] = $payload["second_name"];
		if (!empty($payload["last_name"])) $sentData["last_name"] = $payload["last_name"];
		if (!empty($payload["email"])) $sentData["email"] = $payload["email"];
		if (!empty($payload["phone"])) $sentData["phone"] = preg_replace('/\s|\+|-|\(|\)/','', $payload["phone"]);;
		if (!empty($payload["assigned_by_id"])) $sentData["assigned_by_id"] = $payload["assigned_by_id"];
		if (!empty($payload["address"])) $sentData["address"] = $payload["address"];
		if (!empty($payload["utc"])) $sentData["utc"] = $payload["utc"];
		if (!empty($payload["utm_source"])) $sentData["source"] = $payload["utm_source"];
		if (!empty($payload["comments"])) $sentData["comments"] = $payload["comments"];

		return $this->addLead($sentData);
	}

	/**
	 * @throws Exception
	 */
	private function addLead($fields){
		try {
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => "https://crm.exprus.academy/rest/addcontact",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($fields),
				CURLOPT_HTTPHEADER => [
					"Authorization: Bearer " . $this->token,
					"Content-Type: application/json"
				],
			]);

			$response = curl_exec($curl);
			$err = curl_error($curl);

            var_dump($err);
            var_dump($response);

			curl_close($curl);

			return $this->responsePreparation(json_decode($response));

		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * @param $data
	 * @return object
	 */
	private function responsePreparation($data){
		$isError = false;
		if (!isset($data->ok)) $isError = true;

		return (object) [
			"status" => $isError ? "error" : "ok",
			"data" => $isError ? json_encode($data) : $data->ok
		];
	}

	private function error($message){
		return (object) [
			"status" => "error",
			"data" => $message
		];
	}
}
