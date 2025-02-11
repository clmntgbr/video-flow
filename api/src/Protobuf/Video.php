<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: Message.proto

namespace App\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>App.Protobuf.Video</code>
 */
class Video extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>string mimeType = 2;</code>
     */
    protected $mimeType = '';
    /**
     * Generated from protobuf field <code>int64 size = 3;</code>
     */
    protected $size = 0;
    /**
     * Generated from protobuf field <code>string subtitle = 6;</code>
     */
    protected $subtitle = '';
    /**
     * Generated from protobuf field <code>repeated string subtitles = 4;</code>
     */
    private $subtitles;
    /**
     * Generated from protobuf field <code>repeated string audios = 5;</code>
     */
    private $audios;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type string $mimeType
     *     @type int|string $size
     *     @type string $subtitle
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $subtitles
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $audios
     * }
     */
    public function __construct($data = NULL) {
        \App\Protobuf\GPBMetadata\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string mimeType = 2;</code>
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Generated from protobuf field <code>string mimeType = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setMimeType($var)
    {
        GPBUtil::checkString($var, True);
        $this->mimeType = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 size = 3;</code>
     * @return int|string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Generated from protobuf field <code>int64 size = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setSize($var)
    {
        GPBUtil::checkInt64($var);
        $this->size = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string subtitle = 6;</code>
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Generated from protobuf field <code>string subtitle = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setSubtitle($var)
    {
        GPBUtil::checkString($var, True);
        $this->subtitle = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated string subtitles = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getSubtitles()
    {
        return $this->subtitles;
    }

    /**
     * Generated from protobuf field <code>repeated string subtitles = 4;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setSubtitles($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->subtitles = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated string audios = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getAudios()
    {
        return $this->audios;
    }

    /**
     * Generated from protobuf field <code>repeated string audios = 5;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setAudios($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->audios = $arr;

        return $this;
    }

}

