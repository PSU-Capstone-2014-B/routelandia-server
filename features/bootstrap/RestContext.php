<?php

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;
use Luracast\Restler\Data\String;


/**
 * Rest context.
 *
 * @category   Framework
 * @package    restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0
 */
class RestContext extends BehatContext
{

    private $_startTime = null;
    private $_restObject = null;
    private $_headers = array();
    private $_restObjectType = null;
    private $_restObjectMethod = 'get';
    private $_client = null;
    private $_response = null;
    private $_request = null;
    private $_requestBody = null;
    private $_requestUrl = null;
    private $_type = null;
    private $_charset = null;
    private $_language = null;
    private $_data = null;
    private $requestPayload = null;

    private $_parameters = array();

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here

        $this->_restObject = new stdClass();
        $this->_parameters = $parameters;
        $this->_client = new Guzzle\Service\Client();
        //suppress few errors
        $this->_client
            ->getEventDispatcher()
            ->addListener('request.error',
                function (\Guzzle\Common\Event $event) {
                    switch ($event['response']->getStatusCode()) {
                        case 400:
                        case 401:
                        case 404:
                        case 405:
                        case 406:
                            $event->stopPropagation();
                    }
                });
        $timezone = ini_get('date.timezone');
        if (empty($timezone))
            date_default_timezone_set('UTC');
    }

    public function getParameter($name)
    {
        if (count($this->_parameters) === 0) {


            throw new \Exception('Parameters not loaded!');
        } else {

            $parameters = $this->_parameters;
            return (isset($parameters[$name])) ? $parameters[$name] : null;
        }
    }

    /**
     * ============ json array ===================
     * @Given /^that I send (\[[^]]*\])$/
     *
     * ============ json object ==================
     * @Given /^that I send (\{(?>[^\{\}]+|(?1))*\})$/
     *
     * ============ json string ==================
     * @Given /^that I send ("[^"]*")$/
     *
     * ============ json int =====================
     * @Given /^that I send ([-+]?[0-9]*\.?[0-9]+)$/
     *
     * ============ json null or boolean =========
     * @Given /^that I send (null|true|false)$/
     */
    public function thatISend($data)
    {
        $this->_restObject = json_decode($data);
        $this->_restObjectMethod = 'post';
    }

    /**
     * @Given /^that I send:/
     * @param PyStringNode $data
     */
    public function thatISendPyString(PyStringNode $data)
    {
        $this->thatISend($data);
    }

    /**
     * ============ json array ===================
     * @Given /^the response contains (\[[^]]*\])$/
     *
     * ============ json object ==================
     * @Given /^the response contains (\{(?>[^\{\}]+|(?1))*\})$/
     *
     * ============ json string ==================
     * @Given /^the response contains ("[^"]*")$/
     *
     * ============ json int =====================
     * @Given /^the response contains ([-+]?[0-9]*\.?[0-9]+)$/
     *
     * ============ json null or boolean =========
     * @Given /^the response contains (null|true|false)$/
     */
    public function theResponseContains($response)
    {
        $data = json_encode($this->_data);
        if (!String::contains($data, $response))
            throw new Exception("Response value does not contain '$response' only\n\n"
                . $this->echoLastResponse());
    }

    /**
     * ============ json array ===================
     * @Given /^the response equals (\[[^]]*\])$/
     *
     * ============ json object ==================
     * @Given /^the response equals (\{(?>[^\{\}]+|(?1))*\})$/
     *
     * ============ json string ==================
     * @Given /^the response equals ("[^"]*")$/
     *
     * ============ json int =====================
     * @Given /^the response equals ([-+]?[0-9]*\.?[0-9]+)$/
     *
     * ============ json null or boolean =========
     * @Given /^the response equals (null|true|false)$/
     */
    public function theResponseEquals($response)
    {
        $data = json_encode($this->_data);
        if ($data !== $response)
            throw new Exception("Response value does not match '$response'\n\n"
                . $this->echoLastResponse());
    }

    /**
     * @Given /^the response equals:/
     * @param PyStringNode $data
     */
    public function theResponseEqualsPyString(PyStringNode $response)
    {
        $this->theResponseEquals($response);
    }

    /**
     * @Given /^that I want to make a new "([^"]*)"$/
     */
    public function thatIWantToMakeANew($objectType)
    {
        $this->_restObjectType = ucwords(strtolower($objectType));
        $this->_restObjectMethod = 'post';
    }

    /**
     * @Given /^that I want to update "([^"]*)"$/
     * @Given /^that I want to update an "([^"]*)"$/
     * @Given /^that I want to update a "([^"]*)"$/
     */
    public function thatIWantToUpdate($objectType)
    {
        $this->_restObjectType = ucwords(strtolower($objectType));
        $this->_restObjectMethod = 'put';
    }


    /**
     * @Given /^that I want to find a "([^"]*)"$/
     */
    public function thatIWantToFindA($objectType)
    {
        $this->_restObjectType = ucwords(strtolower($objectType));
        $this->_restObjectMethod = 'get';
    }

    /**
     * @Given /^that I want to delete a "([^"]*)"$/
     * @Given /^that I want to delete an "([^"]*)"$/
     * @Given /^that I want to delete "([^"]*)"$/
     */
    public function thatIWantToDeleteA($objectType)
    {
        $this->_restObjectType = ucwords(strtolower($objectType));
        $this->_restObjectMethod = 'delete';
    }

    /**
     * @Given /^that "([^"]*)" header is set to "([^"]*)"$/
     * @Given /^that "([^"]*)" header is set to (\d+)$/
     */
    public function thatHeaderIsSetTo($header, $value)
    {
        $this->_headers[$header] = $value;
    }


    /**
     * @Given /^that its "([^"]*)" is "([^"]*)"$/
     * @Given /^that his "([^"]*)" is "([^"]*)"$/
     * @Given /^that her "([^"]*)" is "([^"]*)"$/
     * @Given /^its "([^"]*)" is "([^"]*)"$/
     * @Given /^his "([^"]*)" is "([^"]*)"$/
     * @Given /^her "([^"]*)" is "([^"]*)"$/
     * @Given /^that "([^"]*)" is set to "([^"]*)"$/
     * @Given /^"([^"]*)" is set to "([^"]*)"$/
     */
    public function thatItsStringPropertyIs($propertyName, $propertyValue)
    {
        $this->_restObject->$propertyName = $propertyValue;
    }

    /**
     * @Given /^that its "([^"]*)" is (\d+)$/
     * @Given /^that his "([^"]*)" is (\d+)$/
     * @Given /^that her "([^"]*)" is (\d+)$/
     * @Given /^its "([^"]*)" is (\d+)$/
     * @Given /^his "([^"]*)" is (\d+)$/
     * @Given /^her "([^"]*)" is (\d+)$/
     * @Given /^that "([^"]*)" is set to (\d+)$/
     * @Given /^"([^"]*)" is set to (\d+)$/
     */
    public function thatItsNumericPropertyIs($propertyName, $propertyValue)
    {
        $this->_restObject->$propertyName = is_float($propertyValue)
            ? floatval($propertyValue)
            : intval($propertyValue);
    }

    /**
     * @Given /^that its "([^"]*)" is (true|false)$/
     * @Given /^that his "([^"]*)" is (true|false)$/
     * @Given /^that her "([^"]*)" is (true|false)$/
     * @Given /^its "([^"]*)" is (true|false)$/
     * @Given /^his "([^"]*)" is (true|false)$/
     * @Given /^her "([^"]*)" is (true|false)$/
     * @Given /^that "([^"]*)" is set to (true|false)$/
     * @Given /^"([^"]*)" is set to (true|false)$/
     */
    public function thatItsBooleanPropertyIs($propertyName, $propertyValue)
    {
        $this->_restObject->$propertyName = $propertyValue == 'true';
    }

    /**
     * @Given /^the request is sent as JSON$/
     * @Given /^the request is sent as Json$/
     */
    public function theRequestIsSentAsJson()
    {
        $this->_headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->_requestBody = json_encode(
            is_object($this->_restObject)
                ? (array)$this->_restObject
                : $this->_restObject
        );
    }

    /**
     * @When /^I request "([^"]*)"$/
     */
    public function iRequest($pageUrl)
    {
        $this->_startTime = microtime(true);
        $baseUrl = $this->getParameter('base_url');
        $this->_requestUrl = $baseUrl . $pageUrl;
        $url = false !== strpos($pageUrl, '{')
            ? array($this->_requestUrl, (array)$this->_restObject)
            : $this->_requestUrl;

        switch (strtoupper($this->_restObjectMethod)) {
            case 'HEAD':

                $this->_request = $this->_client
                    ->head($url, $this->_headers);
                $this->_response = $this->_request->send();
                break;
            case 'GET':
                $this->_request = $this->_client
                    ->get($url, $this->_headers);
                $this->_response = $this->_request->send();
                break;
            case 'POST':
                $postFields = is_object($this->_restObject)
                    ? (array)$this->_restObject
                    : $this->_restObject;
                $this->_request = $this->_client
                    ->post($url, $this->_headers,
                        (empty($this->_requestBody) ? $postFields :
                            $this->_requestBody));
                $this->_response = $this->_request->send();
                break;
            case 'PUT' :
                $putFields = is_object($this->_restObject)
                    ? (array)$this->_restObject
                    : $this->_restObject;
                $this->_request = $this->_client
                    ->put($url, $this->_headers,
                        (empty($this->_requestBody) ? $putFields :
                            $this->_requestBody));
                $this->_response = $this->_request->send();
                break;
            case 'PATCH' :
                $putFields = is_object($this->_restObject)
                    ? (array)$this->_restObject
                    : $this->_restObject;
                $this->_request = $this->_client
                    ->patch($url, $this->_headers,
                        (empty($this->_requestBody) ? $putFields :
                            $this->_requestBody));
                $this->_response = $this->_request->send();
                break;
            case 'DELETE':
                $this->_request = $this->_client
                    ->delete($url, $this->_headers);
                $this->_response = $this->_request->send();
                break;
        }
        //detect type, extract data
        $this->_language = $this->_response->getHeader('Content-Language');

        $cType = explode('; ', $this->_response->getHeader('Content-type'));
        if (count($cType) > 1) {
            $charset = $cType[1];
            $this->_charset = substr($charset, strpos($charset, '=') + 1);
        }
        $cType = $cType[0];
        switch ($cType) {
            case 'application/json':
                $this->_type = 'json';
                $this->_data = json_decode($this->_response->getBody(true));
                switch (json_last_error()) {
                    case JSON_ERROR_NONE :
                        return;
                    case JSON_ERROR_DEPTH :
                        $message = 'maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH :
                        $message = 'underflow or the modes mismatch';
                        break;
                    case JSON_ERROR_CTRL_CHAR :
                        $message = 'unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX :
                        $message = 'malformed JSON';
                        break;
                    case JSON_ERROR_UTF8 :
                        $message = 'malformed UTF-8 characters, possibly ' .
                            'incorrectly encoded';
                        break;
                    default :
                        $message = 'unknown error';
                        break;
                }
                throw new Exception ('Error parsing JSON, ' . $message
                    . "\n\n" . $this->echoLastResponse());
                break;
            case 'application/xml':
                $this->_type = 'xml';
                libxml_use_internal_errors(true);
                $this->_data = @simplexml_load_string(
                    $this->_response->getBody(true));
                if (!$this->_data) {
                    $message = '';
                    foreach (libxml_get_errors() as $error) {
                        $message .= $error->message . PHP_EOL;
                    }
                    throw new Exception ('Error parsing XML, ' . $message);
                }
                break;
        }
    }

    /**
     * @Then /^the response is JSON$/
     * @Then /^the response should be JSON$/
     */
    public function theResponseIsJson()
    {
        if ($this->_type != 'json') {
            throw new Exception("Response was not JSON\n\n" . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^the response is XML$/
     * @Then /^the response should be XML$/
     */
    public function theResponseIsXml()
    {
        if ($this->_type != 'xml') {
            throw new Exception("Response was not XML\n\n" . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^the response charset is "([^"]*)"$/
     */
    public function theResponseCharsetIs($charset)
    {
        if ($this->_charset != $charset) {
            throw new Exception("Response charset was not $charset\n\n" . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^the response language is "([^"]*)"$/
     */
    public function theResponseLanguageIs($language)
    {
        if ($this->_language != $language) {
            throw new Exception("Response Language was not $language\n\n"
                . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^the response "([^"]*)" header should be "([^"]*)"$/
     */
    public function theResponseHeaderShouldBe($header, $value)
    {
        if (!$this->_response->hasHeader($header)) {
            throw new Exception("Response header $header was not found\n\n"
                . $this->echoLastResponse());
        }
        if ((string)$this->_response->getHeader($header) !== $value) {
            throw new Exception("Response header $header ("
                . (string)$this->_response->getHeader($header)
                . ") does not match `$value`\n\n"
                . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^the response "Expires" header should be Date\+(\d+) seconds$/
     */
    public function theResponseExpiresHeaderShouldBeDatePlusGivenSeconds($seconds)
    {
        $server_time = strtotime($this->_response->getHeader('Date')) + $seconds;
        $expires_time = strtotime($this->_response->getHeader('Expires'));
        if ($expires_time === $server_time || $expires_time === $server_time + 1)
            return;
        return $this->theResponseHeaderShouldBe(
            'Expires',
            gmdate('D, d M Y H:i:s \G\M\T', $server_time)
        );
    }

    /**
     * @Then /^the response time should at least be (\d+) milliseconds$/
     */
    public function theResponseTimeShouldAtLeastBeMilliseconds($milliSeconds)
    {
        usleep(1);
        $diff = 1000 * (microtime(true) - $this->_startTime);
        if ($diff < $milliSeconds) {
            throw new Exception("Response time $diff is "
                . "quicker than $milliSeconds\n\n"
                . $this->echoLastResponse());
        }
    }


    /**
     * @Given /^the type is "([^"]*)"$/
     */
    public function theTypeIs($type)
    {
        $data = $this->_data;

        switch ($type) {
            case 'string':
                if (is_string($data)) return;
            case 'int':
                if (is_int($data)) return;
            case 'float':
                if (is_float($data)) return;
            case 'array' :
                if (is_array($data)) return;
            case 'object' :
                if (is_object($data)) return;
            case 'null' :
                if (is_null($data)) return;
			case 'bool' :
				if (is_bool($data)) return;
        }

        throw new Exception("Response is not of type '$type'\n\n" .
            $this->echoLastResponse());
    }

    /**
     * @Given /^the value equals "([^"]*)"$/
     */
    public function theValueEquals($sample)
    {
        $data = $this->_data;
        if ($data !== $sample)
            throw new Exception("Response value does not match '$sample'\n\n"
                . $this->echoLastResponse());
    }

    /**
     * @Given /^the value equals (\d+)$/
     */
    public function theNumericValueEquals($sample)
    {
        $sample = is_float($sample) ? floatval($sample) : intval($sample);
        return $this->theValueEquals($sample);
    }

    /**
     * @Given /^the value equals (true|false)$/
     */
    public function theBooleanValueEquals($sample)
    {
        $sample = $sample == 'true';
        return $this->theValueEquals($sample);
    }

    /**
     * @Then /^the response is JSON "([^"]*)"$/
     */
    public function theResponseIsJsonWithType($type)
    {
        if ($this->_type != 'json') {
            throw new Exception("Response was not JSON\n\n" . $this->echoLastResponse());
        }

        $data = $this->_data;

        switch ($type) {
            case 'string':
                if (is_string($data)) return;
            case 'int':
                if (is_int($data)) return;
            case 'float':
                if (is_float($data)) return;
            case 'array' :
                if (is_array($data)) return;
            case 'object' :
                if (is_object($data)) return;
            case 'null' :
                if (is_null($data)) return;
        }

        throw new Exception("Response was JSON\n but not of type '$type'\n\n" .
            $this->echoLastResponse());
    }


    /**
     * @Given /^the response has a "([^"]*)" property$/
     * @Given /^the response has an "([^"]*)" property$/
     * @Given /^the response has a property called "([^"]*)"$/
     * @Given /^the response has an property called "([^"]*)"$/
     */
    public function theResponseHasAProperty($propertyName)
    {
        $data = $this->_data;

        if (!empty($data)) {
            if (!isset($data->$propertyName)) {
                throw new Exception("Property '"
                    . $propertyName . "' is not set!\n\n"
                    . $this->echoLastResponse());
            }
        }
    }

    /**
     * @Then /^the "([^"]*)" property equals "([^"]*)"$/
     */
    public function thePropertyEquals($propertyName, $propertyValue)
    {
        $data = $this->_data;

        if (!empty($data)) {
            if (!isset($data->$propertyName)) {
                throw new Exception("Property '"
                    . $propertyName . "' is not set!\n\n"
                    . $this->echoLastResponse());
            }
            if ($data->$propertyName != $propertyValue) {
                throw new \Exception('Property value mismatch! (given: '
                    . $propertyValue . ', match: '
                    . $data->$propertyName . ")\n\n"
                    . $this->echoLastResponse());
            }
        } else {
            throw new Exception("Response was not JSON\n\n"
                . $this->_response->getBody(true));
        }
    }

    /**
     * @Then /^the "([^"]*)" property equals (\d+)$/
     */
    public function thePropertyEqualsNumber($propertyName, $propertyValue)
    {
        $propertyValue = is_float($propertyValue)
            ? floatval($propertyValue) : intval($propertyValue);
        return $this->thePropertyEquals($propertyName, $propertyValue);
    }

    /**
     * @Then /^the "([^"]*)" property equals (true|false)$/
     */
    public function thePropertyEqualsBoolean($propertyName, $propertyValue)
    {
        return $this->thePropertyEquals($propertyName, $propertyValue == 'true');
    }

    /**
     * @Given /^the type of the "([^"]*)" property is ([^"]*)$/
     */
    public function theTypeOfThePropertyIs($propertyName, $typeString)
    {
        $data = $this->_data;

        if (!empty($data)) {
            if (!isset($data->$propertyName)) {
                throw new Exception("Property '"
                    . $propertyName . "' is not set!\n\n"
                    . $this->echoLastResponse());
            }
            // check our type
            switch (strtolower($typeString)) {
                case 'numeric':
                    if (!is_numeric($data->$propertyName)) {
                        throw new Exception("Property '"
                            . $propertyName . "' is not of the correct type: "
                            . $typeString . "!\n\n"
                            . $this->echoLastResponse());
                    }
                    break;
            }

        } else {
            throw new Exception("Response was not JSON\n"
                . $this->_response->getBody(true));
        }
    }

    /**
     * @Then /^the response status code should be (\d+)$/
     */
    public function theResponseStatusCodeShouldBe($httpStatus)
    {
        if ((string)$this->_response->getStatusCode() !== $httpStatus) {
            throw new \Exception('HTTP code does not match ' . $httpStatus .
                ' (actual: ' . $this->_response->getStatusCode() . ")\n\n"
                . $this->echoLastResponse());
        }
    }

    /**
     * @Then /^echo last response$/
     */
    public function echoLastResponse()
    {
        $this->printDebug("$this->_request\n$this->_response");
    }
    
    //Definitions for features required by the Routelandia app below...
    
    /**
     * @Then /^the size of the array is (\d+)$/
     */
    public function theSizeOfTheArrayIs($arg1)
    {
    	$data = $this->_data;
    	$count = count($data);
    	if ($count != $arg1) {
        	throw new Exception("The array does not contain the correct number of items. Expected $arg1 but got $count.");
        }
    }
    
    /**
     * @Given /^I have the payload:$/
     */
    public function iHaveThePayload(PyStringNode $requestPayload)
    {
        $this->requestPayload = $requestPayload;
    }

    /**
     * @Then /^the "([^"]*)" property equals (\d+\.\d+)$/
     */
    public function thePropertyEquals1($arg1, $arg2)
    {
        $data = $this->_data;
        if ($data->$arg1 != $arg2) {
        	throw new Exception("The $arg1 property does not equal $arg2");
        }
    }

    /**
     * @Then /^the "([^"]*)" property equals \'([^\']*)\'$/
     */
    public function thePropertyEquals2($arg1, $arg2)
    {
        $data = $this->_data;
        if ($data->$arg1 != $arg2) {
        	throw new Exception("The $arg1 property does not equal $arg2");
        }
    }
    
    /**
     * @Then /^the "([^"]*)" and "([^"]*)" property equals \'([^\']*)\'$/
     */
    public function theAndPropertyEquals($arg1, $arg2, $arg3)
    {
        $data = $this->_data;
        if ($data->$arg1->$arg2 != $arg3) {
        	throw new Exception("The $arg1 and $arg2 property does not equal $arg3");
        }
    }
    
    /**
     * @Then /^the "([^"]*)" property is an object$/
     */
    public function thePropertyIsAnObject($arg1)
    {
        $data = $this->_data;
        if (!is_object($data->$arg1)) {
        	throw new Exception("The $arg1 property is not an object");
        }
    }

    /**
     * @Then /^the "([^"]*)" property equals null$/
     */
    public function thePropertyEqualsNull($arg1)
    {
        $data = $this->_data;
        if ($data->$arg1 != null) {
        	throw new Exception("This is not the null we were looking for");
        }
    }
    
	public function stationChecker($data)
	{
        if (!empty($data)) {
			$tempStation = new stdClass;
			$tempStation->stationid = $data->stationid;
			$tempStation->upstream = $data->upstream;
			$tempStation->downstream = $data->downstream;
			$tempStation->highwayid = $data->highwayid;
			$tempStation->opposite_stationid = $data->opposite_stationid;
			$tempStation->milepost = $data->milepost;
			$tempStation->length = $data->length;
			$tempStation->locationtext = $data->locationtext;
			$tempStation->linked_list_position = $data->linked_list_position;
			$tempStation->geojson_raw = $data->geojson_raw;
			//foreach (get_object_vars($tempStation) as $val)
			//	print ($val);
			//print (get_object_vars($data));
			if (get_object_vars($tempStation) != get_object_vars($data)) {
				throw new Exception("The station object does not have the correct fields");
			}
        } else {
        	throw new Exception("The data for the station is empty.");
        }
        return true;
    }
    
    public function highwayChecker($data)
    {
        if (!empty($data)) {
			$tempHighway = new stdClass;
			$tempHighway->highwayid = $data->highwayid;
			$tempHighway->direction = $data->direction;
			$tempHighway->highwayname = $data->highwayname;
			$tempHighway->bound = $data->bound;
			if (get_object_vars($tempHighway) != get_object_vars($data)) {
				throw new Exception("The highway object does not have the correct fields");
			}
        } else {
        	throw new Exception("The data for the highway is empty.");
        }
        return true;
    }
    
	public function detectorChecker($data)
	{
        if (!empty($data)) {
			$tempDetector = new stdClass;
			$tempDetector->detectorid = $data->detectorid;
			$tempDetector->stationid = $data->stationid;
			$tempDetector->locationtext = $data->locationtext;
			$tempDetector->lanenumber = $data->lanenumber;
			$tempDetector->end_date = $data->end_date;
			$tempDetector->start_date = $data->start_date;
			$tempDetector->highwayid = $data->highwayid;
			if (get_object_vars($tempDetector) != get_object_vars($data)) {
				throw new Exception("The detector object does not have the correct fields");
			}
        } else {
        	throw new Exception("The data for the detector is empty.");
        }
        return true;
	}
    

	public function onrampChecker($data)
	{
        if (!empty($data)) {
			$tempOnramp = new stdClass;
			$tempOnramp->stationid = $data->stationid;
			$tempOnramp->localjurisdiction = $data->localjurisdiction;
			$tempOnramp->controljurisdiction = $data->controljurisdiction;
			$tempOnramp->highwayid = $data->highwayid;
			$tempOnramp->enabledflag = $data->enabledflag;
			$tempOnramp->milepost = $data->milepost;
			$tempOnramp->milelog = $data->milelog;
			$tempOnramp->milenumber = $data->milenumber;
			$tempOnramp->locationtext = $data->locationtext;
			$tempOnramp->length = $data->length;			
			$tempOnramp->upstream = $data->upstream;
			$tempOnramp->downstream = $data->downstream;
			$tempOnramp->stationclass = $data->stationclass;
			$tempOnramp->segment_odot = $data->segment_odot;
			$tempOnramp->numberlanes = $data->numberlanes;
			$tempOnramp->gisname = $data->gisname;
			$tempOnramp->smoothingconstant = $data->smoothingconstant;
			$tempOnramp->bridginglimit = $data->bridginglimit;
			$tempOnramp->length_mid = $data->length_mid;
			$tempOnramp->segment = $data->segment;
			$tempOnramp->downstream_mile = $data->downstream_mile;
			$tempOnramp->upstream_mile = $data->upstream_mile;
			$tempOnramp->agencyid = $data->agencyid;
			$tempOnramp->opposite_stationid = $data->opposite_stationid;
			$tempOnramp->segment_raw = $data->segment_raw;
			$tempOnramp->segment_50k = $data->segment_50k;
			$tempOnramp->segment_100k = $data->segment_100k;
			$tempOnramp->segment_250k = $data->segment_250k;
			$tempOnramp->segment_500k = $data->segment_500k;
			$tempOnramp->segment_1000k = $data->segment_1000k;
			$tempOnramp->point = $data->point;
			if (get_object_vars($tempOnramp) != get_object_vars($data)) {
				throw new Exception("The onramp object does not have the correct fields");
			}
        } else {
        	throw new Exception("The data for the onramp is empty.");
        }
        return true;
	}

    /**
     * @Then /^the station is a station$/
     */
    public function theStationIsAStation()
    {
    	return $this->stationChecker($this->_data);
    }

	    /**
     * @Then /^the highway is a highway$/
     */
    public function theHighwayIsAHighway()
    {
    	return $this->highwayChecker($this->_data);
    }
    
    /**
     * @Then /^the detector is a detector$/
     */
    public function theDetectorIsADetector()
    {
        return $this->detectorChecker($this->_data);
    }

    
    /**
     * @Then /^all of the stations in the array are stations$/
     */
    public function allOfTheStationsInTheArrayAreStations()
    {
        $data = $this->_data;
        foreach($data as $val)
        	if (!$this->stationChecker($val)) {
        		throw new Exception("This station in the array of stations is not a station");
        	}
    }

    /**
     * @Then /^all of the highways in the array are highways$/
     */
    public function allOfTheHighwaysInTheArrayAreHighways()
    {
        $data = $this->_data;
        foreach($data as $val)
        	if (!$this->highwayChecker($val)) {
        		throw new Exception("This highway in the array of highways is not a highway");
        	}
    }


    /**
     * @Then /^all of the detectors in the array are detectors$/
     */
    public function allOfTheDetectorsInTheArrayAreDetectors()
    {
        $data = $this->_data;
        foreach($data as $val)
        	if (!$this->detectorChecker($val)) {
        		throw new Exception("This detector in the array of detectors is not a detector");
        	}
    }

    /**
     * @Then /^the "([^"]*)" property contains an onramp$/
     */
    public function thePropertyContainsAnOnramp($arg1)
    {
    	
        $data = $this->_data;
        return $this->onrampChecker($data->$arg1);
    }


}
