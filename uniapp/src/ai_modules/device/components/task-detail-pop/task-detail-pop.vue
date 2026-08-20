<template>
    <popup-bottom v-model="show" height="88%" custom-class="bg-[#F4F6FA]" :clearable="false">
        <template #header>
            <view class="bg-white rounded-t-[48rpx] pt-[24rpx] pb-[30rpx] px-[40rpx]">
                <view class="flex justify-center mb-[24rpx]">
                    <view class="w-[80rpx] h-[8rpx] rounded-full bg-[#E5E7EB]"></view>
                </view>
                <view class="flex items-start justify-between gap-[24rpx]">
                    <view class="flex-1 min-w-0">
                        <view class="text-base font-bold text-[#1F2937] leading-snug line-clamp-2">
                            {{ detailTitle }}
                        </view>
                        <view class="text-xs text-[#9CA3AF] mt-[6rpx]">{{ taskTimeText }}</view>
                    </view>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center shrink-0 active:bg-[#E5E7EB]"
                        @click="closePopup">
                        <u-icon name="close" color="#6B7280" size="26"></u-icon>
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[32rpx] pt-[24rpx] pb-[32rpx]">
                            <view
                                class="rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center gap-[20rpx]"
                                :class="statusBannerClass">
                                <view
                                    v-if="isRunning"
                                    class="w-[18rpx] h-[18rpx] rounded-full bg-[#2B6EFF] animate-pulse shrink-0"></view>
                                <u-icon v-else :name="statusIcon" :color="statusIconColor" size="34"></u-icon>
                                <text class="text-sm font-semibold" :class="statusTextClass">{{
                                    statusBannerText
                                }}</text>
                            </view>

                            <view v-if="isFailed && failReason" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <view class="flex items-center gap-[12rpx] mb-[16rpx]">
                                    <u-icon name="error-circle" color="#EF4444" size="28"></u-icon>
                                    <text class="text-xs font-semibold text-[#EF4444]">失败原因</text>
                                </view>
                                <text class="text-sm text-[#374151] leading-relaxed break-all">{{ failReason }}</text>
                            </view>

                            <view class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <view class="flex items-center justify-between py-[20rpx]">
                                    <text class="text-xs text-[#9CA3AF]">任务类型</text>
                                    <text class="text-sm text-[#374151] font-semibold">{{ taskCategoryText }}</text>
                                </view>
                                <view class="h-[2rpx] bg-[#F3F4F6]"></view>
                                <view class="flex items-center justify-between py-[20rpx]">
                                    <text class="text-xs text-[#9CA3AF]">执行设备</text>
                                    <text class="text-sm text-[#374151] font-semibold">{{ deviceName }}</text>
                                </view>
                                <view class="h-[2rpx] bg-[#F3F4F6]"></view>
                                <view class="flex items-center justify-between py-[20rpx]">
                                    <text class="text-xs text-[#9CA3AF]">执行时间</text>
                                    <text class="text-sm text-[#374151] font-semibold">{{ fullTimeText }}</text>
                                </view>
                                <view class="h-[2rpx] bg-[#F3F4F6]"></view>
                                <view class="pt-[24rpx]">
                                    <view class="flex items-center justify-between mb-[18rpx]">
                                        <text class="text-xs text-[#9CA3AF]">执行账号</text>
                                        <view
                                            v-if="accountTypeText"
                                            class="px-[16rpx] py-[4rpx] rounded-[10rpx]"
                                            :style="platformBadgeStyle">
                                            <text class="text-[20rpx] font-bold">{{ accountTypeText }}</text>
                                        </view>
                                    </view>
                                    <view
                                        class="bg-[#F9FAFB] rounded-[24rpx] px-[24rpx] py-[20rpx] flex items-center gap-[20rpx]">
                                        <view class="relative shrink-0">
                                            <image
                                                v-if="accountAvatar"
                                                :src="accountAvatar"
                                                class="w-[80rpx] h-[80rpx] rounded-full"
                                                mode="aspectFill"></image>
                                            <view
                                                v-else
                                                class="w-[80rpx] h-[80rpx] rounded-full bg-[#E5E7EB] flex items-center justify-center">
                                                <text class="text-sm font-bold text-[#9CA3AF]">{{
                                                    accountName.slice(0, 1) || "?"
                                                }}</text>
                                            </view>
                                            <image
                                                v-if="detailData.account_type"
                                                :src="getPlatformIcon(detailData.account_type)"
                                                class="w-[30rpx] h-[30rpx] absolute bottom-0 right-0 rounded-full"></image>
                                        </view>
                                        <view class="flex-1 min-w-0">
                                            <view class="text-sm font-semibold text-[#374151] line-clamp-1">
                                                {{ accountName }}
                                            </view>
                                            <view class="text-xs text-[#9CA3AF] mt-[4rpx] line-clamp-1">
                                                {{ accountCode }}
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view v-if="taskDescription" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <view class="text-xs text-[#9CA3AF] mb-[12rpx]">任务说明</view>
                                <text class="text-sm text-[#374151] leading-relaxed break-all">{{
                                    taskDescription
                                }}</text>
                            </view>

                            <template v-if="detailData.detail">
                                <view
                                    v-if="detailData.task_type == TaskTypeEnum.CUSTOMER"
                                    class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                    <view class="text-xs text-[#9CA3AF] mb-[18rpx]">
                                        线索词（{{ detailData.detail?.keywords?.length || 0 }} 个）
                                    </view>
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="item in detailData.detail?.keywords"
                                            :key="item"
                                            class="px-[20rpx] py-[10rpx] text-xs rounded-[16rpx] bg-[#EFF6FF] text-[#2563EB]">
                                            {{ item }}
                                        </view>
                                    </view>
                                </view>

                                <view v-if="showPublishContent" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                    <!-- 视频：单封面 + 播放 -->
                                    <view
                                        v-if="detailData.detail?.material_type == 1"
                                        class="mb-[24rpx] grid gap-[8rpx] grid-cols-1">
                                        <view
                                            class="relative rounded-[24rpx] overflow-hidden aspect-[3/4]"
                                            @click="handlePreviewImage(0)">
                                            <image
                                                :src="detailData.detail?.pic || publishImages[0]"
                                                class="w-full h-full"
                                                mode="aspectFill"></image>
                                            <view class="w-full h-full flex items-center justify-center absolute top-0 left-0">
                                                <view
                                                    v-if="detailData.detail?.material_url"
                                                    class="rounded-full bg-[#00000066] w-[76rpx] h-[76rpx] flex items-center justify-center"
                                                    @click.stop="handlePlayVideo(detailData.detail?.material_url)">
                                                    <image
                                                        src="/static/images/icons/play.svg"
                                                        class="w-full h-full"></image>
                                                </view>
                                                <view
                                                    v-else
                                                    class="px-[24rpx] py-[12rpx] rounded-[16rpx] bg-[#00000066]"
                                                    @click.stop>
                                                    <text class="text-xs text-white font-medium">
                                                        视频正在生成中…
                                                    </text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                    <!-- 图文：支持多图网格 -->
                                    <view
                                        v-else-if="publishImages.length"
                                        :class="getImageGridClass(publishImages.length)"
                                        class="mb-[24rpx]">
                                        <view
                                            v-for="(item, index) in publishImages"
                                            :key="`${item}-${index}`"
                                            class="relative rounded-[24rpx] overflow-hidden"
                                            :class="publishImages.length === 1 ? 'aspect-[3/4]' : 'aspect-square'"
                                            @click="handlePreviewImage(index)">
                                            <image :src="item" class="w-full h-full" mode="aspectFill"></image>
                                        </view>
                                    </view>
                                    <view class="flex items-center gap-[12rpx] mb-[18rpx]">
                                        <text
                                            class="text-xs font-bold text-[#374151] bg-[#F3F4F6] px-[20rpx] py-[8rpx] rounded-[14rpx]">
                                            {{ publishTimeText }}
                                        </text>
                                        <text
                                            class="text-xs font-semibold px-[20rpx] py-[8rpx] rounded-[14rpx]"
                                            :class="
                                                detailData.detail?.material_type == 1
                                                    ? 'bg-[#F5F3FF] text-[#7C3AED]'
                                                    : 'bg-[#EFF6FF] text-[#2563EB]'
                                            ">
                                            {{
                                                detailData.detail?.material_type == 1
                                                    ? "视频"
                                                    : publishImages.length > 1
                                                    ? `图文·${publishImages.length}张`
                                                    : "图片"
                                            }}
                                        </text>
                                    </view>
                                    <view class="mb-[18rpx]">
                                        <view class="text-xs text-[#9CA3AF] mb-[8rpx]">发布标题</view>
                                        <text class="text-sm font-semibold text-[#1F2937] leading-snug break-all">
                                            {{ detailData.detail?.material_title || "-" }}
                                        </text>
                                    </view>
                                    <view>
                                        <view class="text-xs text-[#9CA3AF] mb-[10rpx]">发布文案</view>
                                        <view class="bg-[#F9FAFB] rounded-[20rpx] px-[24rpx] py-[20rpx]">
                                            <text class="text-xs text-[#4B5563] leading-relaxed break-all">
                                                {{ detailData.detail?.material_subtitle || "-" }}
                                            </text>
                                        </view>
                                    </view>
                                    <view class="mt-[18rpx]" v-if="detailData.detail?.material_tag?.length">
                                        <view class="text-xs text-[#9CA3AF] mb-[10rpx]"># 话题</view>
                                        <view class="flex flex-wrap gap-[10rpx]">
                                            <text
                                                v-for="item in detailData.detail?.material_tag"
                                                :key="item"
                                                class="bg-[#EFF6FF] text-[#2563EB] px-[16rpx] py-[6rpx] rounded-[14rpx] text-xs">
                                                {{ item }}
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="detailData.task_type == TaskTypeEnum.CIRCLE"
                                    class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                    <view
                                        v-if="
                                            detailData.detail?.attachment_type == 1 &&
                                            detailData.detail?.attachment_content?.length > 0
                                        "
                                        :class="getImageGridClass(detailData.detail.attachment_content.length)"
                                        class="mb-[24rpx]">
                                        <view
                                            v-for="(item, index) in detailData.detail.attachment_content"
                                            :key="index"
                                            class="relative rounded-[24rpx] overflow-hidden"
                                            :class="
                                                detailData.detail.attachment_content.length === 1
                                                    ? 'aspect-[3/4]'
                                                    : 'aspect-square'
                                            "
                                            @click="handlePreviewImage(index)">
                                            <image :src="item" class="w-full h-full" mode="aspectFill"></image>
                                        </view>
                                    </view>
                                    <view
                                        v-if="
                                            detailData.detail?.attachment_type == 2 &&
                                            detailData.detail?.attachment_content?.length > 0
                                        "
                                        :class="getImageGridClass(detailData.detail.attachment_content.length)"
                                        class="mb-[24rpx]">
                                        <view
                                            v-for="(item, index) in detailData.detail.attachment_content"
                                            :key="index"
                                            class="relative rounded-[24rpx] overflow-hidden aspect-square">
                                            <video
                                                :src="item"
                                                class="w-full h-full"
                                                :autoplay="false"
                                                :show-loading="false"
                                                :controls="false"
                                                :show-fullscreen-btn="false"
                                                :show-center-play-btn="false"
                                                :show-play-btn="false"
                                                mode="aspectFill"></video>
                                            <view
                                                class="w-full h-full flex items-center justify-center absolute top-0 left-0">
                                                <view
                                                    class="rounded-full bg-[#00000066] w-[76rpx] h-[76rpx] flex items-center justify-center"
                                                    @click="handlePlayVideo(item)">
                                                    <image
                                                        src="/static/images/icons/play.svg"
                                                        class="w-full h-full"></image>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                    <view class="text-xs text-[#9CA3AF] mb-[10rpx]">发布文案</view>
                                    <text class="text-sm text-[#374151] leading-relaxed break-all">
                                        {{ detailData.detail?.content || "-" }}
                                    </text>
                                    <view class="text-[22rpx] text-[#9CA3AF] mt-[14rpx]">
                                        发布时间：{{ detailData.detail?.send_time || "-" }}
                                    </view>
                                </view>
                            </template>

                            <view v-else-if="showContentEmpty" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <empty text="内容还在生成中，请耐心等待..." :size="240" />
                            </view>

                            <view v-if="showStepScreenshots" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <view class="text-xs text-[#9CA3AF] mb-[20rpx]">步骤截图</view>
                                <scroll-view scroll-x class="w-full">
                                    <view class="inline-flex gap-[16rpx] pb-[4rpx]">
                                        <view
                                            v-for="(item, index) in stepScreenshots"
                                            :key="item.id || index"
                                            class="w-[172rpx] shrink-0 rounded-[20rpx] overflow-hidden border border-solid border-[#F3F4F6] bg-white"
                                            @click="handlePreviewStepImage(index)">
                                            <view class="relative h-[216rpx] bg-[#F9FAFB]">
                                                <image
                                                    :src="item.image"
                                                    class="w-full h-full"
                                                    mode="aspectFill"></image>
                                                <view
                                                    class="absolute top-[10rpx] left-[10rpx] px-[12rpx] py-[2rpx] rounded-full bg-[#2B6EFF]">
                                                    <text class="text-[18rpx] font-bold text-white">{{
                                                        index + 1
                                                    }}</text>
                                                </view>
                                            </view>
                                            <view class="px-[10rpx] py-[10rpx]">
                                                <text class="text-[20rpx] text-[#374151] leading-tight line-clamp-2">
                                                    {{ item.message || `步骤 ${index + 1}` }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>

                            <view v-if="resultText" class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]">
                                <view class="text-xs text-[#9CA3AF] mb-[12rpx]">执行结果</view>
                                <text class="text-sm text-[#374151] leading-relaxed break-all">{{ resultText }}</text>
                            </view>

                            <view
                                class="bg-white rounded-[28rpx] p-[32rpx] mt-[24rpx]"
                                v-if="detailData.log && detailData.log.length > 0">
                                <view class="font-semibold text-[28rpx] text-[#1F2937] mb-[28rpx]"> 执行日志 </view>
                                <view class="relative">
                                    <view
                                        class="absolute left-[19rpx] top-[20rpx] bottom-[20rpx] w-[2rpx] bg-[#E5E7EB]"></view>
                                    <view
                                        v-for="(item, index) in detailData.log"
                                        :key="item.id || index"
                                        class="flex gap-[20rpx] mb-[28rpx] last:mb-0">
                                        <view class="flex-shrink-0 flex flex-col items-center w-[40rpx]">
                                            <view
                                                class="w-[20rpx] h-[20rpx] rounded-full mt-[8rpx] z-10"
                                                :class="index === 0 ? 'bg-[#2B6EFF]' : 'bg-[#D1D5DB]'"></view>
                                        </view>
                                        <view class="flex-1 min-w-0 pb-[4rpx]">
                                            <view class="text-sm text-[#374151] leading-relaxed break-all">
                                                {{ item.message }}
                                            </view>
                                            <view class="text-[22rpx] text-[#9CA3AF] mt-[6rpx]">
                                                {{ item.create_time }}
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view
                    class="px-[32rpx] pt-[12rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]"
                    v-if="detailData.auto_type != 1">
                    <view
                        class="h-[96rpx] flex items-center justify-center bg-[#EF4444] text-white font-semibold rounded-[24rpx] active:opacity-80"
                        @click="showConfirmDialog = true">
                        删除任务
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
    <confirm-dialog
        v-model="showConfirmDialog"
        content="确定要删除任务吗？"
        center
        :z-index="1001"
        @confirm="handleDeleteTask" />
    <video-preview v-model="showVideoPreview" title="视频预览" :video-url="playData.url" />
</template>

<script setup lang="ts">
import { deleteDeviceTaskCalendar, getDeviceTaskSubtasks } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import WechatWechatActiveIcon from "@/static/images/common/wechat_s.png";

enum TaskTypeEnum {
    UNKNOWN = 0,
    PUBLISH = 1,
    TAKE_OVER = 2,
    CARE = 3,
    CUSTOMER = 4,
    WECHAT = 5,
    CIRCLE = 7,
}

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits(["delete", "play", "update:modelValue"]);

const detailData = ref<any>({});

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const showConfirmDialog = ref(false);
const showVideoPreview = ref(false);
const playData = reactive({ url: "" });

const { platform } = useDevice();

const statusValue = computed(() => Number(detailData.value.status));
const isRunning = computed(() => statusValue.value === 1);
const isDone = computed(() => statusValue.value === 2);
const isFailed = computed(() => statusValue.value === 3 || statusValue.value === 4);
const isFinishedStatus = computed(() => isDone.value || isFailed.value);

const detailTitle = computed(() => {
    return (
        detailData.value.detail?.name ||
        detailData.value.name ||
        detailData.value.task_name ||
        detailData.value.detail?.material_title ||
        "任务详情"
    );
});

const taskCategoryText = computed(() => {
    const category = detailData.value.task_category || "任务";
    return `${category}(${detailData.value.auto_type === 1 ? "24h" : "手动"})`;
});

const taskTimeText = computed(() => {
    const start = detailData.value.start_time || "";
    const end = detailData.value.end_time || "";
    if (start && end) return `${start}-${end}`;
    return start || end || "待定";
});

const fullTimeText = computed(() => {
    const day = detailData.value.day || "";
    return `${day} ${taskTimeText.value}`.trim() || "-";
});

const deviceName = computed(() => {
    return (
        detailData.value.device_info?.device_name ||
        detailData.value.device_info?.device_code ||
        detailData.value.device_name ||
        detailData.value.device_code ||
        "-"
    );
});

const accountAvatar = computed(() => {
    return (
        detailData.value.account_info?.avatar ||
        detailData.value.account_info?.wechat_avatar ||
        detailData.value.avatar ||
        ""
    );
});

const accountName = computed(() => {
    return (
        detailData.value.account_info?.nickname ||
        detailData.value.account_info?.wechat_nickname ||
        detailData.value.nickname ||
        "未绑定账号"
    );
});

const accountCode = computed(() => {
    return detailData.value.account_info?.account || detailData.value.account || "账号信息暂无";
});

const taskTextForMatch = computed(() => {
    return [
        detailData.value.task_category,
        detailData.value.name,
        detailData.value.task_name,
        detailData.value.detail?.material_title,
    ]
        .filter(Boolean)
        .join("");
});

const isPublishTask = computed(() => {
    const taskType = Number(detailData.value.task_type);
    const source = Number(detailData.value.source);
    return (
        taskType === TaskTypeEnum.PUBLISH ||
        source === 1 ||
        /发布/.test(taskTextForMatch.value) ||
        !!detailData.value.detail?.material_url ||
        !!detailData.value.detail?.publish_time
    );
});

const showPublishContent = computed(() => {
    return (
        isPublishTask.value &&
        !!detailData.value.detail &&
        (!!detailData.value.detail?.material_title ||
            !!detailData.value.detail?.material_subtitle ||
            !!detailData.value.detail?.pic ||
            !!detailData.value.detail?.material_url)
    );
});

const showContentEmpty = computed(() => {
    return [TaskTypeEnum.CUSTOMER, TaskTypeEnum.PUBLISH, TaskTypeEnum.CIRCLE].includes(
        Number(detailData.value.task_type),
    );
});

const publishImages = computed<string[]>(() => {
    const url = detailData.value.detail?.material_url;
    if (url && Number(detailData.value.detail?.material_type) !== 1) {
        return String(url)
            .split(",")
            .map((item) => item.trim())
            .filter(Boolean);
    }
    const pic = detailData.value.detail?.pic;
    return pic ? [pic] : [];
});

const stepScreenshots = computed(() => {
    return (detailData.value.log || []).filter((item: any) => item.image);
});

const showStepScreenshots = computed(() => {
    return isPublishTask.value && isFinishedStatus.value && stepScreenshots.value.length > 0;
});

const taskDescription = computed(() => {
    return detailData.value.detail?.desc || detailData.value.detail?.description || "";
});

const failReason = computed(() => {
    if (!isFailed.value) return "";
    return detailData.value.remark || detailData.value.fail_reason || detailData.value.error_msg || "";
});

const resultText = computed(() => {
    if (isFailed.value) return "";
    return detailData.value.detail?.result || detailData.value.remark || "";
});

const publishTimeText = computed(() => {
    return detailData.value.detail?.publish_time || detailData.value.start_time || "待定";
});

const statusBannerText = computed(() => {
    if (isRunning.value) return "正在执行中...";
    if (isDone.value) return "执行成功";
    if (isFailed.value) return "执行失败";
    return `计划于 ${taskTimeText.value} 执行`;
});

const statusIcon = computed(() => {
    if (isDone.value) return "checkmark-circle";
    if (isFailed.value) return "close-circle";
    return "clock";
});

const statusIconColor = computed(() => {
    if (isDone.value) return "#22C55E";
    if (isFailed.value) return "#EF4444";
    return "#9CA3AF";
});

const statusBannerClass = computed(() => {
    if (isRunning.value) return "bg-[#EFF6FF]";
    if (isDone.value) return "bg-[#F0FDF4]";
    if (isFailed.value) return "bg-[#FEF2F2]";
    return "bg-[#F9FAFB]";
});

const statusTextClass = computed(() => {
    if (isRunning.value) return "text-[#2563EB]";
    if (isDone.value) return "text-[#16A34A]";
    if (isFailed.value) return "text-[#EF4444]";
    return "text-[#6B7280]";
});

const platformMap: Record<number, { label: string; bg: string; color: string }> = {
    1: { label: "视频号", bg: "#1AAD19", color: "#FFFFFF" },
    2: { label: "微信", bg: "#07C160", color: "#FFFFFF" },
    3: { label: "小红书", bg: "#FE2C55", color: "#FFFFFF" },
    4: { label: "抖音", bg: "#000000", color: "#FFFFFF" },
    5: { label: "快手", bg: "#FF6800", color: "#FFFFFF" },
};

const accountTypeText = computed(() => {
    const type = Number(detailData.value.account_type);
    return platformMap[type]?.label || "";
});

const platformBadgeStyle = computed(() => {
    const type = Number(detailData.value.account_type);
    const style = platformMap[type] || { bg: "#6B7280", color: "#FFFFFF" };
    return {
        background: style.bg,
        color: style.color,
    };
});

const getImageGridClass = (count: number): string => {
    if (count === 1) return "grid gap-[8rpx] grid-cols-1";
    if (count === 2) return "grid gap-[8rpx] grid-cols-2";
    if (count === 4) return "grid gap-[8rpx] grid-cols-2";
    return "grid gap-[8rpx] grid-cols-3";
};

const getPlatformIcon = (type: number) => {
    if (type == 2) return WechatWechatActiveIcon;
    return platform.value[type]?.activeIcon || "";
};

const closePopup = () => {
    show.value = false;
};

const handleDeleteTask = async () => {
    showConfirmDialog.value = false;
    uni.showLoading({ title: "删除中...", mask: true });
    try {
        await deleteDeviceTaskCalendar({
            id: detailData.value.task_id,
            sub_task_id: detailData.value.sub_task_id,
            source: detailData.value.source,
        });
        uni.hideLoading();
        uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
        show.value = false;
        emit("delete");
    } catch (error) {
        uni.hideLoading();
        uni.showToast({ title: "删除失败", icon: "none", duration: 3000 });
    }
};

const handlePlayVideo = (url: string) => {
    playData.url = url;
    showVideoPreview.value = true;
};

const handlePreviewImage = (index?: number) => {
    const { task_type, detail } = detailData.value;
    if (Number(task_type) == TaskTypeEnum.CIRCLE) {
        const { attachment_content } = detail;
        if (attachment_content?.length > 0) {
            uni.previewImage({ urls: attachment_content, current: index });
        }
    }
    if (isPublishTask.value) {
        const urls =
            detail?.material_type == 1
                ? [detail?.pic].filter(Boolean)
                : publishImages.value.length
                ? publishImages.value
                : [detail?.pic].filter(Boolean);
        if (urls.length > 0) {
            uni.previewImage({ urls, current: index ?? 0 });
        }
    }
};

const handlePreviewStepImage = (index: number) => {
    const urls = stepScreenshots.value.map((item: any) => item.image).filter(Boolean);
    uni.previewImage({ urls, current: index });
};

const getDetail = async (data: any) => {
    const baseData = {
        ...data,
        task_id: data.id,
        sub_task_id: data.sub_task_id,
        source: data.source,
    };
    detailData.value = baseData;
    const res = await getDeviceTaskSubtasks({
        id: data.id,
        sub_task_id: data.sub_task_id,
        source: data.source,
    });
    detailData.value = {
        ...baseData,
        ...res,
        task_id: data.id,
        sub_task_id: data.sub_task_id,
        source: data.source ?? res?.source,
    };
};

defineExpose({ getDetail });
</script>

<style scoped></style>
