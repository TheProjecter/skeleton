<?php
/**
 * Base MVC View class for a whole or partial HTTP response. Encapsulates headers, redirects,
 * character encoding, quoting, escaping, and content. 
 * 
 * @package A_Http 
 */

class A_Http_View {
	protected $data = array();
	protected $renderer = null;
	protected $headers = array();
	protected $cookies = array();
	protected $redirect = null;
	protected $content = '';
	protected $escape_quote_style = ENT_QUOTES;
	protected $escape_output = false;
	protected $character_set = 'UTF-8';
	protected $locator = null;
	protected $load;
	protected $flash;
	protected $helpers = array();
	
	public function __construct($locator=null) {
		$this->locator = $locator;
	}
	
	public function setCharacterSet($character_set) {
		$this->character_set = $character_set;
		return $this;
	}

	public function setQuoteStyle($escape_quote_style) {
		$this->escape_quote_style = $escape_quote_style;
		return $this;
	}

	public function setEscape($escape_output) {
		$this->escape_output = $escape_output;
		return $this;
	}

	public function setHeader($field, $param=null) {
		if (is_array($param)) {
			$this->headers[$field] = $param;
		} else {
			$this->headers[$field][0] = $param;
		}
		return $this;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function setCookie() {
		$args = func_get_args();
		$this->cookie[$args[0]] = $args;
		return $this;
	}

	public function getCookie($name) {
		if (isset($this->cookie[$name])) {
			return $this->cookie[$name];
		}
	}

	public function setRedirect($url) {
		$this->redirect = $url;
		return $this;
	}

	public function getRedirect() {
		return $this->redirect;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function getContent() {
		return $this->content;
	}

	public function setRenderer($renderer) {
		$this->renderer = $renderer;
		return $this;
	}

	public function hasRenderer() {
		return isset($this->renderer);
	}

	public function set($name, $value) {
		if ($value !== null) {
			$this->data[$name] = $value;
		} else {
			unset($this->data[$name]);
		}
		return $this;
	}

	public function get($name) {
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function has($name) {
		return isset($this->data[$name]);
	}

	public function escape($content, $escape_quote_style=null) {
		return htmlspecialchars($content, $escape_quote_style==null ? $this->escape_quote_style : $escape_quote_style, $this->character_set);
	}
	
	public function render() {
		if ($this->renderer) {
			if ($this->data) {
				foreach ($this->data as $name => $value) {
					if (method_exists($this->data[$name], 'render')) {
						$this->renderer->set($name, $this->data[$name]->render());
					} else {
						$this->renderer->set($name, $value);
					}
				}
			}
		}
		if (method_exists($this->renderer, 'render')) {
			$this->content = $this->renderer->render();
		}
		if (! $this->escape_output) {
			return $this->content;
		} else {
			return $this->escape($this->content);
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		return $this->set($name, $value);
	}

	public function __toString() {
		return $this->render();
	}

	protected function load($scope=null) {
		if (isset($this->load)) {
			$this->load->load($scope);
		} else {
			include_once "A/Controller/Helper/Load.php";
			$this->load = new A_Controller_Helper_Load($this->locator, $this, $scope);
		}
		return $this->load;
	}
 
	protected function flash($name=null, $value=null) {
		if (! isset($this->flash)) {
			include_once "A/Controller/Helper/Flash.php";
			$this->flash = new A_Controller_Helper_Flash($this->locator);
		}
		if ($name) {
			if ($value) {
				$this->flash->set($name, $value);
			} else {
				return $this->flash->get($name);
			}
		}
		return $this->flash;
	}
 
	public function setHelper($name, $helper) {
		if ($name) {
			$this->helpers[$name] = $helper;
		}
		return $this;
	}
 
	protected function helper($name) {
		if (isset($this->helpers[$name])) {
			return $this->helpers[$name];
		}
	}

}