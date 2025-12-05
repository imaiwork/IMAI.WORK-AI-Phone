<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail;

use AlibabaCloud\Tea\Model;

class barrageCensorResult extends Model
{
    /**
     * @description The labels of the moderation result. Multiple labels are separated with commas (,). Valid values:
     *
     *   **spam**: spam
     *   **ad**: ads
     *   **abuse**: abuse
     *   **flood**: excessive junk content
     *   **contraband**: prohibited content
     *   **meaningless**: meaningless content
     *   **normal**: normal content
     *
     * @example normal
     *
     * @var string
     */
    public $label;

    /**
     * @description The score.
     *
     * @example 99.91
     *
     * @var string
     */
    public $rate;

    /**
     * @description The moderation scenario. Valid values: The value is **antispam**.
     *
     * @example antispam
     *
     * @var string
     */
    public $scene;

    /**
     * @description The recommended subsequent operation. Valid values:
     *
     *   **pass**: The content passes the moderation.
     *   **review**: The content needs to be manually reviewed again.
     *   **block**: The content needs to be blocked.
     *
     * @example pass
     *
     * @var string
     */
    public $suggestion;
    protected $_name = [
        'label'      => 'Label',
        'rate'       => 'Rate',
        'scene'      => 'Scene',
        'suggestion' => 'Suggestion',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->label) {
            $res['Label'] = $this->label;
        }
        if (null !== $this->rate) {
            $res['Rate'] = $this->rate;
        }
        if (null !== $this->scene) {
            $res['Scene'] = $this->scene;
        }
        if (null !== $this->suggestion) {
            $res['Suggestion'] = $this->suggestion;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return barrageCensorResult
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Label'])) {
            $model->label = $map['Label'];
        }
        if (isset($map['Rate'])) {
            $model->rate = $map['Rate'];
        }
        if (isset($map['Scene'])) {
            $model->scene = $map['Scene'];
        }
        if (isset($map['Suggestion'])) {
            $model->suggestion = $map['Suggestion'];
        }

        return $model;
    }
}
