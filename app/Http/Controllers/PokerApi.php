<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PokerHelper;

class PokerApi extends Controller
{ 

    private $affID;
    private $secret;

    public function __construct($affID, $secret, $addr, $port) {
        $this->affID = $affID;
        $this->secret = $secret;
        $this->TCP = new PokerHelper($addr, $port);
    }   
    
    public function connect() {
        return $this->TCP->request('<connect csaid="1" affiliateid="'.$this->affID.'"  secretkey="'.$this->secret.'"  version="200604203" advertisementid="0" macaddress="0050563F3136" internalip="134.249.187.32" internalhost="winxp"/>');
    }
    
    public function getIdByLogin($login) {
        $data = $this->TCP->request('<objects><object name="user"><uogetuseridbylogin loginname="'.$login.'" secretkey="'.$this->secret.'"/></object></objects>');
        return $data['uogetuseridbylogin']['@attributes']['userid'];
    }
    
    public function getUserInfo($id) {
        return $this->TCP->request('<objects><object name="user"><uogetuserdetails userid="'.$id.'" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function getRunLink($login) {
        return $this->TCP->request('<objects><object name="user"><uogetuserrunlink loginname="'.$login.'" secretkey="'.$this->secret.'" /></object></objects>');
    }


    public function createPlayer($login, $password, $firstName, $lastName, $email, $sex, $location) {
        return $this->TCP->request('<objects><object name="user"><uoregister_2 loginname="'.$login.'" password="'.$password.'" firstname="'.$firstName.'" lastname="'.$lastName.'" email="'.$email.'@email.com" sexid="'.$sex.'" location="'.$location.'" showlocation="0" affiliateid="'.$this->affID.'" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function changePlayer($id, $firstName, $lastName, $sex, $status, $chatAccess, $address, $city, $phone, $stateId, $countryId) {
        return $this->TCP->request('<objects><object name="user"><uoupdateuserdetails userid="'.$id.'" firstname="'.$firstName.'" lastname="'.$lastName.'" sexid="'.$sex.'" statusid="'.$status.'" chataccess="'.$chatAccess.'" address="'.$address.'" city="'.$city.'" phone="'.$phone.'" stateid="'.$stateId.'" countryid="'.$countryId.'" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function getBalance($id) {
        return $this->TCP->request('<objects><object name="account"><aogetbalanceinfo_2 userid="'.$id.'" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function changeBalance($id, $type, $value) {
        return $this->TCP->request('<objects><object name="account"><aochangebalance_2 userid="'.$id.'" currencytypeid="'.$type.'" amount="'.$value.'" type="1" reason="test request" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function getAvatar($id) {
        return $this->TCP->request('<objects><object name="filemanager"><fmgetplayerlogo userid="'.$id.'" secretkey="'.$this->secret.'"/></object></objects>');
    }
    
    public function setAvatar($login, $imageName, $imageSize, $imageLength, $imageBuffer) {
        return $this->TCP->request('<objects><object name="filemanager"><fmsetplayerlogo loginname="'.$login.'" filename="'.$imageName.'" link="" filesize="'.$imageSize.'" buflen="'.$imageLength.'" buf="'.$imageBuffer.'" secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function changePlayerPassword($id, $password) {
        return $this->TCP->request('<objects><object name="user"><uochangepassword userid="'.$id.'" newpassword="'.$password.'"
secretkey="'.$this->secret.'" /></object></objects>');
    }
    
    public function changePlayerStatus($id, $statusId) {
        return $this->TCP->request('<objects><object name="user"><uochangestatus userid="'.$id.'" statusid="'.$statusId.'"
secretkey="'.$this->secret.'" /></object></objects>');
    }
    
}
