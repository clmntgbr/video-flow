<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: Message.proto

namespace App\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>App.Protobuf.MediaPod</code>
 */
class MediaPod extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string uuid = 1;</code>
     */
    protected $uuid = '';
    /**
     * Generated from protobuf field <code>string userUuid = 2;</code>
     */
    protected $userUuid = '';
    /**
     * Generated from protobuf field <code>.App.Protobuf.Video originalVideo = 3;</code>
     */
    protected $originalVideo = null;
    /**
     * Generated from protobuf field <code>.App.Protobuf.Video video = 4;</code>
     */
    protected $video = null;
    /**
     * Generated from protobuf field <code>string status = 5;</code>
     */
    protected $status = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $uuid
     *     @type string $userUuid
     *     @type \App\Protobuf\Video $originalVideo
     *     @type \App\Protobuf\Video $video
     *     @type string $status
     * }
     */
    public function __construct($data = NULL) {
        \App\Protobuf\GPBMetadata\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string uuid = 1;</code>
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Generated from protobuf field <code>string uuid = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setUuid($var)
    {
        GPBUtil::checkString($var, True);
        $this->uuid = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string userUuid = 2;</code>
     * @return string
     */
    public function getUserUuid()
    {
        return $this->userUuid;
    }

    /**
     * Generated from protobuf field <code>string userUuid = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setUserUuid($var)
    {
        GPBUtil::checkString($var, True);
        $this->userUuid = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.App.Protobuf.Video originalVideo = 3;</code>
     * @return \App\Protobuf\Video|null
     */
    public function getOriginalVideo()
    {
        return $this->originalVideo;
    }

    public function hasOriginalVideo()
    {
        return isset($this->originalVideo);
    }

    public function clearOriginalVideo()
    {
        unset($this->originalVideo);
    }

    /**
     * Generated from protobuf field <code>.App.Protobuf.Video originalVideo = 3;</code>
     * @param \App\Protobuf\Video $var
     * @return $this
     */
    public function setOriginalVideo($var)
    {
        GPBUtil::checkMessage($var, \App\Protobuf\Video::class);
        $this->originalVideo = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.App.Protobuf.Video video = 4;</code>
     * @return \App\Protobuf\Video|null
     */
    public function getVideo()
    {
        return $this->video;
    }

    public function hasVideo()
    {
        return isset($this->video);
    }

    public function clearVideo()
    {
        unset($this->video);
    }

    /**
     * Generated from protobuf field <code>.App.Protobuf.Video video = 4;</code>
     * @param \App\Protobuf\Video $var
     * @return $this
     */
    public function setVideo($var)
    {
        GPBUtil::checkMessage($var, \App\Protobuf\Video::class);
        $this->video = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string status = 5;</code>
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Generated from protobuf field <code>string status = 5;</code>
     * @param string $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkString($var, True);
        $this->status = $var;

        return $this;
    }

}

