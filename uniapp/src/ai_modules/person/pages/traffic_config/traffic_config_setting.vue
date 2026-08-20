<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[200rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="获客与截流设置" title-bold>
        </u-navbar>

        <view class="px-[30rpx] pt-2">
            <template v-if="loading">
                <view v-for="i in 4" :key="i" class="bg-white rounded-[32rpx] p-5 mb-4 animate-pulse">
                    <view class="flex items-center gap-3 mb-4">
                        <view class="w-[64rpx] h-[64rpx] rounded-[16rpx] bg-[#F3F4F6]"></view>
                        <view class="flex flex-col gap-2 flex-1">
                            <view class="h-[30rpx] w-1/3 bg-[#F3F4F6] rounded-full"></view>
                            <view class="h-[22rpx] w-2/3 bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                    <view class="flex gap-2">
                        <view class="h-[56rpx] w-[120rpx] bg-[#F3F4F6] rounded-full"></view>
                        <view class="h-[56rpx] w-[160rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                </view>
            </template>

            <template v-else>
                <view class="flex items-center gap-2 mb-4 px-2">
                    <u-icon name="account-fill" color="#9CA3AF" size="28"></u-icon>
                    <text class="text-[#999999]">当前IP：</text>
                    <text class="font-bold text-primary">{{ personName }}</text>
                </view>
                <setting-sph-account-leads
                    v-if="trafficType === TrafficConfigType.SPH"
                    :config-data="configData"
                    @add="handleAdd(KeyWordsType.AcquisitionWords)"
                    @edit="handleEdit(KeyWordsType.AcquisitionWords, $event)"
                    @remove="removeTag(KeyWordsType.AcquisitionWords, $event)"
                    @input="handleInput(ConfigKeyType.AcquisitionLimit)" />
                <setting-video-intercept
                    v-if="trafficType === TrafficConfigType.Video"
                    :config-data="configData"
                    @add="(type) => handleAdd(type)"
                    @edit="(type, index) => handleEdit(type, index)"
                    @remove="(type, index) => removeTag(type, index)" />

                <setting-group-buy-intercept
                    v-if="trafficType === TrafficConfigType.GroupPurchase"
                    :config-data="configData"
                    @edit="handleEdit(KeyWordsType.GrouponCommentKeywords, $event)"
                    @edit-nickname-filter="handleEdit(KeyWordsType.GrouponNicknameFilters, $event)" />
                <setting-nearby-intercept
                    v-if="trafficType === TrafficConfigType.City"
                    :config-data="configData"
                    @edit-nickname-filter="handleEdit(KeyWordsType.CityCommentKeywords, $event)" />
                <setting-anti-block-frequency
                    v-if="trafficType === TrafficConfigType.Guard"
                    :config-data="configData" />
            </template>
        </view>

        <view class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-2 z-50">
            <u-button
                type="primary"
                shape="circle"
                :ripple="true"
                :loading="saving"
                :disabled="loading"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                }"
                @click="handleSave">
                保存配置
            </u-button>
            <view class="text-center mt-2.5">
                <text class="text-[22rpx] text-[#b4b4b4] flex items-center justify-center gap-1">
                    <u-icon name="checkmark-circle-fill" color="#D1D5DB" size="24"></u-icon>
                    配置自动同步至关联设备
                </text>
            </view>
        </view>
    </view>

    <keywords-edit
        ref="keywordsEditRef"
        v-model="keywordsEditVisible"
        :title="keywordsTitle"
        :maxlength="keywordsType == KeyWordsType.DmScripts ? 200 : 100"
        @confirm="handleKeywordsConfirm" />
</template>

