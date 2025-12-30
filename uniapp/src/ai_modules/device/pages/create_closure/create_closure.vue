<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            :title="createType === CreateTypeEnum.COMMENT ? '评论获客' : '私信获客'"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-4 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="common-step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <scroll-view class="h-full" scroll-y v-if="step === 1">
                <view class="px-4 pb-[100rpx]">
                    <view class="font-bold text-[30rpx]"> 获客行业 </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-[40rpx] py-[30rpx]">
                        <view class="flex items-center gap-x-2">
                            <view class="flex-1 bg-[#F3F3F3] rounded-[10rpx] h-[80rpx] flex items-center px-4">
                                <u-input
                                    class="w-full"
                                    v-model="industryInput"
                                    placeholder="请输入，如：服装设计、女装"
                                    maxlength="100"
                                    clearable
                                    placeholder-style="font-size: 26rpx;" />
                            </view>
                            <view
                                class="w-[160rpx] h-[80rpx] flex items-center justify-center bg-black rounded-[10rpx] text-white font-bold"
                                @click="handleAddIndustry"
                                >添加</view
                            >
                        </view>
                        <view class="mt-[30rpx] bg-[#F3F3F3] rounded-[16rpx]">
                            <scroll-view
                                class="max-h-[300rpx]"
                                ref="industryScrollViewRef"
                                scroll-y
                                :scroll-into-view="scrollToIndustryId"
                                scroll-with-animation="true"
                                v-if="formData.industry.length > 0">
                                <view class="flex flex-wrap gap-2 p-[24rpx]">
                                    <view
                                        v-for="(item, index) in formData.industry"
                                        :key="index"
                                        :id="'industry_' + index"
                                        class="bg-white rounded-[50rpx] px-[24rpx] relative py-[10rpx] text-xs"
                                        @click="handleEditClue(index)">
                                        {{ item }}
                                        <view
                                            class="absolute right-[-10rpx] top-[-10rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                            @click.stop="handleDeleteClue(index)">
                                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </scroll-view>
                            <view v-else class="px-[24rpx] py-[50rpx] text-[#0000004d] text-center">
                                输入或AI生成获客行业
                            </view>
                            <view class="p-[24rpx]">
                                <view
                                    class="w-[160rpx] h-[64rpx] flex items-center justify-center gap-1 bg-white rounded-[10rpx]"
                                    @click="showClueGenPopup = true">
                                    <image
                                        src="@/ai_modules/device/static/icons/gen.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>
                                    <text class="font-bold text-primary">AI生成 </text>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[30rpx]" v-if="historyIndustry.length > 0">
                            <view class="flex items-center justify-between">
                                <view class="font-bold">历史记录</view>
                                <view class="font-bold" @click="showHistoryIndustryPopup = true">
                                    更多
                                    <u-icon name="arrow-right" color="#B2B2B2" size="24"></u-icon>
                                </view>
                            </view>
                            <view class="mt-[20rpx]">
                                <view class="flex flex-wrap gap-[20rpx]">
                                    <view
                                        v-for="(item, index) in historyIndustry.slice(0, 5)"
                                        :key="index"
                                        class="rounded-full px-[24rpx] relative py-[10rpx] shadow-[0_0_0_2rpx_#0000001a]"
                                        @click="handleSelectHistoryIndustry(item.keyword)">
                                        {{ item.keyword }}
                                        <view
                                            class="absolute right-[-14rpx] top-[-14rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                            @click="handleDeleteHistoryIndustry(index)">
                                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="text-[30rpx] font-bold">每个行业看笔记的数量</view>
                        <view class="mt-[28rpx]">
                            <view class="flex flex-wrap gap-[20rpx]">
                                <view
                                    v-for="(item, index) in industryNumList"
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-bold text-[#00000080]"
                                    :key="index"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            formData.industryNum === item &&
                                            industryNumState.currentIndex !== industryNumList.length,
                                    }"
                                    @click="industryNumState.handleSelect(item, index)">
                                    {{ item }}条
                                </view>
                                <view
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-bold text-[#00000080]"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            industryNumState.currentIndex === industryNumList.length,
                                    }"
                                    @click="industryNumState.openCustom">
                                    {{
                                        industryNumState.currentIndex === industryNumList.length
                                            ? `${formData.industryNum}条`
                                            : industryNumState.savedCustomValue
                                            ? `${industryNumState.savedCustomValue}条`
                                            : "自定义"
                                    }}
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view class="h-full" scroll-y v-if="step === 2">
                <view class="px-4 pb-[100rpx]">
                    <view>
                        <view class="font-bold text-[30rpx]"> 评论词筛选 </view>
                        <view
                            class="mt-[36rpx] bg-white rounded-[20rpx] px-[40rpx] py-[24rpx]"
                            v-if="formData.comment_filter_list.length > 0">
                            <view class="font-bold">包含以下关键词</view>
                            <view class="flex flex-wrap gap-2 mt-[24rpx]">
                                <view
                                    v-for="(item, index) in formData.comment_filter_list"
                                    :key="index"
                                    class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2 break-all">
                                    {{ item.value }}
                                    <view
                                        class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleCommentFilterDelete(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] py-[12rpx] flex items-center justify-center gap-x-1"
                                    @click="openCommentFilterEdit">
                                    <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                    <text class="text-primary font-bold">编辑</text>
                                </view>
                            </view>
                        </view>
                        <view v-else class="mt-10">
                            <view
                                class="border border-solid rounded-[20rpx] w-fit px-4 h-[88rpx] flex items-center justify-center mx-auto"
                                @click="openCommentFilterEdit">
                                <u-icon name="plus" size="20"></u-icon>
                                <text class="font-bold ml-1">添加评论词筛选</text>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="font-bold text-[30rpx]"> {{ isComment ? "评论内容" : "私信内容" }} (随机) </view>
                        <view
                            class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[28rpx] flex flex-wrap gap-2"
                            v-if="formData.comment_content_list.length > 0">
                            <view
                                v-for="(item, index) in formData.comment_content_list"
                                :key="index"
                                class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 h-[60rpx] flex items-center gap-x-2 break-all"
                                @click="handleEditCommentContent(index)">
                                {{ item }}
                                <view
                                    class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click.stop="handleCommentContentDelete(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] h-[60rpx] flex items-center justify-center"
                                @click="handleEditCommentContent(-1)">
                                <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                <text class="text-primary font-bold ml-1">添加</text>
                            </view>
                        </view>
                        <view v-else class="mt-10">
                            <view
                                class="border border-solid rounded-[20rpx] w-fit px-4 h-[88rpx] flex items-center justify-center mx-auto"
                                @click="handleEditCommentContent(-1)">
                                <u-icon name="plus" size="20"></u-icon>
                                <text class="font-bold ml-1">添加{{ isComment ? "评论" : "私信" }}内容</text>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="text-[30rpx] font-bold">{{ isComment ? "评论数量上限" : "私信数量上限" }}</view>
                        <view class="mt-[28rpx]">
                            <view class="flex flex-wrap gap-[20rpx]">
                                <view
                                    v-for="(item, index) in commentNumList"
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-bold text-[#00000080]"
                                    :key="index"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            formData.commentNum === item &&
                                            commentNumState.currentIndex !== commentNumList.length,
                                    }"
                                    @click="commentNumState.handleSelect(item, index)">
                                    {{ item }}条
                                </view>
                                <view
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-bold text-[#00000080]"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            commentNumState.currentIndex === commentNumList.length,
                                    }"
                                    @click="commentNumState.openCustom">
                                    {{
                                        commentNumState.currentIndex === commentNumList.length
                                            ? `${formData.commentNum}条`
                                            : commentNumState.savedCustomValue
                                            ? `${commentNumState.savedCustomValue}条`
                                            : "自定义"
                                    }}
                                </view>
                            </view>
                        </view>
                        <view class="text-xs text-[#00000080] mt-4" v-if="!isComment">
                            建议：私信数量不要超过8个/天/账号
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view class="h-full" scroll-y v-if="step === 3">
                <view class="px-4 pb-[100rpx]">
                    <view class="font-bold text-[30rpx]">高级设置</view>
                    <view class="mt-[20rpx] rounded-[20rpx] bg-white px-[36rpx]">
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <text class="font-bold">{{ isComment ? "评论附带点赞" : "私信附带点赞" }}</text>
                            <u-switch v-model="formData.comment_like" active-value="1" inactive-value="0" :size="40" />
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <text class="font-bold">{{ isComment ? "评论附带关注" : "私信附带关注" }}</text>
                            <u-switch
                                v-model="formData.comment_follow"
                                active-value="1"
                                inactive-value="0"
                                :size="40" />
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="showCommentTimePopup = true">
                            <text class="font-bold flex-shrink-0">{{ isComment ? "评论时间" : "私信时间" }}</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="formData.comment_time > -1 ? 'text-primary font-bold' : 'text-[#B2B2B2]'"
                                    >{{ getCommentTimeLabel || "请选择" }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="showChooseRegionPopup = true">
                            <text class="font-bold flex-shrink-0">地区筛选</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="formData.comment_region ? 'text-primary font-bold' : 'text-[#B2B2B2]'"
                                    >{{ formData.comment_region || "请选择" }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="handleEditCommentGender">
                            <text class="font-bold flex-shrink-0">用户性别</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="font-bold line-clamp-1 break-all"
                                    :class="formData.comment_gender ? 'text-primary font-bold' : 'text-[#B2B2B2]'"
                                    >{{ formData.comment_gender || "请选择" }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="handleEditCommentAge">
                            <text class="font-bold flex-shrink-0">用户年龄</text>
                            <view class="flex items-center gap-x-1">
                                <text class="line-clamp-1 break-all text-primary font-bold">{{
                                    formData.comment_age
                                }}</text>
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] gap-2"
                            @click="handleEditCommentAccountFeature">
                            <text class="font-bold flex-shrink-0">账号特征</text>
                            <view class="flex items-center gap-x-1">
                                <text class="line-clamp-1 break-all text-primary font-bold">{{
                                    formData.comment_account_feature == "0" ? "全部" : "跳过认证号"
                                }}</text>
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view v-if="step === 4" scroll-y class="h-full">
                <view class="px-4 pb-[100rpx]">
                    <bast-setting-v2
                        v-model="formData"
                        :show-device="false"
                        :show-accounts="true"
                        :current-frequency="currentFrequency"
                        :platform-types="[AppTypeEnum.XHS, AppTypeEnum.DOUYIN]"
                        @change-frequency="currentFrequency = $event" />

                    <view class="mt-[50rpx]" v-if="taskErrorMsg">
                        <view class="font-bold">任务冲突：</view>
                        <view class="text-font-bold text-[#ff2442] text-xs mt-[20rpx]">
                            {{ taskErrorMsg }}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center px-4 h-[140rpx]" :class="step === 1 ? 'justify-end' : 'justify-between'">
                <template v-if="step != steps.length">
                    <view
                        v-if="step !== 1"
                        class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                        @click="handleStep(step, 'prev')">
                        上一步
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-primary' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-bold flex items-center justify-center"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>

    <u-popup v-model="commonPopup.show" mode="center" width="90%" :border-radius="20" @close="commonPopup.show = false">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">{{ commonPopup.title }}</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="commonPopup.inputValue"
                    placeholder="请输入"
                    type="digit"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="commonPopup.show = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleCommonPopupConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>

    <clue-gen-pop v-model="showClueGenPopup" @confirm="handleClueGenConfirm" />
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        :title="getKeywordsTitle"
        @confirm="handleKeywordsEditConfirm" />
    <comment-filter v-model="showCommentFilterEdit" @confirm="handleCommentFilterConfirm" />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
    <choose-region v-model="showChooseRegionPopup" @confirm="handleChooseRegionConfirm" />
    <choose-age ref="chooseAgeRef" v-model="showAgePopup" @confirm="handleAgeConfirm" />
    <choose-comment-time
        v-model="showCommentTimePopup"
        :value="formData.comment_time_index"
        :list="commentTimeList"
        @confirm="handleCommentTimeConfirm" />
    <popup-bottom
        v-model="showHistoryIndustryPopup"
        title="获客行业记录"
        :is-disabled-touch="true"
        @close="showHistoryIndustryPopup = false">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="industryHistoryPagingRef"
                    v-model="historyIndustry"
                    :fixed="false"
                    @query="getIndustryHistory">
                    <view class="flex flex-wrap gap-[20rpx] p-4">
                        <view
                            v-for="(item, index) in historyIndustry"
                            :key="index"
                            class="rounded-full px-[24rpx] relative py-[10rpx] shadow-[0_0_0_2rpx_#0000001a]"
                            @click="handleSelectHistoryIndustry(item.keyword)">
                            {{ item.keyword }}
                            <view
                                class="absolute right-[-10rpx] top-[-10rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                @click="handleDeleteHistoryIndustry(index)">
                                <u-icon name="close" color="#ffffff" size="16"></u-icon>
                            </view>
                        </view> </view
                ></z-paging>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { createClosureTask, getClosureIndustryHistory, deleteClosureIndustryHistory } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, CreateTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ClueGenPop from "@/ai_modules/device/components/clue-gen-pop/clue-gen-pop.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import CommentFilter from "@/ai_modules/device/components/comment-filter/comment-filter.vue";
import BastSettingV2 from "@/ai_modules/device/components/bast-setting-v2/bast-setting-v2.vue";
import ChooseRegion from "@/ai_modules/device/components/choose-region/choose-region.vue";
import ChooseAge from "@/ai_modules/device/components/choose-age/choose-age.vue";
import ChooseCommentTime from "@/ai_modules/device/components/choose-comment-time/choose-comment-time.vue";

const { on } = useEventBusManager();

const createType = ref<CreateTypeEnum>(CreateTypeEnum.COMMENT);

// 步骤
const steps = ref([
    { step: 1, title: "选择行业" },
    { step: 2, title: "设定评论" },
    { step: 3, title: "高级设置" },
    { step: 4, title: "设定时间" },
]);
const step = ref(1);

const formData = reactive<{
    name: string;
    industry: string[];
    industryNum: number;
    commentNum: number;
    comment_filter_list: { value: string; checked: boolean; id: number }[];
    comment_content_list: string[];
    comment_like: string;
    comment_follow: string;
    comment_time_index: number[];
    comment_region: string;
    comment_gender: string;
    comment_age: string;
    comment_account_feature: string;
    comment_time: number;
    accounts: string[];
    task_frep: number;
    custom_date: string[];
    time_config: string[];
}>({
    name: "",
    industry: [],
    industryNum: 1,
    commentNum: 1,
    comment_filter_list: [],
    comment_content_list: [],
    comment_like: "1",
    comment_follow: "1",
    comment_time_index: [0],
    comment_region: "不限",
    comment_gender: "不限",
    comment_age: "不限",
    comment_account_feature: "0",
    comment_time: 0,
    accounts: [],
    task_frep: 1,
    custom_date: [],
    time_config: ["09:00", "09:30"],
});

const isComment = computed(() => createType.value === CreateTypeEnum.COMMENT);

const industryHistory = ref<string[]>([]);
const industryHistoryPagingRef = shallowRef();

const showKeywordsEdit = ref(false);
const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();
const keywordsEditType = ref<"clue" | "comment" | "comment_content">("clue");
const keywordsEditIndex = ref<number>(-1);

const showCommentTimePopup = ref(false);
const commentTimeList = [
    { value: 0, label: "不限" },
    { value: 1, label: "24小时内" },
    { value: 2, label: "2天内" },
    { value: 3, label: "3天内" },
    { value: 4, label: "4天内" },
    { value: 5, label: "5天内" },
    { value: 6, label: "6天内" },
    { value: 7, label: "7天内" },
];

const showClueGenPopup = ref(false);
const historyIndustry = ref<any[]>([]);
const showHistoryIndustryPopup = ref(false);

const industryInput = ref<string>("");
const scrollToIndustryId = ref<string>("");
const showCommentFilterEdit = ref<boolean>(false);

const showAgePopup = ref(false);
const chooseAgeRef = ref<InstanceType<typeof ChooseAge>>();

const currentFrequency = ref(0);
const showChooseRegionPopup = ref(false);
const showCreateTaskSuccessDialog = ref(false);
const taskErrorMsg = ref<string>("");

const commonPopup = reactive({
    show: false,
    title: "",
    inputValue: 1,
    callback: null as ((val: number) => void) | null,
});

const getKeywordsTitle = computed(() => {
    const titles = {
        clue: "线索词",
        comment: "评论词",
        comment_content: "评论内容",
    };
    return titles[keywordsEditType.value];
});

const getCommentTimeLabel = computed(() => {
    return formData.comment_time > -1 ? commentTimeList[formData.comment_time_index[0]].label : "请选择";
});

const handleSelectHistoryIndustry = (keyword: string) => {
    if (formData.industry.includes(keyword)) {
        uni.$u.toast("已存在");
        return;
    }
    formData.industry.push(keyword);
    showHistoryIndustryPopup.value = false;
};

const handleCommentTimeConfirm = (res: any) => {
    formData.comment_time_index = res;
    formData.comment_time = commentTimeList[formData.comment_time_index[0]].value;
};

const handleEditCommentAge = () => {
    chooseAgeRef.value?.setFormData(formData.comment_age);
    showAgePopup.value = true;
};

const handleAgeConfirm = (data: string) => {
    formData.comment_age = data;
    showAgePopup.value = false;
};

const handleCommonPopupConfirm = () => {
    const val = Number(commonPopup.inputValue);
    if (val < 1) {
        uni.$u.toast("请输入大于等于1的数字");
        return;
    }
    if (commonPopup.callback) {
        commonPopup.callback(val);
    }
    commonPopup.show = false;
};

function useNumSelection(list: number[], initialVal: number, title: string, updateFn: (v: number) => void) {
    const currentIndex = ref(list.indexOf(initialVal));
    const savedCustomValue = ref<number | null>(null);

    const handleSelect = (item: number, index: number) => {
        currentIndex.value = index;
        updateFn(item);
    };

    const openCustom = () => {
        commonPopup.title = title;
        commonPopup.inputValue = savedCustomValue.value || 1;
        commonPopup.callback = (val) => {
            updateFn(val);
            savedCustomValue.value = val;
            currentIndex.value = list.length;
        };
        commonPopup.show = true;
    };
    return reactive({
        currentIndex,
        savedCustomValue,
        handleSelect,
        openCustom,
    });
}

const industryNumList = [1, 3, 5, 10, 20];
const commentNumList = [1, 3, 5, 10, 20];

const industryNumState = useNumSelection(
    industryNumList,
    formData.industryNum,
    "输入每个行业看笔记的数量",
    (val) => (formData.industryNum = val)
);

const commentNumState = useNumSelection(
    commentNumList,
    formData.commentNum,
    "输入评论数量上限",
    (val) => (formData.commentNum = val)
);

const canNext = computed(() => canStepProceed(step.value));
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.industry.length > 0;
        case 2:
            return formData.comment_filter_list.length > 0 && formData.comment_content_list.length > 0;
        case 3:
            return true;
        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请至少添加一个线索",
                3: "请设定时间",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }

    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleAddIndustry = () => {
    if (industryInput.value.trim() === "") {
        uni.$u.toast("请输入获客行业");
        return;
    }
    if (formData.industry.includes(industryInput.value)) {
        uni.$u.toast("已存在");
        return;
    }
    formData.industry.push(industryInput.value);
    industryInput.value = "";
    nextTick(() => {
        scrollToIndustryId.value = "industry_" + (formData.industry.length - 1);
    });
};

const handleKeywordsEditConfirm = (data: any) => {
    if (keywordsEditType.value === "clue") {
        if (keywordsEditIndex.value == -1) {
            formData.industry.push(data);
        } else {
            formData.industry[keywordsEditIndex.value] = data;
        }
    } else if (keywordsEditType.value === "comment") {
        if (keywordsEditIndex.value == -1) {
            formData.comment_filter_list.push(data);
        } else {
            formData.comment_filter_list[keywordsEditIndex.value] = data;
        }
    } else if (keywordsEditType.value === "comment_content") {
        if (keywordsEditIndex.value == -1) {
            formData.comment_content_list.push(data);
        } else {
            formData.comment_content_list[keywordsEditIndex.value] = data;
        }
    }
    showKeywordsEdit.value = false;
};

const handleEditClue = (index: number) => {
    keywordsEditIndex.value = index;
    keywordsEditType.value = "clue";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.industry[index]);
};

