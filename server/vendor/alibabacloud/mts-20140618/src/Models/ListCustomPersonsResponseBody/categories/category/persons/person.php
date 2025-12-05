<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons\person\faces;
use AlibabaCloud\Tea\Model;

class person extends Model
{
    /**
     * @description The array of the faces.
     *
     * @var faces
     */
    public $faces;

    /**
     * @description The description of the figure.
     *
     * @example PersonDescription-****
     *
     * @var string
     */
    public $personDescription;

    /**
     * @description The ID of the figure.
     *
     * @example PersonId-****
     *
     * @var string
     */
    public $personId;

    /**
     * @description The name of the figure.
     *
     * @example PersonName-****
     *
     * @var string
     */
    public $personName;
    protected $_name = [
        'faces'             => 'Faces',
        'personDescription' => 'PersonDescription',
        'personId'          => 'PersonId',
        'personName'        => 'PersonName',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->faces) {
            $res['Faces'] = null !== $this->faces ? $this->faces->toMap() : null;
        }
        if (null !== $this->personDescription) {
            $res['PersonDescription'] = $this->personDescription;
        }
        if (null !== $this->personId) {
            $res['PersonId'] = $this->personId;
        }
        if (null !== $this->personName) {
            $res['PersonName'] = $this->personName;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return person
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Faces'])) {
            $model->faces = faces::fromMap($map['Faces']);
        }
        if (isset($map['PersonDescription'])) {
            $model->personDescription = $map['PersonDescription'];
        }
        if (isset($map['PersonId'])) {
            $model->personId = $map['PersonId'];
        }
        if (isset($map['PersonName'])) {
            $model->personName = $map['PersonName'];
        }

        return $model;
    }
}
