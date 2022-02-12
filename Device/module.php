<?php

/** @noinspection PhpUnhandledExceptionInspection */
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

        ##### Properties
        $this->RegisterPropertyString('CloudDeviceID', '');
        $this->RegisterPropertyString('DeviceName', '');
        $this->RegisterPropertyInteger('SwitchingProfile', 0);
        $this->RegisterPropertyInteger('UpdateInterval', 0);

        ##### Variables

        //Power
        $id = @$this->GetIDForIdent('DevicePower');
        $this->RegisterVariableBoolean('DevicePower', $this->Translate('Light'), '~Switch', 100);
        if ($id == false) {
            IPS_SetIcon(@$this->GetIDForIdent('DevicePower'), 'Bulb');
        }
        $this->EnableAction('DevicePower');

        //Brightness
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Brightness';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Sun');
        $this->RegisterVariableInteger('Brightness', $this->Translate('Brightness'), $profile, 210);
        $this->EnableAction('Brightness');

        ###### Timer
        $this->RegisterTimer('UpdateDeviceState', 0, 'KLYQADEV_UpdateDeviceState(' . $this->InstanceID . ');');

        //Connect to parent (Klyqa Splitter)
        $this->ConnectParent(self::KLYQA_SPLITTER_GUID);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Brightness'];
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
        //Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'DevicePower':
                $this->ToggleDevicePower($Value);
                break;

            case 'Brightness':
                $this->ToggleBrightness($Value);
                break;

        }
    }

    #################### Public

    public function UpdateDeviceState(): void
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            $this->SetUpdateTimer();
            return;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Error, no cloud device id assigned!', 0);
            $this->SetUpdateTimer();
            return;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
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
                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                if ($httpCode != 200) {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetUpdateTimer();
                    return;
                }
            }
            if (array_key_exists('body', $result)) {
                $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($result['body']), 0);
                $deviceData = $result['body'];
                if (is_array($deviceData)) {
                    //Status
                    if (array_key_exists('status', $deviceData)) {
                        $devicePowerState = false;
                        if ($deviceData['status'] == 'on') {
                            $devicePowerState = true;
                        }
                        $this->SetValue('DevicePower', $devicePowerState);
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
                }
            }
        }
        $this->SetUpdateTimer();
    }

    public function ToggleDevicePower(bool $State): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Error, no cloud device id assigned!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
        $this->SetValue('DevicePower', $State);
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
                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                if ($httpCode != 200) {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetTimerInterval('UpdateDeviceState', 5000);
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                if (is_array($body)) {
                    $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($body), 0);
                    if (array_key_exists('status', $body)) {
                        $devicePowerState = false;
                        if ($body['status'] == 'on') {
                            $devicePowerState = true;
                        }
                        if ($devicePowerState != $State) {
                            $success = false;
                            //Set actual value
                            $this->SetValue('DevicePower', $devicePowerState);
                        }
                    }
                }
            }
        }
        $this->SetTimerInterval('UpdateDeviceState', 5000);
        return $success;
    }

    public function ToggleBrightness(int $Percentage): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            return false;
        }
        $cloudDeviceID = $this->ReadPropertyString('CloudDeviceID');
        if (empty($cloudDeviceID)) {
            $this->SendDebug(__FUNCTION__, 'Error, no cloud device id assigned!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateDeviceState', 0);
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
                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                if ($httpCode != 200) {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetTimerInterval('UpdateDeviceState', 5000);
                    return false;
                }
            }
            if (array_key_exists('body', $result)) {
                $body = $result['body'];
                if (is_array($body)) {
                    $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($body), 0);
                    if (array_key_exists('brightness', $body)) {
                        $brightness = $body['brightness'];
                        if (is_array($brightness)) {
                            if (array_key_exists('percentage', $brightness)) {
                                $deviceBrightnessPercentage = $brightness['percentage'];
                                if ($deviceBrightnessPercentage != $Percentage) {
                                    $success = false;
                                    // Set actual value
                                    $this->SetValue('Brightness', $deviceBrightnessPercentage);
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->SetTimerInterval('UpdateDeviceState', 5000);
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
}