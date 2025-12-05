<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618;

use AlibabaCloud\Endpoint\Endpoint;
use AlibabaCloud\OpenApiUtil\OpenApiUtilClient;
use AlibabaCloud\SDK\Mts\V20140618\Models\ActivateMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ActivateMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaTagRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaTagResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddPipelineRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddPipelineResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddSmarttagTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddSmarttagTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddWaterMarkTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddWaterMarkTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\BindInputBucketRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\BindInputBucketResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\BindOutputBucketRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\BindOutputBucketResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\CancelJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\CancelJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateCustomEntityRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateCustomEntityResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateCustomGroupRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateCustomGroupResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateFpShotDBRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\CreateFpShotDBResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeactivateMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeactivateMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomEntityRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomEntityResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomGroupRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomGroupResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomViewRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteCustomViewResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaTagRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaTagResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeletePipelineRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeletePipelineResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteSmarttagTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteSmarttagTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteWaterMarkTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\DeleteWaterMarkTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImportFpShotJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImportFpShotJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListAllMediaBucketRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListAllMediaBucketResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomEntitiesRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomEntitiesResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomGroupsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomGroupsResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomViewsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomViewsResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotImportJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotImportJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListMediaWorkflowExecutionsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListMediaWorkflowExecutionsResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryCopyrightExtractJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryCopyrightExtractJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryCopyrightJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryCopyrightJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryIProductionJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryIProductionJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryPipelineListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryPipelineListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceAbJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceAbJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceExtractJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceExtractJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceM3u8JobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceM3u8JobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\RegisterCustomFaceRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\RegisterCustomFaceResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\RegisterCustomViewRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\RegisterCustomViewResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitCopyrightExtractJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitCopyrightExtractJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitCopyrightJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitCopyrightJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpDBDeleteJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpDBDeleteJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpFileDeleteJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpFileDeleteJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpShotJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitFpShotJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitIProductionJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitIProductionJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaCensorJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaCensorJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaInfoJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaInfoJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSmarttagJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSmarttagJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceAbJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceAbJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceExtractJobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceExtractJobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceM3u8JobRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitTraceM3u8JobResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\TagCustomPersonRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\TagCustomPersonResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnbindInputBucketRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnbindInputBucketResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnbindOutputBucketRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnbindOutputBucketResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnregisterCustomFaceRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UnregisterCustomFaceResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaCategoryRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaCategoryResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaCoverRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaCoverResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaPublishStateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaPublishStateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaWorkflowRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaWorkflowResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaWorkflowTriggerModeRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaWorkflowTriggerModeResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdatePipelineRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdatePipelineResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateSmarttagTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateSmarttagTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponse;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateWaterMarkTemplateRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateWaterMarkTemplateResponse;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Darabonba\OpenApi\Models\OpenApiRequest;
use Darabonba\OpenApi\Models\Params;
use Darabonba\OpenApi\OpenApiClient;

class Mts extends OpenApiClient
{
    public function __construct($config)
    {
        parent::__construct($config);
        $this->_endpointRule = 'regional';
        $this->_endpointMap  = [
            'ap-northeast-2-pop'          => 'mts.aliyuncs.com',
            'ap-southeast-2'              => 'mts.aliyuncs.com',
            'ap-southeast-3'              => 'mts.aliyuncs.com',
            'cn-beijing-finance-1'        => 'mts.aliyuncs.com',
            'cn-beijing-finance-pop'      => 'mts.aliyuncs.com',
            'cn-beijing-gov-1'            => 'mts.aliyuncs.com',
            'cn-beijing-nu16-b01'         => 'mts.aliyuncs.com',
            'cn-chengdu'                  => 'mts.aliyuncs.com',
            'cn-edge-1'                   => 'mts.aliyuncs.com',
            'cn-fujian'                   => 'mts.aliyuncs.com',
            'cn-haidian-cm12-c01'         => 'mts.aliyuncs.com',
            'cn-hangzhou-bj-b01'          => 'mts.aliyuncs.com',
            'cn-hangzhou-finance'         => 'mts.aliyuncs.com',
            'cn-hangzhou-internal-prod-1' => 'mts.aliyuncs.com',
            'cn-hangzhou-internal-test-1' => 'mts.aliyuncs.com',
            'cn-hangzhou-internal-test-2' => 'mts.aliyuncs.com',
            'cn-hangzhou-internal-test-3' => 'mts.aliyuncs.com',
            'cn-hangzhou-test-306'        => 'mts.aliyuncs.com',
            'cn-hongkong-finance-pop'     => 'mts.aliyuncs.com',
            'cn-huhehaote-nebula-1'       => 'mts.aliyuncs.com',
            'cn-north-2-gov-1'            => 'mts.aliyuncs.com',
            'cn-qingdao-nebula'           => 'mts.aliyuncs.com',
            'cn-shanghai-et15-b01'        => 'mts.aliyuncs.com',
            'cn-shanghai-et2-b01'         => 'mts.aliyuncs.com',
            'cn-shanghai-finance-1'       => 'mts.aliyuncs.com',
            'cn-shanghai-inner'           => 'mts.aliyuncs.com',
            'cn-shanghai-internal-test-1' => 'mts.aliyuncs.com',
            'cn-shenzhen-finance-1'       => 'mts.aliyuncs.com',
            'cn-shenzhen-inner'           => 'mts.aliyuncs.com',
            'cn-shenzhen-st4-d01'         => 'mts.aliyuncs.com',
            'cn-shenzhen-su18-b01'        => 'mts.aliyuncs.com',
            'cn-wuhan'                    => 'mts.aliyuncs.com',
            'cn-wulanchabu'               => 'mts.aliyuncs.com',
            'cn-yushanfang'               => 'mts.aliyuncs.com',
            'cn-zhangbei'                 => 'mts.aliyuncs.com',
            'cn-zhangbei-na61-b01'        => 'mts.aliyuncs.com',
            'cn-zhangjiakou-na62-a01'     => 'mts.aliyuncs.com',
            'cn-zhengzhou-nebula-1'       => 'mts.aliyuncs.com',
            'eu-west-1-oxs'               => 'mts.aliyuncs.com',
            'me-east-1'                   => 'mts.aliyuncs.com',
            'rus-west-1-pop'              => 'mts.aliyuncs.com',
            'us-east-1'                   => 'mts.aliyuncs.com',
        ];
        $this->checkConfig($config);
        $this->_endpoint = $this->getEndpoint('mts', $this->_regionId, $this->_endpointRule, $this->_network, $this->_suffix, $this->_endpointMap, $this->_endpoint);
    }

    /**
     * @param string   $productId
     * @param string   $regionId
     * @param string   $endpointRule
     * @param string   $network
     * @param string   $suffix
     * @param string[] $endpointMap
     * @param string   $endpoint
     *
     * @return string
     */
    public function getEndpoint($productId, $regionId, $endpointRule, $network, $suffix, $endpointMap, $endpoint)
    {
        if (!Utils::empty_($endpoint)) {
            return $endpoint;
        }
        if (!Utils::isUnset($endpointMap) && !Utils::empty_(@$endpointMap[$regionId])) {
            return @$endpointMap[$regionId];
        }

        return Endpoint::getEndpointRules($productId, $regionId, $endpointRule, $network, $suffix);
    }

