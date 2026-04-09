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
                    <text class="text-[26rpx] text-[#999999]">当前IP：</text>
                    <text class="text-[26rpx] font-bold text-primary">{{ personName }}</text>
                </view>

                <config-card
                    title="获客线索词"
                    desc="监控视频号账号，出现以下词汇立即寻找线索"
                    icon-name="scan"
                    icon-color="#FF4D4F"
                    icon-bg="#FFF0F0">
                    <tag-list
                        :items="configData.acquisitionWords"
                        add-text="添加"
                        @add="handleAdd('acquisitionWords')"
                        @edit="handleEdit('acquisitionWords', $event)"
                        @remove="removeTag('acquisitionWords', $event)" />
                </config-card>

                <config-card
                    title="截流线索词"
                    desc="社媒平台寻找视频，出现以下词汇立即进入寻找评论"
                    icon-name="share"
                    icon-color="#FF8C00"
                    icon-bg="#FFF5F0">
                    <tag-list
                        :items="configData.interceptionWords"
                        add-text="添加"
                        @add="handleAdd('interceptionWords')"
                        @edit="handleEdit('interceptionWords', $event)"
                        @remove="removeTag('interceptionWords', $event)" />
                </config-card>

                <config-card
                    title="评论区引流话术"
                    desc="评论区回复，引导用户看私信或主页"
                    icon-name="chat"
                    icon-color="#00C08E"
                    icon-bg="#E6F8F3">
                    <script-list
                        :items="configData.commentScripts"
                        add-text="添加话术"
                        @add="handleAdd('commentScripts')"
                        @edit="handleEdit('commentScripts', $event)"
                        @remove="removeTag('commentScripts', $event)" />
                </config-card>

                <config-card
                    title="私信转化话术"
                    desc="自动发送私信，直接留资或成交"
                    icon-name="email"
                    icon-color="#0065FB"
                    icon-bg="#E6F0FF">
                    <script-list
                        :items="configData.dmScripts"
                        add-text="添加话术"
                        @add="handleAdd('dmScripts')"
                        @edit="handleEdit('dmScripts', $event)"
                        @remove="removeTag('dmScripts', $event)" />
                </config-card>

                <config-card title="触达时间限制" desc="" icon-name="clock" icon-color="#8B5CF6" icon-bg="#F3F0FF">
                    <view class="mb-6">
                        <text class="text-[28rpx] font-bold text-[#212121] block mb-3">内容发布日期</text>
                        <view class="grid grid-cols-4 gap-[12rpx]">
                            <view
                                v-for="option in timeOptions"
                                :key="'content-' + option.value"
                                class="h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                                :class="
                                    configData.contentPublishTime === option.value ? 'bg-[#212121]' : 'bg-[#F5F5F5]'
                                "
                                @click="configData.contentPublishTime = option.value">
                                <text
                                    class="text-[26rpx] font-medium"
                                    :class="
                                        configData.contentPublishTime === option.value ? 'text-white' : 'text-[#888888]'
                                    ">
                                    {{ option.label }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view>
                        <text class="text-[28rpx] font-bold text-[#212121] block mb-3">评论发布日期</text>
                        <view class="grid grid-cols-4 gap-[12rpx]">
                            <view
                                v-for="option in timeOptions"
                                :key="'comment-' + option.value"
                                class="h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                                :class="
                                    configData.commentPublishTime === option.value ? 'bg-[#212121]' : 'bg-[#F5F5F5]'
                                "
                                @click="configData.commentPublishTime = option.value">
                                <text
                                    class="text-[26rpx] font-medium"
                                    :class="
                                        configData.commentPublishTime === option.value ? 'text-white' : 'text-[#888888]'
                                    ">
                                    {{ option.label }}
                                </text>
                            </view>
                        </view>
                    </view>
                </config-card>

                <config-card
                    title="防封控与频率限制"
                    desc=""
                    icon-name="setting-fill"
                    icon-color="#0065FB"
                    icon-bg="#E6F0FF">
                    <view class="bg-[#E6F0FF]/60 rounded-[16rpx] p-3 mb-6">
                        <text class="text-[24rpx] text-primary leading-relaxed">
                            已开启"拟人随机停顿"。每次互动后，系统将随机停留
                            30秒~2分钟，模拟真人浏览行为，降低风控风险。
                        </text>
                    </view>
                    <view class="mb-6">
                        <view class="flex items-center justify-between mb-4">
                            <text class="text-[28rpx] font-bold text-[#212121]">截流主动私信每天最大互动人数</text>
                            <text class="text-[32rpx] font-extrabold text-primary"
                                >{{ configData.messageNumber }}人</text
                            >
                        </view>
                        <view class="mb-2">
                            <u-slider
                                v-model="configData.messageNumber"
                                min="1"
                                max="30"
                                inactive-color="#E5E7EB"
                                block-color="#0065fb"
                                block-width="36"></u-slider>
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                            <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                        </view>
                    </view>
                    <view class="mb-6">
                        <view class="flex items-center justify-between mb-4">
                            <text class="text-[28rpx] font-bold text-[#212121]">同城触达评论每天最大互动人数 </text>
                            <text class="text-[32rpx] font-extrabold text-primary"
                                >{{ configData.commentNumber }}人</text
                            >
                        </view>
                        <view class="mb-2">
                            <u-slider
                                v-model="configData.commentNumber"
                                min="1"
                                max="30"
                                inactive-color="#E5E7EB"
                                block-color="#0065fb"
                                block-width="36"></u-slider>
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                            <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                        </view>
                    </view>
                    <view>
                        <view class="flex items-center justify-between mb-4">
                            <text class="text-[28rpx] font-bold text-[#212121]">私信每接管个用户回复数 </text>
                            <text class="text-[32rpx] font-extrabold text-primary"
                                ><text v-if="configData.replyNumber === 1">{{ configData.replyNumber }}人</text>
                                <text v-else>无限制</text>
                            </text>
                        </view>
                        <view class="mb-2">
                            <u-slider
                                v-model="configData.replyNumber"
                                min="1"
                                max="2"
                                inactive-color="#E5E7EB"
                                block-color="#0065fb"
                                block-width="36"></u-slider>
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[22rpx] text-[#b4b4b4]">1条</text>
                            <text class="text-[22rpx] text-[#b4b4b4]">无限制</text>
                        </view>
                    </view>
                </config-card>
            </template>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] z-50">
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
        @confirm="handleKeywordsConfirm" />
</template>

<script setup lang="ts">
import { getPersonDetail, getTrafficConfig, updateTrafficConfig } from "@/api/person";
import keywordsEdit from "@/ai_modules/person/components/keywords-edit/keywords-edit.vue";
import ConfigCard from "./components/config-card.vue";
import TagList from "./components/tag-list.vue";
import ScriptList from "./components/script-list.vue";

// ─── 类型定义 ─────────────────────────────────────────────────────
type ConfigKey = "acquisitionWords" | "interceptionWords" | "commentScripts" | "dmScripts";

interface TimeOption {
    label: string;
    value: number;
}

interface ConfigData {
    acquisitionWords: string[];
    interceptionWords: string[];
    commentScripts: string[];
    dmScripts: string[];
    messageNumber: number;
    commentNumber: number;
    replyNumber: number;
    contentPublishTime: number;
    commentPublishTime: number;
}

const TITLE_MAP: Record<ConfigKey, string> = {
    acquisitionWords: "添加获客线索词",
    interceptionWords: "添加截流线索词",
    commentScripts: "添加评论区话术",
    dmScripts: "添加私信转化话术",
};

// ─── 时间选项 ─────────────────────────────────────────────────────
const timeOptions: TimeOption[] = [
    { label: "当天", value: 1 },
    { label: "2天内", value: 2 },
    { label: "3天内", value: 3 },
    { label: "4天内", value: 4 },
    { label: "5天内", value: 5 },
    { label: "6天内", value: 6 },
    { label: "7天内", value: 7 },
    { label: "不限制", value: -1 },
];

// ─── 页面状态 ─────────────────────────────────────────────────────
const loading = ref<boolean>(true);
const personId = ref<string>("");
const personName = ref<string>("");
const saving = ref<boolean>(false);

const configData = reactive<ConfigData>({
    acquisitionWords: [],
    interceptionWords: [],
    commentScripts: [],
    dmScripts: [],
    messageNumber: 15,
    commentNumber: 15,
    replyNumber: 0,
    contentPublishTime: 0, // 默认：当天
    commentPublishTime: 0, // 默认：当天
});

// ─── 弹窗状态 ─────────────────────────────────────────────────────
const keywordsEditRef = ref<InstanceType<typeof keywordsEdit>>();
const keywordsEditVisible = ref<boolean>(false);
const keywordsType = ref<ConfigKey>("acquisitionWords");
const keywordsIndex = ref<number>(-1);

const keywordsTitle = computed<string>(() => TITLE_MAP[keywordsType.value]);

// ─── 标签操作 ─────────────────────────────────────────────────────
const removeTag = (type: ConfigKey, index: number): void => {
    configData[type].splice(index, 1);
};

const handleEdit = (type: ConfigKey, index: number): void => {
    keywordsType.value = type;
    keywordsIndex.value = index;
    keywordsEditRef.value?.setFormData(configData[type][index]);
    keywordsEditVisible.value = true;
};

const handleAdd = (type: ConfigKey): void => {
    keywordsType.value = type;
    keywordsIndex.value = -1;
    keywordsEditRef.value?.setFormData("");
    keywordsEditVisible.value = true;
};

// ─── 关键词确认 ─────────────────────────────────────────────────────
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

// ─── 检查配置 ─────────────────────────────────────────────────────
const checkConfig = (): boolean => {
    if (configData.acquisitionWords.length === 0) {
        uni.showToast({ title: "请添加获客线索词", icon: "none", duration: 3000 });
        return false;
    }
    if (configData.interceptionWords.length === 0) {
        uni.showToast({ title: "请添加截流线索词", icon: "none", duration: 3000 });
        return false;
    }
    if (configData.commentScripts.length === 0) {
        uni.showToast({ title: "请添加评论区话术", icon: "none", duration: 3000 });
        return false;
    }
    if (configData.dmScripts.length === 0) {
        uni.showToast({ title: "请添加私信转化话术", icon: "none", duration: 3000 });
        return false;
    }
    return true;
};

// ─── 保存配置 ─────────────────────────────────────────────────────
const handleSave = async (): Promise<void> => {
    if (saving.value) return;
    if (!checkConfig()) return;
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        saving.value = true;
        await updateTrafficConfig({
            persona_id: personId.value,
            acquire_keywords: configData.acquisitionWords,
            intercept_keywords: configData.interceptionWords,
            comment_scripts: configData.commentScripts,
            dm_scripts: configData.dmScripts,
            message_number: configData.messageNumber,
            comment_number: configData.commentNumber,
            reply_number: configData.replyNumber === 2 ? 0 : 1,
            content_publish_day: configData.contentPublishTime == -1 ? 0 : configData.contentPublishTime,
            comment_publish_day: configData.commentPublishTime == -1 ? 0 : configData.commentPublishTime,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch {
        uni.hideLoading();
        uni.showToast({ title: "保存失败，请重试", icon: "none", duration: 3000 });
    } finally {
        saving.value = false;
    }
};

// ─── 初始化数据 ───────────────────────────────────────────────────
const getDetail = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult, configResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getTrafficConfig({ persona_id: personId.value }),
        ]);

        if (detailResult.status === "fulfilled") {
            personName.value = detailResult.value?.persona_name ?? "";
        } else {
            personName.value = "";
        }

        if (configResult.status === "fulfilled") {
            const {
                acquire_keywords,
                intercept_keywords,
                comment_scripts,
                dm_scripts,
                message_number,
                comment_number,
                reply_number,
                comment_publish_day,
                content_publish_day,
            } = configResult.value;
            configData.acquisitionWords = acquire_keywords ?? [];
            configData.interceptionWords = intercept_keywords ?? [];
            configData.commentScripts = comment_scripts ?? [];
            configData.dmScripts = dm_scripts ?? [];
            configData.messageNumber = message_number ?? 0;
            configData.commentNumber = comment_number ?? 0;
            configData.replyNumber = reply_number === 0 ? 2 : 1;
            configData.contentPublishTime = content_publish_day == 0 ? -1 : content_publish_day;
            configData.commentPublishTime = comment_publish_day == 0 ? -1 : comment_publish_day;
            console.log(configData);
        }
    } finally {
        loading.value = false;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────
onLoad((options: any) => {
    personId.value = options.id;
    getDetail();
});
</script>
