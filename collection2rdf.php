<?php

require_once(dirname(__FILE__) . '/shared.php');

$basedir = 'collection';

$files = scandir($basedir);
//$files = ['11000.json'];

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
				// create a simple object that we will convert to triples
				
				$doc = new stdclass;
				$doc->id = 'https://scientific-collections.gbif.org/collection/' . $hit->key;
				$doc->type = [];
				$doc->name = $hit->name;
				$doc->alternateName = [];
				
				if (isset($hit->code))
				{
					$doc->alternateName[] = $hit->code;
				}
				
				foreach ($hit->alternativeCodes as $code)
				{
					$doc->alternateName[] = $code->code;
				}
				
				// types (huh?)
				$doc->type = ['Collection'];
				
				foreach ($hit->identifiers as $identifier)
				{
					switch ($identifier->type)
					{
						case 'DOI':
						case 'ROR':
						case 'WIKIDATA':
							$doc->sameAs[] = $identifier->identifier;
							break;
							
						// ignore for now			
						default:
							break;
					}
				}
				
				if (isset($hit->institutionKey))
				{
					$doc->isPartOf = 'https://scientific-collections.gbif.org/institution/' . $hit->institutionKey;
				}

				//print_r($doc);
				
				$triples = [];
				
				foreach ($doc->type as $type)
				{
					$s = $doc->id;
					$p = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
					$o = 'https://schema.org/' . $type;		
					$triples[] = [$s, $p, $o];
				}
				
				$s = $doc->id;			
				$p = 'https://schema.org/name';
				$o = '"' . nice_literal($doc->name) . '"';
				$triples[] = [$s, $p, $o];	
				
				foreach ($doc->alternateName as $name)
				{
					$s = $doc->id;
					$p = 'https://schema.org/alternateName';
					$o = '"' . nice_literal($name) . '"';
					$triples[] = [$s, $p, $o];
				}
				
				if (isset($doc->isPartOf))
				{
					$s = $doc->id;
					$p = 'https://schema.org/isPartOf';
					$o = nice_uri($doc->isPartOf);
					$triples[] = [$s, $p, $o];
				}
	
				$output = dump_triples($triples);			
				echo $output . "\n";
				
			}
		}
	}
}


?>
