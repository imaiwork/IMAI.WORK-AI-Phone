<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList;
use AlibabaCloud\Tea\Model;

class SearchWaterMarkTemplateResponseBody extends Model
{
    /**
     * @description The width of the watermark image in the output video. The value can be an integer or a decimal.
     *
     *   **Integer**: the width of the watermark image. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the width of the watermark image to the width of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example 1
     *
     * @var int
     */
    public $pageNumber;

    /**
     * @description The values of the Height, Width, Dx, and Dy parameters relative to the reference edges. If the values of the Height, Width, Dx, and Dy parameters are decimals between 0 and 1, the values are calculated by referring to the following edges in sequence:
     *
     *   **Width**: the width edge.
     *   **Height**: the height edge.
     *   **Long**: the long edge.
     *   **Short**: the short edge.
     *
     * @example 10
     *
     * @var int
     */
    public $pageSize;

    /**
     * @description The ID of the request.
     *
     * @example FC029D04-8F47-57FF-A759-23383C15617D
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The type of the watermark. Valid values:
     *
     *   Image: an image watermark.
     *   Text: a text watermark.
     *
     * >  Only watermarks of the **Image** types are supported.
     * @example 1
     *
     * @var int
     */
    public $totalCount;

    /**
     * @description The height of the watermark image in the output video. The value can be an integer or a decimal.
     *
     *   **Integer**: the height of the watermark image. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the height of the watermark image to the height of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @var waterMarkTemplateList
     */
    public $waterMarkTemplateList;
    protected $_name = [
        'pageNumber'            => 'PageNumber',
        'pageSize'              => 'PageSize',
        'requestId'             => 'RequestId',
        'totalCount'            => 'TotalCount',
        'waterMarkTemplateList' => 'WaterMarkTemplateList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->pageNumber) {
            $res['PageNumber'] = $this->pageNumber;
        }
        if (null !== $this->pageSize) {
            $res['PageSize'] = $this->pageSize;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->totalCount) {
            $res['TotalCount'] = $this->totalCount;
        }
        if (null !== $this->waterMarkTemplateList) {
            $res['WaterMarkTemplateList'] = null !== $this->waterMarkTemplateList ? $this->waterMarkTemplateList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SearchWaterMarkTemplateResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['PageNumber'])) {
            $model->pageNumber = $map['PageNumber'];
        }
        if (isset($map['PageSize'])) {
            $model->pageSize = $map['PageSize'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['TotalCount'])) {
            $model->totalCount = $map['TotalCount'];
        }
        if (isset($map['WaterMarkTemplateList'])) {
            $model->waterMarkTemplateList = waterMarkTemplateList::fromMap($map['WaterMarkTemplateList']);
        }

        return $model;
    }
}