<script setup lang="ts">
import { getPersonDetail, getTrafficConfig, updateTrafficConfig } from "@/api/person";
import keywordsEdit from "@/ai_modules/person/components/keywords-edit/keywords-edit.vue";
import {
    ConfigData,
    defaultConfigData,
    TrafficConfigType,
    ConfigKeyType,
    KeyWordsType,
    LikeType,
    TITLE_MAP,
    GrouponTab,
    GrouponAction,
} from "./components/type";
import SettingAntiBlockFrequency from "./components/setting-anti-block-frequency.vue";
import SettingGroupBuyIntercept from "./components/setting-group-buy-intercept.vue";
import SettingSphAccountLeads from "./components/setting-sph-account-leads.vue";
import SettingVideoIntercept from "./components/setting-video-intercept.vue";
import SettingNearbyIntercept from "./components/setting-nearby-intercept.vue";

const trafficType = ref<TrafficConfigType>();

const loading = ref<boolean>(true);
const personId = ref<string>("");
const personName = ref<string>("");
const saving = ref<boolean>(false);

const configData = reactive<ConfigData>(defaultConfigData);

const clampNumber = (val: number, min: number, max: number): number => {
    if (isNaN(val) || val < min) return min;
    if (val > max) return max;
    return val;
};

const handleInput = (key: ConfigKeyType): void => {
    configData[key] = clampNumber(Number(configData[key]), 1, 999);
};

const keywordsEditRef = ref<InstanceType<typeof keywordsEdit>>();
const keywordsEditVisible = ref<boolean>(false);
const keywordsType = ref<KeyWordsType>(KeyWordsType.AcquisitionWords);
const keywordsIndex = ref<number>(-1);
const keywordsTitle = computed<string>(() => TITLE_MAP[keywordsType.value] ?? "");

const removeTag = (type: KeyWordsType, index: number): void => {
    configData[type]?.splice(index, 1);
};

const handleEdit = (type: KeyWordsType, index: number): void => {
    keywordsType.value = type;
    keywordsIndex.value = index;
    keywordsEditRef.value?.setFormData(configData[type]?.[index] ?? "");
    keywordsEditVisible.value = true;
};

const handleAdd = (type: KeyWordsType): void => {
    keywordsType.value = type;
    keywordsIndex.value = -1;
    keywordsEditRef.value?.setFormData("");
    keywordsEditVisible.value = true;
};

const handleKeywordsConfirm = (value: string): void => {
    if (!value.trim()) return;
    if (keywordsIndex.value === -1) {
        configData[keywordsType.value].push(value);
    } else {
        configData[keywordsType.value][keywordsIndex.value] = value;
    }
    keywordsEditVisible.value = false;
    keywordsIndex.value = -1;
};

const checkConfig = (): boolean => {
    if (Number(configData.cityAgeMin) > Number(configData.cityAgeMax)) {
        uni.showToast({ title: "年龄最小值不能大于最大值", icon: "none", duration: 3000 });
        return false;
    }
    if (Number(configData.cityAgeMax) > 999) {
        uni.showToast({ title: "年龄最大值不能大于999", icon: "none", duration: 3000 });
        return false;
    }
    return true;
};

