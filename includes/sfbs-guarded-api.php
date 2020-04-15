<?php

namespace OneTwenteyFourLabs;

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

//require_once('thirdParty/httpful_old.phar');
require_once('thirdParty/httpful-php7/bootstrap.php');

/*
* A class to interface with StrikeForce's GuardedID API.
*/
class GuardedIdApi{
    # Fields

    private $sellerID;
    private $userID;
    private $userPassword;
    private $guardedIDUri;
    private $mobileTrustUri;
    private $productId;
    private $licenseType;
    private $sellerInfo;

    # Constructor

    /*
    * @param $guardedIDUri (string) StrikeForce's GuardedID API URI.
    * @param $mobileTrustUri (string) StrikeForce's MobileTrust API URI.
    * @param $sellerID (string) The seller's ID to access StrikeForce's API.
    * @param $userID (string) The user's ID to access StrikeForce's API.
    * @param $userPassword (string) The user's password to access StrikeForce's API.
    * @param $productId (int) The product ID.
    * @param $licenseType (string) The license type.
    */
    public function __construct( $guardedIDUri, $mobileTrustUri, $sellerID, $userID, $userPassword, $productId, $licenseType) {
        $this->guardedIDUri = rTrim($guardedIDUri, "/");
        $this->mobileTrustUri = rTrim($mobileTrustUri, "/");

        $this->sellerID = $sellerID;
        $this->userID = $userID;
        $this->userPassword = $userPassword;

        $this->productId = $productId;
        $this->licenseType = $licenseType;

        //TODO:REMOVE
        /*
        write_log(
            'guardedIDUri: ' . rTrim($guardedIDUri, "/") .
            ', mobileTrustUri: ' .  rTrim($mobileTrustUri, "/") .
            ', sellerID: ' . $sellerID .
            ', userID:'. $userID .
            ', userPassword: ' . $userPassword .
            ', productId: ' . $productId .
            ', licenseType: ' . $licenseType
        );*/
    }

    # Methods

