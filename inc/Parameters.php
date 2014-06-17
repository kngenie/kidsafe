<?php
/*** 
Parameters class for kidsafe
// handles processing of get / post parameters with additional security checking

Number of parms is getting quite high - perhaps better to split based on which pages needs them - or perhaps rationalise / standardise

Also consider making the tests more accurate - datetime etc.

***/


class Parameters
{
	private $parms = array();
	// parms allowed in get - does not have to exist
	// calling code should handle if value does not exist ''
	// value is type of entry expected for checks - does not check for correct values - only for invalid attempts (eg. invalid chars)
	// valid values = domain (fqdn or ip address - including regexp / dot prefix), reltime (any relative time format - eg. 2 hours), url (full url including http(s):\\), relurl (relative url eg. page.php), alphanum (strip spaces / no special chars), text (allows special chars - but convert any <> to &lt; / &gt; to prevent html code), int = integer (as per intval - this means won't accept 0 as a value)
	
	// need to create a proper datetime check
	
	// parameters used in get
	private $getparms = array ('host'=>'domain', 'timeallowed'=>'reltime', 'url'=>'url', 'source'=>'domain', 'addpermission'=>'alphanum', 'redirect'=>'relurl', 'message'=>'alphanum', 'id'=>'alphanum', 'username'=>'alphanum', 'filter'=>'alphanum', 'start'=>'int', 'maxlines'=>'int', 'order'=>'alphanum');
	 
	private $postparms = array ('host'=>'domain', 'site'=>'domain', 'timeallowed'=>'reltime', 'url'=>'url', 'source'=>'domain', 'addgroup'=>'alphanum', 'addtemplate'=>'alphanum', 'user'=>'alphanum', 'username'=>'alphanum', 'password'=>'alphanum', 'site'=>'domain', 'comments'=>'text', 'add'=>'alphanum', 'duration'=>'reltime', 'addpermission'=>'alphanum', 'allowlevel'=>'int', 'newpassword'=>'alphanum', 'repeatpassword'=>'alphanum', 'id'=>'alphanum', 'sites'=>'int', 'template'=>'alphanum', 'custom-groups'=>'alphanum', 'expiry'=>'datetime', 'action'=>'alphanum', 'permission'=>'int', 'log'=>'bool', 'priority'=>'int', 'sitename'=>'domain', 'title'=>'text', 'fullname'=>'text', 'access'=>'int', 'status'=>'bool', 'loginexpiry'=>'int', 'supervisor'=>'bool', 'admin'=>'bool');
	
	// set type to 'get' if we have 1 or more get entries
	// if not get entries, but are post set to 'post'
	// otherwise leave blank
	private $type = ''; 
	
	
	public function __construct ()
	{
		// handle get first - if no get then post (can't have both)
		foreach ($this->getparms as $key=>$value)
		{
			if (isset ($_GET[$key]))
			{
				$this->type = 'get';
				$this->parms[$key] = $this->_checkParm ($_GET[$key], $key, $value);
			}
		}
		if ($this->type != 'get')
		{
			foreach ($this->postparms as $key=>$value)
			{
				if (isset ($_POST[$key]))
				{
					$this->type = 'post';
					$this->parms[$key] = $this->_checkParm ($_POST[$key], $key, $value);
				}
			}
		}
	}

	// returns the paramter to the calling code
	// if no entry returns ''	
	public function getParm ($parm)
	{
		if (isset ($this->parms[$parm])) { return $this->parms[$parm];}
		else {return "";}
	}
	