const buildPayload = () => {
    const c = configData;
    return {
        persona_id: personId.value,

        // ── 获客关键词 ──
        acquire_keywords: c.interceptionSearchWords, // 搜索词
        intercept_keywords: c.interceptionMatchWords, // 匹配词
        comment_scripts: [], // 暂无对应前端字段，按需补充
        dm_scripts: [], // 暂无对应前端字段，按需补充

        // ── 防封与频率 ──
        message_number: c.messageNumber,
        comment_number: c.cityCommentNumber,
        // replyNumber: 2 = 关闭(0)，1 = 开启(1)
        reply_number: c.replyNumber === 2 ? 0 : 1,
        // -1 为"不限"，提交 0
        content_publish_day: c.contentPublishTime === -1 ? 0 : c.contentPublishTime,
        comment_publish_day: c.commentPublishTime === -1 ? 0 : c.commentPublishTime,

        // ── 视频截流 ──
        intercept_max_number: c.interceptionLimit,
        intercept_keyword_used_type: c.interceptionStrategy,

        // ── 视频号获客 ──
        clue_keywords: c.acquisitionWords,
        clue_max_number: c.acquisitionLimit,
        clue_keyword_used_type: c.acquisitionStrategy,

        // ── 每日截流人数上限 ──
        video_cutoff_number: c.videoMessageNumber,
        city_cutoff_number: c.cityMessageNumber,
        group_cutoff_number: c.grouponMessageNumber,

        // ── 团购截流（嵌套对象）──
        group_buy_config: {
            group_buy_method: c.grouponTab, // 团购方式 1收藏 2搜索
            group_buy_keyword: c.grouponTypeKeyword, // 团购类型关键词
            range: c.grouponDistance === -1 ? 0 : c.grouponDistance, // 同城公里数，0不限
            exec_number: c.commentNumber, // 团购任务目标人数
            comment_keywords: c.grouponCommentKeywords, // 评论必须包含的关键词
            group_publish_day: c.grouponPublishDay, // 团购发布天数
            group_num_comment: c.grouponCommentNum, // 从第几个评论开始
            interactive_action: c.grouponActions, // 执行动作 1点赞2关注3评论4私信
            group_thumb_method: c.grouponLikeType, // 点赞方式 1点赞头像 2点赞视频
            view_video_time: c.grouponWatchSeconds, // 观看视频时长(s)
            touch_interval: c.grouponReachInterval, // 触达间隔(s)
            gender: c.grouponGenderFilter, // 性别 0不限1男2女
            filter_ip: c.grouponFilterIp, // 筛选ip
            filter_address: c.grouponFilterRegion, // 筛选地区
            filter_nickname: c.grouponNicknameFilters, // 昵称不包含词汇
        },

        // ── 同城截流（嵌套对象）──
        same_city_config: {
            interactive_action: c.cityActions, // 执行动作 1点赞2关注3评论4私信
            view_video_time: c.cityWatchSeconds, // 观看视频时长(s)
            touch_interval: c.cityReachInterval, // 触达间隔(s)
            range: c.cityDistance === -1 ? 0 : c.cityDistance, // 同城公里数，0不限
            gender: c.cityGenderFilter, // 性别 0不限1男2女
            age_range: {
                min: c.cityAgeMin === "" ? 0 : Number(c.cityAgeMin),
                max: c.cityAgeMax === "" ? 0 : Number(c.cityAgeMax),
            },
            filter_video_thumb_num: c.cityVideoMatchNum, // 视频作品满足点赞数
            filter_video_comment_num: c.cityVideoCommentNum, // 视频评论数不大于
            filter_comment_fans: {
                min: c.cityCommentFansMin,
                max: c.cityCommentFansMax,
            },
            filter_comment_follow: {
                min: c.cityFollowMin,
                max: c.cityFollowMax,
            },
            filter_nickname: c.cityNicknameFilters, // 昵称不包含词汇
        },
    };
};

const handleSave = async (): Promise<void> => {
    if (saving.value) return;
    if (!checkConfig()) return;
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        saving.value = true;
        await updateTrafficConfig(buildPayload());
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    } finally {
        saving.value = false;
    }
};

