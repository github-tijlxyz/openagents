<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: api.proto

namespace App\Services\Extism\Proto;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>UninstallPluginResponse</code>
 */
class UninstallPluginResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>optional .Error error = 1;</code>
     */
    protected $error = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \App\Services\Extism\Proto\Error $error
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Api::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>optional .Error error = 1;</code>
     * @return \App\Services\Extism\Proto\Error|null
     */
    public function getError()
    {
        return $this->error;
    }

    public function hasError()
    {
        return isset($this->error);
    }

    public function clearError()
    {
        unset($this->error);
    }

    /**
     * Generated from protobuf field <code>optional .Error error = 1;</code>
     * @param \App\Services\Extism\Proto\Error $var
     * @return $this
     */
    public function setError($var)
    {
        GPBUtil::checkMessage($var, \App\Services\Extism\Proto\Error::class);
        $this->error = $var;

        return $this;
    }

}

