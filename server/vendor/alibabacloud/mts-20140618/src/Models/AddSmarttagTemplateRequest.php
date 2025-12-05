<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class AddSmarttagTemplateRequest extends Model
{
    /**
     * @description This parameter is required.
     *
     * @example ocr
     *
     * @var string
     */
    public $analyseTypes;

    /**
     * @example celebrity
     *
     * @var string
     */
    public $faceCategoryIds;

    /**
     * @example { "faceDetThreshold":0.999, "faceRegThreshold":0.9 }
     *
     * @var string
     */
    public $faceCustomParamsConfig;

    /**
     * @description This parameter is required.
     *
     * @example common
     *
     * @var string
     */
    public $industry;

    /**
     * @example true
     *
     * @var bool
     */
    public $isDefault;

    /**
     * @example "type": "name,location,organization,other" }
     *
     * @var string
     */
    public $keywordConfig;

    /**
     * @example { "movie":"name,alias,chnl,genre", "music":"songName,artistName", "person":"name,gender" }
     *
     * @var string
     */
    public $knowledgeConfig;

    /**
     * @example hmi
     *
     * @var string
     */
    public $labelType;

    /**
     * @example 1.0
     *
     * @var string
     */
    public $labelVersion;

    /**
     * @example common
     *
     * @var string
     */
    public $landmarkGroupIds;

    /**
     * @example general,item,weapon,animal
     *
     * @var string
     */
    public $objectGroupIds;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $resourceOwnerAccount;

    /**
     * @var int
     */
    public $resourceOwnerId;

    /**
     * @description This parameter is required.
     *
     * @example search
     *
     * @var string
     */
    public $scene;

    /**
     * @description This parameter is required.
     *
     * @example template-example-****
     *
     * @var string
     */
    public $templateName;
    protected $_name = [
        'analyseTypes'           => 'AnalyseTypes',
        'faceCategoryIds'        => 'FaceCategoryIds',
        'faceCustomParamsConfig' => 'FaceCustomParamsConfig',
        'industry'               => 'Industry',
        'isDefault'              => 'IsDefault',
        'keywordConfig'          => 'KeywordConfig',
        'knowledgeConfig'        => 'KnowledgeConfig',
        'labelType'              => 'LabelType',
        'labelVersion'           => 'LabelVersion',
        'landmarkGroupIds'       => 'LandmarkGroupIds',
        'objectGroupIds'         => 'ObjectGroupIds',
        'ownerAccount'           => 'OwnerAccount',
        'ownerId'                => 'OwnerId',
        'resourceOwnerAccount'   => 'ResourceOwnerAccount',
        'resourceOwnerId'        => 'ResourceOwnerId',
        'scene'                  => 'Scene',
        'templateName'           => 'TemplateName',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->analyseTypes) {
            $res['AnalyseTypes'] = $this->analyseTypes;
        }
        if (null !== $this->faceCategoryIds) {
            $res['FaceCategoryIds'] = $this->faceCategoryIds;
        }
        if (null !== $this->faceCustomParamsConfig) {
            $res['FaceCustomParamsConfig'] = $this->faceCustomParamsConfig;
        }
        if (null !== $this->industry) {
            $res['Industry'] = $this->industry;
        }
        if (null !== $this->isDefault) {
            $res['IsDefault'] = $this->isDefault;
        }
        if (null !== $this->keywordConfig) {
            $res['KeywordConfig'] = $this->keywordConfig;
        }
        if (null !== $this->knowledgeConfig) {
            $res['KnowledgeConfig'] = $this->knowledgeConfig;
        }
        if (null !== $this->labelType) {
            $res['LabelType'] = $this->labelType;
        }
        if (null !== $this->labelVersion) {
            $res['LabelVersion'] = $this->labelVersion;
        }
        if (null !== $this->landmarkGroupIds) {
            $res['LandmarkGroupIds'] = $this->landmarkGroupIds;
        }
        if (null !== $this->objectGroupIds) {
            $res['ObjectGroupIds'] = $this->objectGroupIds;
        }
        if (null !== $this->ownerAccount) {
            $res['OwnerAccount'] = $this->ownerAccount;
        }
        if (null !== $this->ownerId) {
            $res['OwnerId'] = $this->ownerId;
        }
        if (null !== $this->resourceOwnerAccount) {
            $res['ResourceOwnerAccount'] = $this->resourceOwnerAccount;
        }
        if (null !== $this->resourceOwnerId) {
            $res['ResourceOwnerId'] = $this->resourceOwnerId;
        }
        if (null !== $this->scene) {
            $res['Scene'] = $this->scene;
        }
        if (null !== $this->templateName) {
            $res['TemplateName'] = $this->templateName;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return AddSmarttagTemplateRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AnalyseTypes'])) {
            $model->analyseTypes = $map['AnalyseTypes'];
        }
        if (isset($map['FaceCategoryIds'])) {
            $model->faceCategoryIds = $map['FaceCategoryIds'];
        }
        if (isset($map['FaceCustomParamsConfig'])) {
            $model->faceCustomParamsConfig = $map['FaceCustomParamsConfig'];
        }
        if (isset($map['Industry'])) {
            $model->industry = $map['Industry'];
        }
        if (isset($map['IsDefault'])) {
            $model->isDefault = $map['IsDefault'];
        }
        if (isset($map['KeywordConfig'])) {
            $model->keywordConfig = $map['KeywordConfig'];
        }
        if (isset($map['KnowledgeConfig'])) {
            $model->knowledgeConfig = $map['KnowledgeConfig'];
        }
        if (isset($map['LabelType'])) {
            $model->labelType = $map['LabelType'];
        }
        if (isset($map['LabelVersion'])) {
            $model->labelVersion = $map['LabelVersion'];
        }
        if (isset($map['LandmarkGroupIds'])) {
            $model->landmarkGroupIds = $map['LandmarkGroupIds'];
        }
        if (isset($map['ObjectGroupIds'])) {
            $model->objectGroupIds = $map['ObjectGroupIds'];
        }
        if (isset($map['OwnerAccount'])) {
            $model->ownerAccount = $map['OwnerAccount'];
        }
        if (isset($map['OwnerId'])) {
            $model->ownerId = $map['OwnerId'];
        }
        if (isset($map['ResourceOwnerAccount'])) {
            $model->resourceOwnerAccount = $map['ResourceOwnerAccount'];
        }
        if (isset($map['ResourceOwnerId'])) {
            $model->resourceOwnerId = $map['ResourceOwnerId'];
        }
        if (isset($map['Scene'])) {
            $model->scene = $map['Scene'];
        }
        if (isset($map['TemplateName'])) {
            $model->templateName = $map['TemplateName'];
        }

        return $model;
    }
}
