<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: api.proto

namespace App\Services\Extism\Proto;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Determine how to sort results from the API
 *
 * Generated from protobuf message <code>Sort</code>
 */
class Sort extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.Direction direction = 1;</code>
     */
    protected $direction = 0;
    /**
     * Generated from protobuf field <code>.Field field = 2;</code>
     */
    protected $field = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $direction
     *     @type int $field
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Api::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.Direction direction = 1;</code>
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Generated from protobuf field <code>.Direction direction = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setDirection($var)
    {
        GPBUtil::checkEnum($var, \App\Services\Extism\Proto\Direction::class);
        $this->direction = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Field field = 2;</code>
     * @return int
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Generated from protobuf field <code>.Field field = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setField($var)
    {
        GPBUtil::checkEnum($var, \App\Services\Extism\Proto\Field::class);
        $this->field = $var;

        return $this;
    }

}

