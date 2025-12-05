<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults\result;

use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults\result\results\details;
use AlibabaCloud\Tea\Model;

class results extends Model
{
    /**
     * @description The risky content that the moderated text hits. A text entry can hit multiple pieces of risky content.
     *
     * @var details[]
     */
    public $details;

    /**
     * @description The category of the moderation result for the moderated text. Valid values:
     *
     *   normal: normal content
     *   spam: spam
     *   ad: ad
     *   politics: political content
     *   terrorism: terrorist content
     *   abuse: abuse
     *   porn: pornographic content
     *   flood: excessive junk content
     *   contraband: prohibited content
     *   meaningless: meaningless content
     *   customized: custom content, such as a custom keyword
     *
     * @example porn
     *
     * @var string
     */
    public $label;

    /**
     * @description The score of the confidence level. Valid values: 0 to 100. A greater value indicates a higher confidence level. If a value of pass is returned for the suggestion parameter, a higher confidence level indicates a higher probability that the content is normal. If a value of review or block is returned for the suggestion parameter, a higher confidence level indicates a higher probability that the content contains violations.
     *
     * >  This score is for reference only. We strongly recommend that you do not use this score in your business. We recommend that you use the values that are returned for the suggestion, label, and sublabel parameters to determine whether the content contains violations. The sublabel parameter is returned by some operations.
     * @example 99.90
     *
     * @var float
     */
    public $rate;

    /**
     * @description The moderation scenario.
     *
     * @example antispam
     *
     * @var string
     */
    public $scene;

    /**
     * @description The recommended subsequent operation. Valid values:
     *
     *   pass: The content passes the moderation.
     *   review: The content needs to be manually reviewed again.
     *   block: The content contains violations. We recommend that you delete or block the content.
     *
     * @example block
     *
     * @var string
     */
    public $suggestion;
    protected $_name = [
        'details'    => 'details',
        'label'      => 'label',
        'rate'       => 'rate',
        'scene'      => 'scene',
        'suggestion' => 'suggestion',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->details) {
            $res['details'] = [];
            if (null !== $this->details && \is_array($this->details)) {
                $n = 0;
                foreach ($this->details as $item) {
                    $res['details'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->label) {
            $res['label'] = $this->label;
        }
        if (null !== $this->rate) {
            $res['rate'] = $this->rate;
        }
        if (null !== $this->scene) {
            $res['scene'] = $this->scene;
        }
        if (null !== $this->suggestion) {
            $res['suggestion'] = $this->suggestion;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return results
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['details'])) {
            if (!empty($map['details'])) {
                $model->details = [];
                $n              = 0;
                foreach ($map['details'] as $item) {
                    $model->details[$n++] = null !== $item ? details::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['label'])) {
            $model->label = $map['label'];
        }
        if (isset($map['rate'])) {
            $model->rate = $map['rate'];
        }
        if (isset($map['scene'])) {
            $model->scene = $map['scene'];
        }
        if (isset($map['suggestion'])) {
            $model->suggestion = $map['suggestion'];
        }

        return $model;
    }
}
