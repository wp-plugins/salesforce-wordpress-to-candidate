<?php


/*
 * Copyright (c) 2007, salesforce.com, inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 *    Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *    following disclaimer.
 *
 *    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *    the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *    Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
 *    promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
/**
 * This file contains one class.
 * @package SalesforceSoapClient
 */
/**
 * SalesforceSoapClient
 * @package SalesforceSoapClient
 */
class SforceBaseClient {
  protected $sforce;
  protected $sessionId;
  protected $location;
  protected $version = '1.0.6';

  protected $namespace;

  // clientId specifies which application or toolkit is accessing the
  // salesforce.com API. For applications that are certified salesforce.com
  // solutions, replace this with the value provided by salesforce.com.
  // Otherwise, leave this value as 'phpClient/1.0'.
  protected $client_id;

  public function printDebugInfo() {
    echo "PHP Toolkit Version: $this->version\r\n";
    echo 'Current PHP version: ' . phpversion();
    echo "\r\n";
    echo 'SOAP enabled: ';
    if (extension_loaded('soap')) {
      echo 'True';
    } else {
      echo 'False';
    }
    echo "\r\n";
    echo 'OpenSSL enabled: ';
    if (extension_loaded('openssl')) {
      echo 'True';
    } else {
      echo 'False';
    }
  }

  /**
   * Connect method to www.salesforce.com
   *
   * @param string $wsdl   Salesforce.com Partner WSDL
   */
  public function createConnection($wsdl) {
    $_SERVER['HTTP_USER_AGENT'] = 'Salesforce/PHPToolkit/1.0';

    $soapClientArray = null;
    if (phpversion() > '5.1.2') {
      $soapClientArray = array (
        'encoding' => 'utf-8',
        'trace' => 1,
        'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
      );
    } else {
      $soapClientArray = array (
        'encoding' => 'utf-8',
        'trace' => 1
      );
    }
    $this->sforce = new SoapClient($wsdl,$soapClientArray);
    return $this->sforce;
  }

  /**
   * Login to Salesforce.com and starts a client session.
   *
   * @param string $username   Username
   * @param string $password   Password
   *
   * @return LoginResult
   */
  public function login($username, $password) {
    $this->sforce->__setSoapHeaders(NULL);
    if (isset ($this->client_id)) {
      $header_array = array ();
      $this->_setClientId($header_array);
      $this->sforce->__setSoapHeaders($header_array);
    }
    $result = $this->sforce->login(array (
      'username' => $username,
      'password' => $password
    ));
    $result = $result->result;
    $this->_setLoginHeader($result);
    return $result;
  }

  /**
   * Specifies the session ID returned from the login server after a successful
   * login.
   */
  protected function _setLoginHeader($loginResult) {
    $this->sessionId = $loginResult->sessionId;
    $this->setSessionHeader($this->sessionId);
    $serverURL = $loginResult->serverUrl;
    $this->setEndPoint($serverURL);
  }

  /**
   * Set the endpoint.
   *
   * @param string $location   Location
   */
  public function setEndpoint($location) {
    $this->location = $location;
    $this->sforce->__setLocation($location);
  }