    /*
    * Call this function to sell a license.
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $subscription (WC_Subscription) The subscription object from WooCommerce.
    * @param $licenseCount (int) The number of licenses sold.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function sellLicense($orderNumber, $subscription, $licenseCount){
        // to get the user info please see the following document for calls
        // https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
        // https://docs.woothemes.com/wc-apidocs/class-WC_Abstract_Order.html

        $licenseXml =
              "<GIDLicense xmlns='http://GuardedID.com/GIDLicense.xsd'>"
            . "<License>"
            . "<OrderNumber>{$orderNumber}</OrderNumber>"
            . "<Count>{$licenseCount}</Count>"
            . "<LicenseType>{$this->licenseType}</LicenseType>"
            . "<ProductID>{$this->productId}</ProductID>"
            . "</License>"
            . "<Customer>"
            . "<ReferenceNo>{$orderNumber}</ReferenceNo>"
            . "<OrderNo>{$orderNumber}</OrderNo>"
            . "<LastName>{$subscription->get_billing_last_name()}</LastName>"
            . "<FirstName>{$subscription->get_billing_first_name()}</FirstName>"
            . "<Company>{$subscription->get_billing_company()}</Company>"
            . "<Address>{$subscription->get_billing_address_1()}{$subscription->get_billing_address_2()}</Address>"
            . "<City>{$subscription->get_billing_city()}</City>"
            . "<State>{$subscription->get_billing_state()}</State>"
            . "<Zip>{$subscription->get_billing_postcode()}</Zip>"
            . "<Phone>{$subscription->get_billing_phone()}</Phone>"
            . "<Email>{$subscription->get_billing_email()}</Email>"
            . "</Customer>"
            . "</GIDLicense>";

        //TODO:REMOVE
        //write_log('guarded-api.php:' . __LINE__ . $licenseXml);

        $request = $this->guardedIdRequest("SellLicense", "&LicenseXml={$licenseXml}");

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Call this function to temporarily make a license unusable.
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $licenseKey (string) The license key.
    * @param $reasonForChange (string) The reason for the change.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function suspendLicense($orderNumber, $licenseKey, $reasonForChange){
        $licenseXml =
              "<GIDLicense xmlns='http://GuardedID.com/GIDLicense.xsd'>"
            . "<License>"
            . "<OrderNumber>{$orderNumber}</OrderNumber>"
            . "<LicenseKey>{$licenseKey}</LicenseKey>"
            . "<ProductID>{$this->productId}</ProductID>"
            . "<ReasonForChange>{$reasonForChange}</ReasonForChange>"
            . "</License>"
            . "</GIDLicense>";
        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__ . $licenseXml);

        $request =  $this->guardedIdRequest("SuspendLicense", "&LicenseXml={$licenseXml}");
        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Call this function to enable a license that has been suspended.
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $licenseKey (string) The license key.
    * @param $reasonForChange (string) The reason for the change.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function enableLicense($orderNumber, $licenseKey, $reasonForChange){
        $licenseXml =
              "<GIDLicense xmlns='http://GuardedID.com/GIDLicense.xsd'>"
            . "<License>"
            . "<OrderNumber>{$orderNumber}</OrderNumber>"
            . "<LicenseKey>{$licenseKey}</LicenseKey>"
            . "<ProductID>{$this->productId}</ProductID>"
            . "<ReasonForChange>{$reasonForChange}</ReasonForChange>"
            . "</License>"
            . "</GIDLicense>";

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__ . $licenseXml);

        $request =  $this->guardedIdRequest("EnableLicense", "&LicenseXml={$licenseXml}");

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Call this function to increment the license count.
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $licenseKey (string) The license key.
    * @param $count (int) The number to increment the license count by.
    * @param $reasonForChange (string) The reason for the change.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function incrementLicenseCount($orderNumber, $licenseKey, $count, $reasonForChange){
        $licenseXml =
              "<GIDLicense xmlns='http://GuardedID.com/GIDLicense.xsd'>"
            . "<License>"
            . "<OrderNumber>{$orderNumber}</OrderNumber>"
            . "<Count>{$count}</Count>"
            . "<LicenseKey>{$licenseKey}</LicenseKey>"
            . "<ProductID>{$this->productId}</ProductID>"
            . "<ReasonForChange>{$reasonForChange}</ReasonForChange>"
            . "</License>"
            . "</GIDLicense>";

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__ . $licenseXml);

        $request =  $this->guardedIdRequest("IncrementLicenseCount", "&LicenseXml={$licenseXml}");

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Call this function to disassociate a user’s machineid with the license key.
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $licenseKey (string) The license key.
    * @param $reasonForChange (string) The reason for the change.
    * @param $machineID (string) The machine ID.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function deactivateMachine($orderNumber, $licenseKey, $reasonForChange, $machineID){
        $licenseXml =
              "<GIDLicense xmlns='http://GuardedID.com/GIDLicense.xsd'>"
            . "<License>"
            . "<OrderNumber>{$orderNumber}</OrderNumber>"
            . "<LicenseKey>{$licenseKey}</LicenseKey>"
            . "<ProductID>{$this->productId}</ProductID>"
            . "<ReasonForChange>{$reasonForChange}</ReasonForChange>"
            . "</License>"
            . "<MachineInfo>"
            . "<MachineID>{$machineID}</MachineID>"
            . "</MachineInfo>"
            . "</GIDLicense>";

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__ . $licenseXml);

        $request =  $this->guardedIdRequest("DeactivateMachine", "&LicenseXml={$licenseXml}");

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }
    /*
    * Call this function to get information on any license key.
    *
    * @param $licenseKey (string) The license key.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function getLicenseInfo($licenseKey){
        $request =  $this->guardedIdRequest("GetLicenseInfo", "&LicenseKey={$licenseKey}");

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Call this function to create an SCloud "profile".
    *
    * @param $orderNumber (int) The order number. Must be unique.
    * @param $subscription (WC_Subscription) The subscription object from WooCommerce.
    * @param $licenseKey (string) The license key.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    public function userRegistration($orderNumber, $subscription, $licenseKey)
    {
        // To get the user info please see the following document for calls
        // https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
        // https://docs.woothemes.com/wc-apidocs/class-WC_Abstract_Order.html

        $jsonPayload =
            "{'Email':'{$subscription->get_billing_email()}',"
            . "'License':'{$licenseKey}',"
            . "'CustomerReferenceNumber':'{$orderNumber}',"
            . "'ProductID':'{$this->productId}',"
            . "'SellerID':'{$this->sellerID}'}";

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__ . $jsonPayload);

        $request =  $this->mobileTrustRequest("User/UserRegistration", $jsonPayload);

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($request);
        return $request;
    }

    /*
    * Builds an http call for the GuardedID API.
    *
    * @param $relativeUri (string) The relative URI.
    * @param $appendToPayload (string) The string to append to the payload.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    private function guardedIdRequest($relativeUri, $appendToPayload){
        $sellerInfo = "SellerID={$this->sellerID}&UserID={$this->userID}&UserPassword={$this->userPassword}";

        //TODO:REMOVE
        /*write_log('sf-guarded-id-api.php:' . __LINE__ .
            'guardedIDUri:' . $this->guardedIDUri .
            ', relativeUri' .  $relativeUri .
            ', contentType: ' . 'application/x-www-form-urlencoded'  .
            ', payload: ' . $sellerInfo . $appendToPayload
        );*/


        $body = $this->postRequest(
            $this->guardedIDUri,
            $relativeUri,
            "application/x-www-form-urlencoded",
            $sellerInfo . $appendToPayload
        );

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($body);

        // Must parse again (Httpful already parsed once) since the response
        // we want is embeded as an XML string inside of a parent node.
        // i.e. <string>"<RealResponse>etc..</RealResponse>"</string>
        $response = simplexml_load_string($body);

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($response);
        return $response;
    }

    /*
    * Builds an http call for the MobileTrust API.
    *
    * @param $relativeUri (string) The relative URI.
    * @param $appendToPayload (string) The string to append to the payload.
    * @return (SimpleXMLElement) A SimpleXMLElement object.
    */
    private function mobileTrustRequest($relativeUri, $appendToPayload) {
        $response = $this->postRequest($this->mobileTrustUri, $relativeUri, "application/json", $appendToPayload);

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($response);
        return $response;
    }

    /*
    * Preforms an http post.
    *
    * @param $serverUri (string) StrikeForce's API URI.
    * @param $relativeUri (string) The relative URI.
    * @param $contentType (string) Set the http post's Content-Type header value.
    * @param $payload (string) The body of the http post request.
    * @return (mixed) The response's body.
    */
    private function postRequest($serverUri, $relativeUri, $contentType, $payload){

        $relativeUri = lTrim($relativeUri, "/");

        // TODO: wrap entire function in try catch.
        // TODO: Retry 3Xs failed requests.
        $response = \Httpful\Request::post("{$serverUri}/{$relativeUri}")
            ->addHeader("Content-Type", $contentType)
            ->body($payload)
            ->expectsXml()
            ->send();

        //TODO:REMOVE
        //write_log('sf-guarded-id-api.php:' . __LINE__);
        //write_log($response->body);
        //write_log("*** Response = {$response->body}");

        return $response->body;
    }
}