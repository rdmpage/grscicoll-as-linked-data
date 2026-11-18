<?php

$basedir = 'institution';

$files = scandir($basedir);

$identifier_type = [];

foreach ($files as $filename)
{
	if (preg_match('/\.json$/', $filename))
	{	
		$json = file_get_contents($basedir . '/' . $filename);
		
		$obj = json_decode($json);
		
		if ($obj)
		{
			foreach ($obj->results as $hit)
			{
				//echo $hit->code . "\n";
				
				foreach ($hit->identifiers as $identifier)
				{
					if (!isset($identifier_type[$identifier->type]))
					{
						$identifier_type[$identifier->type] = 0;
					}
					$identifier_type[$identifier->type]++;
				}
				
				
			}
		
		}
	}
}

print_r($identifier_type);

?>
