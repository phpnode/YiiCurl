<?php
Yii::import("packages.curl.*");
/**
 * A wrapper that provides easy access to curl functions.
 * @author Charles Pick
 * @package packages.curl
 */
class ACurl extends CComponent {
	/**
	 * Holds the options to use for this request
	 * @see getOptions
	 * @see setOptions
	 * @var ACurlOptions
	 */
	protected $_options;

	/**
	 * Holds the curl handle
	 * @var resource
	 */
	protected $_handle;

	/**
	 * Holds the cache key
	 * @var string
	 */
	protected $_cacheKey;

	/**
	 * The caching duration
	 * @var integer
	 */
	protected $_cacheDuration;

	/**
	 * The cache dependency
	 * @var CCacheDependency
	 */
	protected $_cacheDependency;

	/**
	 * The cache component to use when caching results
	 * @var CCache
	 */
	protected $_cacheComponent;

	/**
	 * Whether to cache the query result or not.
	 * Defaults to false.
	 * @var boolean
	 */
	protected $_cache = false;

	/**
	 * Returns the CURL handle for this request
	 * @var resource
	 */
	public function getHandle() {
		if ($this->_handle === null) {
			$this->_handle = curl_init();
		}
		return $this->_handle;
	}

	/**
	 * Sets the curl handle for this request.
	 * @param resource $value The CURL handle
	 * @return ACurl $this with the handle set.
	 */
	public function setHandle($value) {
		$this->_handle = $value;
		return $this;
	}

	/**
	 * Gets the options to use for this request.
	 * @return ACurlOptions the options
	 */
	public function getOptions() {
		if ($this->_options === null) {
			$this->_options = new ACurlOptions(array(
				"userAgent" => "Yii PHP Framework / ACurl",
				"header" => true,
				"followLocation" => true,
				"returnTransfer" => true,
				"timeout" => 30,
				"encoding" => "gzip",
				"ssl_verifypeer" => false,


			));
		}
		return $this->_options;
	}

	/**
	 * Sets the options to the given value.
	 * @param mixed $value the options, either an array or an ACurlOptions object
	 * @return ACurl $this with the modified options
	 */
	public function setOptions($value) {
		if (is_array($value)) {
			$value = new ACurlOptions($value);
		}
		$this->_options = $value;
		return $this;
	}

	/**
	 * Prepares the CURL request, applies the options to the handler.
	 */
	public function prepareRequest() {
		$this->getOptions()->applyTo($this);

	}

	/**
	 * Sets the post data and the URL to post to and performs the request
	 * @param string $url The URL to post to.
	 * @param array $data The data to post key=>value
	 * @param boolean $execute whether to execute the request immediately or not, defaults to true
	 * @return ACurlResponse|ACurl the curl response, or $this if $execute is set to false
	 */
	public function post($url, $data = array(), $execute = true) {
		$this->getOptions()->url = $url;
		$this->getOptions()->postfields = is_string($data) ? $data : http_build_query($data);
		$this->getOptions()->post = true;
		$this->prepareRequest();
		return $execute ? $this->exec() : $this;
	}


	/**
	 * Sets the PUT data and the URL to PUT to and performs the request
	 * @param string $url The URL to PUT to.
	 * @param array $data The data to PUT key=>value
	 * @param boolean $execute whether to execute the request immediately or not, defaults to true
	 * @return ACurlResponse|ACurl the curl response, or $this if $execute is set to false
	 */
	public function put($url, $data = array(), $execute = true) {
		$this->getOptions()->url = $url;
		$this->getOptions()->postfields = is_string($data) ? $data : http_build_query($data);
		$this->getOptions()->post = true;
		$this->getOptions()->customRequest = "PUT";
		$this->prepareRequest();
		return $execute ? $this->exec() : $this;
	}

	/**
	 * Sets the DELETE data and the URL to DELETE to and performs the request
	 * @param string $url The URL to DELETE to.
	 * @param boolean $execute whether to execute the request immediately or not, defaults to true
	 * @return ACurlResponse|ACurl the curl response, or $this if $execute is set to false
	 */
	public function delete($url, $execute = true) {
		$this->getOptions()->url = $url;
		$this->getOptions()->customRequest = "DELETE";
		$this->prepareRequest();
		return $execute ? $this->exec() : $this;
	}


