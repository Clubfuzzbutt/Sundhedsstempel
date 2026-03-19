<?php

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
}

?>

