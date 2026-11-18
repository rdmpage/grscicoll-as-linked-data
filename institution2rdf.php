<?php

// Take a look at institutions to get a sense of what it contains

require_once(dirname(__FILE__) . '/shared.php');

$basedir = 'institution';

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
				$doc->id = 'https://scientific-collections.gbif.org/institution/' . $hit->key;
				$doc->name = $hit->name;
				$doc->sameAs = [];
				$doc->alternateName = [];
				
				if (isset($hit->code))
				{
					$doc->alternateName[] = $hit->code;
				}
				
				if (isset($hit->description))
				{
					$doc->description = $hit->description;
				}
								
				// types
				// everything is an organisation
				$doc->type = ['Organization'];
				
				// map types to schema.org (maybe need to expand this, but to what?)
				foreach ($hit->types as $type)
				{
					switch ($type)
					{
						case 'UniversityCollege':
							$doc->type[] = 'CollegeOrUniversity';
							break;
							
						case 'Museum':
							$doc->type[] = 'Museum';
							break;

						// these don't really have a clean mapping
						case 'MuseumHerbariumPrivateNonProfit':
						case 'OtherTypeResearchInstitutionBiorepository':
						case 'OtherInstitutionalType':
						case 'ZooAquarium':
						case 'BotanicalGarden':
						case 'Herbarium':
						case 'MedicalResearchInstitute':
						case 'BiomedicalResearchInstitute':
						default:
							break;
					}				
				}
				
				// identifiers
				
				/*
				[ROR] => 2148q
				[IH_IRN] => 4568
				[UUID] => 1401
				[GRSCICOLL_URI] => 21468
				[GBIF_PORTAL] => 8
				[WIKIDATA] => 3413
				[ISIL] => 675
				[GRSCICOLL_ID] => 7301
				[LSID] => 3353
				[NCBI_BIOCOLLECTION] => 5751
				[CITES] => 263
				[URL] => 43
				[SYMBIOTA_UUID] => 28
				[GRID] => 16
				[URI] => 22
				[UNKNOWN] => 7
				*/
				
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
				
				// location
				if (isset($hit->longitude) && isset($hit->latitude))
				{
					$doc->{'wdt:P625'} = 'POINT(' . format_coordinate($hit->longitude) . ' ' . format_coordinate($hit->latitude) . ')';
				}
				
				// logo
				if (isset($hit->logoUrl))
				{
					$doc->logo = $hit->logoUrl;
				}

				// print_r($doc);
				
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
				
				if (isset($doc->description))
				{
					$s = $doc->id;
					$p = 'https://schema.org/description';
					$o = '"' . nice_literal($doc->description) . '"';
					$triples[] = [$s, $p, $o];
				}				

				foreach ($doc->sameAs as $sameAs)
				{
					$s = $doc->id;
					$p = 'https://schema.org/sameAs';
					$o = nice_uri($sameAs);
					$triples[] = [$s, $p, $o];
				}
				
				// point location
				if (isset($doc->{'wdt:P625'}))
				{
					$s = $doc->id;
					$p = 'http://www.wikidata.org/prop/direct/P625';
					$o = '"' . $doc->{'wdt:P625'} . '"^^<http://www.opengis.net/ont/geosparql#wktLiteral>';
					$triples[] = [$s, $p, $o];
				}

				// logo image
				if (isset($doc->logo))
				{
					$s = $doc->id;
					$p = 'https://schema.org/logo';
					$o = nice_uri($doc->logo);
					$triples[] = [$s, $p, $o];
				}
					
				$output = dump_triples($triples);			
				echo $output . "\n";


			}
		}
	}
}


?>
