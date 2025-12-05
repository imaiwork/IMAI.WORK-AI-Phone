<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result;

use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\frames;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\hintWordsInfo;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\logoData;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\programCodeData;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\qrcodeLocations;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\sfaceData;
use AlibabaCloud\Tea\Model;

class results extends Model
{
    /**
     * @description The category of the moderation results. Valid values vary based on the specified moderation scenario.
     *
     *   If the Scenes parameter is set to porn, the valid values are:
     *
     *   normal: no pornographic content
     *   sexy: sexy content
     *   porn: pornographic content
     *
     *   If the Scenes parameter is set to terrorism, the valid values are:
     *
     *   normal: no pornographic content
     *   bloody: bloody content
     *   explosion: explosions and smoke
     *   outfit: special costume
     *   logo: special logo
     *   weapon: weapon
     *   politics: political content
     *   violence: violence
     *   crowd: crowd
     *   parade: parade
     *   carcrash: car accident
     *   flag: flag
     *   location: landmark
     *   others: other content
     *
     *   If the Scenes parameter is set to ad, the valid values are:
     *
     *   normal: no pornographic content
     *   ad: ad violation
     *   politics: politically sensitive content in text
     *   porn: pornographic content in text
     *   abuse: abuse in text
     *   terrorism: terrorist content in text
     *   contraband: prohibited content in text
     *   spam: junk content in text
     *   npx: illegal ad
     *   qrcode: QR code
     *   programCode: mini program code
     *
     *   If the Scenes parameter is set to qrcode, the valid values are:
     *
     *   normal: no pornographic content
     *   qrcode: QR code
     *   programCode: mini program code
     *
     *   If the Scenes parameter is set to live, the valid values are:
     *
     *   normal: no pornographic content
     *   meaningless: no content in the image, such as black or white screen
     *   PIP: picture-in-picture
     *   smoking: smoking
     *   drivelive: live broadcasting in a running vehicle
     *
     *   If the Scenes parameter is set to logo, the valid values are:
     *
     *   normal: no pornographic content
     *   TV: controlled logo
     *   trademark: trademark
     *
     * @example sexy
     *
     * @var string
     */
    public $label;

    /**
     * @description The score of the confidence level. Valid values: 0 to 100. A greater value indicates a higher confidence level. If a value of pass is returned for the suggestion parameter, a higher confidence level indicates a higher probability that the content is normal. If a value of review or block is returned for the suggestion parameter, a higher confidence level indicates a higher probability that the content contains violations.
     *
     * >  This score is for reference only. We strongly recommend that you do not use this score in your business. We recommend that you use the values that are returned for the suggestion, label, and sublabel parameters to determine whether the content contains violations. The sublabel parameter is returned by some operations.
     * @example 91.54
     *
     * @var float
     */
    public $rate;

    /**
     * @description The image moderation scenario. Valid values:
     *
     *   porn: pornography
     *   terrorism: terrorist content
     *   ad: ad violation
     *   qrcode: QR code
     *   live: undesirable scene
     *   logo: special logo
     *
     * @example porn
     *
     * @var string
     */
    public $scene;

    /**
     * @description The recommended subsequent operation. Valid values:
     *
     *   pass: The content passes the moderation. No further actions are required.
     *   review: The moderation object contains suspected violations and requires human review.
     *   block: The moderation object contains violations. We recommend that you delete or block the object.
     *
     * @example block
     *
     * @var string
     */
    public $suggestion;

    /**
     * @description If the temporary access URL of the image is too long, a truncated temporary access URL is returned for each frame.
     *
     * @var frames[]
     */
    public $frames;

    /**
     * @description The information about the term hit by the ad or violation text detected in the moderated image.
     *
     * @var hintWordsInfo[]
     */
    public $hintWordsInfo;

    /**
     * @description The information about the logo detected in the moderated image.
     *
     * @var logoData[]
     */
    public $logoData;

    /**
     * @description ocrData
     *
     * @var string[]
     */
    public $ocrData;

    /**
     * @description The location information of the mini program code detected in the moderated image.
     *
     * @var programCodeData[]
     */
    public $programCodeData;

    /**
     * @description The information about the text that is included in the QR code detected in the moderated image.
     *
     * @var string[]
     */
    public $qrcodeData;

    /**
     * @description The coordinates of the QR code detected in the image.
     *
     * @var qrcodeLocations[]
     */
    public $qrcodeLocations;

