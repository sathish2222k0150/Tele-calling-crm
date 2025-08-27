<?php
// capi-functions.php

function sendConversionApiEvent($leadData, $testCode = null) {
    if (!defined('FACEBOOK_PIXEL_ID') || !defined('FACEBOOK_ACCESS_TOKEN')) {
        error_log('Facebook credentials are not defined.');
        return false;
    }

    $url = 'https://graph.facebook.com/v19.0/' . FACEBOOK_PIXEL_ID . '/events?access_token=' . FACEBOOK_ACCESS_TOKEN;

    $userData = [
        'em' => hash('sha256', strtolower(trim($leadData['email'] ?? ''))),
        'ph' => hash('sha256', preg_replace('/[^0-9]/', '', $leadData['phone_number'] ?? '')),
    ];
    
    $nameParts = explode(' ', trim($leadData['name'] ?? ''), 2);
    if (!empty($nameParts[0])) $userData['fn'] = hash('sha256', strtolower($nameParts[0]));
    if (!empty($nameParts[1])) $userData['ln'] = hash('sha256', strtolower($nameParts[1]));

    $payload = [
        'data' => [
            [
                'event_name'      => 'CompleteRegistration',
                'event_time'      => time(),
                'action_source'   => 'system_generated',
                'user_data'       => $userData,
                'event_id'        => uniqid('crm_lead_'),
                'custom_data' => [
                    'lead_id' => $leadData['id'],
                    'status'  => 'converted'
                ]
            ]
        ]
    ];
    
    if ($testCode) {
        $payload['test_event_code'] = $testCode;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // ================== CRITICAL FIX FOR LOCAL TESTING ==================
    // This line tells cURL not to worry about the SSL certificate.
    // It is often necessary on local XAMPP/WAMP servers.
    // WARNING: You should remove or comment this out on a live production server.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // ===================================================================

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("CAPI Curl Error: " . $error);
        return false;
    }
    
    return json_decode($response, true);
}
?>