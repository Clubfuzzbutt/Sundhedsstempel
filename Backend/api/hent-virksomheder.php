<?php
/*  henter kun cvr data
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    return json_decode($response, true);
}

$cvr = "10117224";
echo "Henter data for CVR: $cvr fra CVRAPI.dk...<br>";
$data = hentVirksomhedDataFraCVRAPI($cvr);

if ($data) {
    echo "<h3 style='color:green;'>SUCCESS! Data modtaget:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Tjek om der er regnskabsdata
    if (isset($data['annual_report_period'])) {
        echo "<p>Seneste regnskabsår: " . $data['annual_report_period'] . "</p>";
    }
    if (isset($data['profit']) || isset($data['loss'])) {
        echo "<p>Resultat: " . ($data['profit'] ?? $data['loss'] ?? 'Ikke tilgængeligt') . "</p>";
    }
} else {
    echo "<h3 style='color:red;'>Ingen data modtaget.</h3>";
} */


//Henter årsregnskaber.

/* virker for maxi zoo, men ikke for andre da ders xml er anderledes opsat
function hentRegnskabsData($cvr) {
    // 1. Hent liste over regnskaber
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
    
    // 2. Find XBRL URL (application/xml)
    $xbxlUrl = null;
    if (isset($data['hits']['hits'][0]['_source']['dokumenter'])) {
        foreach ($data['hits']['hits'][0]['_source']['dokumenter'] as $dok) {
            if ($dok['dokumentMimeType'] == 'application/xml') {
                $xbxlUrl = $dok['dokumentUrl'];
                break;
            }
        }
    }
    
    if (!$xbxlUrl) return null;
    
    // 3. Hent XBRL filen med User-Agent
    $ch = curl_init($xbxlUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $xml = curl_exec($ch);
    curl_close($ch);
    
    if (!$xml) return null;
    
    // 4. Udtræk nøgletal fra XML
    $nogletal = [];
    
    // Omsætning (Revenue) - seneste år (contextRef D0)
    if (preg_match('/<fsa:Revenue\s+contextRef="D0"[^>]*>(\d+)/', $xml, $match)) {
        $nogletal['omsaetning'] = (int)$match[1];
    }
    
    // Resultat (ProfitLoss)
    if (preg_match('/<fsa:ProfitLoss\s+contextRef="D0"[^>]*>(\d+)/', $xml, $match)) {
        $nogletal['resultat'] = (int)$match[1];
    }
    
    // Egenkapital (Equity)
    if (preg_match('/<fsa:Equity\s+contextRef="I0"[^>]*>(\d+)/', $xml, $match)) {
        $nogletal['egenkapital'] = (int)$match[1];
    }
    
    // Aktiver (Assets)
    if (preg_match('/<fsa:Assets\s+contextRef="I0"[^>]*>(\d+)/', $xml, $match)) {
        $nogletal['aktiver'] = (int)$match[1];
    }
    
    // Soliditetsgrad (EquityRatio)
    if (preg_match('/<mrv:EquityRatio\s+contextRef="D0"[^>]*>(\d+)/', $xml, $match)) {
        $nogletal['soliditetsgrad'] = (int)$match[1];
    } elseif (isset($nogletal['egenkapital']) && isset($nogletal['aktiver']) && $nogletal['aktiver'] > 0) {
        $nogletal['soliditetsgrad'] = round(($nogletal['egenkapital'] / $nogletal['aktiver']) * 100);
    }
    
    // Overskudsgrad
    if (isset($nogletal['resultat']) && isset($nogletal['omsaetning']) && $nogletal['omsaetning'] > 0) {
        $nogletal['overskudsgrad'] = round(($nogletal['resultat'] / $nogletal['omsaetning']) * 100, 1);
    }
    
    return $nogletal;
}

// Test
$cvr = "10117224";
$data = hentRegnskabsData($cvr);

if ($data) {
    echo "<h2>MAXI ZOO DENMARK A/S (CVR: $cvr)</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Nøgletal</th><th>Værdi</th></tr>";
    echo "<tr><td>Omsætning</td><td>" . number_format($data['omsaetning'], 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Resultat</td><td>" . number_format($data['resultat'], 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Egenkapital</td><td>" . number_format($data['egenkapital'], 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Aktiver</td><td>" . number_format($data['aktiver'], 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Soliditetsgrad</td><td>" . $data['soliditetsgrad'] . "%</td></tr>";
    echo "<tr><td>Overskudsgrad</td><td>" . $data['overskudsgrad'] . "%</td></tr>";
    echo "</table>";
} else {
    echo "Ingen data for CVR: $cvr";
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
    if (preg_match('/<(?:ifrs-full|fsa|DSV):Revenue[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetning'] = (int)$match[1];
    }
    
    // Resultat (ProfitLoss)
    if (preg_match('/<(?:ifrs-full|fsa|DSV):ProfitLoss[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['resultat'] = (int)$match[1];
    }
    
    // Egenkapital (Equity)
    if (preg_match('/<(?:ifrs-full|fsa|DSV):Equity[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['egenkapital'] = (int)$match[1];
    }
    
    // Aktiver (Assets)
    if (preg_match('/<(?:ifrs-full|fsa|DSV):Assets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['aktiver'] = (int)$match[1];
    }
    
    // Omsætningsaktiver (CurrentAssets)
    if (preg_match('/<(?:ifrs-full|fsa|DSV):CurrentAssets[^>]*>(\d+)/i', $xml, $match)) {
        $nogletal['omsaetningsaktiver'] = (int)$match[1];
    }
    
    // Kortfristet gæld (CurrentLiabilities)
    if (preg_match('/<(?:ifrs-full|fsa|DSV):CurrentLiabilities[^>]*>(\d+)/i', $xml, $match)) {
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
$cvr = "47458714";
$data = hentRegnskabsData($cvr);

if ($data) {
    echo "<h2>DSV (CVR: $cvr)</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Nøgletal</th><th>Værdi</th></tr>";
    echo "<tr><td>Omsætning</td><td>" . number_format($data['omsaetning'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Resultat</td><td>" . number_format($data['resultat'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Egenkapital</td><td>" . number_format($data['egenkapital'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Aktiver</td><td>" . number_format($data['aktiver'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Omsætningsaktiver</td><td>" . number_format($data['omsaetningsaktiver'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td>Kortfristet gæld</td><td>" . number_format($data['kortfristetGaeld'] ?? 0, 0, ',', '.') . " kr.</td></tr>";
    echo "<tr><td style='background:#e8f5e9'><strong>Soliditetsgrad</strong></td>";
    echo "<td style='background:#e8f5e9'><strong>" . ($data['soliditetsgrad'] ?? 'N/A') . "%</strong></td></tr>";
    echo "<tr><td style='background:#fff3e0'><strong>Overskudsgrad</strong></td>";
    echo "<td style='background:#fff3e0'><strong>" . ($data['overskudsgrad'] ?? 'N/A') . "%</strong></td></tr>";
    echo "<tr><td style='background:#e3f2fd'><strong>Likviditetsgrad</strong></td>";
    echo "<td style='background:#e3f2fd'><strong>" . ($data['likviditetsgrad'] ?? 'N/A') . "%</strong></td></tr>";
    echo "</table>";
    
    // Sundhedsstempel
    if (isset($data['soliditetsgrad'])) {
        if ($data['soliditetsgrad'] >= 30) {
            $farve = "#4CAF50";
            $status = "STABIL";
            $beskrivelse = "Virksomheden har en stærk økonomi og lav risiko";
        } elseif ($data['soliditetsgrad'] >= 15) {
            $farve = "#FFC107";
            $status = "MIDDEL";
            $beskrivelse = "Virksomheden har en moderat økonomi";
        } else {
            $farve = "#F44336";
            $status = "USIKKER";
            $beskrivelse = "Virksomheden har en svag økonomi og høj risiko";
        }
        
        echo "<div style='background-color: $farve; color: white; padding: 20px; text-align: center; border-radius: 10px; margin-top: 20px;'>";
        echo "<h2>🏥 ØKONOMISK SUNDHEDSSTEMPEL: $status</h2>";
        echo "<p>$beskrivelse</p>";
        echo "</div>";
    }
} else {
    echo "Ingen data for CVR: $cvr";
}
?>