    /**
     * @description The information about the terrorist content detected in the moderated image.
     *
     * @var sfaceData[]
     */
    public $sfaceData;
    protected $_name = [
        'label'           => 'Label',
        'rate'            => 'Rate',
        'scene'           => 'Scene',
        'suggestion'      => 'Suggestion',
        'frames'          => 'frames',
        'hintWordsInfo'   => 'hintWordsInfo',
        'logoData'        => 'logoData',
        'ocrData'         => 'ocrData',
        'programCodeData' => 'programCodeData',
        'qrcodeData'      => 'qrcodeData',
        'qrcodeLocations' => 'qrcodeLocations',
        'sfaceData'       => 'sfaceData',
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
        if (null !== $this->frames) {
            $res['frames'] = [];
            if (null !== $this->frames && \is_array($this->frames)) {
                $n = 0;
                foreach ($this->frames as $item) {
                    $res['frames'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->hintWordsInfo) {
            $res['hintWordsInfo'] = [];
            if (null !== $this->hintWordsInfo && \is_array($this->hintWordsInfo)) {
                $n = 0;
                foreach ($this->hintWordsInfo as $item) {
                    $res['hintWordsInfo'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->logoData) {
            $res['logoData'] = [];
            if (null !== $this->logoData && \is_array($this->logoData)) {
                $n = 0;
                foreach ($this->logoData as $item) {
                    $res['logoData'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->ocrData) {
            $res['ocrData'] = $this->ocrData;
        }
        if (null !== $this->programCodeData) {
            $res['programCodeData'] = [];
            if (null !== $this->programCodeData && \is_array($this->programCodeData)) {
                $n = 0;
                foreach ($this->programCodeData as $item) {
                    $res['programCodeData'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->qrcodeData) {
            $res['qrcodeData'] = $this->qrcodeData;
        }
        if (null !== $this->qrcodeLocations) {
            $res['qrcodeLocations'] = [];
            if (null !== $this->qrcodeLocations && \is_array($this->qrcodeLocations)) {
                $n = 0;
                foreach ($this->qrcodeLocations as $item) {
                    $res['qrcodeLocations'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->sfaceData) {
            $res['sfaceData'] = [];
            if (null !== $this->sfaceData && \is_array($this->sfaceData)) {
                $n = 0;
                foreach ($this->sfaceData as $item) {
                    $res['sfaceData'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
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
        if (isset($map['frames'])) {
            if (!empty($map['frames'])) {
                $model->frames = [];
                $n             = 0;
                foreach ($map['frames'] as $item) {
                    $model->frames[$n++] = null !== $item ? frames::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['hintWordsInfo'])) {
            if (!empty($map['hintWordsInfo'])) {
                $model->hintWordsInfo = [];
                $n                    = 0;
                foreach ($map['hintWordsInfo'] as $item) {
                    $model->hintWordsInfo[$n++] = null !== $item ? hintWordsInfo::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['logoData'])) {
            if (!empty($map['logoData'])) {
                $model->logoData = [];
                $n               = 0;
                foreach ($map['logoData'] as $item) {
                    $model->logoData[$n++] = null !== $item ? logoData::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['ocrData'])) {
            if (!empty($map['ocrData'])) {
                $model->ocrData = $map['ocrData'];
            }
        }
        if (isset($map['programCodeData'])) {
            if (!empty($map['programCodeData'])) {
                $model->programCodeData = [];
                $n                      = 0;
                foreach ($map['programCodeData'] as $item) {
                    $model->programCodeData[$n++] = null !== $item ? programCodeData::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['qrcodeData'])) {
            if (!empty($map['qrcodeData'])) {
                $model->qrcodeData = $map['qrcodeData'];
            }
        }
        if (isset($map['qrcodeLocations'])) {
            if (!empty($map['qrcodeLocations'])) {
                $model->qrcodeLocations = [];
                $n                      = 0;
                foreach ($map['qrcodeLocations'] as $item) {
                    $model->qrcodeLocations[$n++] = null !== $item ? qrcodeLocations::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['sfaceData'])) {
            if (!empty($map['sfaceData'])) {
                $model->sfaceData = [];
                $n                = 0;
                foreach ($map['sfaceData'] as $item) {
                    $model->sfaceData[$n++] = null !== $item ? sfaceData::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
