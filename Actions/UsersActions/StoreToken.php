<?php

namespace Actions\UsersActions;

class StoreToken
{

    /**
     * @param string $tokenPath
     * @param \Google_Client $client
     */
    public function __invoke( \Google_Client $client ): void
    {
        $accessToken = $client->getAccessToken();
        $_SESSION["accessToken"] = json_encode($accessToken);
    }
}