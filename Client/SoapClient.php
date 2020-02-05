<?php

namespace Bigfoot\Bundle\ImportBundle\Client;

use Bigfoot\Bundle\ImportBundle\Exception\EmptyResponseException;

/**
 * Class SoapClient
 *
 * @package Bigfoot\Bundle\ImportBundle\Client
 */
class SoapClient extends \SoapClient
{
    /**
     * @var string
     */
    protected $requestHeaders;

    /**
     * @var string
     */
    protected $responseHeaders;

    /**
     * @var string
     */
    protected $responseBody;

    /**
     * @var boolean
     */
    protected $requestInnerXml = false;

    /**
     * @var array
     */
    protected $namespaces = array();

    /**
     * Get envelope namespace
     * Return namespace defined for envelope if found
     *
     * @return mixed
     */
    protected function getEnvelopeNamespace()
    {
        foreach ($this->namespaces as $key => $namespace) {
            if (is_array($namespace) && isset($namespace['envelope']) && $namespace['envelope']) {
                unset($this->namespaces[$key]);

                return $namespace;
            }
        }

        return false;
    }

    /**
     * Create soap enveloppe
     *
     * @return \DOMDocument
     */
    protected function createSoapEnvelope()
    {
        $namespace = $this->getEnvelopeNamespace();

        if ($namespace) {
            $uri    = $namespace['uri'];
            $prefix = $namespace['prefix'];
        } else {
            $uri    = 'http://schemas.xmlsoap.org/soap/envelope/';
            $prefix = 'soap';
        }

        $soap = new \DOMDocument('1.0', 'UTF-8');
        $root = $soap->createElementNS($uri, $prefix.':Envelope');
        $root->appendChild($soap->createElement($prefix.':Header'));
        $root->appendChild($soap->createElement($prefix.':Body'));
        $soap->appendChild($root);

        $this->removeNamespace($prefix.':Envelope');

        foreach ($this->namespaces as $name => $value) {
            $soap->createAttributeNS($value, $name);
        }

        return $soap;
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param bool $one_way
     * @return string
     */
    public function __doRequest($request, $location, $action, $version = 1, $one_way = false)
    {
        $soap = $this->createSoapEnvelope();

        if ($this->requestHeaders) {
            $headerDom = new \DOMDocument('1.0', 'UTF-8');
            $headerDom->loadXML($this->requestHeaders);
            $headerNode = $soap->importNode($headerDom->documentElement, true);
            $soap->firstChild->firstChild->appendChild($headerNode);
        }

        $requestDom = new \DOMDocument('1.0', 'UTF-8');
        $requestDom->loadXML($request);

        if ($this->requestInnerXml) {
            foreach ($requestDom->firstChild->childNodes as $node) {
                $requestNode = $soap->importNode($node, true);
                $soap->firstChild->firstChild->nextSibling->appendChild($requestNode);
            }
        } else {
            $requestNode = $soap->importNode($requestDom->documentElement, true);
            $soap->firstChild->firstChild->nextSibling->appendChild($requestNode);
        }

        $request = $soap->saveXML($soap->documentElement);

        $responseBody = '';
        $responseHeader = '';

        $response = parent::__doRequest($request, $location, $action, $version);

        if (empty($response)) {
            throw new EmptyResponseException();
        }

        $soap = new \DOMDocument('1.0', 'UTF-8');
        $soap->loadXML($response);

        $xpath = new \DOMXPath($soap);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        foreach ($xpath->query('/soap:Envelope/soap:Body/*') as $responseNode) {
            $responseBody .= $soap->saveXML($responseNode);
        }

        foreach ($xpath->query('/soap:Envelope/soap:Header/*') as $responseNode) {
            $responseHeader .= $soap->saveXML($responseNode);
        }

        $this->responseHeader = $responseHeader;
        $this->responseBody = $responseBody;

        return $responseBody;
    }

    /**
     * @param $requestHeaders
     * @return $this
     */
    public function setRequestHeaders($requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * @param $responseHeaders
     * @return $this
     */
    public function setResponseHeaders($responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param $responseBody
     * @return $this
     */
    public function setResponseBody($responseBody)
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * @param boolean $requestInnerXml
     * @return $this
     */
    public function setRequestInnerXml($requestInnerXml)
    {
        $this->requestInnerXml = $requestInnerXml;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRequestInnerXml()
    {
        return $this->requestInnerXml;
    }

    /**
     * Gets the value of namespaces.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Sets the value of namespaces.
     *
     * @param array $namespaces the namespaces
     * @return self
     */
    public function setNamespaces($namespaces)
    {
        $this->namespaces = $namespaces;

        return $this;
    }

    /**
     * Get namespace
     *
     * @param  string $name
     * @return mixed
     */
    public function getNamespace($name)
    {
        if (isset($this->namespaces[$name])) {
            return $this->namespaces[$name];
        }

        return false;
    }

    /**
     * Add namesapce
     *
     * @param string $name
     * @param string $value
     */
    public function addNamespace($name, $value)
    {
        $this->namespaces[$name] = $value;

        return $this;
    }

    /**
     * Remove namespace
     *
     * @param  string $name
     * @return boolean
     */
    public function removeNamespace($name)
    {
        if ($this->getNamespace($name)) {
            unset($this->namespaces[$name]);

            return true;
        }

        return false;
    }
}
