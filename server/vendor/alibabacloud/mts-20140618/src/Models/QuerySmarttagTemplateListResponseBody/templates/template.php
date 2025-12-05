<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListResponseBody\templates;

use AlibabaCloud\Tea\Model;

class template extends Model
{
    /**
     * @description The analysis types that are used in the template. One or more values are returned. Valid values:
     *
     *   **ocr**: text recognition
     *   **asr**: speech recognition
     *   **classification**: video classification
     *   **shows**: program recognition
     *   **face**: facial recognition
     *   **role**: figure recognition
     *   **object**: object recognition
     *   **tvstation**: logo recognition
     *   **action**: action recognition
     *   **emotion**: facial expression recognition
     *   **landmark**: landmark recognition
     *   **scene**: scene recognition
     *   **movieip**: movie intellectual property recognition
     *   **subtitle**: subtitle extraction
     *
     * @example ocr,asr,classification,shows,face,role,object,tvstation,action,emotion,landmark,scene
     *
     * @var string
     */
    public $analyseTypes;

    /**
     * @description The IDs of the system facial image libraries that are used in the template. One or more values are returned. Valid values:
     *
     *   celebrity: the facial image library of celebrities
     *   politician: the facial image library of politicians
     *   sensitive: the facial image library of sensitive figures
     *
     * @example politician,sensitive,celebrity
     *
     * @var string
     */
    public $faceCategoryIds;

    /**
     * @description The configurations of face-related algorithms. The value of this parameter is a JSON string and consists of the thresholds set for face detection and facial recognition. Valid values:
     *
     *   **faceDetThreshold**: The default threshold for face detection is 0.999. The threshold takes effect only for the faces that are strange to the system.
     *   **faceRegThreshold**: The default threshold for facial recognition is 0.9.
     *
     * @example { "faceDetThreshold":0.999, "faceRegThreshold":0.9 }
     *
     * @var string
     */
    public $faceCustomParamsConfig;

    /**
     * @description The industry to which the template applies. Default value: **common**. Valid values:
     *
     *   **microVideo**: short video industry
     *   **common**: general industries
     *
     * @example common
     *
     * @var string
     */
    public $industry;

    /**
     * @description Indicates whether the template is the default template. Valid values:
     *
     *   **true**: The template is the default template.
     *   **false**: The template is not the default template.
     *
     * @example false
     *
     * @var bool
     */
    public $isDefault;

    /**
     * @description The configuration of keyword tags. The type field specifies the category of a keyword tag. You can specify one or more values and separate the values with commas (,). Valid values:
     *
     *   name
     *   location
     *   organization
     *   other
     *
     * > Keyword tags of all the categories are returned in one of the following scenarios: The KeywordConfig parameter is not specified or the Keyword field is invalid because it is not a JSON string, or the KeywordConfig parameter does not contain the type field or the type field is invalid.
     * @example { "type": "name,location,organization,other" }
     *
     * @var string
     */
    public $keywordConfig;

    /**
     * @description The fields to be identified as knowledge graph information when tags are returned in Smart tagging V2.0 and Smart tagging V2.0-custom modes. For more information, see [Knowledge graph fields in smart tagging jobs](https://help.aliyun.com/document_detail/356383.html). If this parameter is not specified or the specified value is NULL or invalid because it is not a JSON string, the following fields are returned:
     *
     *   movie-related fields:
     *
     *   name: the name of the intellectual property that is featured in the movie
     *   alias: the alias of the intellectual property that is featured in the movie
     *   chnl: the category of the movie
     *   genre: the genre of the movie
     *   country: the country or region in which the movie was produced
     *   language: the language of the movie
     *   releaseYear: the year when the movie was released
     *
     *   music-related fields:
     *
     *   songName: the name of the song
     *   artistName: the name of the singer
     *   artistArea: the region to which the singer belongs, such as China, Japan, Korea, Europe, and America, or others.
     *   albumName: the name of the album
     *
     *   person-related fields:
     *
     *   name: the name of the person
     *   gender: the gender of the person
     *   citizenship: the nationality of the person
     *   occupation: the occupation of the person
     *   classification: the type into which the person is classified
     *   nationality: the ethnic group of the person
     *   birthPlace: the place where the person was born
     *   birthDate: the date when the person was born
     *
     *   landmark-related fields:
     *
     *   name: the display name of the landmark
     *   nameEn: the English name of the landmark
     *   Description: the description of the parameter
     *   address: the address of the landmark
     *
     *   item-related fields:
     *
     *   brandName: the brand of the item
     *   finegrainName: the fine-grained description of the item
     *
     * @example { "movie":"name,alias,chnl,genre", "music":"songName,artistName", "person":"name,gender" }
     *
     * @var string
     */
    public $knowledgeConfig;

    /**
     * @description The type of the tagging. Default value: **auto**. Valid values:
     *
     *   **auto**: machine tagging
     *   **hmi**: tagging by human and machine
     *
     * @example hmi
     *
     * @var string
     */
    public $labelType;

    /**
     * @description The version of the smart tagging feature. Default value: 1.0. Valid values:
     *
     *   1.0: Smart tagging V1.0
     *   2.0: Smart tagging V2.0 (CPV tagging)
     *   2.0-custom: Smart tagging V2.0-custom (CPV tagging by using custom models)
     *
     * @example 1.0
     *
     * @var string
     */
    public $labelVersion;

    /**
     * @description The IDs of the landmark libraries that are used in the template.
     *
     * @example common
     *
     * @var string
     */
    public $landmarkGroupIds;

    /**
     * @description The IDs of the object libraries that are used in the template.
     *
     * @example general,item,weapon,animal
     *
     * @var string
     */
    public $objectGroupIds;

    /**
     * @description The scenario in which the template is used. Valid values:
     *
     *   **search**: search scenarios
     *   **recommend**: recommendation scenarios
     *
     * @example search
     *
     * @var string
     */
    public $scene;

    /**
     * @description The ID of the template.
     *
     * @example 05de22f255284c7a8d2aab535dde****
     *
     * @var string
     */
    public $templateId;

    /**
     * @description The name of the template.
     *
     * @example example-****
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
        'scene'                  => 'Scene',
        'templateId'             => 'TemplateId',
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
        if (null !== $this->scene) {
            $res['Scene'] = $this->scene;
        }
        if (null !== $this->templateId) {
            $res['TemplateId'] = $this->templateId;
        }
        if (null !== $this->templateName) {
            $res['TemplateName'] = $this->templateName;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return template
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
        if (isset($map['Scene'])) {
            $model->scene = $map['Scene'];
        }
        if (isset($map['TemplateId'])) {
            $model->templateId = $map['TemplateId'];
        }
        if (isset($map['TemplateName'])) {
            $model->templateName = $map['TemplateName'];
        }

        return $model;
    }
}