const getDetail = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult, configResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getTrafficConfig({ persona_id: personId.value }),
        ]);
        if (detailResult.status === "fulfilled") {
            personName.value = detailResult.value?.persona_name ?? "";
        }
        if (configResult.status === "fulfilled") {
            const d = configResult.value;
            // -- 视频号获客 --
            configData.acquisitionWords = d.clue_keywords ?? [];
            configData.acquisitionLimit = d.clue_max_number ?? 50;
            configData.acquisitionStrategy = d.clue_keyword_used_type ?? 1;

            // -- 视频截流 --
            configData.interceptionSearchWords = d.acquire_keywords ?? [];
            configData.interceptionMatchWords = d.intercept_keywords ?? [];
            configData.interceptionLimit = d.intercept_max_number ?? 30;
            configData.interceptionStrategy = d.intercept_keyword_used_type ?? 1;

            // -- 团购截流设置 --
            const groupBuyConfig = d.group_buy_config ?? {};
            configData.grouponTab = groupBuyConfig.group_buy_method ?? GrouponTab.Search;
            configData.commentNumber = groupBuyConfig.exec_number ?? 15;
            configData.grouponTypeKeyword = groupBuyConfig.group_buy_keyword ?? "";
            configData.grouponDistance = groupBuyConfig.range ?? 0;
            configData.grouponCommentKeywords = groupBuyConfig.comment_keywords ?? [];
            configData.grouponPublishDay = groupBuyConfig.group_publish_day ?? 1;
            configData.grouponCommentNum = groupBuyConfig.group_num_comment ?? 1;
            configData.grouponActions = groupBuyConfig.interactive_action?.map((i: number) => Number(i)) || [
                GrouponAction.Like,
                GrouponAction.Follow,
                GrouponAction.Comment,
            ];
            configData.grouponLikeType = groupBuyConfig.group_thumb_method ?? LikeType.Avatar;
            configData.grouponWatchSeconds = groupBuyConfig.view_video_time ?? 10;
            configData.grouponReachInterval = groupBuyConfig.touch_interval ?? 10;
            configData.grouponGenderFilter = groupBuyConfig.gender ?? 0;
            configData.grouponFilterIp = groupBuyConfig.filter_ip ?? "";
            configData.grouponFilterRegion = groupBuyConfig.filter_address ?? "";
            configData.grouponNicknameFilters = groupBuyConfig.filter_nickname ?? [];

            // -- 同城截流 --
            const cityConfig = d.same_city_config ?? {};
            configData.cityActions = cityConfig.interactive_action?.map((i: number) => Number(i)) || [
                GrouponAction.Like,
                GrouponAction.Follow,
                GrouponAction.Comment,
            ];
            configData.cityDistance = cityConfig.range ?? 0;
            configData.cityWatchSeconds = cityConfig.view_video_time ?? 10;
            configData.cityReachInterval = cityConfig.touch_interval ?? 10;
            configData.cityGenderFilter = cityConfig.gender ?? 0;
            configData.cityAgeMin = cityConfig.age_range?.min ?? 18;
            configData.cityAgeMax = cityConfig.age_range?.max ?? 60;
            configData.cityVideoMatchNum = cityConfig.filter_video_thumb_num ?? 0;
            configData.cityVideoCommentNum = cityConfig.filter_video_comment_num ?? 0;
            configData.cityCommentFansMin = cityConfig.filter_comment_fans?.min ?? 0;
            configData.cityCommentFansMax = cityConfig.filter_comment_fans?.max ?? 0;
            configData.cityFollowMin = cityConfig.filter_comment_follow?.min ?? 0;
            configData.cityFollowMax = cityConfig.filter_comment_follow?.max ?? 0;
            configData.cityNicknameFilters = cityConfig.filter_nickname ?? [];

            // -- 防封与频率设置 --
            configData.contentPublishTime = d.content_publish_day == 0 ? -1 : d.content_publish_day;
            configData.commentPublishTime = d.comment_publish_day == 0 ? -1 : d.comment_publish_day;
            configData.messageNumber = d.message_number ?? 15;
            configData.cityCommentNumber = d.comment_number ?? 15;
            configData.videoMessageNumber = d.video_cutoff_number ?? 15;
            configData.cityMessageNumber = d.city_cutoff_number ?? 15;
            configData.grouponMessageNumber = d.group_cutoff_number ?? 15;
            configData.replyNumber = d.reply_number === 0 ? 2 : 1;
        }
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    personId.value = options.id;
    trafficType.value = Number(options.type);
    getDetail();
});
</script>