	// perform basic security checking
	// any failures then we enter a '' into the value
	function _checkParm ($value, $parmname, $parmtype)
	{
		if ($parmtype=='url')
		{
			// if empty (eg. gone direct to addrule.php) then return ''
			if ($value == '') {return '';}
			// urls are encoded so we decode
			$unsafe_url = urldecode($value);
			
			
			// check url is valid (# used as regular expression delimeter so we don't have to escape /) and separate out the domain part 
			$url_array = parse_url($unsafe_url);
			// check that this is a http / https (do not allow file://)
			if ($url_array == false || !isset($url_array['scheme']) || ($url_array['scheme']!= 'http' && $url_array['scheme']!= 'https'))
			{
				if (isset($debug) && $debug) {print "Error in parameter $parmname, unrecognised scheme\n$value\n";}
				$err =  Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Error with parameter $parmname, unrecognised scheme\n$value\n"); 
				return '';
			}
			
			// check no < are in the url which could be used for xss 
			// if invalid then we set to '' - also set ['error'] to provide a message back
			if (preg_match ('/&lt;|</', $unsafe_url)) 
			{
				if ($debug) {print "Error in parameter $parmname, invalid character\n";}
				$err =  Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Error with parameter $parmname, invalid character"); 
				return '';
			}
			else 
			{
				// we hav now verfied url as being safe
				return $unsafe_url;
			}
			
			
		}
		elseif ($parmtype == 'relurl')
		{
			$unsafe_page = $value;
			// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
			if (preg_match('/^[\w-\.]+$/', $unsafe_page))
			{
				return $unsafe_page;
			}
			else 
			{
				return "";
			}
		}
		elseif ($parmtype == 'domain')
		{
			$unsafe_host = $value;
			// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
			if (preg_match('/^\*?[\w-\.]+$/', $unsafe_host))
			{
				return $unsafe_host;
			}
			// check if it's a regexp (just test regexp doesn't give a false (different from 0 if it doesn't match)
			elseif (strpos ($unsafe_host, '/') === 0)
			{
				// reutrns false on invalid - 0 on no match, 1 on match
				if (@preg_match($unsafe_host, '') === false)
				{
					if ($debug) {print "Error in parameter $parmname, not a regular epxression\n";}
					$err =  Errors::getInstance();
					$err->errorEvent(ERROR_PARAMETER, "Error with parameter $parmname, not a regular expression"); 
					return '';
				}
				else 
				{
					return $unsafe_host;
				}
			}
			else
			{
				return '';
			}
		}
		// rel time - just check for xx mins / xx hours etc. actually use strtotime to parse 
		elseif ($parmtype == 'reltime')
		{
			$unsafe_time = $value;
			// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
			// just allow minutes or hours - don't do days or secs
			if (preg_match('/^\d+\s*(min(utes)?)|(hours?)$/', $unsafe_time))
			{
				return $unsafe_time;
			}
			// always
			elseif ($unsafe_time == 'Always')
			{
				return 'Always';
			}
			else
			{
				return '';
			}
		}
		// alphanum and -_ and special case just '*'
		elseif ($parmtype == 'alphanum')
		{
			// just allow printable chars (\w)
			if (preg_match('/^[\w-_]+$/', $value))
			{
				return $value;
			}
			elseif ($value == '*')
			{
				return $value;
			}
			else
			{
				return '';
			}
		}
		// just strip out <> - replace with &lt;&gt;
		elseif ($parmtype == 'text')
		{
			$unsafe_text = $value;
			$unsafe_text = preg_replace ('/</', '&lt;', $unsafe_text);
			$unsafe_text = preg_replace ('/>/', '&gt;', $unsafe_text);
			return $unsafe_text;
		}
		// Need to properly test datetime - currently just using datetime
		// needs to allow * or 0 (equivalant to 0000-00-00 00:00)
		elseif ($parmtype == 'datetime')
		{
			$unsafe_text = $value;
			$unsafe_text = preg_replace ('/</', '&lt;', $unsafe_text);
			$unsafe_text = preg_replace ('/>/', '&gt;', $unsafe_text);
			return $unsafe_text;
		}
		// use intval()
		elseif ($parmtype == 'int')
		{
			$int_value = intval($value);
			if ($int_value != 0) {return $int_value;}
			else {return '';}
		}
		// bool - allow 1 0 true false
		// default for invalid = false (therefore calling functions should default as false = safe value - eg. Admin only if true
		elseif ($parmtype == 'bool')
		{
			if ($value == 'true' || $value == '1')
			{
				return true;
			}
			else {return false;}
		}
		
		// possible invalid type
		return "";
	}
	
	
}
?>
