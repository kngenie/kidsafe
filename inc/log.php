<?php
/* gets log entries - same parameters as viewlog.php
but as php values rather than as parameters

This can be called from static or ajax

*/

// filter 
// start
// maxlines ???
// order

// open file
$logfile = fopen($logfilename, "r");
if ($start != 0) {fseek($logfile, $start);}

while ($thisentry = fgets($logfile))
{
	// does it match filter
	
	// split rule so we can check for appropriate log entry type - and perhaps add span
	$thisentry_split = explode (' ', $thisentry, 5);
	// logentrytype - set to accept, reject or '' depending upon the type of rule - could also assign other rules - is used as class within span (log-other / log-accept / log-reject)
	$logentrytype = 'other';
	// split to find rule number
	$thisentry_parts = '';
	
	if (isset($thisentry_split[4])) 
	{
		$thisentry_parts = explode (':', trim($thisentry_split[4]));
	}
	if (isset ($thisentry_split[3]) && $thisentry_split[3] == 'ACCEPT') {$logentrytype = 'accept';}
	// special case - if this is the default rule
	elseif (isset ($thisentry_split[3]) && $thisentry_split[3] == 'REJECT' && isset($thisentry_parts[2]) && $thisentry_parts[2] == '1') {$logentrytype = 'default';}
	elseif (isset ($thisentry_split[3]) && $thisentry_split[3] == 'REJECT') {$logentrytype = 'reject';}
	
	// entry with added span and <br>\n
	$html_logentry = "<span class=\"log-$logentrytype\">$thisentry</span><br>\n";
	
	// first is it view all - if so don't need to test
	if ($filter == 'all')
	{
		// add span if this is accept or reject
		$logentries[] = $html_logentry;
		continue;
	}
	// reject entry
	elseif (($filter == 'access' || $filter == 'reject') && isset ($thisentry_split[3]) && $thisentry_split[3] == "REJECT") 
	{
		$logentries[] = $html_logentry;
	}
	elseif (($filter == 'access' || $filter == 'accept') && isset ($thisentry_split[3]) && $thisentry_split[3] == "ACCEPT")
	{
		$logentries[] = $html_logentry;
	}
}
fclose ($logfile);

$html_log = '';

// Need at least 1 entry
if (count ($logentries) > 1)
{
	// order
	if ($order == 'oldest')
	{
		$direction = 1;
		if ($maxlines != 0 && count($logentries) > $maxlines)
		{
			$startpos = count($logentries) - $maxlines -1;
			$endpos = count($logentries) - 1;
		}
		else 
		{
			$startpos = 0;
			$endpos = count($logentries) - 1;
		}
	}
	else
	{
		$direction = -1;
		if ($maxlines != 0 && count($logentries) > $maxlines)
		{
			$endpos = count($logentries) - $maxlines -1;
			$startpos = count($logentries) - 1;
		}
		else 
		{
			$endpos = 0;
			$startpos = count($logentries) - 1;
		}
	}
	
	//$html_log .= "Start $startpos, End $endpos, Direction $direction";
	
	
	for ($i=$startpos; $i!=$endpos; $i+=$direction)
	{
		$html_log .= $logentries[$i];
	}
}






?>
