<?php

namespace Controllers;

use Actions\UsersActions\StoreToken;
use Config\Environment;

class UsersController
{
    use Environment;

    /**
     * Returns an authorized API client.
     * @return \Google_Client the authorized client object
     */
    public function setAuthCode(): \Google_Client
    {
        $authCode = $_GET['code'];

        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($this->env()['credentials']);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if( $authCode ){
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new \Exception(join(', ', $accessToken));
            }

            // Store token
            $storeToken = new StoreToken();
            $storeToken($client);

            // Return view
            $return = $this->env()['frontend_url'] . "?auth=true";
            header("location:".$return);
        }

        return $client;
    }
}