	/**
	 * Sets the URL and performs the GET request
	 * perform the actual request.
	 * @param string $url The URL to get.
	 * @param boolean $execute whether to execute the request immediately or not, defaults to true
	 * @return ACurlResponse|ACurl the curl response, or $this if $execute is set to false
	 */
	public function get($url, $execute = true) {
		$this->getOptions()->url = $url;
		$this->getOptions()->post = false;
		$this->prepareRequest();
		return $execute ? $this->exec() : $this;
	}
	/**
	 * Sets the URL and performs the HEAD request
	 * @param string $url The URL to post to.
	 * @param boolean $execute whether to execute the request immediately or not, defaults to true
	 * @return ACurlResponse|ACurl the curl response, or $this if $execute is set to false
	 */
	public function head($url, $execute = true) {
		$this->getOptions()->url = $url;
		$this->getOptions()->nobody = true;
		$this->prepareRequest();
		return $execute ? $this->exec() : $this;
	}

	/**
	 * Executes the request and returns the response.
	 * @return ACurlResponse|false the wrapped curl response, or false if the request is stopped by beforeRequest()
	 * @throws ACurlException a curl exception if there was a probl
	 */
	public function exec() {
		$response = new ACurlResponse;
		$response->request = $this;
		$data = false;
		if (!$this->beforeRequest()) {
			return false;
		}
		$cache = $this->_cache;
		if ($this->getOptions()->itemAt("post") || $this->getOptions()->itemAt("customRequest")) {
			$cache = false;
		}
		if ($cache) {
			$data = $this->getCacheComponent()->get($this->getCacheKey());
		}
		if ($data === false) {
			$data = curl_exec($this->getHandle());
			if ($cache) {
				$this->getCacheComponent()->set($this->getCacheKey(),$data,$this->_cacheDuration,$this->_cacheDependency);
			}
		}
		$response->data = $data;
		if ($this->getOptions()->header) {
			$response->headers = mb_substr($response->data, 0, $response->info->header_size);
			$response->data = mb_substr($response->data, $response->info->header_size);
			if (mb_strlen($response->data) == 0) {
				$response->data = false;
			}
		}
		if ($response->getIsError() && $response->getLastHeaders() !== false) {
			throw new ACurlException($response->getLastHeaders()->http_code,"Curl Error: ".$response->getLastHeaders()->http_code,$response);
		}
		if (curl_error($this->getHandle())) {
			throw new ACurlException(curl_errno($this->getHandle()),curl_error($this->getHandle()), $response);
		}
		$this->afterRequest($response);
		return $response;
	}

	/**
	 * Enables caching for curl requests
	 * @param integer $duration the caching duration
	 * @param CCacheDependency $dependency the cache dependency for this request
	 * @return ACurl $this with the cache setting applied
	 */
	public function cache($duration = 60, $dependency = null) {
		$this->_cache = true;
		$this->_cacheDuration = $duration;
		$this->_cacheDependency = $dependency;
		return $this;
	}

	/**
	 * Sets the cache component to use for this request
	 * @param CCache $cacheComponent the cache component
	 */
	public function setCacheComponent($cacheComponent) {
		$this->_cacheComponent = $cacheComponent;
	}

	/**
	 * Gets the cache component for this curl request
	 * @return CCache the caching component to use for this request
	 */
	public function getCacheComponent() {
		if ($this->_cacheComponent === null) {
			$this->_cacheComponent = Yii::app()->getCache();
		}
		return $this->_cacheComponent;
	}

	/**
	 * Sets the cache key for this request
	 * @param string $cacheKey the cache key
	 * @return string the cache key
	 */
	public function setCacheKey($cacheKey) {
		return $this->_cacheKey = $cacheKey;
	}

	/**
	 * Gets the cache key for this request
	 * @return string the cache key
	 */
	public function getCacheKey() {
		if ($this->_cacheKey === null) {
			$this->_cacheKey = "ACurl:cachedRequest:".sha1(serialize($this->getOptions()->toArray()));
		}
		return $this->_cacheKey;
	}
	/**
	 * This method is invoked before making a curl request.
	 * The default implementation raises the {@link onBeforeRequest} event.
	 * @return boolean whether the event is valid and the request should continue
	 */
	protected function beforeRequest() {
		if($this->hasEventHandler('onBeforeRequest'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeRequest($event);
			return $event->isValid;
		}
		else
			return true;
	}
	/**
	 * This event is raised before the curl request is made
	 * @param CModelEvent $event the event parameter
	 */
	public function onBeforeRequest($event) {
		$this->raiseEvent('onBeforeRequest',$event);
	}
	/**
	 * This method is invoked after a curl request.
	 * The default implementation raises the {@link onAfterRequest} event.
	 * @param ACurlResponse $response the curl response
	 */
	protected function afterRequest(ACurlResponse $response) {
		if ($this->hasEventHandler('onAfterRequest')) {
			$event = new CModelEvent();
			$event->params['response'] = $response;
			$this->onAfterRequest($event);
		}
	}
	/**
	 * This event is raised after the curl request is made
	 * @param CModelEvent $event the event parameter
	 */
	public function onAfterRequest($event) {
		$this->raiseEvent('onBeforeRequest',$event);
	}
}
