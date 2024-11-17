<?php

// Connect to the database
function getDatabaseConnection() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=fortune', 'username', 'password');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection error: ' . $e->getMessage());
    }
}

// Connecting to SOAP
function getSoapClient() {
    return new \SoapClient(null, [
        'location'      =>  'http://localhost:7878/',
        'uri'           =>  'urn:AC', //AC - AzerothCore, TC - TrinityCore and so on..
        'login'         =>  'username', //Game Master Account
        'password'      =>  'password', //Game Master Account
        'style'         =>  SOAP_RPC,
        'keep_alive'    =>  false
    ]);
}
