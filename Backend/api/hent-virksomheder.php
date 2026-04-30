<?php
/*  henter kun cvr data
error_reporting(E_ALL);
ini_set('display_errors', 1);
*//*
function hentVirksomhedDataFraCVRAPI($cvr) {
    $url = "http://cvrapi.dk/api?search={$cvr}&country=dk";
    
    // Opret kontekst med User Agent
    $options = [
        'http' => [
            'header' => "User-Agent: Mit Sundhedsstempel Projekt - Studieprojekt\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        echo "FEJL: Kunne ikke hente data fra CVRAPI.dk<br>";
        return null;
    }
    $data = json_decode($response, true);
    return $data['name'] ?? '';
}
*/
//kan ikke finde for virksomheder der kun oploaded via pdf

function hentRegnskabsData($cvr) {
    $url = "http://distribution.virk.dk/offentliggoerelser/_search";
    
    $postData = json_encode([
        "query" => ["term" => ["cvrNummer" => $cvr]],
        "size" => 1,
        "sort" => [["offentliggoerelsesTidspunkt" => ["order" => "desc"]]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return null;
    
    $data = json_decode($response, true);
    
    // Find XBRL URL
    $xbxlUrl = null;
    if (isset($data['hits']['hits'][0]['_source']['dokumenter'])) {
        $dokumenter = $data['hits']['hits'][0]['_source']['dokumenter'];
        for ($i = count($dokumenter) - 1; $i >= 0; $i--) {
            if ($dokumenter[$i]['dokumentMimeType'] == 'application/xml') {
                $xbxlUrl = $dokumenter[$i]['dokumentUrl'];
                break;
            }
        }
    }
    
    if (!$xbxlUrl) return null;
    
    $ch = curl_init($xbxlUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $xml = curl_exec($ch);
    curl_close($ch);
    
    if (!$xml) return null;
    
    $nogletal = [];
    
    // Omsætning (Revenue)
    if (preg_match('/<[a-z]+:Revenue[^>]*>(\d+)/i', $xml, $match)) {                 
        $nogletal['omsaetning'] = (int)$match[1];
    } elseif (preg_match('/Revenue[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetning'] = (int)$match[1];
    }

    // Resultat (ProfitLoss)
    if (preg_match('/<[a-z]+:ProfitLoss[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['resultat'] = (int)$match[1];
    } elseif (preg_match('/<[a-z]+:NetProfitLoss[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['resultat'] = (int)$match[1];
    } elseif (preg_match('/ProfitLoss[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['resultat'] = (int)$match[1];
    }

    // Egenkapital (Equity)
    if (preg_match('/<[a-z]+:Equity[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['egenkapital'] = (int)$match[1];
    } elseif (preg_match('/<[a-z]+:EquityAttributableToOwnersOfParent[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['egenkapital'] = (int)$match[1];
    } elseif (preg_match('/Equity[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['egenkapital'] = (int)$match[1];
    }

    // Aktiver (Assets)
    if (preg_match('/<[a-z]+:Assets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['aktiver'] = (int)$match[1];
    } elseif (preg_match('/Assets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['aktiver'] = (int)$match[1];
    }

    // Omsætningsaktiver (CurrentAssets)
    if (preg_match('/<[a-z]+:CurrentAssets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetningsaktiver'] = (int)$match[1];
    } elseif (preg_match('/<[a-z]+:TotalCurrentAssets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetningsaktiver'] = (int)$match[1];
    } elseif (preg_match('/CurrentAssets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetningsaktiver'] = (int)$match[1];
    } 
    elseif (preg_match('/Current\s+assets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetningsaktiver'] = (int)$match[1];
    }

    // Kortfristet gæld (CurrentLiabilities)
    if (preg_match('/<[a-z]+:CurrentLiabilities[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['kortfristetGaeld'] = (int)$match[1];
    } elseif (preg_match('/<[a-z]+:TotalCurrentLiabilities[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['kortfristetGaeld'] = (int)$match[1];
    } elseif (preg_match('/CurrentLiabilities[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['kortfristetGaeld'] = (int)$match[1];
    }
    // Ekstra forsøg: Nogle gange hedder det "Short term debt" eller lignende
    elseif (preg_match('/Short[ -]term\s+(?:debt|liabilities)[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['kortfristetGaeld'] = (int)$match[1];
    }



    // Beregn nøgletal
    if (isset($nogletal['egenkapital']) && isset($nogletal['aktiver']) && $nogletal['aktiver'] > 0) {
        $nogletal['soliditetsgrad'] = round(($nogletal['egenkapital'] / $nogletal['aktiver']) * 100, 1);
    }
    
    if (isset($nogletal['resultat']) && isset($nogletal['omsaetning']) && $nogletal['omsaetning'] > 0) {
        $nogletal['overskudsgrad'] = round(($nogletal['resultat'] / $nogletal['omsaetning']) * 100, 1);
    }
    
    if (isset($nogletal['omsaetningsaktiver']) && isset($nogletal['kortfristetGaeld']) && $nogletal['kortfristetGaeld'] > 0) {
        $nogletal['likviditetsgrad'] = round(($nogletal['omsaetningsaktiver'] / $nogletal['kortfristetGaeld']) * 100, 1);
    }
    
    return $nogletal;
}

// Test med DSV
// Hent data for det CVR der sendes via URL
$cvr = isset($_GET['cvr']) ? $_GET['cvr'] : '10117224';
$data = hentRegnskabsData($cvr);
//$navn = hentVirksomhedDataFraCVRAPI($cvr);

// Returner JSON (INGEN HTML)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($data && isset($data['omsaetning'])) {
    $data['virksomhedsnavn'] = '';
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Ingen regnskabsdata fundet for CVR: ' . $cvr]);
}

?>