const handleClueGenConfirm = (clueList: any[]) => {
    formData.industry.push(...clueList);
    showClueGenPopup.value = false;
};

const handleDeleteClue = (index: number) => {
    formData.industry.splice(index, 1);
};

const handleDeleteHistoryIndustry = async (index: number) => {
    uni.showLoading({
        title: "删除中...",
        mask: true,
    });
    try {
        await deleteClosureIndustryHistory({
            id: historyIndustry.value[index].id,
        });
        historyIndustry.value.splice(index, 1);
        uni.hideLoading();
        uni.showToast({
            title: "删除成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.showToast({
            title: error || "删除失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const openCommentFilterEdit = () => {
    showCommentFilterEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_filter_list);
};

const handleCommentFilterDelete = (index: number) => {
    formData.comment_filter_list.splice(index, 1);
};

const handleCommentFilterConfirm = (data: any) => {
    formData.comment_filter_list = data;
};

const handleEditCommentContent = (index: number) => {
    keywordsEditIndex.value = index;
    keywordsEditType.value = "comment_content";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_content_list[index]);
};

const handleCommentContentDelete = (index: number) => {
    formData.comment_content_list.splice(index, 1);
};

const handleChooseRegionConfirm = (data: any) => {
    if (data.isAll || data.regionList.length === 0) {
        formData.comment_region = "不限";
    } else {
        formData.comment_region = data.regionList.join(";");
    }
    showChooseRegionPopup.value = false;
};

const handleEditCommentGender = () => {
    const genderList = ["不限", "男", "女"];
    uni.showActionSheet({
        itemList: genderList,
        success: (res: any) => {
            formData.comment_gender = genderList[res.tapIndex];
        },
    });
};

const handleEditCommentAccountFeature = () => {
    uni.showActionSheet({
        itemList: ["全部", "跳过认证号"],
        success: (res: any) => {
            if (res.tapIndex === 0) {
                formData.comment_account_feature = "0";
            } else {
                formData.comment_account_feature = "1";
            }
        },
    });
};

const handleCreateTask = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    if (!formData.accounts.length) {
        uni.$u.toast("请选择发布账号");
        return;
    }
    if (currentFrequency.value === 5 && !formData.custom_date.length) {
        uni.$u.toast("请选择任务日期");
        return;
    }
    if (!formData.time_config[0] || !formData.time_config[1]) {
        uni.$u.toast("请选择任务时间");
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createClosureTask({
            name: formData.name,
            accounts: formData.accounts,
            task_frep: formData.task_frep,
            task_type: createType.value == CreateTypeEnum.COMMENT ? 1 : 2,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            task_date: formData.custom_date,
            industry: formData.industry.join(";"),
            industry_num: formData.industryNum,
            filter: formData.comment_filter_list.map((item: any) => item.value),
            content: formData.comment_content_list,
            send_num: formData.commentNum,
            is_like: formData.comment_like,
            is_follow: formData.comment_follow,
            send_time: formData.comment_time,
            gender: formData.comment_gender,
            old: formData.comment_age,
            region: formData.comment_region,
            account_feature: formData.comment_account_feature,
        });
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
};

const getIndustryHistory = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getClosureIndustryHistory({
            task_type: createType.value == CreateTypeEnum.COMMENT ? 1 : 2,
            page_no,
            page_size,
        });
        industryHistoryPagingRef.value?.complete(lists);
    } catch (error) {
        industryHistoryPagingRef.value?.complete([]);
    }
};

onLoad((options: any) => {
    createType.value = options.type as CreateTypeEnum;
    formData.name = `${createType.value == CreateTypeEnum.COMMENT ? "评论" : "私信"}获客任务${uni.$u.timeFormat(
        new Date(),
        "yyyymmddhhMM"
    )}`;
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) return;
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) return;
            formData.accounts = data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }));
        }
    });
});
</script>
