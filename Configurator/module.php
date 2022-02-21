<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class KlyqaConfigurator extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{243E0FEF-A7D1-6126-149E-ACE89A5D3F69}';
    private const KLYQA_SPLITTER_GUID = '{D71BFBAE-AD9F-00C1-1C83-BFD49EB41D2C}';
    private const KLYQA_SPLITTER_DATA_GUID = '{8F385BA9-23F8-2969-19F8-0E04ABD77E5B}';
    private const KLYQA_DEVICE_GUID = '{9BC6DF5F-FE71-B3FA-3234-B83329C6EF7D}';
    private const KLYQA_GROUP_GUID = '{C1A5757A-F7CF-6976-E794-940D2887D78F}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyInteger('CategoryID', 0);

        //Connect to parent (Klyqa Splitter)
        $this->ConnectParent(self::KLYQA_SPLITTER_GUID);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();
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

    public function GetConfigurationForm(): string
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        //Version info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $formData['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']);
        //Settings
        $values = $this->GetSettings();
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function GetSettings(): array
    {
        $values = [];
        if (!$this->HasActiveParent()) {
            return $values;
        }
        $location = $this->GetCategoryPath($this->ReadPropertyInteger(('CategoryID')));
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
                return $values;
            }
        }
        if (array_key_exists('body', $result)) {
            $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($result['body']), 0);
            $data = $result['body'];
            if (is_array($data)) {
                //Category Devices
                $values[] = [
                    'id'       => 1,
                    'expanded' => true,
                    'name'     => $this->Translate('Devices'),
                    'Product'  => '',
                    'ID'       => ''
                ];
                //Devices
                if (array_key_exists('devices', $data)) {
                    $devices = $data['devices'];
                    if (is_array($devices)) {
                        foreach ($devices as $device) {
                            $name = $this->Translate('Unknown');
                            if (array_key_exists('name', $device)) {
                                $name = $device['name'];
                            }
                            $product = $this->Translate('Unknown');
                            if (array_key_exists('productId', $device)) {
                                $product = $device['productId'];
                            }
                            switch ($product) {
                                case '@klyqa.lighting.cw-ww.e27':
                                    $switchingProfile = 1;
                                    break;

                                case '@klyqa.lighting.rgb-cw-ww.e27':
                                    $switchingProfile = 2;
                                    break;

                                default:
                                    $switchingProfile = 0;
                            }
                            $cloudDeviceID = $this->Translate('Unknown');
                            if (array_key_exists('cloudDeviceId', $device)) {
                                $cloudDeviceID = $device['cloudDeviceId'];
                            }
                            $deviceInstanceID = $this->GetDeviceInstanceID($cloudDeviceID);
                            $values[] = [
                                'parent'     => 1,
                                'name'       => $name,
                                'Product'    => $product,
                                'ID'         => $cloudDeviceID,
                                'instanceID' => $deviceInstanceID,
                                'create'     => [
                                    'moduleID'      => self::KLYQA_DEVICE_GUID,
                                    'name'          => $name . ' (Klyqa ' . $this->Translate('Device') . ')',
                                    'configuration' => [
                                        'CloudDeviceID'    => (string) $cloudDeviceID,
                                        'DeviceName'       => (string) $name,
                                        'SwitchingProfile' => $switchingProfile

                                    ],
                                    'location' => $location
                                ]
                            ];
                        }
                    }
                }
                //Category Groups
                $values[] = [
                    'id'       => 2,
                    'expanded' => true,
                    'name'     => $this->Translate('Groups'),
                    'Product'  => '',
                    'ID'       => ''
                ];
                //Rooms
                if (array_key_exists('rooms', $data)) {
                    $rooms = $data['rooms'];
                    if (is_array($rooms)) {
                        foreach ($rooms as $room) {
                            $name = $this->Translate('Unknown');
                            if (array_key_exists('name', $room)) {
                                $name = $room['name'];
                            }
                            $product = $this->Translate('Room');
                            $groupID = $this->Translate('Unknown');
                            if (array_key_exists('id', $room)) {
                                $groupID = $room['id'];
                            }
                            $groupInstanceID = $this->GetGroupInstanceID($groupID);
                            $values[] = [
                                'parent'     => 2,
                                'name'       => $name,
                                'Product'    => $product,
                                'ID'         => $groupID,
                                'instanceID' => $groupInstanceID,
                                'create'     => [
                                    'moduleID'      => self::KLYQA_GROUP_GUID,
                                    'name'          => $name . ' (Klyqa ' . $this->Translate('Room') . ')',
                                    'configuration' => [
                                        'GroupID'   => (string) $groupID,
                                        'GroupName' => (string) $name
                                    ],
                                    'location' => $location
                                ]
                            ];
                        }
                    }
                }
                //Groups
                if (array_key_exists('deviceGroups', $data)) {
                    $groups = $data['deviceGroups'];
                    if (is_array($groups)) {
                        foreach ($groups as $group) {
                            $name = $this->Translate('Unknown');
                            if (array_key_exists('name', $group)) {
                                $name = $group['name'];
                            }
                            $product = $this->Translate('Group');
                            $groupID = $this->Translate('Unknown');
                            if (array_key_exists('id', $group)) {
                                $groupID = $group['id'];
                            }
                            $groupInstanceID = $this->GetGroupInstanceID($groupID);
                            $values[] = [
                                'parent'     => 2,
                                'name'       => $name,
                                'Product'    => $product,
                                'ID'         => $groupID,
                                'instanceID' => $groupInstanceID,
                                'create'     => [
                                    'moduleID'      => self::KLYQA_GROUP_GUID,
                                    'name'          => $name . ' (Klyqa ' . $this->Translate('Group') . ')',
                                    'configuration' => [
                                        'GroupID'   => (string) $groupID,
                                        'GroupName' => (string) $name
                                    ],
                                    'location' => $location
                                ]
                            ];
                        }
                    }
                }
            }
        }
        return $values;
    }

    private function GetCategoryPath(int $CategoryID): array
    {
        if ($CategoryID === 0) {
            return [];
        }
        $path[] = IPS_GetName($CategoryID);
        $parentID = IPS_GetObject($CategoryID)['ParentID'];
        while ($parentID > 0) {
            $path[] = IPS_GetName($parentID);
            $parentID = IPS_GetObject($parentID)['ParentID'];
        }
        return array_reverse($path);
    }

    private function GetDeviceInstanceID(string $CloudDeviceID): int
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(self::KLYQA_DEVICE_GUID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'CloudDeviceID') == $CloudDeviceID) {
                $id = $instance;
            }
        }
        return $id;
    }

    private function GetGroupInstanceID(string $GroupID): int
    {
        $id = 0;
        $instances = IPS_GetInstanceListByModuleID(self::KLYQA_GROUP_GUID);
        foreach ($instances as $instance) {
            if (IPS_GetProperty($instance, 'GroupID') == $GroupID) {
                $id = $instance;
            }
        }
        return $id;
    }
}