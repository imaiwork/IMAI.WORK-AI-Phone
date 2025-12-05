<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomGroupsResponseBody\customGroups;

use AlibabaCloud\Tea\Model;

class customGroup extends Model
{
    /**
     * @var string
     */
    public $customGroupDescription;

    /**
     * @example 1
     *
     * @var string
     */
    public $customGroupId;

    /**
     * @var string
     */
    public $customGroupName;
    protected $_name = [
        'customGroupDescription' => 'CustomGroupDescription',
        'customGroupId'          => 'CustomGroupId',
        'customGroupName'        => 'CustomGroupName',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->customGroupDescription) {
            $res['CustomGroupDescription'] = $this->customGroupDescription;
        }
        if (null !== $this->customGroupId) {
            $res['CustomGroupId'] = $this->customGroupId;
        }
        if (null !== $this->customGroupName) {
            $res['CustomGroupName'] = $this->customGroupName;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return customGroup
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomGroupDescription'])) {
            $model->customGroupDescription = $map['CustomGroupDescription'];
        }
        if (isset($map['CustomGroupId'])) {
            $model->customGroupId = $map['CustomGroupId'];
        }
        if (isset($map['CustomGroupName'])) {
            $model->customGroupName = $map['CustomGroupName'];
        }

        return $model;
    }
}
