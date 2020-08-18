<?php

namespace Services;

use Config\Environment;

class SheetsService
{
    use Environment;

    /**
     * Returns an authorized API client.
     * @return \Google_Client the authorized client object
     */
    public function getClient(): \Google_Client
    {
        $accessToken = $this->getToken();

        //Reading data from spreadsheet.
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($this->env()['credentials']);
        $client->setAccessType('offline');
       // $client->setPrompt('select_account consent');

        if( $accessToken != null ) $client->setAccessToken($accessToken);

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {

            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                header("location:".$authUrl);
                die();
            }
        }

        return $client;
    }

    /**
     * @param string $tokenPath
     * @param \Google_Client $client
     * @return array
     */
    private function getToken(): ?array
    {
        $accessToken = null;

        if( isset($_SESSION["accessToken"]) ) $accessToken = json_decode($_SESSION["accessToken"], true);

        return $accessToken;
    }
}