    /**
     * @summary Activates a media workflow.
     *  *
     * @description You can call this operation to activate a media workflow that has been deactivated. After you activate a media workflow, you cannot modify the workflow information, such as the name, topology, or trigger mode. A media workflow is activated by default after it is created.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ActivateMediaWorkflowRequest $request ActivateMediaWorkflowRequest
     * @param RuntimeOptions               $runtime runtime options for this request RuntimeOptions
     *
     * @return ActivateMediaWorkflowResponse ActivateMediaWorkflowResponse
     */
    public function activateMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ActivateMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ActivateMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Activates a media workflow.
     *  *
     * @description You can call this operation to activate a media workflow that has been deactivated. After you activate a media workflow, you cannot modify the workflow information, such as the name, topology, or trigger mode. A media workflow is activated by default after it is created.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ActivateMediaWorkflowRequest $request ActivateMediaWorkflowRequest
     *
     * @return ActivateMediaWorkflowResponse ActivateMediaWorkflowResponse
     */
    public function activateMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->activateMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Adds a media file.
     *  *
     * @description *   You can call this operation to process videos that are uploaded to Object Storage Service (OSS) but not processed. This way, you do not need to upload the videos to OSS again. If you have configured media workflows, OSS automatically notifies ApsaraVideo Media Processing (MPS) when a media file is uploaded to OSS. MPS automatically finds the corresponding workflow in the Active state based on the specified OSS bucket and object. Therefore, in most cases, you do not need to manually call the AddMedia operation to process the media file.
     * *   Media information is automatically obtained only when the specified media workflow is in the Active state. If no media workflow is specified or the specified media workflow is not in the Active state, media information is not obtained.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaRequest $request AddMediaRequest
     * @param RuntimeOptions  $runtime runtime options for this request RuntimeOptions
     *
     * @return AddMediaResponse AddMediaResponse
     */
    public function addMediaWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->cateId)) {
            $query['CateId'] = $request->cateId;
        }
        if (!Utils::isUnset($request->coverURL)) {
            $query['CoverURL'] = $request->coverURL;
        }
        if (!Utils::isUnset($request->description)) {
            $query['Description'] = $request->description;
        }
        if (!Utils::isUnset($request->fileURL)) {
            $query['FileURL'] = $request->fileURL;
        }
        if (!Utils::isUnset($request->inputUnbind)) {
            $query['InputUnbind'] = $request->inputUnbind;
        }
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->mediaWorkflowUserData)) {
            $query['MediaWorkflowUserData'] = $request->mediaWorkflowUserData;
        }
        if (!Utils::isUnset($request->overrideParams)) {
            $query['OverrideParams'] = $request->overrideParams;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->tags)) {
            $query['Tags'] = $request->tags;
        }
        if (!Utils::isUnset($request->title)) {
            $query['Title'] = $request->title;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddMedia',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddMediaResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Adds a media file.
     *  *
     * @description *   You can call this operation to process videos that are uploaded to Object Storage Service (OSS) but not processed. This way, you do not need to upload the videos to OSS again. If you have configured media workflows, OSS automatically notifies ApsaraVideo Media Processing (MPS) when a media file is uploaded to OSS. MPS automatically finds the corresponding workflow in the Active state based on the specified OSS bucket and object. Therefore, in most cases, you do not need to manually call the AddMedia operation to process the media file.
     * *   Media information is automatically obtained only when the specified media workflow is in the Active state. If no media workflow is specified or the specified media workflow is not in the Active state, media information is not obtained.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaRequest $request AddMediaRequest
     *
     * @return AddMediaResponse AddMediaResponse
     */
    public function addMedia($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addMediaWithOptions($request, $runtime);
    }

    /**
     * @summary Adds a tag to a media file.
     *  *
     * @description You can call this operation to add only one tag. To add multiple tags at a time, you can call the [UpdateMedia](https://help.aliyun.com/document_detail/44464.html) operation.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaTagRequest $request AddMediaTagRequest
     * @param RuntimeOptions     $runtime runtime options for this request RuntimeOptions
     *
     * @return AddMediaTagResponse AddMediaTagResponse
     */
    public function addMediaTagWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->tag)) {
            $query['Tag'] = $request->tag;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddMediaTag',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddMediaTagResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Adds a tag to a media file.
     *  *
     * @description You can call this operation to add only one tag. To add multiple tags at a time, you can call the [UpdateMedia](https://help.aliyun.com/document_detail/44464.html) operation.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaTagRequest $request AddMediaTagRequest
     *
     * @return AddMediaTagResponse AddMediaTagResponse
     */
    public function addMediaTag($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addMediaTagWithOptions($request, $runtime);
    }

    /**
     * @summary Creates a media workflow.
     *  *
     * @description *   You can call this operation to define the topology, activities, and dependencies of a media workflow. The topology is represented by a directed acyclic graph (DAG) in the console. For more information, see [Workflow activities](https://help.aliyun.com/document_detail/68494.html). You can view and run the workflows that are created by calling this operation in the ApsaraVideo Media Processing (MPS) console.
     * *   MPS media workflows can be automatically triggered only by using the prefix of the file path. Automatic triggering by using the suffix is not supported. For more information about the trigger rules, see [Workflow triggering rules for files](https://help.aliyun.com/document_detail/68574.html).
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaWorkflowRequest $request AddMediaWorkflowRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return AddMediaWorkflowResponse AddMediaWorkflowResponse
     */
    public function addMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->topology)) {
            $query['Topology'] = $request->topology;
        }
        if (!Utils::isUnset($request->triggerMode)) {
            $query['TriggerMode'] = $request->triggerMode;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Creates a media workflow.
     *  *
     * @description *   You can call this operation to define the topology, activities, and dependencies of a media workflow. The topology is represented by a directed acyclic graph (DAG) in the console. For more information, see [Workflow activities](https://help.aliyun.com/document_detail/68494.html). You can view and run the workflows that are created by calling this operation in the ApsaraVideo Media Processing (MPS) console.
     * *   MPS media workflows can be automatically triggered only by using the prefix of the file path. Automatic triggering by using the suffix is not supported. For more information about the trigger rules, see [Workflow triggering rules for files](https://help.aliyun.com/document_detail/68574.html).
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddMediaWorkflowRequest $request AddMediaWorkflowRequest
     *
     * @return AddMediaWorkflowResponse AddMediaWorkflowResponse
     */
    public function addMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Adds an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @param AddPipelineRequest $request AddPipelineRequest
     * @param RuntimeOptions     $runtime runtime options for this request RuntimeOptions
     *
     * @return AddPipelineResponse AddPipelineResponse
     */
    public function addPipelineWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->notifyConfig)) {
            $query['NotifyConfig'] = $request->notifyConfig;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->role)) {
            $query['Role'] = $request->role;
        }
        if (!Utils::isUnset($request->speed)) {
            $query['Speed'] = $request->speed;
        }
        if (!Utils::isUnset($request->speedLevel)) {
            $query['SpeedLevel'] = $request->speedLevel;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddPipeline',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddPipelineResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Adds an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @param AddPipelineRequest $request AddPipelineRequest
     *
     * @return AddPipelineResponse AddPipelineResponse
     */
    public function addPipeline($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addPipelineWithOptions($request, $runtime);
    }

    /**
     * @summary 添加labelVersion、knowledgeConfig配置
     *  *
     * @param AddSmarttagTemplateRequest $request AddSmarttagTemplateRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return AddSmarttagTemplateResponse AddSmarttagTemplateResponse
     */
    public function addSmarttagTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->analyseTypes)) {
            $query['AnalyseTypes'] = $request->analyseTypes;
        }
        if (!Utils::isUnset($request->faceCategoryIds)) {
            $query['FaceCategoryIds'] = $request->faceCategoryIds;
        }
        if (!Utils::isUnset($request->faceCustomParamsConfig)) {
            $query['FaceCustomParamsConfig'] = $request->faceCustomParamsConfig;
        }
        if (!Utils::isUnset($request->industry)) {
            $query['Industry'] = $request->industry;
        }
        if (!Utils::isUnset($request->isDefault)) {
            $query['IsDefault'] = $request->isDefault;
        }
        if (!Utils::isUnset($request->keywordConfig)) {
            $query['KeywordConfig'] = $request->keywordConfig;
        }
        if (!Utils::isUnset($request->knowledgeConfig)) {
            $query['KnowledgeConfig'] = $request->knowledgeConfig;
        }
        if (!Utils::isUnset($request->labelType)) {
            $query['LabelType'] = $request->labelType;
        }
        if (!Utils::isUnset($request->labelVersion)) {
            $query['LabelVersion'] = $request->labelVersion;
        }
        if (!Utils::isUnset($request->landmarkGroupIds)) {
            $query['LandmarkGroupIds'] = $request->landmarkGroupIds;
        }
        if (!Utils::isUnset($request->objectGroupIds)) {
            $query['ObjectGroupIds'] = $request->objectGroupIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->scene)) {
            $query['Scene'] = $request->scene;
        }
        if (!Utils::isUnset($request->templateName)) {
            $query['TemplateName'] = $request->templateName;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddSmarttagTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddSmarttagTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 添加labelVersion、knowledgeConfig配置
     *  *
     * @param AddSmarttagTemplateRequest $request AddSmarttagTemplateRequest
     *
     * @return AddSmarttagTemplateResponse AddSmarttagTemplateResponse
     */
    public function addSmarttagTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addSmarttagTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Creates a custom transcoding template. You need to configure the information such as the container format, video stream settings, and audio stream settings.
     *  *
     * @description When you call this operation, you need to set transcoding parameters such as those related to the container format, video stream, and audio stream. If you do not specify some parameters, streams that are generated by using the template do not contain the information specified by those parameters.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddTemplateRequest $request AddTemplateRequest
     * @param RuntimeOptions     $runtime runtime options for this request RuntimeOptions
     *
     * @return AddTemplateResponse AddTemplateResponse
     */
    public function addTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->audio)) {
            $query['Audio'] = $request->audio;
        }
        if (!Utils::isUnset($request->container)) {
            $query['Container'] = $request->container;
        }
        if (!Utils::isUnset($request->muxConfig)) {
            $query['MuxConfig'] = $request->muxConfig;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->transConfig)) {
            $query['TransConfig'] = $request->transConfig;
        }
        if (!Utils::isUnset($request->video)) {
            $query['Video'] = $request->video;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Creates a custom transcoding template. You need to configure the information such as the container format, video stream settings, and audio stream settings.
     *  *
     * @description When you call this operation, you need to set transcoding parameters such as those related to the container format, video stream, and audio stream. If you do not specify some parameters, streams that are generated by using the template do not contain the information specified by those parameters.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddTemplateRequest $request AddTemplateRequest
     *
     * @return AddTemplateResponse AddTemplateResponse
     */
    public function addTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Creates a watermark template.
     *  *
     * @description After you create a watermark template by calling this operation, you can specify the watermark template and watermark asset when you [submit a transcoding job](https://help.aliyun.com/document_detail/29226.html). This allows you to add watermark information to the output video.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddWaterMarkTemplateRequest $request AddWaterMarkTemplateRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return AddWaterMarkTemplateResponse AddWaterMarkTemplateResponse
     */
    public function addWaterMarkTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->config)) {
            $query['Config'] = $request->config;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'AddWaterMarkTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return AddWaterMarkTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Creates a watermark template.
     *  *
     * @description After you create a watermark template by calling this operation, you can specify the watermark template and watermark asset when you [submit a transcoding job](https://help.aliyun.com/document_detail/29226.html). This allows you to add watermark information to the output video.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param AddWaterMarkTemplateRequest $request AddWaterMarkTemplateRequest
     *
     * @return AddWaterMarkTemplateResponse AddWaterMarkTemplateResponse
     */
    public function addWaterMarkTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->addWaterMarkTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Binds an input media bucket.
     *  *
     * @description Before you call this operation to bind an input media bucket, you must create a media bucket. For more information, see [Add media buckets](https://help.aliyun.com/document_detail/42430.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param BindInputBucketRequest $request BindInputBucketRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return BindInputBucketResponse BindInputBucketResponse
     */
    public function bindInputBucketWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->bucket)) {
            $query['Bucket'] = $request->bucket;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->referer)) {
            $query['Referer'] = $request->referer;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'BindInputBucket',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return BindInputBucketResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Binds an input media bucket.
     *  *
     * @description Before you call this operation to bind an input media bucket, you must create a media bucket. For more information, see [Add media buckets](https://help.aliyun.com/document_detail/42430.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param BindInputBucketRequest $request BindInputBucketRequest
     *
     * @return BindInputBucketResponse BindInputBucketResponse
     */
    public function bindInputBucket($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->bindInputBucketWithOptions($request, $runtime);
    }

    /**
     * @summary Binds an output media bucket to the media library.
     *  *
     * @description Before you call this operation to bind an output media bucket to the media library, you must create a media bucket. For more information, see [Add media buckets](https://help.aliyun.com/document_detail/42430.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param BindOutputBucketRequest $request BindOutputBucketRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return BindOutputBucketResponse BindOutputBucketResponse
     */
    public function bindOutputBucketWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->bucket)) {
            $query['Bucket'] = $request->bucket;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'BindOutputBucket',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return BindOutputBucketResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Binds an output media bucket to the media library.
     *  *
     * @description Before you call this operation to bind an output media bucket to the media library, you must create a media bucket. For more information, see [Add media buckets](https://help.aliyun.com/document_detail/42430.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param BindOutputBucketRequest $request BindOutputBucketRequest
     *
     * @return BindOutputBucketResponse BindOutputBucketResponse
     */
    public function bindOutputBucket($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->bindOutputBucketWithOptions($request, $runtime);
    }

    /**
     * @summary Cancels a transcoding job.
     *  *
     * @description *   You can cancel a transcoding job only if the job is in the Submitted state.
     * *   We recommend that you call the **UpdatePipeline** operation to set the status of the ApsaraVideo Media Processing (MPS) queue to Paused before you cancel a job. This suspends job scheduling in the MPS queue. After the job is canceled, you must set the status of the MPS queue back to Active so that the other jobs in the MPS queue can be scheduled.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param CancelJobRequest $request CancelJobRequest
     * @param RuntimeOptions   $runtime runtime options for this request RuntimeOptions
     *
     * @return CancelJobResponse CancelJobResponse
     */
    public function cancelJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'CancelJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return CancelJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Cancels a transcoding job.
     *  *
     * @description *   You can cancel a transcoding job only if the job is in the Submitted state.
     * *   We recommend that you call the **UpdatePipeline** operation to set the status of the ApsaraVideo Media Processing (MPS) queue to Paused before you cancel a job. This suspends job scheduling in the MPS queue. After the job is canceled, you must set the status of the MPS queue back to Active so that the other jobs in the MPS queue can be scheduled.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param CancelJobRequest $request CancelJobRequest
     *
     * @return CancelJobResponse CancelJobResponse
     */
    public function cancelJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->cancelJobWithOptions($request, $runtime);
    }

    /**
     * @param CreateCustomEntityRequest $request CreateCustomEntityRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return CreateCustomEntityResponse CreateCustomEntityResponse
     */
    public function createCustomEntityWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customEntityInfo)) {
            $query['CustomEntityInfo'] = $request->customEntityInfo;
        }
        if (!Utils::isUnset($request->customEntityName)) {
            $query['CustomEntityName'] = $request->customEntityName;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'CreateCustomEntity',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return CreateCustomEntityResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param CreateCustomEntityRequest $request CreateCustomEntityRequest
     *
     * @return CreateCustomEntityResponse CreateCustomEntityResponse
     */
    public function createCustomEntity($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->createCustomEntityWithOptions($request, $runtime);
    }

    /**
     * @param CreateCustomGroupRequest $request CreateCustomGroupRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return CreateCustomGroupResponse CreateCustomGroupResponse
     */
    public function createCustomGroupWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customGroupDescription)) {
            $query['CustomGroupDescription'] = $request->customGroupDescription;
        }
        if (!Utils::isUnset($request->customGroupName)) {
            $query['CustomGroupName'] = $request->customGroupName;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'CreateCustomGroup',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return CreateCustomGroupResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param CreateCustomGroupRequest $request CreateCustomGroupRequest
     *
     * @return CreateCustomGroupResponse CreateCustomGroupResponse
     */
    public function createCustomGroup($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->createCustomGroupWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a job of creating a media fingerprint library.
     *  *
     * @description *   You can call this operation to submit a job to create a video or text fingerprint library. You can use a text fingerprint library to store fingerprints for text.
     * *   You can submit a job of creating a text fingerprint library only in the China (Shanghai) region.
     * *   By default, you can submit up to 10 jobs of creating a video fingerprint library to an ApsaraVideo Media Processing (MPS) queue at a time. If you submit more than 10 jobs at a time, the call may fail.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param CreateFpShotDBRequest $request CreateFpShotDBRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return CreateFpShotDBResponse CreateFpShotDBResponse
     */
    public function createFpShotDBWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->config)) {
            $query['Config'] = $request->config;
        }
        if (!Utils::isUnset($request->description)) {
            $query['Description'] = $request->description;
        }
        if (!Utils::isUnset($request->modelId)) {
            $query['ModelId'] = $request->modelId;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'CreateFpShotDB',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return CreateFpShotDBResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a job of creating a media fingerprint library.
     *  *
     * @description *   You can call this operation to submit a job to create a video or text fingerprint library. You can use a text fingerprint library to store fingerprints for text.
     * *   You can submit a job of creating a text fingerprint library only in the China (Shanghai) region.
     * *   By default, you can submit up to 10 jobs of creating a video fingerprint library to an ApsaraVideo Media Processing (MPS) queue at a time. If you submit more than 10 jobs at a time, the call may fail.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param CreateFpShotDBRequest $request CreateFpShotDBRequest
     *
     * @return CreateFpShotDBResponse CreateFpShotDBResponse
     */
    public function createFpShotDB($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->createFpShotDBWithOptions($request, $runtime);
    }

    /**
     * @summary Disables a media workflow.
     *  *
     * @description The time when the media workflow was created.
     *  *
     * @param DeactivateMediaWorkflowRequest $request DeactivateMediaWorkflowRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return DeactivateMediaWorkflowResponse DeactivateMediaWorkflowResponse
     */
    public function deactivateMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeactivateMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeactivateMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Disables a media workflow.
     *  *
     * @description The time when the media workflow was created.
     *  *
     * @param DeactivateMediaWorkflowRequest $request DeactivateMediaWorkflowRequest
     *
     * @return DeactivateMediaWorkflowResponse DeactivateMediaWorkflowResponse
     */
    public function deactivateMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deactivateMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a custom entity from a custom library.
     *  *
     * @param DeleteCustomEntityRequest $request DeleteCustomEntityRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteCustomEntityResponse DeleteCustomEntityResponse
     */
    public function deleteCustomEntityWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customEntityId)) {
            $query['CustomEntityId'] = $request->customEntityId;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteCustomEntity',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteCustomEntityResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a custom entity from a custom library.
     *  *
     * @param DeleteCustomEntityRequest $request DeleteCustomEntityRequest
     *
     * @return DeleteCustomEntityResponse DeleteCustomEntityResponse
     */
    public function deleteCustomEntity($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteCustomEntityWithOptions($request, $runtime);
    }

    /**
     * @summary Delete a custom image library.
     *  *
     * @description You can call this operation only in the China (Beijing), China (Shanghai), and China (Hangzhou) regions.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteCustomGroupRequest $request DeleteCustomGroupRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteCustomGroupResponse DeleteCustomGroupResponse
     */
    public function deleteCustomGroupWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteCustomGroup',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteCustomGroupResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Delete a custom image library.
     *  *
     * @description You can call this operation only in the China (Beijing), China (Shanghai), and China (Hangzhou) regions.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteCustomGroupRequest $request DeleteCustomGroupRequest
     *
     * @return DeleteCustomGroupResponse DeleteCustomGroupResponse
     */
    public function deleteCustomGroup($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteCustomGroupWithOptions($request, $runtime);
    }

    /**
     * @param DeleteCustomViewRequest $request DeleteCustomViewRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteCustomViewResponse DeleteCustomViewResponse
     */
    public function deleteCustomViewWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customEntityId)) {
            $query['CustomEntityId'] = $request->customEntityId;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->customViewId)) {
            $query['CustomViewId'] = $request->customViewId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteCustomView',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteCustomViewResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param DeleteCustomViewRequest $request DeleteCustomViewRequest
     *
     * @return DeleteCustomViewResponse DeleteCustomViewResponse
     */
    public function deleteCustomView($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteCustomViewWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes media files from ApsaraVideo Media Processing (MPS).
     *  *
     * @description This operation allows you to logically delete a media file. The media file can no longer be processed, but the corresponding objects in the input and output Object Storage Service (OSS) buckets are retained.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaRequest $request DeleteMediaRequest
     * @param RuntimeOptions     $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteMediaResponse DeleteMediaResponse
     */
    public function deleteMediaWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaIds)) {
            $query['MediaIds'] = $request->mediaIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteMedia',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteMediaResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes media files from ApsaraVideo Media Processing (MPS).
     *  *
     * @description This operation allows you to logically delete a media file. The media file can no longer be processed, but the corresponding objects in the input and output Object Storage Service (OSS) buckets are retained.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaRequest $request DeleteMediaRequest
     *
     * @return DeleteMediaResponse DeleteMediaResponse
     */
    public function deleteMedia($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteMediaWithOptions($request, $runtime);
    }

    /**
     * @summary Removes a tag for a media file.
     *  *
     * @description You can call this operation to remove only one tag at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaTagRequest $request DeleteMediaTagRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteMediaTagResponse DeleteMediaTagResponse
     */
    public function deleteMediaTagWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->tag)) {
            $query['Tag'] = $request->tag;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteMediaTag',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteMediaTagResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Removes a tag for a media file.
     *  *
     * @description You can call this operation to remove only one tag at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaTagRequest $request DeleteMediaTagRequest
     *
     * @return DeleteMediaTagResponse DeleteMediaTagResponse
     */
    public function deleteMediaTag($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteMediaTagWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a media workflow. This does not affect workflow instances that are running.
     *  *
     * @description After you delete or disable a workflow, the workflow cannot be used. In this case, the workflow is not automatically triggered when you upload a file to the bucket specified by the workflow.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaWorkflowRequest $request DeleteMediaWorkflowRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteMediaWorkflowResponse DeleteMediaWorkflowResponse
     */
    public function deleteMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a media workflow. This does not affect workflow instances that are running.
     *  *
     * @description After you delete or disable a workflow, the workflow cannot be used. In this case, the workflow is not automatically triggered when you upload a file to the bucket specified by the workflow.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteMediaWorkflowRequest $request DeleteMediaWorkflowRequest
     *
     * @return DeleteMediaWorkflowResponse DeleteMediaWorkflowResponse
     */
    public function deleteMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @description You can call this operation to delete only one MPS queue at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeletePipelineRequest $request DeletePipelineRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return DeletePipelineResponse DeletePipelineResponse
     */
    public function deletePipelineWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeletePipeline',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeletePipelineResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @description You can call this operation to delete only one MPS queue at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeletePipelineRequest $request DeletePipelineRequest
     *
     * @return DeletePipelineResponse DeletePipelineResponse
     */
    public function deletePipeline($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deletePipelineWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a template.
     *  *
     * @description You can call this operation to delete only one template at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteSmarttagTemplateRequest $request DeleteSmarttagTemplateRequest
     * @param RuntimeOptions                $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteSmarttagTemplateResponse DeleteSmarttagTemplateResponse
     */
    public function deleteSmarttagTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteSmarttagTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteSmarttagTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a template.
     *  *
     * @description You can call this operation to delete only one template at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteSmarttagTemplateRequest $request DeleteSmarttagTemplateRequest
     *
     * @return DeleteSmarttagTemplateResponse DeleteSmarttagTemplateResponse
     */
    public function deleteSmarttagTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteSmarttagTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a custom transcoding template.
     *  *
     * @description A custom transcoding template cannot be deleted if it is being used by a job that has been submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteTemplateRequest $request DeleteTemplateRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteTemplateResponse DeleteTemplateResponse
     */
    public function deleteTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a custom transcoding template.
     *  *
     * @description A custom transcoding template cannot be deleted if it is being used by a job that has been submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteTemplateRequest $request DeleteTemplateRequest
     *
     * @return DeleteTemplateResponse DeleteTemplateResponse
     */
    public function deleteTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a watermark template.
     *  *
     * @description A watermark template cannot be deleted if it is being used by a submitted job.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteWaterMarkTemplateRequest $request DeleteWaterMarkTemplateRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return DeleteWaterMarkTemplateResponse DeleteWaterMarkTemplateResponse
     */
    public function deleteWaterMarkTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->waterMarkTemplateId)) {
            $query['WaterMarkTemplateId'] = $request->waterMarkTemplateId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'DeleteWaterMarkTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return DeleteWaterMarkTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a watermark template.
     *  *
     * @description A watermark template cannot be deleted if it is being used by a submitted job.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param DeleteWaterMarkTemplateRequest $request DeleteWaterMarkTemplateRequest
     *
     * @return DeleteWaterMarkTemplateResponse DeleteWaterMarkTemplateResponse
     */
    public function deleteWaterMarkTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->deleteWaterMarkTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Reviews images and text and returns the review results.
     *  *
     * @description *   The moderation results are synchronously returned after the moderation is complete.
     * *   You can use the image and text moderation feature only in the China (Beijing), China (Shanghai), and Singapore regions.
     * ### QPS limits
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ImAuditRequest $request ImAuditRequest
     * @param RuntimeOptions $runtime runtime options for this request RuntimeOptions
     *
     * @return ImAuditResponse ImAuditResponse
     */
    public function imAuditWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->bizType)) {
            $query['BizType'] = $request->bizType;
        }
        if (!Utils::isUnset($request->contents)) {
            $query['Contents'] = $request->contents;
        }
        if (!Utils::isUnset($request->images)) {
            $query['Images'] = $request->images;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->scenes)) {
            $query['Scenes'] = $request->scenes;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ImAudit',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ImAuditResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Reviews images and text and returns the review results.
     *  *
     * @description *   The moderation results are synchronously returned after the moderation is complete.
     * *   You can use the image and text moderation feature only in the China (Beijing), China (Shanghai), and Singapore regions.
     * ### QPS limits
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ImAuditRequest $request ImAuditRequest
     *
     * @return ImAuditResponse ImAuditResponse
     */
    public function imAudit($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->imAuditWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a job of importing text files to a text fingerprint library.
     *  *
     * @description *   You can call this operation to import multiple text files to a text fingerprint library at a time. The system extracts fingerprints from the text files and saves the fingerprints to the text fingerprint library.
     * *   You can call this operation only in the China (Shanghai) region.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ImportFpShotJobRequest $request ImportFpShotJobRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return ImportFpShotJobResponse ImportFpShotJobResponse
     */
    public function importFpShotJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->fpDBId)) {
            $query['FpDBId'] = $request->fpDBId;
        }
        if (!Utils::isUnset($request->fpImportConfig)) {
            $query['FpImportConfig'] = $request->fpImportConfig;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ImportFpShotJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ImportFpShotJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a job of importing text files to a text fingerprint library.
     *  *
     * @description *   You can call this operation to import multiple text files to a text fingerprint library at a time. The system extracts fingerprints from the text files and saves the fingerprints to the text fingerprint library.
     * *   You can call this operation only in the China (Shanghai) region.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ImportFpShotJobRequest $request ImportFpShotJobRequest
     *
     * @return ImportFpShotJobResponse ImportFpShotJobResponse
     */
    public function importFpShotJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->importFpShotJobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries all media buckets bound to the media library.
     *  *
     * @description A maximum of 100 media buckets can be returned.
     * ### QPS limit
     * You can call this operation up to 10 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListAllMediaBucketRequest $request ListAllMediaBucketRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return ListAllMediaBucketResponse ListAllMediaBucketResponse
     */
    public function listAllMediaBucketWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListAllMediaBucket',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListAllMediaBucketResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries all media buckets bound to the media library.
     *  *
     * @description A maximum of 100 media buckets can be returned.
     * ### QPS limit
     * You can call this operation up to 10 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListAllMediaBucketRequest $request ListAllMediaBucketRequest
     *
     * @return ListAllMediaBucketResponse ListAllMediaBucketResponse
     */
    public function listAllMediaBucket($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listAllMediaBucketWithOptions($request, $runtime);
    }

    /**
     * @summary Queries a list of entities in a custom library.
     *  *
     * @param ListCustomEntitiesRequest $request ListCustomEntitiesRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return ListCustomEntitiesResponse ListCustomEntitiesResponse
     */
    public function listCustomEntitiesWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListCustomEntities',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListCustomEntitiesResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries a list of entities in a custom library.
     *  *
     * @param ListCustomEntitiesRequest $request ListCustomEntitiesRequest
     *
     * @return ListCustomEntitiesResponse ListCustomEntitiesResponse
     */
    public function listCustomEntities($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listCustomEntitiesWithOptions($request, $runtime);
    }

    /**
     * @param ListCustomGroupsRequest $request ListCustomGroupsRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return ListCustomGroupsResponse ListCustomGroupsResponse
     */
    public function listCustomGroupsWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListCustomGroups',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListCustomGroupsResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param ListCustomGroupsRequest $request ListCustomGroupsRequest
     *
     * @return ListCustomGroupsResponse ListCustomGroupsResponse
     */
    public function listCustomGroups($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listCustomGroupsWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the information about all figures and faces in a specific figure library.
     *  *
     * @description You can specify the ID of a figure or a figure library to query the corresponding information. If neither the figure ID nor figure library ID is specified, the operation returns the information about all figures and faces in all figure libraries within the current RAM user.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListCustomPersonsRequest $request ListCustomPersonsRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return ListCustomPersonsResponse ListCustomPersonsResponse
     */
    public function listCustomPersonsWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->categoryId)) {
            $query['CategoryId'] = $request->categoryId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->personId)) {
            $query['PersonId'] = $request->personId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListCustomPersons',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListCustomPersonsResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the information about all figures and faces in a specific figure library.
     *  *
     * @description You can specify the ID of a figure or a figure library to query the corresponding information. If neither the figure ID nor figure library ID is specified, the operation returns the information about all figures and faces in all figure libraries within the current RAM user.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListCustomPersonsRequest $request ListCustomPersonsRequest
     *
     * @return ListCustomPersonsResponse ListCustomPersonsResponse
     */
    public function listCustomPersons($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listCustomPersonsWithOptions($request, $runtime);
    }

    /**
     * @param ListCustomViewsRequest $request ListCustomViewsRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return ListCustomViewsResponse ListCustomViewsResponse
     */
    public function listCustomViewsWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customEntityId)) {
            $query['CustomEntityId'] = $request->customEntityId;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListCustomViews',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListCustomViewsResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param ListCustomViewsRequest $request ListCustomViewsRequest
     *
     * @return ListCustomViewsResponse ListCustomViewsResponse
     */
    public function listCustomViews($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listCustomViewsWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media fingerprint libraries.
     *  *
     * @description *   You can call this operation to query the status and information about the media fingerprint libraries based on the specified IDs.
     * *   You can query text fingerprint libraries only in the China (Shanghai) region.
     * *   You can call this operation to query up to 10 media fingerprint libraries.
     * ### QPS limit
     * You can call this operation up to 500 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotDBRequest $request ListFpShotDBRequest
     * @param RuntimeOptions      $runtime runtime options for this request RuntimeOptions
     *
     * @return ListFpShotDBResponse ListFpShotDBResponse
     */
    public function listFpShotDBWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->fpDBIds)) {
            $query['FpDBIds'] = $request->fpDBIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListFpShotDB',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListFpShotDBResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media fingerprint libraries.
     *  *
     * @description *   You can call this operation to query the status and information about the media fingerprint libraries based on the specified IDs.
     * *   You can query text fingerprint libraries only in the China (Shanghai) region.
     * *   You can call this operation to query up to 10 media fingerprint libraries.
     * ### QPS limit
     * You can call this operation up to 500 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotDBRequest $request ListFpShotDBRequest
     *
     * @return ListFpShotDBResponse ListFpShotDBResponse
     */
    public function listFpShotDB($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listFpShotDBWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media files in a media fingerprint library.
     *  *
     * @description *   You can call this operation to query media files in a specific media fingerprint library based on the library ID. This operation supports paged queries.
     * *   You can call this operation only in the China (Beijing), China (Hangzhou), China (Shanghai), and Singapore regions.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotFilesRequest $request ListFpShotFilesRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return ListFpShotFilesResponse ListFpShotFilesResponse
     */
    public function listFpShotFilesWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->endTime)) {
            $query['EndTime'] = $request->endTime;
        }
        if (!Utils::isUnset($request->fpDBId)) {
            $query['FpDBId'] = $request->fpDBId;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->startTime)) {
            $query['StartTime'] = $request->startTime;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListFpShotFiles',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListFpShotFilesResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media files in a media fingerprint library.
     *  *
     * @description *   You can call this operation to query media files in a specific media fingerprint library based on the library ID. This operation supports paged queries.
     * *   You can call this operation only in the China (Beijing), China (Hangzhou), China (Shanghai), and Singapore regions.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotFilesRequest $request ListFpShotFilesRequest
     *
     * @return ListFpShotFilesResponse ListFpShotFilesResponse
     */
    public function listFpShotFiles($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listFpShotFilesWithOptions($request, $runtime);
    }

    /**
     * @summary Queries jobs of importing text files to a text fingerprint library.
     *  *
     * @description You can call this operation only in the China (Shanghai) region.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotImportJobRequest $request ListFpShotImportJobRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return ListFpShotImportJobResponse ListFpShotImportJobResponse
     */
    public function listFpShotImportJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListFpShotImportJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListFpShotImportJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries jobs of importing text files to a text fingerprint library.
     *  *
     * @description You can call this operation only in the China (Shanghai) region.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListFpShotImportJobRequest $request ListFpShotImportJobRequest
     *
     * @return ListFpShotImportJobResponse ListFpShotImportJobResponse
     */
    public function listFpShotImportJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listFpShotImportJobWithOptions($request, $runtime);
    }

    /**
     * @summary Traverses transcoding jobs
     *  *
     * @description *   By default, the returned transcoding jobs are sorted by CreationTime in descending order.
     * *   You can call this operation to return transcoding jobs of the last 90 days. The jobs are returned based on the specified time range.
     * *   You can filter query results by configuring request parameters such as job status, creation time interval, and ApsaraVideo Media Processing (MPS) queue for transcoding.
     * *   By default, MPS does not allow you to access data across regions within the same account. Before you call this operation, make sure that the region that you specify is the same as the region of the transcoding jobs to be queried. Otherwise, this operation may fail to be called, or invalid information may be returned.
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListJobRequest $request ListJobRequest
     * @param RuntimeOptions $runtime runtime options for this request RuntimeOptions
     *
     * @return ListJobResponse ListJobResponse
     */
    public function listJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->endOfJobCreatedTimeRange)) {
            $query['EndOfJobCreatedTimeRange'] = $request->endOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->startOfJobCreatedTimeRange)) {
            $query['StartOfJobCreatedTimeRange'] = $request->startOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Traverses transcoding jobs
     *  *
     * @description *   By default, the returned transcoding jobs are sorted by CreationTime in descending order.
     * *   You can call this operation to return transcoding jobs of the last 90 days. The jobs are returned based on the specified time range.
     * *   You can filter query results by configuring request parameters such as job status, creation time interval, and ApsaraVideo Media Processing (MPS) queue for transcoding.
     * *   By default, MPS does not allow you to access data across regions within the same account. Before you call this operation, make sure that the region that you specify is the same as the region of the transcoding jobs to be queried. Otherwise, this operation may fail to be called, or invalid information may be returned.
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListJobRequest $request ListJobRequest
     *
     * @return ListJobResponse ListJobResponse
     */
    public function listJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listJobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the execution instances of a media workflow.
     *  *
     * @description This operation returns execution instances only in the recent 90 days.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListMediaWorkflowExecutionsRequest $request ListMediaWorkflowExecutionsRequest
     * @param RuntimeOptions                     $runtime runtime options for this request RuntimeOptions
     *
     * @return ListMediaWorkflowExecutionsResponse ListMediaWorkflowExecutionsResponse
     */
    public function listMediaWorkflowExecutionsWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->inputFileURL)) {
            $query['InputFileURL'] = $request->inputFileURL;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->mediaWorkflowName)) {
            $query['MediaWorkflowName'] = $request->mediaWorkflowName;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'ListMediaWorkflowExecutions',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return ListMediaWorkflowExecutionsResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the execution instances of a media workflow.
     *  *
     * @description This operation returns execution instances only in the recent 90 days.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param ListMediaWorkflowExecutionsRequest $request ListMediaWorkflowExecutionsRequest
     *
     * @return ListMediaWorkflowExecutionsResponse ListMediaWorkflowExecutionsResponse
     */
    public function listMediaWorkflowExecutions($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->listMediaWorkflowExecutionsWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the template analysis job and returns a list of available preset templates when the template analysis job is complete.
     *  *
     * @description The time when the job was created.
     *  *
     * @param QueryAnalysisJobListRequest $request QueryAnalysisJobListRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryAnalysisJobListResponse QueryAnalysisJobListResponse
     */
    public function queryAnalysisJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->analysisJobIds)) {
            $query['AnalysisJobIds'] = $request->analysisJobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryAnalysisJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryAnalysisJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the template analysis job and returns a list of available preset templates when the template analysis job is complete.
     *  *
     * @description The time when the job was created.
     *  *
     * @param QueryAnalysisJobListRequest $request QueryAnalysisJobListRequest
     *
     * @return QueryAnalysisJobListResponse QueryAnalysisJobListResponse
     */
    public function queryAnalysisJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryAnalysisJobListWithOptions($request, $runtime);
    }

    /**
     * @summary 查询版权水印提取任务
     *  *
     * @param QueryCopyrightExtractJobRequest $request QueryCopyrightExtractJobRequest
     * @param RuntimeOptions                  $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryCopyrightExtractJobResponse QueryCopyrightExtractJobResponse
     */
    public function queryCopyrightExtractJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryCopyrightExtractJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryCopyrightExtractJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 查询版权水印提取任务
     *  *
     * @param QueryCopyrightExtractJobRequest $request QueryCopyrightExtractJobRequest
     *
     * @return QueryCopyrightExtractJobResponse QueryCopyrightExtractJobResponse
     */
    public function queryCopyrightExtractJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryCopyrightExtractJobWithOptions($request, $runtime);
    }

    /**
     * @summary 查询视频版权水印任务
     *  *
     * @param QueryCopyrightJobRequest $request QueryCopyrightJobRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryCopyrightJobResponse QueryCopyrightJobResponse
     */
    public function queryCopyrightJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->createTimeEnd)) {
            $query['CreateTimeEnd'] = $request->createTimeEnd;
        }
        if (!Utils::isUnset($request->createTimeStart)) {
            $query['CreateTimeStart'] = $request->createTimeStart;
        }
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->level)) {
            $query['Level'] = $request->level;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryCopyrightJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryCopyrightJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 查询视频版权水印任务
     *  *
     * @param QueryCopyrightJobRequest $request QueryCopyrightJobRequest
     *
     * @return QueryCopyrightJobResponse QueryCopyrightJobResponse
     */
    public function queryCopyrightJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryCopyrightJobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the jobs of clearing or deleting a media fingerprint library.
     *  *
     * @description You can call this operation to query the specified jobs of clearing or deleting a media fingerprint library based on the job IDs. If you do not specify job IDs, the system returns the latest 20 jobs that are submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpDBDeleteJobListRequest $request QueryFpDBDeleteJobListRequest
     * @param RuntimeOptions                $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryFpDBDeleteJobListResponse QueryFpDBDeleteJobListResponse
     */
    public function queryFpDBDeleteJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryFpDBDeleteJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryFpDBDeleteJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the jobs of clearing or deleting a media fingerprint library.
     *  *
     * @description You can call this operation to query the specified jobs of clearing or deleting a media fingerprint library based on the job IDs. If you do not specify job IDs, the system returns the latest 20 jobs that are submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpDBDeleteJobListRequest $request QueryFpDBDeleteJobListRequest
     *
     * @return QueryFpDBDeleteJobListResponse QueryFpDBDeleteJobListResponse
     */
    public function queryFpDBDeleteJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryFpDBDeleteJobListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the jobs of deleting media files from a media fingerprint library.
     *  *
     * @description You can call this operation to query the specified jobs of deleting media files from a media fingerprint library based on the job IDs. If you do not specify job IDs, the system returns the latest 20 jobs that are submitted.
     * ### QPS limit
     * You can call this operation up to 500 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpFileDeleteJobListRequest $request QueryFpFileDeleteJobListRequest
     * @param RuntimeOptions                  $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryFpFileDeleteJobListResponse QueryFpFileDeleteJobListResponse
     */
    public function queryFpFileDeleteJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryFpFileDeleteJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryFpFileDeleteJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the jobs of deleting media files from a media fingerprint library.
     *  *
     * @description You can call this operation to query the specified jobs of deleting media files from a media fingerprint library based on the job IDs. If you do not specify job IDs, the system returns the latest 20 jobs that are submitted.
     * ### QPS limit
     * You can call this operation up to 500 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpFileDeleteJobListRequest $request QueryFpFileDeleteJobListRequest
     *
     * @return QueryFpFileDeleteJobListResponse QueryFpFileDeleteJobListResponse
     */
    public function queryFpFileDeleteJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryFpFileDeleteJobListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media fingerprint analysis jobs. You can call this operation to query video fingerprint analysis jobs and text fingerprint analysis jobs.
     *  *
     * @description *   After a media fingerprint analysis job is submitted, the media fingerprinting service compares the fingerprints of the job input with those of the media files in the media fingerprint library. You can call this operation to query the job results.
     * *   You can query the results of a text fingerprint analysis job only in the China (Shanghai) region.
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpShotJobListRequest $request QueryFpShotJobListRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryFpShotJobListResponse QueryFpShotJobListResponse
     */
    public function queryFpShotJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->endOfJobCreatedTimeRange)) {
            $query['EndOfJobCreatedTimeRange'] = $request->endOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->startOfJobCreatedTimeRange)) {
            $query['StartOfJobCreatedTimeRange'] = $request->startOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryFpShotJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryFpShotJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media fingerprint analysis jobs. You can call this operation to query video fingerprint analysis jobs and text fingerprint analysis jobs.
     *  *
     * @description *   After a media fingerprint analysis job is submitted, the media fingerprinting service compares the fingerprints of the job input with those of the media files in the media fingerprint library. You can call this operation to query the job results.
     * *   You can query the results of a text fingerprint analysis job only in the China (Shanghai) region.
     * ### [](#qps)QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryFpShotJobListRequest $request QueryFpShotJobListRequest
     *
     * @return QueryFpShotJobListResponse QueryFpShotJobListResponse
     */
    public function queryFpShotJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryFpShotJobListWithOptions($request, $runtime);
    }

    /**
     * @param QueryIProductionJobRequest $request QueryIProductionJobRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryIProductionJobResponse QueryIProductionJobResponse
     */
    public function queryIProductionJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryIProductionJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryIProductionJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param QueryIProductionJobRequest $request QueryIProductionJobRequest
     *
     * @return QueryIProductionJobResponse QueryIProductionJobResponse
     */
    public function queryIProductionJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryIProductionJobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries transcoding jobs at a time by job ID.
     *  *
     * @description *   By default, returned jobs are sorted in descending order by CreationTime.
     * *   You can call this operation to query up to 10 transcoding jobs at a time.
     * *   If you do not set the JobIds parameter, the `InvalidParameter` error code is returned.
     * ## QPS limit
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryJobListRequest $request QueryJobListRequest
     * @param RuntimeOptions      $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryJobListResponse QueryJobListResponse
     */
    public function queryJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries transcoding jobs at a time by job ID.
     *  *
     * @description *   By default, returned jobs are sorted in descending order by CreationTime.
     * *   You can call this operation to query up to 10 transcoding jobs at a time.
     * *   If you do not set the JobIds parameter, the `InvalidParameter` error code is returned.
     * ## QPS limit
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryJobListRequest $request QueryJobListRequest
     *
     * @return QueryJobListResponse QueryJobListResponse
     */
    public function queryJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryJobListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the information about a content moderation job.
     *  *
     * @description In the content moderation results, the moderation results of the video are sorted in ascending order by time into a timeline. If the video is long, the content moderation results are paginated, and the first page is returned. You can call this operation again to query the remaining moderation results of the video.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaCensorJobDetailRequest $request QueryMediaCensorJobDetailRequest
     * @param RuntimeOptions                   $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaCensorJobDetailResponse QueryMediaCensorJobDetailResponse
     */
    public function queryMediaCensorJobDetailWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaCensorJobDetail',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaCensorJobDetailResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the information about a content moderation job.
     *  *
     * @description In the content moderation results, the moderation results of the video are sorted in ascending order by time into a timeline. If the video is long, the content moderation results are paginated, and the first page is returned. You can call this operation again to query the remaining moderation results of the video.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaCensorJobDetailRequest $request QueryMediaCensorJobDetailRequest
     *
     * @return QueryMediaCensorJobDetailResponse QueryMediaCensorJobDetailResponse
     */
    public function queryMediaCensorJobDetail($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaCensorJobDetailWithOptions($request, $runtime);
    }

    /**
     * @summary Queries content moderation jobs.
     *  *
     * @description You can call this operation to query only the content moderation jobs within three months.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaCensorJobListRequest $request QueryMediaCensorJobListRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaCensorJobListResponse QueryMediaCensorJobListResponse
     */
    public function queryMediaCensorJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->endOfJobCreatedTimeRange)) {
            $query['EndOfJobCreatedTimeRange'] = $request->endOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->jobIds)) {
            $query['JobIds'] = $request->jobIds;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->startOfJobCreatedTimeRange)) {
            $query['StartOfJobCreatedTimeRange'] = $request->startOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaCensorJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaCensorJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries content moderation jobs.
     *  *
     * @description You can call this operation to query only the content moderation jobs within three months.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaCensorJobListRequest $request QueryMediaCensorJobListRequest
     *
     * @return QueryMediaCensorJobListResponse QueryMediaCensorJobListResponse
     */
    public function queryMediaCensorJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaCensorJobListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the results of media information analysis jobs.
     *  *
     * @description *   In asynchronous mode, the media information can be retrieved only after the Message Service (MNS) callback of **submitting a media information job** is returned. If you have not retrieved the media information for a long period, the job may have failed.
     * *   You can call this operation to query up to 10 media information analysis jobs at a time.
     * *   By default, returned jobs are sorted in descending order by CreationTime.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaInfoJobListRequest $request QueryMediaInfoJobListRequest
     * @param RuntimeOptions               $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaInfoJobListResponse QueryMediaInfoJobListResponse
     */
    public function queryMediaInfoJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaInfoJobIds)) {
            $query['MediaInfoJobIds'] = $request->mediaInfoJobIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaInfoJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaInfoJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the results of media information analysis jobs.
     *  *
     * @description *   In asynchronous mode, the media information can be retrieved only after the Message Service (MNS) callback of **submitting a media information job** is returned. If you have not retrieved the media information for a long period, the job may have failed.
     * *   You can call this operation to query up to 10 media information analysis jobs at a time.
     * *   By default, returned jobs are sorted in descending order by CreationTime.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaInfoJobListRequest $request QueryMediaInfoJobListRequest
     *
     * @return QueryMediaInfoJobListResponse QueryMediaInfoJobListResponse
     */
    public function queryMediaInfoJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaInfoJobListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media files based on media file IDs.
     *  *
     * @description You can call this operation to query up to 10 media files at a time.
     * ## QPS limit
     * You can call this operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryMediaListRequest $request QueryMediaListRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaListResponse QueryMediaListResponse
     */
    public function queryMediaListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->includeMediaInfo)) {
            $query['IncludeMediaInfo'] = $request->includeMediaInfo;
        }
        if (!Utils::isUnset($request->includePlayList)) {
            $query['IncludePlayList'] = $request->includePlayList;
        }
        if (!Utils::isUnset($request->includeSnapshotList)) {
            $query['IncludeSnapshotList'] = $request->includeSnapshotList;
        }
        if (!Utils::isUnset($request->includeSummaryList)) {
            $query['IncludeSummaryList'] = $request->includeSummaryList;
        }
        if (!Utils::isUnset($request->mediaIds)) {
            $query['MediaIds'] = $request->mediaIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media files based on media file IDs.
     *  *
     * @description You can call this operation to query up to 10 media files at a time.
     * ## QPS limit
     * You can call this operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryMediaListRequest $request QueryMediaListRequest
     *
     * @return QueryMediaListResponse QueryMediaListResponse
     */
    public function queryMediaList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media files based on their Object Storage Service (OSS) URLs.
     *  *
     * @description *   You can call this operation to query up to 10 media files at a time.
     * *   Before you call this operation, you must call the [AddMedia](https://help.aliyun.com/document_detail/44458.html) operation to add media files.
     * *   You can call this operation to query only media files that are processed in a workflow. To obtain comprehensive information about a media file that is newly uploaded to OSS, you can call this operation after the corresponding workflow is complete. To query media files that are not processed in a workflow, you must call the [SubmitMediaInfoJob](https://help.aliyun.com/document_detail/29220.html) operation to submit a media information analysis job. After the job is complete, you can query the information about the media files.
     * ## QPS limit
     * You can call this API operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryMediaListByURLRequest $request QueryMediaListByURLRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaListByURLResponse QueryMediaListByURLResponse
     */
    public function queryMediaListByURLWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->fileURLs)) {
            $query['FileURLs'] = $request->fileURLs;
        }
        if (!Utils::isUnset($request->includeMediaInfo)) {
            $query['IncludeMediaInfo'] = $request->includeMediaInfo;
        }
        if (!Utils::isUnset($request->includePlayList)) {
            $query['IncludePlayList'] = $request->includePlayList;
        }
        if (!Utils::isUnset($request->includeSnapshotList)) {
            $query['IncludeSnapshotList'] = $request->includeSnapshotList;
        }
        if (!Utils::isUnset($request->includeSummaryList)) {
            $query['IncludeSummaryList'] = $request->includeSummaryList;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaListByURL',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaListByURLResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media files based on their Object Storage Service (OSS) URLs.
     *  *
     * @description *   You can call this operation to query up to 10 media files at a time.
     * *   Before you call this operation, you must call the [AddMedia](https://help.aliyun.com/document_detail/44458.html) operation to add media files.
     * *   You can call this operation to query only media files that are processed in a workflow. To obtain comprehensive information about a media file that is newly uploaded to OSS, you can call this operation after the corresponding workflow is complete. To query media files that are not processed in a workflow, you must call the [SubmitMediaInfoJob](https://help.aliyun.com/document_detail/29220.html) operation to submit a media information analysis job. After the job is complete, you can query the information about the media files.
     * ## QPS limit
     * You can call this API operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param QueryMediaListByURLRequest $request QueryMediaListByURLRequest
     *
     * @return QueryMediaListByURLResponse QueryMediaListByURLResponse
     */
    public function queryMediaListByURL($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaListByURLWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media workflow execution instances.
     *  *
     * @description *   You can call this operation to query a maximum of 10 media workflow execution instances at a time.
     * *   Before you call this operation, make sure that the workflow pipeline is enabled. Otherwise, the workflow may not run as expected. For example, the following exceptions may occur: the workflow node is invalid and jobs created in the workflow cannot be executed.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaWorkflowExecutionListRequest $request QueryMediaWorkflowExecutionListRequest
     * @param RuntimeOptions                         $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaWorkflowExecutionListResponse QueryMediaWorkflowExecutionListResponse
     */
    public function queryMediaWorkflowExecutionListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->runIds)) {
            $query['RunIds'] = $request->runIds;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaWorkflowExecutionList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaWorkflowExecutionListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media workflow execution instances.
     *  *
     * @description *   You can call this operation to query a maximum of 10 media workflow execution instances at a time.
     * *   Before you call this operation, make sure that the workflow pipeline is enabled. Otherwise, the workflow may not run as expected. For example, the following exceptions may occur: the workflow node is invalid and jobs created in the workflow cannot be executed.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaWorkflowExecutionListRequest $request QueryMediaWorkflowExecutionListRequest
     *
     * @return QueryMediaWorkflowExecutionListResponse QueryMediaWorkflowExecutionListResponse
     */
    public function queryMediaWorkflowExecutionList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaWorkflowExecutionListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries registered media workflows.
     *  *
     * @description You can call this operation to query up to 10 media workflows at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaWorkflowListRequest $request QueryMediaWorkflowListRequest
     * @param RuntimeOptions                $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryMediaWorkflowListResponse QueryMediaWorkflowListResponse
     */
    public function queryMediaWorkflowListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowIds)) {
            $query['MediaWorkflowIds'] = $request->mediaWorkflowIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryMediaWorkflowList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryMediaWorkflowListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries registered media workflows.
     *  *
     * @description You can call this operation to query up to 10 media workflows at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryMediaWorkflowListRequest $request QueryMediaWorkflowListRequest
     *
     * @return QueryMediaWorkflowListResponse QueryMediaWorkflowListResponse
     */
    public function queryMediaWorkflowList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryMediaWorkflowListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries an ApsaraVideo Media Processing (MPS) queue by using the ID of the queue.
     *  *
     * @description *   You can call this operation to query up to 10 MPS queues at a time.
     * *   If `"Code": "InvalidIdentity.ServiceDisabled","Message": "The request identity was not allowed operated.","Recommend"` is returned after you call this operation, check whether the RAM user that you use is assigned the AliyunMTSDefaultRole role to obtain the permissions on MPS and whether your Alibaba Cloud account has overdue payments.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryPipelineListRequest $request QueryPipelineListRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryPipelineListResponse QueryPipelineListResponse
     */
    public function queryPipelineListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineIds)) {
            $query['PipelineIds'] = $request->pipelineIds;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryPipelineList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryPipelineListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries an ApsaraVideo Media Processing (MPS) queue by using the ID of the queue.
     *  *
     * @description *   You can call this operation to query up to 10 MPS queues at a time.
     * *   If `"Code": "InvalidIdentity.ServiceDisabled","Message": "The request identity was not allowed operated.","Recommend"` is returned after you call this operation, check whether the RAM user that you use is assigned the AliyunMTSDefaultRole role to obtain the permissions on MPS and whether your Alibaba Cloud account has overdue payments.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryPipelineListRequest $request QueryPipelineListRequest
     *
     * @return QueryPipelineListResponse QueryPipelineListResponse
     */
    public function queryPipelineList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryPipelineListWithOptions($request, $runtime);
    }

    /**
     * @param QuerySmarttagJobRequest $request QuerySmarttagJobRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return QuerySmarttagJobResponse QuerySmarttagJobResponse
     */
    public function querySmarttagJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QuerySmarttagJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QuerySmarttagJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param QuerySmarttagJobRequest $request QuerySmarttagJobRequest
     *
     * @return QuerySmarttagJobResponse QuerySmarttagJobResponse
     */
    public function querySmarttagJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->querySmarttagJobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries the analysis template of a smart tagging job.
     *  *
     * @description If you call this operation to query the information about a smart tagging template, you must specify the template ID. Otherwise, the operation returns the information about all the templates that are created by the current RAM user.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QuerySmarttagTemplateListRequest $request QuerySmarttagTemplateListRequest
     * @param RuntimeOptions                   $runtime runtime options for this request RuntimeOptions
     *
     * @return QuerySmarttagTemplateListResponse QuerySmarttagTemplateListResponse
     */
    public function querySmarttagTemplateListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QuerySmarttagTemplateList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QuerySmarttagTemplateListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries the analysis template of a smart tagging job.
     *  *
     * @description If you call this operation to query the information about a smart tagging template, you must specify the template ID. Otherwise, the operation returns the information about all the templates that are created by the current RAM user.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QuerySmarttagTemplateListRequest $request QuerySmarttagTemplateListRequest
     *
     * @return QuerySmarttagTemplateListResponse QuerySmarttagTemplateListResponse
     */
    public function querySmarttagTemplateList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->querySmarttagTemplateListWithOptions($request, $runtime);
    }

    /**
     * @summary Queries snapshot jobs.
     *  *
     * @description The status of the snapshot jobs that you want to query.
     * *   **Submitted**: The job was submitted.
     * *   **Snapshoting**: The job is being processed.
     * *   **Success**: The job was successfully processed.
     * *   **Fail**: The job failed.
     *  *
     * @param QuerySnapshotJobListRequest $request QuerySnapshotJobListRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return QuerySnapshotJobListResponse QuerySnapshotJobListResponse
     */
    public function querySnapshotJobListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->endOfJobCreatedTimeRange)) {
            $query['EndOfJobCreatedTimeRange'] = $request->endOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->maximumPageSize)) {
            $query['MaximumPageSize'] = $request->maximumPageSize;
        }
        if (!Utils::isUnset($request->nextPageToken)) {
            $query['NextPageToken'] = $request->nextPageToken;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->snapshotJobIds)) {
            $query['SnapshotJobIds'] = $request->snapshotJobIds;
        }
        if (!Utils::isUnset($request->startOfJobCreatedTimeRange)) {
            $query['StartOfJobCreatedTimeRange'] = $request->startOfJobCreatedTimeRange;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QuerySnapshotJobList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QuerySnapshotJobListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries snapshot jobs.
     *  *
     * @description The status of the snapshot jobs that you want to query.
     * *   **Submitted**: The job was submitted.
     * *   **Snapshoting**: The job is being processed.
     * *   **Success**: The job was successfully processed.
     * *   **Fail**: The job failed.
     *  *
     * @param QuerySnapshotJobListRequest $request QuerySnapshotJobListRequest
     *
     * @return QuerySnapshotJobListResponse QuerySnapshotJobListResponse
     */
    public function querySnapshotJobList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->querySnapshotJobListWithOptions($request, $runtime);
    }

    /**
     * @summary You can call this operation to query up to 10 transcoding templates at a time.
     *  *
     * @description The IDs of the transcoding templates that you want to query. You can query up to 10 transcoding templates at a time. Separate multiple IDs of custom transcoding templates with commas (,).
     *  *
     * @param QueryTemplateListRequest $request QueryTemplateListRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryTemplateListResponse QueryTemplateListResponse
     */
    public function queryTemplateListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateIds)) {
            $query['TemplateIds'] = $request->templateIds;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryTemplateList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryTemplateListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary You can call this operation to query up to 10 transcoding templates at a time.
     *  *
     * @description The IDs of the transcoding templates that you want to query. You can query up to 10 transcoding templates at a time. Separate multiple IDs of custom transcoding templates with commas (,).
     *  *
     * @param QueryTemplateListRequest $request QueryTemplateListRequest
     *
     * @return QueryTemplateListResponse QueryTemplateListResponse
     */
    public function queryTemplateList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryTemplateListWithOptions($request, $runtime);
    }

    /**
     * @summary 查询视频溯源水印ab流任务
     *  *
     * @param QueryTraceAbJobRequest $request QueryTraceAbJobRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryTraceAbJobResponse QueryTraceAbJobResponse
     */
    public function queryTraceAbJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryTraceAbJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryTraceAbJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 查询视频溯源水印ab流任务
     *  *
     * @param QueryTraceAbJobRequest $request QueryTraceAbJobRequest
     *
     * @return QueryTraceAbJobResponse QueryTraceAbJobResponse
     */
    public function queryTraceAbJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryTraceAbJobWithOptions($request, $runtime);
    }

    /**
     * @summary 查询溯源水印提取任务
     *  *
     * @param QueryTraceExtractJobRequest $request QueryTraceExtractJobRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryTraceExtractJobResponse QueryTraceExtractJobResponse
     */
    public function queryTraceExtractJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryTraceExtractJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryTraceExtractJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 查询溯源水印提取任务
     *  *
     * @param QueryTraceExtractJobRequest $request QueryTraceExtractJobRequest
     *
     * @return QueryTraceExtractJobResponse QueryTraceExtractJobResponse
     */
    public function queryTraceExtractJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryTraceExtractJobWithOptions($request, $runtime);
    }

    /**
     * @summary 查询视频溯源水印m3u8任务
     *  *
     * @param QueryTraceM3u8JobRequest $request QueryTraceM3u8JobRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryTraceM3u8JobResponse QueryTraceM3u8JobResponse
     */
    public function queryTraceM3u8JobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->createTimeEnd)) {
            $query['CreateTimeEnd'] = $request->createTimeEnd;
        }
        if (!Utils::isUnset($request->createTimeStart)) {
            $query['CreateTimeStart'] = $request->createTimeStart;
        }
        if (!Utils::isUnset($request->jobId)) {
            $query['JobId'] = $request->jobId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryTraceM3u8Job',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryTraceM3u8JobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 查询视频溯源水印m3u8任务
     *  *
     * @param QueryTraceM3u8JobRequest $request QueryTraceM3u8JobRequest
     *
     * @return QueryTraceM3u8JobResponse QueryTraceM3u8JobResponse
     */
    public function queryTraceM3u8Job($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryTraceM3u8JobWithOptions($request, $runtime);
    }

    /**
     * @summary Queries watermark templates.
     *  *
     * @description You can call this operation to query up to 10 watermark templates at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryWaterMarkTemplateListRequest $request QueryWaterMarkTemplateListRequest
     * @param RuntimeOptions                    $runtime runtime options for this request RuntimeOptions
     *
     * @return QueryWaterMarkTemplateListResponse QueryWaterMarkTemplateListResponse
     */
    public function queryWaterMarkTemplateListWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->waterMarkTemplateIds)) {
            $query['WaterMarkTemplateIds'] = $request->waterMarkTemplateIds;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'QueryWaterMarkTemplateList',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return QueryWaterMarkTemplateListResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries watermark templates.
     *  *
     * @description You can call this operation to query up to 10 watermark templates at a time.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param QueryWaterMarkTemplateListRequest $request QueryWaterMarkTemplateListRequest
     *
     * @return QueryWaterMarkTemplateListResponse QueryWaterMarkTemplateListResponse
     */
    public function queryWaterMarkTemplateList($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->queryWaterMarkTemplateListWithOptions($request, $runtime);
    }

    /**
     * @summary Registers a custom face.
     *  *
     * @description *   You can call this operation to register only one custom face at a time.
     * *   A maximum of 10 images can be registered for a custom face.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param RegisterCustomFaceRequest $request RegisterCustomFaceRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return RegisterCustomFaceResponse RegisterCustomFaceResponse
     */
    public function registerCustomFaceWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->categoryId)) {
            $query['CategoryId'] = $request->categoryId;
        }
        if (!Utils::isUnset($request->imageUrl)) {
            $query['ImageUrl'] = $request->imageUrl;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->personId)) {
            $query['PersonId'] = $request->personId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'RegisterCustomFace',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return RegisterCustomFaceResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Registers a custom face.
     *  *
     * @description *   You can call this operation to register only one custom face at a time.
     * *   A maximum of 10 images can be registered for a custom face.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param RegisterCustomFaceRequest $request RegisterCustomFaceRequest
     *
     * @return RegisterCustomFaceResponse RegisterCustomFaceResponse
     */
    public function registerCustomFace($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->registerCustomFaceWithOptions($request, $runtime);
    }

    /**
     * @param RegisterCustomViewRequest $request RegisterCustomViewRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return RegisterCustomViewResponse RegisterCustomViewResponse
     */
    public function registerCustomViewWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->algorithm)) {
            $query['Algorithm'] = $request->algorithm;
        }
        if (!Utils::isUnset($request->customEntityId)) {
            $query['CustomEntityId'] = $request->customEntityId;
        }
        if (!Utils::isUnset($request->customGroupId)) {
            $query['CustomGroupId'] = $request->customGroupId;
        }
        if (!Utils::isUnset($request->imageUrl)) {
            $query['ImageUrl'] = $request->imageUrl;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'RegisterCustomView',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return RegisterCustomViewResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param RegisterCustomViewRequest $request RegisterCustomViewRequest
     *
     * @return RegisterCustomViewResponse RegisterCustomViewResponse
     */
    public function registerCustomView($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->registerCustomViewWithOptions($request, $runtime);
    }

    /**
     * @summary Queries media workflows in the specified state.
     *  *
     * @description You can call this operation to query media workflows in the specified state. If you do not specify the state, all media workflows are queried by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchMediaWorkflowRequest $request SearchMediaWorkflowRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return SearchMediaWorkflowResponse SearchMediaWorkflowResponse
     */
    public function searchMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->stateList)) {
            $query['StateList'] = $request->stateList;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SearchMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SearchMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Queries media workflows in the specified state.
     *  *
     * @description You can call this operation to query media workflows in the specified state. If you do not specify the state, all media workflows are queried by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchMediaWorkflowRequest $request SearchMediaWorkflowRequest
     *
     * @return SearchMediaWorkflowResponse SearchMediaWorkflowResponse
     */
    public function searchMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->searchMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Searches for ApsaraVideo Media Processing (MPS) queues in the specified state.
     *  *
     * @description You can call this operation to query MPS queues in the specified state. If you do not specify the state, all MPS queues are queried by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchPipelineRequest $request SearchPipelineRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return SearchPipelineResponse SearchPipelineResponse
     */
    public function searchPipelineWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SearchPipeline',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SearchPipelineResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Searches for ApsaraVideo Media Processing (MPS) queues in the specified state.
     *  *
     * @description You can call this operation to query MPS queues in the specified state. If you do not specify the state, all MPS queues are queried by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchPipelineRequest $request SearchPipelineRequest
     *
     * @return SearchPipelineResponse SearchPipelineResponse
     */
    public function searchPipeline($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->searchPipelineWithOptions($request, $runtime);
    }

    /**
     * @summary You can call this operation to query custom transcoding templates in the specified state.
     *  *
     * @description You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchTemplateRequest $request SearchTemplateRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return SearchTemplateResponse SearchTemplateResponse
     */
    public function searchTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->namePrefix)) {
            $query['NamePrefix'] = $request->namePrefix;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SearchTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SearchTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary You can call this operation to query custom transcoding templates in the specified state.
     *  *
     * @description You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SearchTemplateRequest $request SearchTemplateRequest
     *
     * @return SearchTemplateResponse SearchTemplateResponse
     */
    public function searchTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->searchTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Searches for watermark templates.
     *  *
     * @description The total number of returned entries.
     *  *
     * @param SearchWaterMarkTemplateRequest $request SearchWaterMarkTemplateRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return SearchWaterMarkTemplateResponse SearchWaterMarkTemplateResponse
     */
    public function searchWaterMarkTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pageNumber)) {
            $query['PageNumber'] = $request->pageNumber;
        }
        if (!Utils::isUnset($request->pageSize)) {
            $query['PageSize'] = $request->pageSize;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SearchWaterMarkTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SearchWaterMarkTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Searches for watermark templates.
     *  *
     * @description The total number of returned entries.
     *  *
     * @param SearchWaterMarkTemplateRequest $request SearchWaterMarkTemplateRequest
     *
     * @return SearchWaterMarkTemplateResponse SearchWaterMarkTemplateResponse
     */
    public function searchWaterMarkTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->searchWaterMarkTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a preset template analysis job.
     *  *
     * @description *   After you call the SubmitAnalysisJob operation to submit a preset template analysis job, ApsaraVideo Media Processing (MPS) intelligently analyzes the input file of the job and recommends a suitable preset template. You can call the [QueryAnalysisJobList](https://help.aliyun.com/document_detail/29224.html) operation to query the analysis result or enable asynchronous notifications to receive the analysis result.
     * *   The analysis result is retained only for two weeks after it is generated. The analysis result is deleted after two weeks. If you use the recommended preset template in a transcoding job after two weeks, the job fails, and the `AnalysisResultNotFound` error code is returned.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitAnalysisJobRequest $request SubmitAnalysisJobRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitAnalysisJobResponse SubmitAnalysisJobResponse
     */
    public function submitAnalysisJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->analysisConfig)) {
            $query['AnalysisConfig'] = $request->analysisConfig;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->priority)) {
            $query['Priority'] = $request->priority;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitAnalysisJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitAnalysisJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a preset template analysis job.
     *  *
     * @description *   After you call the SubmitAnalysisJob operation to submit a preset template analysis job, ApsaraVideo Media Processing (MPS) intelligently analyzes the input file of the job and recommends a suitable preset template. You can call the [QueryAnalysisJobList](https://help.aliyun.com/document_detail/29224.html) operation to query the analysis result or enable asynchronous notifications to receive the analysis result.
     * *   The analysis result is retained only for two weeks after it is generated. The analysis result is deleted after two weeks. If you use the recommended preset template in a transcoding job after two weeks, the job fails, and the `AnalysisResultNotFound` error code is returned.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitAnalysisJobRequest $request SubmitAnalysisJobRequest
     *
     * @return SubmitAnalysisJobResponse SubmitAnalysisJobResponse
     */
    public function submitAnalysisJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitAnalysisJobWithOptions($request, $runtime);
    }

    /**
     * @summary 提交版权水印提取任务
     *  *
     * @param SubmitCopyrightExtractJobRequest $request SubmitCopyrightExtractJobRequest
     * @param RuntimeOptions                   $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitCopyrightExtractJobResponse SubmitCopyrightExtractJobResponse
     */
    public function submitCopyrightExtractJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->callBack)) {
            $query['CallBack'] = $request->callBack;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->url)) {
            $query['Url'] = $request->url;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitCopyrightExtractJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitCopyrightExtractJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 提交版权水印提取任务
     *  *
     * @param SubmitCopyrightExtractJobRequest $request SubmitCopyrightExtractJobRequest
     *
     * @return SubmitCopyrightExtractJobResponse SubmitCopyrightExtractJobResponse
     */
    public function submitCopyrightExtractJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitCopyrightExtractJobWithOptions($request, $runtime);
    }

    /**
     * @summary 提交视频版权水印
     *  *
     * @param SubmitCopyrightJobRequest $request SubmitCopyrightJobRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitCopyrightJobResponse SubmitCopyrightJobResponse
     */
    public function submitCopyrightJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->callBack)) {
            $query['CallBack'] = $request->callBack;
        }
        if (!Utils::isUnset($request->description)) {
            $query['Description'] = $request->description;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->level)) {
            $query['Level'] = $request->level;
        }
        if (!Utils::isUnset($request->message)) {
            $query['Message'] = $request->message;
        }
        if (!Utils::isUnset($request->output)) {
            $query['Output'] = $request->output;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->startTime)) {
            $query['StartTime'] = $request->startTime;
        }
        if (!Utils::isUnset($request->totalTime)) {
            $query['TotalTime'] = $request->totalTime;
        }
        if (!Utils::isUnset($request->url)) {
            $query['Url'] = $request->url;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitCopyrightJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitCopyrightJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 提交视频版权水印
     *  *
     * @param SubmitCopyrightJobRequest $request SubmitCopyrightJobRequest
     *
     * @return SubmitCopyrightJobResponse SubmitCopyrightJobResponse
     */
    public function submitCopyrightJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitCopyrightJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a job of clearing or deleting a media fingerprint library.
     *  *
     * @description You can call this operation to clear or delete the specified media fingerprint library based on the library ID. If you clear a media fingerprint library, the content in the library is deleted, but the library is not deleted. If you delete a media fingerprint library, both the library and the content in the library are deleted. If you do not specify the operation type, the system clears the media fingerprint library by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpDBDeleteJobRequest $request SubmitFpDBDeleteJobRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitFpDBDeleteJobResponse SubmitFpDBDeleteJobResponse
     */
    public function submitFpDBDeleteJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->delType)) {
            $query['DelType'] = $request->delType;
        }
        if (!Utils::isUnset($request->fpDBId)) {
            $query['FpDBId'] = $request->fpDBId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitFpDBDeleteJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitFpDBDeleteJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a job of clearing or deleting a media fingerprint library.
     *  *
     * @description You can call this operation to clear or delete the specified media fingerprint library based on the library ID. If you clear a media fingerprint library, the content in the library is deleted, but the library is not deleted. If you delete a media fingerprint library, both the library and the content in the library are deleted. If you do not specify the operation type, the system clears the media fingerprint library by default.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpDBDeleteJobRequest $request SubmitFpDBDeleteJobRequest
     *
     * @return SubmitFpDBDeleteJobResponse SubmitFpDBDeleteJobResponse
     */
    public function submitFpDBDeleteJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitFpDBDeleteJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a job of deleting media files from a media fingerprint library.
     *  *
     * @description ## [](#)Limits
     * *   You can call this operation to delete up to 200 media files from a media fingerprint library at a time.
     * *   This operation is available in the following regions: China (Beijing), China (Hangzhou), China (Shanghai), and Singapore.
     * ## [](#qps-)QPS limits
     * You can call this operation up to 10 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpFileDeleteJobRequest $request SubmitFpFileDeleteJobRequest
     * @param RuntimeOptions               $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitFpFileDeleteJobResponse SubmitFpFileDeleteJobResponse
     */
    public function submitFpFileDeleteJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->fileIds)) {
            $query['FileIds'] = $request->fileIds;
        }
        if (!Utils::isUnset($request->fpDBId)) {
            $query['FpDBId'] = $request->fpDBId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->primaryKeys)) {
            $query['PrimaryKeys'] = $request->primaryKeys;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitFpFileDeleteJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitFpFileDeleteJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a job of deleting media files from a media fingerprint library.
     *  *
     * @description ## [](#)Limits
     * *   You can call this operation to delete up to 200 media files from a media fingerprint library at a time.
     * *   This operation is available in the following regions: China (Beijing), China (Hangzhou), China (Shanghai), and Singapore.
     * ## [](#qps-)QPS limits
     * You can call this operation up to 10 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpFileDeleteJobRequest $request SubmitFpFileDeleteJobRequest
     *
     * @return SubmitFpFileDeleteJobResponse SubmitFpFileDeleteJobResponse
     */
    public function submitFpFileDeleteJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitFpFileDeleteJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a media fingerprint analysis job to query the media fingerprint repository for a media file with the identical or similar fingerprint as the input file.
     *  *
     * @description *   You can call this operation to submit a video, audio, image, or text fingerprint analysis job.
     * *   This operation asynchronously submits a job. The query results may not have been generated when the response is returned. After the results are generated, an asynchronous message is returned.
     * *   You can submit a text fingerprint analysis job only in the China (Shanghai) region.
     * *   The input file of the job must be in one of the following formats:
     *     *   Image formats: JPEG, PNG, and BMP.
     *     *   Video formats: MP4, AVI, MKV, MPG, TS, MOV, FLV, MXF.
     *     *   Video encoding formats: MPEG2, MPEG4, H264, HEVC, and WMV.
     * ### QPS limit
     * You can call this operation up to 150 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpShotJobRequest $request SubmitFpShotJobRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitFpShotJobResponse SubmitFpShotJobResponse
     */
    public function submitFpShotJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->fpShotConfig)) {
            $query['FpShotConfig'] = $request->fpShotConfig;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitFpShotJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitFpShotJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a media fingerprint analysis job to query the media fingerprint repository for a media file with the identical or similar fingerprint as the input file.
     *  *
     * @description *   You can call this operation to submit a video, audio, image, or text fingerprint analysis job.
     * *   This operation asynchronously submits a job. The query results may not have been generated when the response is returned. After the results are generated, an asynchronous message is returned.
     * *   You can submit a text fingerprint analysis job only in the China (Shanghai) region.
     * *   The input file of the job must be in one of the following formats:
     *     *   Image formats: JPEG, PNG, and BMP.
     *     *   Video formats: MP4, AVI, MKV, MPG, TS, MOV, FLV, MXF.
     *     *   Video encoding formats: MPEG2, MPEG4, H264, HEVC, and WMV.
     * ### QPS limit
     * You can call this operation up to 150 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitFpShotJobRequest $request SubmitFpShotJobRequest
     *
     * @return SubmitFpShotJobResponse SubmitFpShotJobResponse
     */
    public function submitFpShotJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitFpShotJobWithOptions($request, $runtime);
    }

    /**
     * @description *   Jobs that are submitted by calling this operation run in an asynchronous manner. After a job is added to the ApsaraVideo Media Processing (MPS) queue, the job is scheduled to run. You can call the [QueryIProductionJob](https://help.aliyun.com/document_detail/170217.html) operation or configure a callback to query the job result.
     * *   Capabilities provided by the intelligent production feature vary based on the region. Before you call this operation to submit an intelligent production job, check whether the job is supported in the region in which your service is activated. For more information, see [Regions and endpoints](https://help.aliyun.com/document_detail/43248.html).
     * ### [](#qps)QPS limit
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitIProductionJobRequest $request SubmitIProductionJobRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitIProductionJobResponse SubmitIProductionJobResponse
     */
    public function submitIProductionJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->functionName)) {
            $query['FunctionName'] = $request->functionName;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->jobParams)) {
            $query['JobParams'] = $request->jobParams;
        }
        if (!Utils::isUnset($request->modelId)) {
            $query['ModelId'] = $request->modelId;
        }
        if (!Utils::isUnset($request->notifyUrl)) {
            $query['NotifyUrl'] = $request->notifyUrl;
        }
        if (!Utils::isUnset($request->output)) {
            $query['Output'] = $request->output;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->scheduleParams)) {
            $query['ScheduleParams'] = $request->scheduleParams;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitIProductionJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitIProductionJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @description *   Jobs that are submitted by calling this operation run in an asynchronous manner. After a job is added to the ApsaraVideo Media Processing (MPS) queue, the job is scheduled to run. You can call the [QueryIProductionJob](https://help.aliyun.com/document_detail/170217.html) operation or configure a callback to query the job result.
     * *   Capabilities provided by the intelligent production feature vary based on the region. Before you call this operation to submit an intelligent production job, check whether the job is supported in the region in which your service is activated. For more information, see [Regions and endpoints](https://help.aliyun.com/document_detail/43248.html).
     * ### [](#qps)QPS limit
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitIProductionJobRequest $request SubmitIProductionJobRequest
     *
     * @return SubmitIProductionJobResponse SubmitIProductionJobResponse
     */
    public function submitIProductionJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitIProductionJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits transcoding jobs. If the transcoding jobs and workflows created in the ApsaraVideo Media Processing (MPS) console cannot meet your business requirements, you can call the SubmitJobs operation to submit transcoding jobs. Specify transcoding parameters as required when you call the SubmitJobs operation.
     *  *
     * @description *   SubmitJobs is an asynchronous operation. After you submit transcoding jobs, the jobs are added to an MPS queue to be scheduled and run. The transcoding jobs may not have been complete when the response is returned. After you call this operation, you can call the [QueryJobList](https://help.aliyun.com/document_detail/602836.html) operation to query the job results. You can also associate a Message Service (MNS) queue or topic with the MPS queue to receive notifications on the jobs. For more information, see [Receive notifications](https://www.alibabacloud.com/help/zh/apsaravideo-for-media-processing/latest/receive-message-notifications).
     * *   An input file can be up to 100 GB in size. If the size of the input file exceeds this limit, the job may fail.
     * *   If you use an **intelligent preset template** to transcode an input file, you must first call the [SubmitAnalysisJob](https://help.aliyun.com/document_detail/29223.html) operation to submit a preset template analysis job. After the analysis job is complete, you can call the [QueryAnalysisJobList](https://help.aliyun.com/document_detail/29224.html)operation to obtain the available preset templates for the input file. When you submit a transcoding job, set TemplateId to the ID of an available preset template. If you specify a preset template that is not in the available preset templates, the transcoding job fails.
     * *   If you use a **static preset template** to transcode an input file, you do not need to submit a preset template analysis job.
     * *   If you want to use multiple accounts in MPS, you can create Resource Access Management (RAM) users by using your Alibaba Cloud account. For more information, see [Create a RAM user and grant permissions to the RAM user](https://help.aliyun.com/document_detail/42841.html). If the Alibaba Cloud account that is used to query transcoding jobs is not the one that is used to submit the transcoding jobs, no data is returned.
     * *   For information about transcoding FAQ, see [FAQ about MPS](https://help.aliyun.com/document_detail/38986.html).
     * ### [](#qps)QPS limits
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitJobsRequest $request SubmitJobsRequest
     * @param RuntimeOptions    $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitJobsResponse SubmitJobsResponse
     */
    public function submitJobsWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->outputBucket)) {
            $query['OutputBucket'] = $request->outputBucket;
        }
        if (!Utils::isUnset($request->outputLocation)) {
            $query['OutputLocation'] = $request->outputLocation;
        }
        if (!Utils::isUnset($request->outputs)) {
            $query['Outputs'] = $request->outputs;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitJobs',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitJobsResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits transcoding jobs. If the transcoding jobs and workflows created in the ApsaraVideo Media Processing (MPS) console cannot meet your business requirements, you can call the SubmitJobs operation to submit transcoding jobs. Specify transcoding parameters as required when you call the SubmitJobs operation.
     *  *
     * @description *   SubmitJobs is an asynchronous operation. After you submit transcoding jobs, the jobs are added to an MPS queue to be scheduled and run. The transcoding jobs may not have been complete when the response is returned. After you call this operation, you can call the [QueryJobList](https://help.aliyun.com/document_detail/602836.html) operation to query the job results. You can also associate a Message Service (MNS) queue or topic with the MPS queue to receive notifications on the jobs. For more information, see [Receive notifications](https://www.alibabacloud.com/help/zh/apsaravideo-for-media-processing/latest/receive-message-notifications).
     * *   An input file can be up to 100 GB in size. If the size of the input file exceeds this limit, the job may fail.
     * *   If you use an **intelligent preset template** to transcode an input file, you must first call the [SubmitAnalysisJob](https://help.aliyun.com/document_detail/29223.html) operation to submit a preset template analysis job. After the analysis job is complete, you can call the [QueryAnalysisJobList](https://help.aliyun.com/document_detail/29224.html)operation to obtain the available preset templates for the input file. When you submit a transcoding job, set TemplateId to the ID of an available preset template. If you specify a preset template that is not in the available preset templates, the transcoding job fails.
     * *   If you use a **static preset template** to transcode an input file, you do not need to submit a preset template analysis job.
     * *   If you want to use multiple accounts in MPS, you can create Resource Access Management (RAM) users by using your Alibaba Cloud account. For more information, see [Create a RAM user and grant permissions to the RAM user](https://help.aliyun.com/document_detail/42841.html). If the Alibaba Cloud account that is used to query transcoding jobs is not the one that is used to submit the transcoding jobs, no data is returned.
     * *   For information about transcoding FAQ, see [FAQ about MPS](https://help.aliyun.com/document_detail/38986.html).
     * ### [](#qps)QPS limits
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limits](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitJobsRequest $request SubmitJobsRequest
     *
     * @return SubmitJobsResponse SubmitJobsResponse
     */
    public function submitJobs($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitJobsWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a content moderation job.
     *  *
     * @description The job that you submit by calling this operation is run in asynchronous mode. The job is added to an ApsaraVideo Media Processing (MPS) queue and then scheduled, queued, and run. You can call the [QueryMediaCensorJobDetail](https://help.aliyun.com/document_detail/91779.html) operation or configure an asynchronous notification to obtain the job result.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitMediaCensorJobRequest $request SubmitMediaCensorJobRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitMediaCensorJobResponse SubmitMediaCensorJobResponse
     */
    public function submitMediaCensorJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->barrages)) {
            $query['Barrages'] = $request->barrages;
        }
        if (!Utils::isUnset($request->coverImages)) {
            $query['CoverImages'] = $request->coverImages;
        }
        if (!Utils::isUnset($request->description)) {
            $query['Description'] = $request->description;
        }
        if (!Utils::isUnset($request->externalUrl)) {
            $query['ExternalUrl'] = $request->externalUrl;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->title)) {
            $query['Title'] = $request->title;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        if (!Utils::isUnset($request->videoCensorConfig)) {
            $query['VideoCensorConfig'] = $request->videoCensorConfig;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitMediaCensorJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitMediaCensorJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a content moderation job.
     *  *
     * @description The job that you submit by calling this operation is run in asynchronous mode. The job is added to an ApsaraVideo Media Processing (MPS) queue and then scheduled, queued, and run. You can call the [QueryMediaCensorJobDetail](https://help.aliyun.com/document_detail/91779.html) operation or configure an asynchronous notification to obtain the job result.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitMediaCensorJobRequest $request SubmitMediaCensorJobRequest
     *
     * @return SubmitMediaCensorJobResponse SubmitMediaCensorJobResponse
     */
    public function submitMediaCensorJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitMediaCensorJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a media information analysis job.
     *  *
     * @description After you call the SubmitMediaInfoJob operation, ApsaraVideo Media Processing (MPS) analyzes the input media file and generates the analysis results. You can call the [QueryMediaInfoJobList](https://help.aliyun.com/document_detail/29221.html) operation to query the analysis results.
     * > We recommend that you submit a media information analysis job after you confirm that the media file is uploaded to Object Storage Service (OSS). You can configure upload callbacks to be notified of the upload status of files.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitMediaInfoJobRequest $request SubmitMediaInfoJobRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitMediaInfoJobResponse SubmitMediaInfoJobResponse
     */
    public function submitMediaInfoJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->async)) {
            $query['Async'] = $request->async;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitMediaInfoJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitMediaInfoJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a media information analysis job.
     *  *
     * @description After you call the SubmitMediaInfoJob operation, ApsaraVideo Media Processing (MPS) analyzes the input media file and generates the analysis results. You can call the [QueryMediaInfoJobList](https://help.aliyun.com/document_detail/29221.html) operation to query the analysis results.
     * > We recommend that you submit a media information analysis job after you confirm that the media file is uploaded to Object Storage Service (OSS). You can configure upload callbacks to be notified of the upload status of files.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitMediaInfoJobRequest $request SubmitMediaInfoJobRequest
     *
     * @return SubmitMediaInfoJobResponse SubmitMediaInfoJobResponse
     */
    public function submitMediaInfoJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitMediaInfoJobWithOptions($request, $runtime);
    }

    /**
     * @param SubmitSmarttagJobRequest $request SubmitSmarttagJobRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitSmarttagJobResponse SubmitSmarttagJobResponse
     */
    public function submitSmarttagJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->content)) {
            $query['Content'] = $request->content;
        }
        if (!Utils::isUnset($request->contentAddr)) {
            $query['ContentAddr'] = $request->contentAddr;
        }
        if (!Utils::isUnset($request->contentType)) {
            $query['ContentType'] = $request->contentType;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->notifyUrl)) {
            $query['NotifyUrl'] = $request->notifyUrl;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->priority)) {
            $query['Priority'] = $request->priority;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        if (!Utils::isUnset($request->title)) {
            $query['Title'] = $request->title;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitSmarttagJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitSmarttagJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @param SubmitSmarttagJobRequest $request SubmitSmarttagJobRequest
     *
     * @return SubmitSmarttagJobResponse SubmitSmarttagJobResponse
     */
    public function submitSmarttagJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitSmarttagJobWithOptions($request, $runtime);
    }

    /**
     * @summary Submits a snapshot job.
     *  *
     * @description *   Only JPG images can be generated by calling this operation.
     * *   Asynchronous mode: This operation may return a response before snapshots are captured. Snapshot jobs are queued in the background and asynchronously processed by ApsaraVideo Media Processing (MPS). If the **Interval** or **Num** parameter is set, the snapshot job is processed in asynchronous mode. For more information about FAQ about capturing snapshots, see [FAQ about taking snapshots](https://help.aliyun.com/document_detail/60805.html).
     * *   Notifications: When you submit a snapshot job, the **PipelineId** parameter is required. An asynchronous message is sent only after the notification feature is enabled for the MPS queue.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitSnapshotJobRequest $request SubmitSnapshotJobRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitSnapshotJobResponse SubmitSnapshotJobResponse
     */
    public function submitSnapshotJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->snapshotConfig)) {
            $query['SnapshotConfig'] = $request->snapshotConfig;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitSnapshotJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitSnapshotJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Submits a snapshot job.
     *  *
     * @description *   Only JPG images can be generated by calling this operation.
     * *   Asynchronous mode: This operation may return a response before snapshots are captured. Snapshot jobs are queued in the background and asynchronously processed by ApsaraVideo Media Processing (MPS). If the **Interval** or **Num** parameter is set, the snapshot job is processed in asynchronous mode. For more information about FAQ about capturing snapshots, see [FAQ about taking snapshots](https://help.aliyun.com/document_detail/60805.html).
     * *   Notifications: When you submit a snapshot job, the **PipelineId** parameter is required. An asynchronous message is sent only after the notification feature is enabled for the MPS queue.
     * ### QPS limit
     * You can call this operation up to 50 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param SubmitSnapshotJobRequest $request SubmitSnapshotJobRequest
     *
     * @return SubmitSnapshotJobResponse SubmitSnapshotJobResponse
     */
    public function submitSnapshotJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitSnapshotJobWithOptions($request, $runtime);
    }

    /**
     * @summary 提交视频溯源水印ab流任务
     *  *
     * @param SubmitTraceAbJobRequest $request SubmitTraceAbJobRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitTraceAbJobResponse SubmitTraceAbJobResponse
     */
    public function submitTraceAbJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->callBack)) {
            $query['CallBack'] = $request->callBack;
        }
        if (!Utils::isUnset($request->cipherBase64ed)) {
            $query['CipherBase64ed'] = $request->cipherBase64ed;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->level)) {
            $query['Level'] = $request->level;
        }
        if (!Utils::isUnset($request->output)) {
            $query['Output'] = $request->output;
        }
        if (!Utils::isUnset($request->startTime)) {
            $query['StartTime'] = $request->startTime;
        }
        if (!Utils::isUnset($request->totalTime)) {
            $query['TotalTime'] = $request->totalTime;
        }
        if (!Utils::isUnset($request->url)) {
            $query['Url'] = $request->url;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitTraceAbJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitTraceAbJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 提交视频溯源水印ab流任务
     *  *
     * @param SubmitTraceAbJobRequest $request SubmitTraceAbJobRequest
     *
     * @return SubmitTraceAbJobResponse SubmitTraceAbJobResponse
     */
    public function submitTraceAbJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitTraceAbJobWithOptions($request, $runtime);
    }

    /**
     * @summary 提交溯源水印提取任务
     *  *
     * @param SubmitTraceExtractJobRequest $request SubmitTraceExtractJobRequest
     * @param RuntimeOptions               $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitTraceExtractJobResponse SubmitTraceExtractJobResponse
     */
    public function submitTraceExtractJobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->callBack)) {
            $query['CallBack'] = $request->callBack;
        }
        if (!Utils::isUnset($request->input)) {
            $query['Input'] = $request->input;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->url)) {
            $query['Url'] = $request->url;
        }
        if (!Utils::isUnset($request->userData)) {
            $query['UserData'] = $request->userData;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitTraceExtractJob',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitTraceExtractJobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 提交溯源水印提取任务
     *  *
     * @param SubmitTraceExtractJobRequest $request SubmitTraceExtractJobRequest
     *
     * @return SubmitTraceExtractJobResponse SubmitTraceExtractJobResponse
     */
    public function submitTraceExtractJob($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitTraceExtractJobWithOptions($request, $runtime);
    }

    /**
     * @summary 提交视频溯源水印m3u8文件任务
     *  *
     * @param SubmitTraceM3u8JobRequest $request SubmitTraceM3u8JobRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return SubmitTraceM3u8JobResponse SubmitTraceM3u8JobResponse
     */
    public function submitTraceM3u8JobWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->keyUri)) {
            $query['KeyUri'] = $request->keyUri;
        }
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->output)) {
            $query['Output'] = $request->output;
        }
        if (!Utils::isUnset($request->params)) {
            $query['Params'] = $request->params;
        }
        if (!Utils::isUnset($request->trace)) {
            $query['Trace'] = $request->trace;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'SubmitTraceM3u8Job',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return SubmitTraceM3u8JobResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 提交视频溯源水印m3u8文件任务
     *  *
     * @param SubmitTraceM3u8JobRequest $request SubmitTraceM3u8JobRequest
     *
     * @return SubmitTraceM3u8JobResponse SubmitTraceM3u8JobResponse
     */
    public function submitTraceM3u8Job($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->submitTraceM3u8JobWithOptions($request, $runtime);
    }

    /**
     * @summary The description of the figure. The description can be up to 512 characters in length.
     *  *
     * @description The response parameters.
     *  *
     * @param TagCustomPersonRequest $request TagCustomPersonRequest
     * @param RuntimeOptions         $runtime runtime options for this request RuntimeOptions
     *
     * @return TagCustomPersonResponse TagCustomPersonResponse
     */
    public function tagCustomPersonWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->categoryDescription)) {
            $query['CategoryDescription'] = $request->categoryDescription;
        }
        if (!Utils::isUnset($request->categoryId)) {
            $query['CategoryId'] = $request->categoryId;
        }
        if (!Utils::isUnset($request->categoryName)) {
            $query['CategoryName'] = $request->categoryName;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->personDescription)) {
            $query['PersonDescription'] = $request->personDescription;
        }
        if (!Utils::isUnset($request->personId)) {
            $query['PersonId'] = $request->personId;
        }
        if (!Utils::isUnset($request->personName)) {
            $query['PersonName'] = $request->personName;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'TagCustomPerson',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return TagCustomPersonResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary The description of the figure. The description can be up to 512 characters in length.
     *  *
     * @description The response parameters.
     *  *
     * @param TagCustomPersonRequest $request TagCustomPersonRequest
     *
     * @return TagCustomPersonResponse TagCustomPersonResponse
     */
    public function tagCustomPerson($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->tagCustomPersonWithOptions($request, $runtime);
    }

    /**
     * @summary Unbinds an input media bucket from the media library.
     *  *
     * @description # Usage notes
     * You can call this operation to unbind an input media bucket from the media library based on the name of the output media bucket.
     * # QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UnbindInputBucketRequest $request UnbindInputBucketRequest
     * @param RuntimeOptions           $runtime runtime options for this request RuntimeOptions
     *
     * @return UnbindInputBucketResponse UnbindInputBucketResponse
     */
    public function unbindInputBucketWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->bucket)) {
            $query['Bucket'] = $request->bucket;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->roleArn)) {
            $query['RoleArn'] = $request->roleArn;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UnbindInputBucket',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UnbindInputBucketResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Unbinds an input media bucket from the media library.
     *  *
     * @description # Usage notes
     * You can call this operation to unbind an input media bucket from the media library based on the name of the output media bucket.
     * # QPS limits
     * You can call this API operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UnbindInputBucketRequest $request UnbindInputBucketRequest
     *
     * @return UnbindInputBucketResponse UnbindInputBucketResponse
     */
    public function unbindInputBucket($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->unbindInputBucketWithOptions($request, $runtime);
    }

    /**
     * @summary You can call this operation to unbind an output media bucket from the media library based on the name of the output media bucket.
     * ## QPS limit
     * You can call this operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @description The name of the output media bucket to be unbound. To obtain the media bucket name, you can log on to the **ApsaraVideo Media Processing (MPS)** console and choose **Workflows** > **Media Buckets** in the left-side navigation pane. Alternatively, you can log on to the **Object Storage Service (OSS) console** and click **My OSS Paths**.
     *  *
     * @param UnbindOutputBucketRequest $request UnbindOutputBucketRequest
     * @param RuntimeOptions            $runtime runtime options for this request RuntimeOptions
     *
     * @return UnbindOutputBucketResponse UnbindOutputBucketResponse
     */
    public function unbindOutputBucketWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->bucket)) {
            $query['Bucket'] = $request->bucket;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UnbindOutputBucket',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UnbindOutputBucketResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary You can call this operation to unbind an output media bucket from the media library based on the name of the output media bucket.
     * ## QPS limit
     * You can call this operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @description The name of the output media bucket to be unbound. To obtain the media bucket name, you can log on to the **ApsaraVideo Media Processing (MPS)** console and choose **Workflows** > **Media Buckets** in the left-side navigation pane. Alternatively, you can log on to the **Object Storage Service (OSS) console** and click **My OSS Paths**.
     *  *
     * @param UnbindOutputBucketRequest $request UnbindOutputBucketRequest
     *
     * @return UnbindOutputBucketResponse UnbindOutputBucketResponse
     */
    public function unbindOutputBucket($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->unbindOutputBucketWithOptions($request, $runtime);
    }

    /**
     * @summary Deletes a custom face, all the custom faces that are registered in a custom figure library, or a custom figure library.
     *  *
     * @description You can call this operation to delete a specific custom face, all the custom faces that are registered in a custom figure library, or a custom figure library.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UnregisterCustomFaceRequest $request UnregisterCustomFaceRequest
     * @param RuntimeOptions              $runtime runtime options for this request RuntimeOptions
     *
     * @return UnregisterCustomFaceResponse UnregisterCustomFaceResponse
     */
    public function unregisterCustomFaceWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->categoryId)) {
            $query['CategoryId'] = $request->categoryId;
        }
        if (!Utils::isUnset($request->faceId)) {
            $query['FaceId'] = $request->faceId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->personId)) {
            $query['PersonId'] = $request->personId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UnregisterCustomFace',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UnregisterCustomFaceResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Deletes a custom face, all the custom faces that are registered in a custom figure library, or a custom figure library.
     *  *
     * @description You can call this operation to delete a specific custom face, all the custom faces that are registered in a custom figure library, or a custom figure library.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped, and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UnregisterCustomFaceRequest $request UnregisterCustomFaceRequest
     *
     * @return UnregisterCustomFaceResponse UnregisterCustomFaceResponse
     */
    public function unregisterCustomFace($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->unregisterCustomFaceWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the basic information about a media file.
     *  *
     * @description The basic information that you can update by calling this operation includes the title, description, and category of a media file. This operation applies to a full update. You must set all the parameters unless you want to replace the value of a specific parameter with a NULL value.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaRequest $request UpdateMediaRequest
     * @param RuntimeOptions     $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaResponse UpdateMediaResponse
     */
    public function updateMediaWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->cateId)) {
            $query['CateId'] = $request->cateId;
        }
        if (!Utils::isUnset($request->coverURL)) {
            $query['CoverURL'] = $request->coverURL;
        }
        if (!Utils::isUnset($request->description)) {
            $query['Description'] = $request->description;
        }
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->tags)) {
            $query['Tags'] = $request->tags;
        }
        if (!Utils::isUnset($request->title)) {
            $query['Title'] = $request->title;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMedia',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the basic information about a media file.
     *  *
     * @description The basic information that you can update by calling this operation includes the title, description, and category of a media file. This operation applies to a full update. You must set all the parameters unless you want to replace the value of a specific parameter with a NULL value.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaRequest $request UpdateMediaRequest
     *
     * @return UpdateMediaResponse UpdateMediaResponse
     */
    public function updateMedia($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the category to which a media file belongs.
     *  *
     * @description You can call this operation to update only the category of a media file. For more information about how to update all the information about a media file, see [UpdateMedia](https://help.aliyun.com/document_detail/44464.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaCategoryRequest $request UpdateMediaCategoryRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaCategoryResponse UpdateMediaCategoryResponse
     */
    public function updateMediaCategoryWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->cateId)) {
            $query['CateId'] = $request->cateId;
        }
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMediaCategory',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaCategoryResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the category to which a media file belongs.
     *  *
     * @description You can call this operation to update only the category of a media file. For more information about how to update all the information about a media file, see [UpdateMedia](https://help.aliyun.com/document_detail/44464.html).
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaCategoryRequest $request UpdateMediaCategoryRequest
     *
     * @return UpdateMediaCategoryResponse UpdateMediaCategoryResponse
     */
    public function updateMediaCategory($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaCategoryWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the thumbnail of a media file.
     *  *
     * @description You can call this operation to update only the thumbnail of a media file. For more information about how to update all the information about a media file, see [UpdateMedia](https://help.aliyun.com/document_detail/44464.html).
     * ## Limits on QPS
     * You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param UpdateMediaCoverRequest $request UpdateMediaCoverRequest
     * @param RuntimeOptions          $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaCoverResponse UpdateMediaCoverResponse
     */
    public function updateMediaCoverWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->coverURL)) {
            $query['CoverURL'] = $request->coverURL;
        }
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMediaCover',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaCoverResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the thumbnail of a media file.
     *  *
     * @description You can call this operation to update only the thumbnail of a media file. For more information about how to update all the information about a media file, see [UpdateMedia](https://help.aliyun.com/document_detail/44464.html).
     * ## Limits on QPS
     * You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limits](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     *  *
     * @param UpdateMediaCoverRequest $request UpdateMediaCoverRequest
     *
     * @return UpdateMediaCoverResponse UpdateMediaCoverResponse
     */
    public function updateMediaCover($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaCoverWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the publishing status of a media file.
     *  *
     * @description The published state indicates that the access control list (ACL) of media playback resources and snapshot files is set to inherit the ACL of the bucket to which they belong. The unpublished state indicates that the ACL of media playback resources and snapshot files is set to private.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaPublishStateRequest $request UpdateMediaPublishStateRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaPublishStateResponse UpdateMediaPublishStateResponse
     */
    public function updateMediaPublishStateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaId)) {
            $query['MediaId'] = $request->mediaId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->publish)) {
            $query['Publish'] = $request->publish;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMediaPublishState',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaPublishStateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the publishing status of a media file.
     *  *
     * @description The published state indicates that the access control list (ACL) of media playback resources and snapshot files is set to inherit the ACL of the bucket to which they belong. The unpublished state indicates that the ACL of media playback resources and snapshot files is set to private.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaPublishStateRequest $request UpdateMediaPublishStateRequest
     *
     * @return UpdateMediaPublishStateResponse UpdateMediaPublishStateResponse
     */
    public function updateMediaPublishState($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaPublishStateWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the topology of a media workflow.
     *  *
     * @description *   You can call this operation to update the topology of a media workflow. To update the trigger mode of a media workflow, call the [UpdateMediaWorkflowTriggerMode](https://help.aliyun.com/document_detail/70372.html) operation.
     * *   After you delete or deactivate a media workflow, the workflow cannot be used. In this case, the workflow is not automatically triggered when you upload a file to the bucket specified by the workflow.
     * <warning>Deleting or deactivating a workflow will not affect tasks that have already been submitted. If a workflow is deleted or deactivated after a task has been submitted, tasks that are already in the processing queue will not be canceled and will be executed normally and charged the corresponding fees.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).>
     *  *
     * @param UpdateMediaWorkflowRequest $request UpdateMediaWorkflowRequest
     * @param RuntimeOptions             $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaWorkflowResponse UpdateMediaWorkflowResponse
     */
    public function updateMediaWorkflowWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->topology)) {
            $query['Topology'] = $request->topology;
        }
        if (!Utils::isUnset($request->triggerMode)) {
            $query['TriggerMode'] = $request->triggerMode;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMediaWorkflow',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaWorkflowResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the topology of a media workflow.
     *  *
     * @description *   You can call this operation to update the topology of a media workflow. To update the trigger mode of a media workflow, call the [UpdateMediaWorkflowTriggerMode](https://help.aliyun.com/document_detail/70372.html) operation.
     * *   After you delete or deactivate a media workflow, the workflow cannot be used. In this case, the workflow is not automatically triggered when you upload a file to the bucket specified by the workflow.
     * <warning>Deleting or deactivating a workflow will not affect tasks that have already been submitted. If a workflow is deleted or deactivated after a task has been submitted, tasks that are already in the processing queue will not be canceled and will be executed normally and charged the corresponding fees.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).>
     *  *
     * @param UpdateMediaWorkflowRequest $request UpdateMediaWorkflowRequest
     *
     * @return UpdateMediaWorkflowResponse UpdateMediaWorkflowResponse
     */
    public function updateMediaWorkflow($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaWorkflowWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the trigger mode of a media workflow.
     *  *
     * @description You can call this operation only to modify the trigger mode of a media workflow. To modify other information about the workflow, call the [UpdateMediaWorkflow](https://help.aliyun.com/document_detail/44438.html) operation.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaWorkflowTriggerModeRequest $request UpdateMediaWorkflowTriggerModeRequest
     * @param RuntimeOptions                        $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateMediaWorkflowTriggerModeResponse UpdateMediaWorkflowTriggerModeResponse
     */
    public function updateMediaWorkflowTriggerModeWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->mediaWorkflowId)) {
            $query['MediaWorkflowId'] = $request->mediaWorkflowId;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->triggerMode)) {
            $query['TriggerMode'] = $request->triggerMode;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateMediaWorkflowTriggerMode',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateMediaWorkflowTriggerModeResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the trigger mode of a media workflow.
     *  *
     * @description You can call this operation only to modify the trigger mode of a media workflow. To modify other information about the workflow, call the [UpdateMediaWorkflow](https://help.aliyun.com/document_detail/44438.html) operation.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateMediaWorkflowTriggerModeRequest $request UpdateMediaWorkflowTriggerModeRequest
     *
     * @return UpdateMediaWorkflowTriggerModeResponse UpdateMediaWorkflowTriggerModeResponse
     */
    public function updateMediaWorkflowTriggerMode($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateMediaWorkflowTriggerModeWithOptions($request, $runtime);
    }

    /**
     * @summary Updates an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @description *   You can call this operation to modify the name, status, and notification settings of the specified MPS queue.
     * *   If a paused MPS queue is selected in a workflow or a job, such as a video review or media fingerprint job, the workflow or job fails.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdatePipelineRequest $request UpdatePipelineRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdatePipelineResponse UpdatePipelineResponse
     */
    public function updatePipelineWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->extendConfig)) {
            $query['ExtendConfig'] = $request->extendConfig;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->notifyConfig)) {
            $query['NotifyConfig'] = $request->notifyConfig;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->pipelineId)) {
            $query['PipelineId'] = $request->pipelineId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->role)) {
            $query['Role'] = $request->role;
        }
        if (!Utils::isUnset($request->state)) {
            $query['State'] = $request->state;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdatePipeline',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdatePipelineResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates an ApsaraVideo Media Processing (MPS) queue.
     *  *
     * @description *   You can call this operation to modify the name, status, and notification settings of the specified MPS queue.
     * *   If a paused MPS queue is selected in a workflow or a job, such as a video review or media fingerprint job, the workflow or job fails.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdatePipelineRequest $request UpdatePipelineRequest
     *
     * @return UpdatePipelineResponse UpdatePipelineResponse
     */
    public function updatePipeline($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updatePipelineWithOptions($request, $runtime);
    }

    /**
     * @summary 更新智能标签模板接口支持cpv
     *  *
     * @param UpdateSmarttagTemplateRequest $request UpdateSmarttagTemplateRequest
     * @param RuntimeOptions                $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateSmarttagTemplateResponse UpdateSmarttagTemplateResponse
     */
    public function updateSmarttagTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->analyseTypes)) {
            $query['AnalyseTypes'] = $request->analyseTypes;
        }
        if (!Utils::isUnset($request->faceCategoryIds)) {
            $query['FaceCategoryIds'] = $request->faceCategoryIds;
        }
        if (!Utils::isUnset($request->faceCustomParamsConfig)) {
            $query['FaceCustomParamsConfig'] = $request->faceCustomParamsConfig;
        }
        if (!Utils::isUnset($request->industry)) {
            $query['Industry'] = $request->industry;
        }
        if (!Utils::isUnset($request->isDefault)) {
            $query['IsDefault'] = $request->isDefault;
        }
        if (!Utils::isUnset($request->keywordConfig)) {
            $query['KeywordConfig'] = $request->keywordConfig;
        }
        if (!Utils::isUnset($request->knowledgeConfig)) {
            $query['KnowledgeConfig'] = $request->knowledgeConfig;
        }
        if (!Utils::isUnset($request->labelType)) {
            $query['LabelType'] = $request->labelType;
        }
        if (!Utils::isUnset($request->labelVersion)) {
            $query['LabelVersion'] = $request->labelVersion;
        }
        if (!Utils::isUnset($request->landmarkGroupIds)) {
            $query['LandmarkGroupIds'] = $request->landmarkGroupIds;
        }
        if (!Utils::isUnset($request->objectGroupIds)) {
            $query['ObjectGroupIds'] = $request->objectGroupIds;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->scene)) {
            $query['Scene'] = $request->scene;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        if (!Utils::isUnset($request->templateName)) {
            $query['TemplateName'] = $request->templateName;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateSmarttagTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateSmarttagTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary 更新智能标签模板接口支持cpv
     *  *
     * @param UpdateSmarttagTemplateRequest $request UpdateSmarttagTemplateRequest
     *
     * @return UpdateSmarttagTemplateResponse UpdateSmarttagTemplateResponse
     */
    public function updateSmarttagTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateSmarttagTemplateWithOptions($request, $runtime);
    }

    /**
     * @description A custom transcoding template cannot be updated if it is being used by a job that has been submitted.The ID of the template. You can obtain the template ID from the response of the [AddTemplate](https://help.aliyun.com/document_detail/213306.html) operation.
     * ### QPS limits
     * You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateTemplateRequest $request UpdateTemplateRequest
     * @param RuntimeOptions        $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateTemplateResponse UpdateTemplateResponse
     */
    public function updateTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->audio)) {
            $query['Audio'] = $request->audio;
        }
        if (!Utils::isUnset($request->container)) {
            $query['Container'] = $request->container;
        }
        if (!Utils::isUnset($request->muxConfig)) {
            $query['MuxConfig'] = $request->muxConfig;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->templateId)) {
            $query['TemplateId'] = $request->templateId;
        }
        if (!Utils::isUnset($request->transConfig)) {
            $query['TransConfig'] = $request->transConfig;
        }
        if (!Utils::isUnset($request->video)) {
            $query['Video'] = $request->video;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @description A custom transcoding template cannot be updated if it is being used by a job that has been submitted.The ID of the template. You can obtain the template ID from the response of the [AddTemplate](https://help.aliyun.com/document_detail/213306.html) operation.
     * ### QPS limits
     * You can call this operation up to 100 times per second. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateTemplateRequest $request UpdateTemplateRequest
     *
     * @return UpdateTemplateResponse UpdateTemplateResponse
     */
    public function updateTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateTemplateWithOptions($request, $runtime);
    }

    /**
     * @summary Updates the name and configurations of the specified watermark template.
     *  *
     * @description *   You can call this operation to update the information about a watermark template based on the ID of the watermark template. For example, you can update the name and configurations of a watermark template.
     * *   A watermark template cannot be updated if it is being used by a job that has been submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateWaterMarkTemplateRequest $request UpdateWaterMarkTemplateRequest
     * @param RuntimeOptions                 $runtime runtime options for this request RuntimeOptions
     *
     * @return UpdateWaterMarkTemplateResponse UpdateWaterMarkTemplateResponse
     */
    public function updateWaterMarkTemplateWithOptions($request, $runtime)
    {
        Utils::validateModel($request);
        $query = [];
        if (!Utils::isUnset($request->config)) {
            $query['Config'] = $request->config;
        }
        if (!Utils::isUnset($request->name)) {
            $query['Name'] = $request->name;
        }
        if (!Utils::isUnset($request->ownerAccount)) {
            $query['OwnerAccount'] = $request->ownerAccount;
        }
        if (!Utils::isUnset($request->ownerId)) {
            $query['OwnerId'] = $request->ownerId;
        }
        if (!Utils::isUnset($request->resourceOwnerAccount)) {
            $query['ResourceOwnerAccount'] = $request->resourceOwnerAccount;
        }
        if (!Utils::isUnset($request->resourceOwnerId)) {
            $query['ResourceOwnerId'] = $request->resourceOwnerId;
        }
        if (!Utils::isUnset($request->waterMarkTemplateId)) {
            $query['WaterMarkTemplateId'] = $request->waterMarkTemplateId;
        }
        $req = new OpenApiRequest([
            'query' => OpenApiUtilClient::query($query),
        ]);
        $params = new Params([
            'action'      => 'UpdateWaterMarkTemplate',
            'version'     => '2014-06-18',
            'protocol'    => 'HTTPS',
            'pathname'    => '/',
            'method'      => 'POST',
            'authType'    => 'AK',
            'style'       => 'RPC',
            'reqBodyType' => 'formData',
            'bodyType'    => 'json',
        ]);

        return UpdateWaterMarkTemplateResponse::fromMap($this->callApi($params, $req, $runtime));
    }

    /**
     * @summary Updates the name and configurations of the specified watermark template.
     *  *
     * @description *   You can call this operation to update the information about a watermark template based on the ID of the watermark template. For example, you can update the name and configurations of a watermark template.
     * *   A watermark template cannot be updated if it is being used by a job that has been submitted.
     * ### QPS limit
     * You can call this operation up to 100 times per second per account. Requests that exceed this limit are dropped and you will experience service interruptions. We recommend that you take note of this limit when you call this operation. For more information, see [QPS limit](https://help.aliyun.com/document_detail/342832.html).
     *  *
     * @param UpdateWaterMarkTemplateRequest $request UpdateWaterMarkTemplateRequest
     *
     * @return UpdateWaterMarkTemplateResponse UpdateWaterMarkTemplateResponse
     */
    public function updateWaterMarkTemplate($request)
    {
        $runtime = new RuntimeOptions([]);

        return $this->updateWaterMarkTemplateWithOptions($request, $runtime);
    }
}
