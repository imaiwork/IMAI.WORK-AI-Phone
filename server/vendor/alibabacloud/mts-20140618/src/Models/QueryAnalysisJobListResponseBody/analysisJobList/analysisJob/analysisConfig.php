<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\analysisConfig\propertiesControl;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\analysisConfig\qualityControl;
use AlibabaCloud\Tea\Model;

class analysisConfig extends Model
{
    /**
     * @description The control on the attributes of the job output.
     *
     * @var propertiesControl
     */
    public $propertiesControl;

    /**
     * @description The quality control on the job output.
     *
     * @var qualityControl
     */
    public $qualityControl;
    protected $_name = [
        'propertiesControl' => 'PropertiesControl',
        'qualityControl'    => 'QualityControl',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->propertiesControl) {
            $res['PropertiesControl'] = null !== $this->propertiesControl ? $this->propertiesControl->toMap() : null;
        }
        if (null !== $this->qualityControl) {
            $res['QualityControl'] = null !== $this->qualityControl ? $this->qualityControl->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return analysisConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['PropertiesControl'])) {
            $model->propertiesControl = propertiesControl::fromMap($map['PropertiesControl']);
        }
        if (isset($map['QualityControl'])) {
            $model->qualityControl = qualityControl::fromMap($map['QualityControl']);
        }

        return $model;
    }
}
