<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class KlyqaGroup extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{243E0FEF-A7D1-6126-149E-ACE89A5D3F69}';
    private const MODULE_PREFIX = 'KLYQAGRP';
    private const KLYQA_SPLITTER_GUID = '{D71BFBAE-AD9F-00C1-1C83-BFD49EB41D2C}';
    private const KLYQA_SPLITTER_DATA_GUID = '{8F385BA9-23F8-2969-19F8-0E04ABD77E5B}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ##### Properties
        $this->RegisterPropertyString('GroupID', '');
        $this->RegisterPropertyString('GroupName', '');
        $this->RegisterPropertyInteger('SwitchingProfile', 0);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        $this->RegisterPropertyString('Devices', '');

        ##### Variables

        //Power
        $id = @$this->GetIDForIdent('GroupPower');
        $this->RegisterVariableBoolean('GroupPower', $this->Translate('Lights'), '~Switch', 100);
        if ($id == false) {
            IPS_SetIcon(@$this->GetIDForIdent('GroupPower'), 'Bulb');
        }
        $this->EnableAction('GroupPower');

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
        $this->RegisterTimer('UpdateGroupState', 0, 'KLYQAGRP_UpdateGroupState(' . $this->InstanceID . ');');

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

        if (empty($this->ReadPropertyString('Devices'))) {
            $this->GetGroupDevices();
        }
        $this->SetTimerInterval('UpdateGroupState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        $this->UpdateGroupState();
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
            case 'GroupPower':
                $this->ToggleGroupPower($Value);
                break;

            case 'Brightness':
                $this->ToggleBrightness($Value);
                break;

        }
    }

    #################### Public

    public function GetGroupDevices(): void
    {
        if (!$this->HasActiveParent()) {
            return;
        }
        $groupID = $this->ReadPropertyString('GroupID');
        if (empty($groupID)) {
            return;
        }
        $values = [];
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetSettings';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 200) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                return;
            }
        }
        if (array_key_exists('body', $result)) {
            $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($result['body']), 0);
            $data = $result['body'];
            if (is_array($data)) {
                //Rooms
                if (array_key_exists('rooms', $data)) {
                    $rooms = $data['rooms'];
                    if (is_array($rooms)) {
                        foreach ($rooms as $room) {
                            if (array_key_exists('id', $room)) {
                                $roomID = $room['id'];
                                if ($roomID == $groupID) {
                                    if (array_key_exists('devices', $room)) {
                                        $roomDevices = $room['devices'];
                                        if (is_array($roomDevices)) {
                                            foreach ($roomDevices as $roomDevice) {
                                                if (array_key_exists('cloudDeviceId', $roomDevice)) {
                                                    $roomDeviceID = $roomDevice['cloudDeviceId'];
                                                    if (array_key_exists('devices', $data)) {
                                                        $devices = $data['devices'];
                                                        if (is_array($devices)) {
                                                            foreach ($devices as $device) {
                                                                if (array_key_exists('cloudDeviceId', $device)) {
                                                                    $deviceID = $device['cloudDeviceId'];
                                                                    if ($deviceID == $roomDeviceID) {
                                                                        if (array_key_exists('name', $device)) {
                                                                            $name = $device['name'];
                                                                            array_push($values, ['CloudDeviceID' => (string) $roomDeviceID, 'Name' => (string) $name]);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //Groups
                if (array_key_exists('deviceGroups', $data)) {
                    $groups = $data['deviceGroups'];
                    if (is_array($groups)) {
                        foreach ($groups as $group) {
                            if (array_key_exists('id', $group)) {
                                $deviceGroupID = $group['id'];
                                if ($deviceGroupID == $groupID) {
                                    if (array_key_exists('devices', $group)) {
                                        $groupDevices = $group['devices'];
                                        if (is_array($groupDevices)) {
                                            foreach ($groupDevices as $groupDevice) {
                                                if (array_key_exists('cloudDeviceId', $groupDevice)) {
                                                    $groupDeviceID = $groupDevice['cloudDeviceId'];
                                                    if (array_key_exists('devices', $data)) {
                                                        $devices = $data['devices'];
                                                        if (is_array($devices)) {
                                                            foreach ($devices as $device) {
                                                                if (array_key_exists('cloudDeviceId', $device)) {
                                                                    $deviceID = $device['cloudDeviceId'];
                                                                    if ($deviceID == $groupDeviceID) {
                                                                        if (array_key_exists('name', $device)) {
                                                                            $name = $device['name'];
                                                                            array_push($values, ['CloudDeviceID' => (string) $groupDeviceID, 'Name' => (string) $name]);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            @IPS_SetProperty($this->InstanceID, 'Devices', json_encode($values));
            if (@IPS_HasChanges($this->InstanceID)) {
                @IPS_ApplyChanges($this->InstanceID);
            }
        }
    }

    public function UpdateGroupState(): void
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            $this->SetUpdateTimer();
            return;
        }
        $devices = json_decode($this->ReadPropertyString('Devices'), true);
        if (empty($devices)) {
            $this->SetUpdateTimer();
            return;
        }
        $this->SetTimerInterval('UpdateGroupState', 0);
        if (is_array($devices)) {
            $groupPowerState = false;
            $groupBrightness = [];
            $success = false;
            foreach ($devices as $device) {
                if (array_key_exists('CloudDeviceID', $device)) {
                    $cloudDeviceID = $device['CloudDeviceID'];
                    if (!empty($cloudDeviceID)) {
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
                                    $this->SendDebug(__FUNCTION__, 'Continue, result http code: ' . $httpCode . ', must be 200!', 0);
                                    continue;
                                } else {
                                    $success = true;
                                }
                            }
                            if (array_key_exists('body', $result)) {
                                $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($result['body']), 0);
                                $deviceData = $result['body'];
                                if (is_array($deviceData)) {
                                    //Status
                                    if (array_key_exists('status', $deviceData)) {
                                        $deviceStatus = $deviceData['status'];
                                        if ($deviceStatus == 'on') {
                                            $groupPowerState = true;
                                        }
                                    }
                                    //Brightness
                                    if (array_key_exists('brightness', $deviceData)) {
                                        $brightness = $deviceData['brightness'];
                                        if (is_array($brightness)) {
                                            if (array_key_exists('percentage', $brightness)) {
                                                $percentage = $brightness['percentage'];
                                                array_push($groupBrightness, $percentage);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($success) {
                $this->SetValue('GroupPower', $groupPowerState);
                $sum = array_sum($groupBrightness);
                $amount = count($groupBrightness);
                $averageBrightness = $sum / $amount;
                $this->SetValue('Brightness', $averageBrightness);
            }
        }
        $this->SetUpdateTimer();
    }

    public function ToggleGroupPower(bool $State): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateGroupState', 0);
        $actualState = $this->GetValue('GroupPower');
        $this->SetValue('GroupPower', $State);
        $powerState = 'off';
        if ($State) {
            $powerState = 'on';
        }
        $payload = [];
        $devices = json_decode($this->ReadPropertyString('Devices'), true);
        if (empty($devices)) {
            $this->SetTimerInterval('UpdateGroupState', 5000);
            return false;
        }
        if (is_array($devices)) {
            foreach ($devices as $device) {
                if (array_key_exists('CloudDeviceID', $device)) {
                    $cloudDeviceID = $device['CloudDeviceID'];
                    if (!empty($cloudDeviceID)) {
                        array_push($payload, ['id' => $cloudDeviceID, 'state' => ['status' => $powerState]]);
                    }
                }
            }
        }
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetGroupState';
        $buffer['Params'] = ['payload' => json_encode($payload)];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                if ($httpCode != 200) {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetTimerInterval('UpdateGroupState', 5000);
                    //Revert
                    $this->SetValue('GroupPower', $actualState);
                    return false;
                }
            }
        }
        $this->SetTimerInterval('UpdateGroupState', 5000);
        return $success;
    }

    public function ToggleBrightness(int $Percentage): bool
    {
        if (!$this->HasActiveParent()) {
            $this->SendDebug(__FUNCTION__, 'Parent splitter instance is inactive!', 0);
            return false;
        }
        $this->SetTimerInterval('UpdateGroupState', 0);
        $actualPercentage = $this->GetValue('Brightness');
        $this->SetValue('Brightness', $Percentage);
        $payload = [];
        $devices = json_decode($this->ReadPropertyString('Devices'), true);
        if (empty($devices)) {
            $this->SetTimerInterval('UpdateGroupState', 5000);
            return false;
        }
        if (is_array($devices)) {
            foreach ($devices as $device) {
                if (array_key_exists('CloudDeviceID', $device)) {
                    $cloudDeviceID = $device['CloudDeviceID'];
                    if (!empty($cloudDeviceID)) {
                        array_push($payload, ['id' => $cloudDeviceID, 'state' => ['brightness' => ['percentage' => $Percentage]]]);
                    }
                }
            }
        }
        $success = true;
        $data = [];
        $buffer = [];
        $data['DataID'] = self::KLYQA_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'SetGroupState';
        $buffer['Params'] = ['payload' => json_encode($payload)];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (is_array($result)) {
            if (array_key_exists('httpCode', $result)) {
                $httpCode = $result['httpCode'];
                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                if ($httpCode != 200) {
                    $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                    $this->SetTimerInterval('UpdateGroupState', 5000);
                    //Revert
                    $this->SetValue('Brightness', $actualPercentage);
                    return false;
                }
            }
        }
        $this->SetTimerInterval('UpdateGroupState', 5000);
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
        $this->SetTimerInterval('UpdateGroupState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }
}