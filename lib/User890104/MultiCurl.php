<?php
namespace User890104;

class MultiCurl {
    private $multi_handle;
    private $handles = [];

    public function __construct() {
        $multi_handle = curl_multi_init();

        if ($multi_handle === false) {
            throw new Exception('cURL multi init failed');
        }

        $this->multi_handle = $multi_handle;
    }

    public function __destruct() {
        foreach ($this->handles as $handle) {
            curl_multi_remove_handle($this->multi_handle, $handle);
        }

        curl_multi_close($this->multi_handle);

        foreach ($this->handles as $handle) {
            curl_close($handle);
        }

        $this->handles = [];
    }

    public function addRequest($params, $name = null) {
        $handle = curl_init();

        if ($handle === false) {
            throw new Exception('cURL init failed');
        }

        if (curl_setopt_array($handle, $params) === false) {
            throw new Exception('cURL setopt failed');
        }

        $res = curl_multi_add_handle($this->multi_handle, $handle);

        if ($res !== 0) {
            throw new Exception('cURL multi add failed');
        }

		if (!is_null($name)) {
			$this->handles[$name] = $handle;
		}
		else {
			$this->handles[] = $handle;
		}
		
        return $handle;
    }

    public function removeRequest($handle) {
        if (!in_array($handle, $this->handles)) {
            return false;
        }

        if (curl_multi_remove_handle($this->multi_handle, $handle) !== 0) {
            throw new Exception('cURL multi remove failed');
        }

        return true;
    }

    public function run() {
        $running = null;

        do {
            $res = curl_multi_exec($this->multi_handle, $running);
        }
        while ($res === CURLM_CALL_MULTI_PERFORM);

        if ($running === 0) {
            return false;
        }

        do {
            $res = curl_multi_select($this->multi_handle);
        }
        while ($res === 0);

        if ($res === -1) {
            usleep(100);
        }

        return true;
    }

    public function getResponses() {
        $responses = [];

        foreach ($this->handles as $name => $handle) {
            $errno = curl_errno($handle);

            if ($errno === CURLE_OK) {
                $result = curl_multi_getcontent($handle);
            }
            else {
                $result = false;
            }
			
			$response = [
                'result' => $result,
                'errno' => $errno,
                'error' => curl_error($handle),
                'code' => curl_getinfo($handle, CURLINFO_HTTP_CODE),
            ];

			if (!is_null($name)) {
				$responses[$name] = $response;
			}
			else {
				$responses[] = $response;
			}
        }

        return $responses;
    }
}
