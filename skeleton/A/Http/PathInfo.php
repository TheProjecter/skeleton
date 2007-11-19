<?php
/*
This class maps PATH_INFO values onto request vars using an array that describes
the mappings. The array in in the format of the following example:

$map = array(
	'' => array(		// '' is the route array used when no match is found
		'controller',		// position in PATH_INFO and requestprotected to map onto
		'action',
		'id',
		),
	'date' => array(	// if 'date' is found in the first element of PATH_INFO use the map below
		'' => array(
			'controller',
			'year',
			'month',
			'day',
			),
		),
	);
	
The mapper starts at the root level of the array and searches keys for a match on the first
element in the PATH_INFO. If a key match is found it then uses the array under that key to search
the next element in PATH_INFO. If no match is found then the [''] index in the array is used. 
Every array should have a [''] key containing the route array to use to map PATH_INFO to Request vars. 

In the example above, the PATH_INFO of "/view/person/Bob/" will map to the Request as 
"?controller=view&action=person&id=Bob" because 'view' does not match a key in the root array 
of the map, in this map that is '' and 'date'. The [''] array at the level where no match was found 
is used for the mapping. The PATH_INFO of "/date/2006/January/1st/" will map to the Request
as "?controller=date&year=2006&month=January&day=1st" because 'date' matches a key in the map.

*Additional parameters in the PATH_INFO past those defined in the map with be combined in pairs
to request vars. For example, /view/person/Bob/age/42/height/84/ will map the the Reqest as
"?controller=view&action=person&id=Bob&age=42&height=84". This can be turned off with the
$map_extra_param_pairs parameter. 

The position where mapping stopped is saves so additional maps can be applied to PATH_INFO. For
example, the Front Http might map the first two values in PATH_INFO to the default
/controller/action/ parameters. Different dispatched controllers may then set their own maps
to map the parameters in PATH_INFO starting at the third parameter. 
*/
class A_Http_PathInfo {
	protected $map = array(
					'' => array(
						'controller',
						'action',
						),
					);
	protected $map_extra_param_pairs;
	protected $path = '';
	protected $path_pos = 0;		// the position in path_info after the end of the current route
	protected $script_extension = '.php';

    public function __construct($map=null, $map_extra_param_pairs=true) {
    	if ($map != null) {
    		$this->map = $map;
    	}
    	$this->map_extra_param_pairs = $map_extra_param_pairs;

        if (isset($_SERVER['PATH_INFO'])) {
        	$path = $_SERVER['PATH_INFO'];
        } else {
	        $path = $_SERVER['REQUEST_URI'];
	        if (strpos($path, $this->script_extension) !== FALSE) {
				$base = $_SERVER['SCRIPT_NAME'];			// using script name
	        } else {
	            $base = dirname($_SERVER['SCRIPT_NAME']);		// using rewrite rules
	        }
	        if ($base != '/') {
	        	$len = strlen($base) + 1;
	        	$path = substr($path, $len);
	        }
	        if (strstr($path, '?')) {
	            $path = substr($path, 0, strpos($path, '?'));
	        }
        }
        $this->path = trim($path, '/');
	}
	
	public function setScriptExtension($script_extension) {
		$this->script_extension = $script_extension;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function setMap($map) {
		$this->map = $map;
	}

	public function addMap($map) {
		$this->map = array_merge($this->map, $map);
	}

	public function run($request) {
#        if ($this->path) {
// search map for route
        	$request->set('PATH_INFO', $this->path);
        	if ($this->map) {
        		$path_info = explode('/', $this->path);
        		$i = $this->path_pos;			// start a previous position
        		$path_info_size = count($path_info);
        		$map =& $this->map;
        		while ($i < $path_info_size) {
        			$value = $path_info[$i];
        			if ($value) {
        				if (array_key_exists($value, $map)) {
        					$map =& $map[$value];
        				} else {
		        			++$i;
        					break;
        				}
        			}
        			++$i;
        		}
				       		
// assign parameters based on route
				if (isset($map[''])) {
					$route = $map[''];
	        		$route_size = count($route);
					for ($i=$this->path_pos, $j=0; $j<$route_size; ++$i, ++$j) {
						if (!isset($path_info[$i])) {
							$path_info[$i] = '';
						}
						if (is_array($route[$j])) {
							if (isset($route[$j]['replace']) && $route[$j]['replace']) {
								if (is_array($route[$j]['replace'])) {
									foreach ($route[$j]['replace'] as $name => $value) {
										$request->set($name, $value);
									}
								} else {
									$request->set($route[$j]['name'], $route[$j]['replace']);
								}
							} elseif ($path_info[$i] == '' && isset($route[$j]['default'])&& $route[$j]['default']) {
								$request->set($route[$j]['name'], $route[$j]['default']);
							} else {
								$request->set($route[$j]['name'], $path_info[$i]);
							}
							if (isset($route[$j]['stop'])) {
								break;
							}
						} else {
							$request->set($route[$j], $path_info[$i]);
						}
					}
					$this->path_pos = $i;	// save position so route can be re-run
				}

				if ($this->map_extra_param_pairs) {
// assign extra parameter pairs
	        		while ($i < $path_info_size) {
						$param = isset($path_info[$i]) ? $path_info[$i] : null;
		        		if (++$i < $path_info_size) {
							$value = isset($path_info[$i]) ? $path_info[$i] : null;
							if ($param != null) {
								$request->set($param, $value);
							}
						}
						++$i;
	        		}
				}
        	}
        }
#	}
}
