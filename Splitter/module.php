<?php

/** @noinspection PhpRedundantMethodOverrideInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class KlyqaSplitter extends IPSModule
{
    //Helper
    use KlyqaAPI;
    //Constants
    private const LIBRARY_GUID = '{243E0FEF-A7D1-6126-149E-ACE89A5D3F69}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ##### Properties
        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyString('UserName', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Timeout', 5000);

        ##### Attributes
        $this->RegisterAttributeString('AccountToken', '');
        $this->RegisterAttributeString('AccessToken', '');
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

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        $this->ValidateConfiguration();
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
        $formData['actions'][0]['caption'] = $this->ReadAttributeString('AccessToken') ? 'Token: ' . substr($this->ReadAttributeString('AccessToken'), 0, 16) . '...' : 'Token: Not registered yet!';
        return json_encode($formData);
    }

    public function ForwardData($JSONString): string
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetSettings':
                $response = $this->GetSettings();
                break;

            case 'GetDeviceList':
                $response = $this->GetDeviceList();
                break;

            case 'GetDeviceState':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetDeviceState($params['cloudDeviceId']);
                break;

            case 'SetDeviceState':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetDeviceState($params['cloudDeviceId'], $params['payload']);
                break;

            case 'SetGroupState':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetGroupState($params['payload']);
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = '';
        }
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    public function DebugTokens(): void
    {
        $this->SendDebug(__FUNCTION__, 'Account Token: ' . $this->ReadAttributeString('AccountToken'), 0);
        $this->SendDebug(__FUNCTION__, 'Access Token: ' . $this->ReadAttributeString('AccessToken'), 0);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): void
    {
        $status = 102;
        $userName = $this->ReadPropertyString('UserName');
        $password = $this->ReadPropertyString('Password');
        //Check password
        if (empty($password)) {
            $status = 203;
        }
        //Check username
        if (empty($userName)) {
            $status = 202;
        }
        //Check username and password
        if (empty($userName) && empty($password)) {
            $status = 201;
        }
        if (!empty($userName) && !empty($password)) {
            $this->GetTokens();
            if (empty($this->ReadAttributeString('AccessToken'))) {
                $status = 204;
            }
        }
        $active = $this->CheckInstance();
        if (!$active) {
            $status = 104;
        }
        $this->SetStatus($status);
    }

    private function CheckInstance(): bool
    {
        $result = $this->ReadPropertyBoolean('Active');
        if (!$result) {
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
        }
        return $result;
    }
}