  /**
   * Set the Session ID
   *
   * @param string $sessionId   Session ID
   */
  public function setSessionHeader($sessionId) {
    $this->sforce->__setSoapHeaders(NULL);
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
      'sessionId' => $sessionId
    ));
    $this->sessionId = $sessionId;
    $header_array = array (
      $session_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);
  }

  /**
   * Set the Partner Client ID.
   *
   * @param string $clientId   Partner Client ID
   */
  public function setClientId($clientId) {
    $this->client_id = $clientId;
  }

  protected function _setClientId(& $array) {
    // See note about client_id at the top.
    if (isset ($this->client_id)) {
      $call_options = new SoapHeader($this->namespace, 'CallOptions', array (
        'client' => $this->client_id
      ));
      array_push($array, $call_options);
    }
  }

  protected function _setSessionHeader() {
    $this->sforce->__setSoapHeaders(NULL);
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
      'sessionId' => $this->sessionId
    ));
    $header_array = array (
      $session_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);
  }

  protected function _setAssignmentRuleHeader($header) {
    // clear the headers first!
    $this->sforce->__setSoapHeaders(NULL);
    $assignment_header = new SoapHeader($this->namespace, 'AssignmentRuleHeader', array (
      'assignmentRuleId' => $header->assignmentRuleId,
      'useDefaultRule' => $header->useDefaultRuleFlag
    ));
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
      'sessionId' => $this->sessionId
    ));
    $header_array = array (
      $session_header,
      $assignment_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);
  }

  private function _setMruHeader($header) {
    // clear the headers first!
    $this->sforce->__setSoapHeaders(NULL);
    $mru_header = new SoapHeader($this->namespace, 'MruHeader', array (
      'updateMru' => $header->updateMruFlag
    ));
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
      'sessionId' => $this->sessionId
    ));
    $header_array = array (
      $session_header,
      $mru_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);
  }

  private function _setQueryOptions($header) {
    // clear the headers first!
    $this->sforce->__setSoapHeaders(NULL);
    $query_header = new SoapHeader($this->namespace, 'QueryOptions', array (
      'batchSize' => $header->batchSize
    ));
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
      'sessionId' => $this->sessionId
    ));
    $header_array = array (
      $session_header,
      $query_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);

  }

  public function getSessionId() {
    return $this->sessionId;
  }

  public function getLocation() {
    return $this->location;
  }

  public function getConnection() {
    return $this->sforce;
  }

  public function getFunctions() {
    return $this->sforce->__getFunctions();
  }

  public function getTypes() {
    return $this->sforce->__getTypes();
  }

  public function getLastRequest() {
    return $this->sforce->__getLastRequest();
  }

  public function getLastRequestHeaders() {
    return $this->sforce->__getLastRequestHeaders();
  }

  public function getLastResponse() {
    return $this->sforce->__getLastResponse();
  }

  public function getLastResponseHeaders() {
    return $this->sforce->__getLastResponseHeaders();
  }

  private function _convertToAny($fields) {
    $anyString = '';
    foreach ($fields as $key => $value) {
      $anyString = $anyString . '<' . $key . '>' . $value . '</' . $key . '>';
    }
    return $anyString;
  }

  /**
   * Converts a Lead into an Account, Contact, or (optionally) an Opportunity.
   *
   * @param array $leadConverts    Array of LeadConvert
   *
   * @return LeadConvertResult
   */
  public function convertLead($leadConverts) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->leadConverts = $leadConverts;
    return $this->sforce->convertLead($arg);
  }

  /**
   * Adds one or more new individual objects to your organization's data.
   * @param array $sObjects    Array of one or more sObjects (up to 200) to create.
   * @param AssignmentRuleHeader $assignment_header is optional.  Defaults to NULL
   * @param MruHeader $mru_header is optional.  Defaults to NULL
   * @return SaveResult
   */
  public function create($sObjects, $assignment_header = NULL, $mru_header = NULL) {
    $arg = new stdClass;
    foreach ($sObjects as $sObject) {
      if (isset ($sObject->fields)) {
        $sObject->any = $this->_convertToAny($sObject->fields);
      }
    }
    $arg->sObjects = $sObjects;
    if ($assignment_header != NULL) {
      $this->_setAssignmentRuleHeader($assignment_header);
    }
    if ($mru_header != NULL) {
      $this->_setMruHeader($mru_header);
    }
    if ($assignment_header == NULL & $mru_header == NULL) {
      $this->_setSessionHeader();
    }
    return $this->sforce->create($arg)->result;
  }

  /**
   * Deletes one or more new individual objects to your organization's data.
   *
   * @param array $ids    Array of fields
   * @return DeleteResult
   */
  public function delete($ids) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->ids = $ids;
    return $this->sforce->delete($arg)->result;
  }

  /**
   * Deletes one or more new individual objects to your organization's data.
   *
   * @param array $ids    Array of fields
   * @return DeleteResult
   */
  public function undelete($ids) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->ids = $ids;
    return $this->sforce->undelete($arg)->result;
  }


  /**
   * Retrieves a list of available objects for your organization's data.
   *
   * @return DescribeGlobalResult
   */
  public function describeGlobal() {
    $this->_setSessionHeader();
    return $this->sforce->describeGlobal()->result;
  }

  /**
   * Use describeLayout to retrieve information about the layout (presentation
   * of data to users) for a given object type. The describeLayout call returns
   * metadata about a given page layout, including layouts for edit and
   * display-only views and record type mappings. Note that field-level security
   * and layout editability affects which fields appear in a layout.
   *
   * @param string Type   Object Type
   * @return DescribeLayoutResult
   */
  public function describeLayout($type) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    return $this->sforce->describeLayout($arg)->result;
  }

  /**
   * Describes metadata (field list and object properties) for the specified
   * object.
   *
   * @param string $type    Object type
   * @return DescribsSObjectResult
   */
  public function describeSObject($type) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    return $this->sforce->describeSObject($arg)->result;
  }

  /**
   * An array-based version of describeSObject; describes metadata (field list
   * and object properties) for the specified object or array of objects.
   *
   * @param array $arrayOfTypes    Array of object types.
   * @return DescribsSObjectResult
   */
  public function describeSObjects($arrayOfTypes) {
    $this->_setSessionHeader();
    return $this->sforce->describeSObjects($arrayOfTypes)->result;
  }

  /**
   * The describeTabs call returns information about the standard apps and
   * custom apps, if any, available for the user who sends the call, including
   * the list of tabs defined for each app.
   *
   * @return DescribeTabSetResult
   */
  public function describeTabs() {
    $this->_setSessionHeader();
    return $this->sforce->describeTabs()->result;
  }

  /**
   * Retrieves the list of individual objects that have been deleted within the
   * given timespan for the specified object.
   *
   * @param string $type    Ojbect type
   * @param date $startDate  Start date
   * @param date $endDate   End Date
   * @return GetDeletedResult
   */
  public function getDeleted($type, $startDate, $endDate) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    $arg->startDate = $startDate;
    $arg->endDate = $endDate;
    return $this->sforce->getDeleted($arg)->result;
  }

  /**
   * Retrieves the list of individual objects that have been updated (added or
   * changed) within the given timespan for the specified object.
   *
   * @param string $type    Ojbect type
   * @param date $startDate  Start date
   * @param date $endDate   End Date
   * @return GetUpdatedResult
   */
  public function getUpdated($type, $startDate, $endDate) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    $arg->startDate = $startDate;
    $arg->endDate = $endDate;
    return $this->sforce->getUpdated($arg)->result;
  }

  /**
   * Executes a query against the specified object and returns data that matches
   * the specified criteria.
   *
   * @param String $query Query String
   * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
   * @return QueryResult
   */
  public function query($query, $queryOptions = NULL) {
    if ($queryOptions != NULL) {
      $this->_setQueryOptions($queryOptions);
    } else {
      $this->_setSessionHeader();
    }
    $QueryResult = $this->sforce->query(array (
      'queryString' => $query
    ))->result;
    $this->_handleRecords($QueryResult);
    return $QueryResult;
  }

  /**
   * Retrieves the next batch of objects from a query.
   *
   * @param QueryLocator $queryLocator Represents the server-side cursor that tracks the current processing location in the query result set.
   * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
   * @return QueryResult
   */
  public function queryMore($queryLocator, $queryOptions = NULL) {
    if ($queryOptions != NULL) {
      $this->_setQueryOptions($queryOptions);
    } else {
      $this->_setSessionHeader();
    }
    $arg = new stdClass;
    $arg->queryLocator = $queryLocator;
    $QueryResult = $this->sforce->queryMore($arg)->result;
    $this->_handleRecords($QueryResult);
    return $QueryResult;
  }

  /**
   * Retrieves data from specified objects, whether or not they have been deleted.
   *
   * @param String $query Query String
   * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
   * @return QueryResult
   */
  public function queryAll($query, $queryOptions = NULL) {
    if ($queryOptions != NULL) {
      $this->_setQueryOptions($queryOptions);
    } else {
      $this->_setSessionHeader();
    }
    $QueryResult = $this->sforce->queryAll(array (
    'queryString' => $query
    ))->result;
    $this->_handleRecords($QueryResult);
    return $QueryResult;
  }


  private function _handleRecords(& $QueryResult) {
    if ($QueryResult->size > 0) {
      if ($QueryResult->size == 1) {
        $recs = array (
          $QueryResult->records
        );
      } else {
        $recs = $QueryResult->records;
      }
      $QueryResult->records = $recs;
    }
  }

  /**
   * Retrieves one or more objects based on the specified object IDs.
   *
   * @param string $fieldList      One or more fields separated by commas.
   * @param string $sObjectType    Object from which to retrieve data.
   * @param array $ids            Array of one or more IDs of the objects to retrieve.
   * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
   * @return sObject[]
   */
  public function retrieve($fieldList, $sObjectType, $ids, $queryOptions = NULL) {
    if ($queryOptions != NULL) {
      $this->_setQueryOptions($queryOptions);
    } else {
      $this->_setSessionHeader();
    }
    $arg = new stdClass;
    $arg->fieldList = $fieldList;
    $arg->sObjectType = new SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    $arg->ids = $ids;
    return $this->sforce->retrieve($arg)->result;
  }

  /**
   * Executes a text search in your organization's data.
   *
   * @param string $searchString   Search string that specifies the text expression to search for.
   * @return SearchResult
   */
  public function search($searchString) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->searchString = new SoapVar($searchString, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    return $this->sforce->search($arg)->result;
  }

  /**
   * Creates new objects and updates existing objects; uses a custom field to
   * determine the presence of existing objects. In most cases, we recommend
   * that you use upsert instead of create because upsert is idempotent.
   * Available in the API version 7.0 and later.
   *
   * @param string $ext_Id        External Id
   * @param array  $sObjects  Array of sObjects
   * @return UpsertResult
   */
  public function upsert($ext_Id, $sObjects) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->externalIDFieldName = new SoapVar($ext_Id, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    foreach ($sObjects as $sObject) {
      if (isset ($sObject->fields)) {
        $sObject->any = $this->_convertToAny($sObject->fields);
      }
    }
    $arg->sObjects = $sObjects;
    return $this->sforce->upsert($arg)->result;
  }

  /**
   * Updates one or more new individual objects to your organization's data.
   * @param array sObjects    Array of sObjects
   * @param AssignmentRuleHeader $assignment_header is optional.  Defaults to NULL
   * @param MruHeader $mru_header is optional.  Defaults to NULL
   * @return UpdateResult
   */
  public function update($sObjects, $assignment_header = NULL, $mru_header = NULL) {
    $arg = new stdClass;
    foreach ($sObjects as $sObject) {
      if (isset ($sObject->fields)) {
        $sObject->any = $this->_convertToAny($sObject->fields);
      }
    }
    $arg->sObjects = $sObjects;
    if ($assignment_header != NULL) {
      $this->_setAssignmentRuleHeader($assignment_header);
    }
    if ($mru_header != NULL) {
      $this->_setMruHeader($mru_header);
    }
    if ($assignment_header == NULL & $mru_header == NULL) {
      $this->_setSessionHeader();
    }
    return $this->sforce->update($arg)->result;
  }

  /**
   * Retrieves the current system timestamp (GMT) from the Web service.
   *
   * @return timestamp
   */
  public function getServerTimestamp() {
    $this->_setSessionHeader();
    return $this->sforce->getServerTimestamp()->result;
  }

  public function getUserInfo() {
    $this->_setSessionHeader();
    return $this->sforce->getUserInfo()->result;
  }

  /**
   * Sets the specified user's password to the specified value.
   *
   * @param string $userId    ID of the User.
   * @param string $password  New password
   */
  public function setPassword($userId, $password) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->userId = new SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    $arg->password = $password;
    return $this->sforce->setPassword($arg);

  }

  /**
   * Changes a user's password to a system-generated value.
   *
   * @param string $userId    Id of the User
   * @return password
   */
  public function resetPassword($userId) {
    $this->_setSessionHeader();
    $arg = new stdClass;
    $arg->userId = new SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    return $this->sforce->resetPassword($arg)->result;
  }
}
?>