<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class KlyqaDevice extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{243E0FEF-A7D1-6126-149E-ACE89A5D3F69}';
    private const MODULE_PREFIX = 'KLYQADEV';
    private const KLYQA_SPLITTER_GUID = '{D71BFBAE-AD9F-00C1-1C83-BFD49EB41D2C}';
    private const KLYQA_SPLITTER_DATA_GUID = '{8F385BA9-23F8-2969-19F8-0E04ABD77E5B}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties
        $this->RegisterPropertyString('CloudDeviceID', '');
        $this->RegisterPropertyString('DeviceName', '');
        $this->RegisterPropertyInteger('SwitchingProfile', 0);
        $this->RegisterPropertyInteger('UpdateInterval', 0);

        ########## Variables

        //Power
        $this->RegisterVariableBoolean('Power', $this->Translate('Light'), '~Switch', 100);
        $this->EnableAction('Power');

        //Presets
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Presets';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 2700, '2700', '', 0xFFA757);
        IPS_SetVariableProfileAssociation($profile, 3650, '3650', '', 0xFFC595);
        IPS_SetVariableProfileAssociation($profile, 4600, '4600', '', 0xFFDCBF);
        IPS_SetVariableProfileAssociation($profile, 5550, '5550', '', 0xFFEEE0);
        IPS_SetVariableProfileAssociation($profile, 6500, '6500', '', 0xFFFEFA);
        IPS_SetVariableProfileText($profile, '', '°K');
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        $this->RegisterVariableInteger('Presets', $this->Translate('Presets'), $profile, 300);
        $this->EnableAction('Presets');

        //Temperature
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Temperature';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 2700, 6500, 1);
        IPS_SetVariableProfileText($profile, '', '°K');
        IPS_SetVariableProfileIcon($profile, 'Temperature');
        $this->RegisterVariableInteger('Temperature', $this->Translate('Temperature'), $profile, 310);
        $this->EnableAction('Temperature');

        //Brightness
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Brightness';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Sun');
        $this->RegisterVariableInteger('Brightness', $this->Translate('Brightness'), $profile, 400);
        $this->EnableAction('Brightness');

        ########### Timer
        $this->RegisterTimer('UpdateDeviceState', 0, 'KLYQADEV_UpdateDeviceState(' . $this->InstanceID . ');');

        //Connect to parent (Klyqa Splitter)
        $this->ConnectParent(self::KLYQA_SPLITTER_GUID);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Mode', 'Temperature', 'Presets', 'Brightness'];
        foreach ($profiles as $profile) {
            $this->DeleteProfile($profile);
        }
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        ########## Maintain Variables

        if ($this->ReadPropertyInteger('SwitchingProfile') != 2) {
            //Mode
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
            if (!IPS_VariableProfileExists($profile)) {
                IPS_CreateVariableProfile($profile, 1);
            }
            IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Color'), '', -1);
            IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Whitetone'), '', -1);
            IPS_SetVariableProfileIcon($profile, 'Bulb');
            $this->MaintainVariable('Mode', $this->Translate('Mode'), 1, $profile, 110, true);
            $this->EnableAction('Mode');
            //Color
            $id = @$this->GetIDForIdent('Color');
            $this->MaintainVariable('Color', $this->Translate('Color'), 1, '~HexColor', 200, true);
            if ($id == false) {
                IPS_SetIcon(@$this->GetIDForIdent('Color'), 'Paintbrush');
            }
            $this->EnableAction('Color');
        } else {
            $this->MaintainVariable('Mode', $this->Translate('Mode'), 1, '', 0, false);
            $this->DeleteProfile('Mode');
            $this->MaintainVariable('Color', $this->Translate('Color'), 1, '', 0, false);
        }
        $this->SetTimerInterval('UpdateDeviceState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        $this->UpdateDeviceState();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        //Version info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $formData['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']);

        return json_encode($formData);
    }

    public function ReceiveData($JSONString)
    {
        //Received data from splitter, not used at the moment!
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'Power':
                $this->ToggleDevicePower($Value);
                break;

            case 'Mode':
                $this->SetLightMode($Value);
                break;

            case 'Color':
                $this->SetLightColor($Value);
                break;

            case 'Temperature':
                $this->SetLightTemperature($Value);
                break;

            case 'Presets':
                $this->SetValue($Ident, $Value);
                $this->SetLightTemperature($Value);
                break;

            case 'Brightness':
                $this->SetBrightness($Value);
                break;

        }
    }

    #################### Public

    public function UpdateDeviceState(): bool
    {
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $result = $this->GetLightState();
        $this->SetUpdateTimer();
        return $result;
    }

    public function ToggleDevicePower(bool $State): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Abort, parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Abort, no cloud device id is assigned!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $this->SetValue('Power', $State);
        $powerState = 'off';
        if ($State) {
            $powerState = 'on';
        }
        $payload = '{"payload":{"status":"' . $powerState . '"}}';
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetDeviceState';
        $buffer['Params'] = ['cloudDeviceId' => $cloudDeviceID, 'payload' => $payload];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                if ($httpCode == 200) {
                    $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    //Revert
                    $this->SetValue('Power', !$State);
                    $this->SetUpdateTimer();
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                $this->SendDebug(__FUNCTION__, 'Body data: ' . json_encode($body), 0);
                $this->UpdateLightState(json_encode($body));
                if (is_array($body)) {
                    if (array_key_exists('status', $body)) {
                        $devicePowerState = false;
                        if ($body['status'] == 'on') {
                            $devicePowerState = true;
                        }
                        if ($devicePowerState != $State) {
                            $success = false;
                        }
                    }
                }
            }
        }
        $this->SetUpdateTimer();
        return $success;
    }

    public function SetLightMode(int $Mode): bool
    {
        $this->SetUpdateTimer();
        $this->SetValue('Mode', $Mode);
        switch ($Mode) {
            case 0: # RGB
                IPS_SetHidden(@$this->GetIDForIdent('Color'), false);
                IPS_SetHidden(@$this->GetIDForIdent('Temperature'), true);
                IPS_SetHidden(@$this->GetIDForIdent('Presets'), true);
                break;

            default:
                IPS_SetHidden(@$this->GetIDForIdent('Color'), true);
                IPS_SetHidden(@$this->GetIDForIdent('Temperature'), false);
                IPS_SetHidden(@$this->GetIDForIdent('Presets'), false);
        }
        return true;
    }

    public function SetLightColor(int $Color): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Abort, parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Abort, no cloud device id is assigned!', 0);
            return false;
        }
        if ($this->ReadPropertyInteger('SwitchingProfile') == 2) {
            $this->SendDebug(__FUNCTION__, 'Abort, the device does not support color!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $actualColor = $this->GetValue('Color');
        $brightness = $this->GetValue('Brightness');
        $this->SetValue('Color', $Color);
        $rgb['r'] = (($Color >> 16) & 0xFF);
        $rgb['g'] = (($Color >> 8) & 0xFF);
        $rgb['b'] = ($Color & 0xFF);
        $payload = '{"payload":{"color":{"red":' . $rgb['r'] . ', "green":' . $rgb['g'] . ', "blue":' . $rgb['b'] . '}, "brightness": {"percentage":' . $brightness . '}}}';
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetDeviceState';
        $buffer['Params'] = ['cloudDeviceId' => $cloudDeviceID, 'payload' => $payload];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                if ($httpCode == 200) {
                    $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    //Revert
                    $this->SetValue('Color', $actualColor);
                    $this->SetUpdateTimer();
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                $this->SendDebug(__FUNCTION__, 'Body data: ' . json_encode($body), 0);
                $this->UpdateLightState(json_encode($body));
                if (is_array($body)) {
                    if (array_key_exists('mode', $body)) {
                        if ($body['mode'] = !'rgb') {
                            $success = false;
                        }
                    }
                    if (array_key_exists('color', $body)) {
                        $color = $body['color'];
                        if (array_key_exists('red', $color)) {
                            if ($color['red'] = !$rgb['r']) {
                                $success = false;
                            }
                        }
                        if (array_key_exists('green', $color)) {
                            if ($color['green'] = !$rgb['g']) {
                                $success = false;
                            }
                        }
                        if (array_key_exists('blue', $color)) {
                            if ($color['blue'] = !$rgb['b']) {
                                $success = false;
                            }
                        }
                    }
                }
            }
        }
        $this->SetUpdateTimer();
        return $success;
    }

    public function SetLightTemperature(int $Temperature): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Abort, parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Abbort, no cloud device id is assigned!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $actualTemperature = $this->GetValue('Temperature');
        $brightness = $this->GetValue('Brightness');
        $this->SetValue('Temperature', $Temperature);
        $payload = '{"payload":{"brightness": {"percentage":' . $brightness . '},"temperature":' . $Temperature . '}}';
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetDeviceState';
        $buffer['Params'] = ['cloudDeviceId' => $cloudDeviceID, 'payload' => $payload];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                if ($httpCode == 200) {
                    $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    //Revert
                    $this->SetValue('Temperature', $actualTemperature);
                    $this->SetUpdateTimer();
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                $this->SendDebug(__FUNCTION__, 'Body data: ' . json_encode($body), 0);
                $this->UpdateLightState(json_encode($body));
                if (is_array($body)) {
                    if (array_key_exists('mode', $body)) {
                        if ($body['mode'] = !'cct') {
                            $success = false;
                        }
                    }
                    if (array_key_exists('temperature', $body)) {
                        if ($body['temperature'] != $Temperature) {
                            $success = false;
                        }
                    }
                }
            }
        }
        $this->SetUpdateTimer();
        return $success;
    }

    public function SetBrightness(int $Percentage): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Abort, parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Abort, no cloud device id is assigned!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $actualBrightness = $this->GetValue('Brightness');
        $this->SetValue('Brightness', $Percentage);
        $payload = '{"payload":{"brightness":{"percentage": ' . $Percentage . '}}}';
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetDeviceState';
        $buffer['Params'] = ['cloudDeviceId' => $cloudDeviceID, 'payload' => $payload];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                if ($httpCode == 200) {
                    $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    //Revert
                    $this->SetValue('Brightness', $actualBrightness);
                    $this->SetUpdateTimer();
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                $this->SendDebug(__FUNCTION__, 'Body data: ' . json_encode($body), 0);
                $this->UpdateLightState(json_encode($body));
                if (is_array($body)) {
                    if (array_key_exists('brightness', $body)) {
                        $brightness = $body['brightness'];
                        if (is_array($brightness)) {
                            if (array_key_exists('percentage', $brightness)) {
                                $deviceBrightnessPercentage = $brightness['percentage'];
                                if ($deviceBrightnessPercentage != $Percentage) {
                                    $success = false;
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->SetUpdateTimer();
        return $success;
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function DeleteProfile(string $ProfileName): void
    {
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $ProfileName;
        if (@IPS_VariableProfileExists($profile)) {
            IPS_DeleteVariableProfile($profile);
        }
    }

    private function SetUpdateTimer(): void
    {
        $this->SetTimerInterval('UpdateDeviceState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

    private function GetLightState(): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Abort, parent splitter instance is inactive!', 0);
            $this->SetUpdateTimer();
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Abort, no cloud device id is assigned!', 0);
            $this->SetUpdateTimer();
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $success = false;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetDeviceState';
        $buffer['Params'] = ['cloudDeviceId' => $cloudDeviceID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                if ($httpCode == 200) {
                    $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetUpdateTimer();
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                $this->SendDebug(__FUNCTION__, 'Body data: ' . json_encode($body), 0);
                $this->UpdateLightState(json_encode($body));
                if (is_array($body)) {
                    $success = true;
                }
            }
        }
        return $success;
    }

    private function UpdateLightState(string $Data): void
    {
        $this->SendDebug(__FUNCTION__, 'Device data: ' . $Data, 0);
        $deviceData = json_decode($Data, true);
        if (is_array($deviceData)) {
            //Status
            if (array_key_exists('status', $deviceData)) {
                $devicePowerState = false;
                if ($deviceData['status'] == 'on') {
                    $devicePowerState = true;
                }
                $this->SetValue('Power', $devicePowerState);
            }
            //Mode
            $rgbMode = false;
            if (array_key_exists('mode', $deviceData)) {
                if ($deviceData['mode'] == 'rgb') {
                    $rgbMode = true;
                }
                if ($this->ReadPropertyInteger('SwitchingProfile') != 2) {
                    $mode = 1;
                    if ($rgbMode) {
                        $mode = 0;
                    }
                    $this->SetLightMode($mode);
                }
            }
            // Color
            if ($this->ReadPropertyInteger('SwitchingProfile') != 2) {
                if (array_key_exists('color', $deviceData)) {
                    $color = $deviceData['color'];
                    $red = 0;
                    $green = 0;
                    $blue = 0;
                    if (array_key_exists('red', $color)) {
                        $red = $color['red'];
                    }
                    if (array_key_exists('green', $color)) {
                        $green = $color['green'];
                    }
                    if (array_key_exists('blue', $color)) {
                        $blue = $color['blue'];
                    }
                    $color = ($red * 256 * 256) + ($green * 256) + $blue;
                    if ($rgbMode) {
                        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
                        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Color'), '', $color);
                    }
                    $this->SetValue('Color', ($red * 256 * 256) + ($green * 256) + $blue);
                }
            }
            //Brightness
            if (array_key_exists('brightness', $deviceData)) {
                $brightness = $deviceData['brightness'];
                if (is_array($brightness)) {
                    if (array_key_exists('percentage', $brightness)) {
                        $this->SetValue('Brightness', $brightness['percentage']);
                    }
                }
            }
            //Light temperature
            if (array_key_exists('temperature', $deviceData)) {
                $temperature = $deviceData['temperature'];
                $this->SetValue('Temperature', $temperature);
                if ($this->ReadPropertyInteger('SwitchingProfile') != 2) {
                    if (!$rgbMode) {
                        $temperature = $temperature / 100;
                        if ($temperature < 66) {
                            $red = 255;
                            $green = max(min(round(99.4708025861 * log($temperature) - 161.1195681661), 255), 0);
                            $blue = $temperature <= 19 ? 0 : max(min(round(138.5177312231 * log($temperature - 10) - 305.0447927307), 255), 0);
                        } else {
                            $red = max(min(round(329.698727446 * (($temperature - 60) ^ -0.1332047592)), 255), 0);
                            $green = max(min(round(288.1221695283 * (($temperature - 60) ^ -0.0755148492)), 255), 0);
                            $blue = 255;
                        }
                        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
                        $color = ($red * 256 * 256) + ($green * 256) + $blue;
                        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Whitetone'), '', $color);
                    }
                }
            }
        }
    }
}