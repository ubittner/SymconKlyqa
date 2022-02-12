<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait KlyqaAPI
{
    #################### Public

    public function GetAuthProfile(): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/auth/profile';
        return $this->SendDataToKlyqa($endpoint, 'GET', '');
    }

    public function GetSettings(): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/settings';
        return $this->SendDataToKlyqa($endpoint, 'GET', '');
    }

    public function GetDeviceList(): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/device';
        return $this->SendDataToKlyqa($endpoint, 'GET', '');
    }

    public function GetDeviceState(string $CloudDeviceID): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/device/' . $CloudDeviceID . '/state';
        return $this->SendDataToKlyqa($endpoint, 'GET', '');
    }

    public function SetDeviceState(string $CloudDeviceID, string $Payload): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/device/' . $CloudDeviceID . '/state';
        return $this->SendDataToKlyqa($endpoint, 'POST', $Payload);
    }

    public function SetGroupState(string $Payload): string
    {
        $endpoint = 'https://app-api.prod.qconnex.io/device/group/state';
        return $this->SendDataToKlyqa($endpoint, 'POST', $Payload);
    }

    #################### Tokens

    /**
     * Gets the account and access token.
     */
    public function GetTokens(): void
    {
        if (!empty($this->ReadAttributeString('AccessToken'))) {
            return;
        }
        $userName = $this->ReadPropertyString('UserName');
        $password = $this->ReadPropertyString('Password');
        if (empty($userName) || empty($password)) {
            return;
        }
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $postfields = '{"email":"' . $userName . '","password":"' . $password . '"}';
        $this->SendDebug(__FUNCTION__, 'Postfields: ' . $postfields, 0);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://app-api.prod.qconnex.io/auth/login',
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $postfields,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!curl_errno($ch)) {
            switch ($http_code) {
                case 200: # OK
                case 201: # Created
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);
                    $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                    $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                    $data = json_decode($body, true);
                    if (is_array($data)) {
                        if (array_key_exists('accountToken', $data)) {
                            $accountToken = $data['accountToken'];
                            if (!empty($accountToken)) {
                                $this->SendDebug(__FUNCTION__, 'Account token: ' . $accountToken, 0);
                                $this->WriteAttributeString('AccountToken', $accountToken);
                                $this->UpdateFormField('AccountToken', 'caption', 'Account token: ' . substr($accountToken, 0, 16) . ' ...');
                            }
                        }
                        if (array_key_exists('accessToken', $data)) {
                            $accessToken = $data['accessToken'];
                            if (!empty($accessToken)) {
                                $this->SendDebug(__FUNCTION__, 'Access token: ' . $accessToken, 0);
                                $this->WriteAttributeString('AccessToken', $accessToken);
                                $this->UpdateFormField('AccessToken', 'caption', 'Access token: ' . substr($accessToken, 0, 16) . ' ...');
                            }
                        }
                    }
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', An error has occurred: ' . json_encode($error_msg), KL_ERROR);
        }
        curl_close($ch);
    }

    public function UpdateAccessToken(string $NewAccessToken): void
    {
        $this->WriteAttributeString('AccessToken', $NewAccessToken);
        $this->UpdateFormField('AccessToken', 'caption', 'Access token: ' . substr($NewAccessToken, 0, 16) . ' ...');
    }

    public function DeleteAccessToken(): void
    {
        $this->WriteAttributeString('AccessToken', '');
        $this->UpdateFormField('AccessToken', 'caption', 'Access token: ' . $this->ReadAttributeString('AccessToken') ? 'Access token: ' . substr($this->ReadAttributeString('AccessToken'), 0, 16) . ' ...' : 'Access token: Not registered yet!');
    }

    #################### Private

    private function SendDataToKlyqa(string $Endpoint, string $CustomRequest, string $Postfields): string
    {
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $Endpoint, 0);
        $this->SendDebug(__FUNCTION__, 'CustomRequest: ' . $CustomRequest, 0);
        $result = [];
        $accessToken = $this->ReadAttributeString('AccessToken');
        if (empty($accessToken)) {
            return json_encode($result);
        }
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST   => $CustomRequest,
            CURLOPT_URL             => $Endpoint,
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_POSTFIELDS      => $Postfields,
            CURLOPT_HTTPHEADER      => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json']]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $body = '';
        if (!curl_errno($ch)) {
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            switch ($httpCode) {
                case 200:  # OK
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = json_decode(substr($response, $header_size), true);
                    $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
                    $this->SendDebug(__FUNCTION__, 'Response body: ' . json_encode($body), 0);
                    break;

            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($ch);
        $result = ['httpCode' => $httpCode, 'body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }
}