<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[env(safe-area-inset-bottom)]">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="素材详情" title-bold>
        </u-navbar>

        <view class="px-[30rpx] pt-2 pb-6 flex flex-col gap-4">
            <view class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)] flex gap-4">
                <view
                    class="relative w-[160rpx] h-[214rpx] rounded-[20rpx] overflow-hidden flex-shrink-0 bg-gray-100"
                    @click="previewImage(detail.thumbnail_url)">
                    <image
                        :src="detail.thumbnail_url"
                        class="w-full h-full"
                        mode="aspectFill"
                        v-if="detail.thumbnail_url || detail.material_type === 2">
                    </image>
                    <video
                        :src="detail.file_url"
                        :autoplay="false"
                        :show-loading="false"
                        :controls="false"
                        :show-fullscreen-btn="false"
                        :show-center-play-btn="false"
                        :show-play-btn="false"
                        class="w-full h-full"
                        v-else></video>
                    <text v-if="detail.duration" class="absolute bottom-1.5 left-2 text-white text-[22rpx] font-medium">
                        {{ formatAudioTime(detail.duration) }}
                    </text>
                    <view
                        v-if="detail.material_type === 1"
                        class="absolute top-0 right-0 w-full h-full flex items-center justify-center bg-[#000000]/50">
                        <view
                            class="w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                            @click.stop="previewVideo(detail.file_url)">
                            <u-icon name="play-right-fill" color="#ffffff" size="20" class="ml-0.5"></u-icon>
                        </view>
                    </view>
                </view>
                <view class="flex-1 flex flex-col justify-between py-1">
                    <text class="text-[30rpx] font-extrabold text-[#1A1A1A] leading-snug line-clamp-2 break-all">
                        {{ detail.material_name }}
                    </text>
                </view>
            </view>

            <view
                class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]"
                v-if="detail.publish_mode === 1">
                <text class="text-[30rpx] font-extrabold text-[#1A1A1A] block mb-4">视频生成记录</text>

                <view v-if="loading" class="flex flex-col gap-4 animate-pulse">
                    <view v-for="i in 2" :key="i" class="flex gap-3">
                        <view class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                        <view class="flex-1 flex flex-col justify-center gap-2">
                            <view class="h-[28rpx] w-3/4 bg-[#F3F4F6] rounded-full"></view>
                            <view class="h-[24rpx] w-1/2 bg-[#F3F4F6] rounded-full"></view>
                            <view class="h-[22rpx] w-1/3 bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                </view>

                <view v-else class="flex flex-col gap-4">
                    <view
                        v-for="(record, index) in records"
                        :key="record.id ?? index"
                        class="p-2 -mx-2 rounded-[20rpx] active:bg-[#F8FAFC]">
                        <view class="flex gap-3">
                            <view
                                class="relative w-[140rpx] h-[140rpx] rounded-[16rpx] overflow-hidden flex-shrink-0"
                                :class="{
                                    'bg-[#EFF6FF]': isRunning(record.status),
                                    'bg-[#ECFDF5]': record.status === 3,
                                    'bg-[#FFF5F5]': record.status === 2,
                                }">
                                <image v-if="record.pic" :src="record.pic" class="w-full h-full" mode="aspectFill" />
                                <video
                                    v-else-if="record.status === 3"
                                    :src="record.video_result_url"
                                    :controls="false"
                                    :show-loading="false"
                                    :show-fullscreen-btn="false"
                                    :show-center-play-btn="false"
                                    :show-play-btn="false"
                                    class="w-full h-full"
                                    mode="aspectFill" />
                                <view
                                    v-if="record.status === 3"
                                    class="absolute top-0 left-0 w-full h-full bg-[#000000]/50 flex items-center justify-center"
                                    @click="previewVideo(record.video_result_url)">
                                    <u-icon name="play-right-fill" color="#ffffff" size="28" class="ml-0.5" />
                                </view>
                                <text
                                    v-if="record.status === 3"
                                    class="absolute bottom-1 left-1.5 text-white text-[20rpx]">
                                    {{ formatAudioTime(Number(record.duration)) }}
                                </text>

                                <template v-if="record.status === 0">
                                    <view class="w-full h-full flex flex-col items-center justify-center gap-[6rpx]">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                                            <u-icon name="clock" color="#3B82F6" size="26" />
                                        </view>
                                        <text class="text-[18rpx] text-[#60A5FA] font-medium">待处理</text>
                                    </view>
                                </template>

                                <template v-else-if="record.status === 1">
                                    <view class="w-full h-full flex flex-col items-center justify-center gap-[6rpx]">
                                        <view class="relative w-[52rpx] h-[52rpx]">
                                            <view
                                                class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[#BFDBFE]" />
                                            <view
                                                class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[transparent] animate-spin"
                                                style="border-top-color: #0065fb" />
                                            <view class="absolute inset-0 flex items-center justify-center">
                                                <view class="w-[10rpx] h-[10rpx] rounded-full bg-primary" />
                                            </view>
                                        </view>
                                        <text class="text-[18rpx] text-primary font-medium">查询中</text>
                                    </view>
                                </template>

                                <template v-else-if="record.status === 2">
                                    <view class="w-full h-full flex flex-col items-center justify-center gap-[6rpx]">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#FEE2E2] flex items-center justify-center">
                                            <u-icon name="close" color="#EF4444" size="26" />
                                        </view>
                                        <text class="text-[18rpx] text-[#EF4444] font-medium">合成失败</text>
                                    </view>
                                </template>
                            </view>

                            <!-- 右侧信息 -->
                            <view class="flex-1 flex flex-col justify-center gap-1.5 min-w-0">
                                <view class="flex items-center gap-[8rpx]">
                                    <text class="text-[28rpx] font-bold text-[#1A1A1A] truncate flex-1">
                                        {{ record.name }}
                                    </text>
                                    <!-- 状态角标 -->
                                    <view
                                        class="flex-shrink-0 flex items-center gap-[4rpx] px-[10rpx] py-[4rpx] rounded-full"
                                        :class="{
                                            'bg-[#EFF6FF]': record.status === 0,
                                            'bg-[#EEF4FF] border border-solid border-[#BFDBFE]': record.status === 1,
                                            'bg-[#FEF2F2]': record.status === 2,
                                            'bg-[#ECFDF5]': record.status === 3,
                                        }">
                                        <!-- 待处理：灰蓝静态点 -->
                                        <view
                                            v-if="record.status === 0"
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-[#93C5FD]" />
                                        <!-- 查询中：蓝色脉冲点 -->
                                        <view
                                            v-else-if="record.status === 1"
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-primary animate-pulse" />
                                        <!-- 合成失败：红点 -->
                                        <view
                                            v-else-if="record.status === 2"
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-[#EF4444]" />
                                        <!-- 合成成功：绿点 -->
                                        <view
                                            v-else-if="record.status === 3"
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-[#10B981]" />
                                        <text
                                            class="text-[18rpx] font-medium"
                                            :class="{
                                                'text-[#60A5FA]': record.status === 0,
                                                'text-primary': record.status === 1,
                                                'text-[#EF4444]': record.status === 2,
                                                'text-[#059669]': record.status === 3,
                                            }">
                                            {{
                                                {
                                                    0: "待处理",
                                                    1: "视频查询中",
                                                    2: "合成失败",
                                                    3: "合成成功",
                                                }[record.status]
                                            }}
                                        </text>
                                    </view>
                                </view>

                                <text class="text-xs text-[#666666] truncate">
                                    {{ record.device_name }} ({{ record.device_code }})
                                </text>
                                <text class="text-[22rpx] text-[#B4B4B4]">{{ record.create_time }}</text>
                            </view>
                        </view>

                        <view
                            v-if="record.status === 2 && record.remark"
                            class="mt-[12rpx] px-[12rpx] py-[10rpx] bg-[#FFF5F5] rounded-[12rpx] flex items-center gap-[6rpx]">
                            <u-icon name="info-circle" color="#EF4444" size="22" class="flex-shrink-0 mt-[2rpx]" />
                            <text class="text-[22rpx] text-[#EF4444] leading-[1.5] flex-1"
                                >失败原因：{{ record.remark }}</text
                            >
                        </view>
                    </view>

                    <view v-if="records.length === 0" class="py-8 flex flex-col items-center gap-2">
                        <text class="text-[#999999]">暂无生成记录</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getMaterialDetail, updateMaterialStatus, getMaterialUsageRecord } from "@/api/person";
import { formatAudioTime } from "@/utils/util";

// ─── 类型定义 ─────────────────────────────────────────────────────

interface MaterialDetail {
    material_name: string;
    thumbnail_url: string;
    file_url: string;
    duration: number;
    limit: number;
    material_type: number;
    publish_mode: number;
}

interface DeviceItem {
    device_name: string;
    use_num: number;
    [key: string]: any;
}

interface GenerateRecord {
    id: number;
    duration: string;
    device_name: string;
    device_code: string;
    create_time: string;
    pic: string;
    video_result_url: string;
    name: string;
    // 1: 等待处理, 2: 生成中, 3: 已生成, 4: 失败
    status: number;
    remark: string;
}

// ─── 页面状态 ─────────────────────────────────────────────────────

const loading = ref<boolean>(true);
const detailId = ref<string>("");
const personaId = ref<string>("");

const showVideoPreview = ref<boolean>(false);

const detail = ref<MaterialDetail>({
    material_name: "",
    thumbnail_url: "",
    file_url: "",
    duration: 0,
    limit: 3,
    material_type: 1,
    publish_mode: 1,
});

const videoUrl = ref<string>("");

const devices = ref<DeviceItem[]>([]);
const records = ref<GenerateRecord[]>([]);

const isRunning = (status: number) => status === 0 || status === 1;

// ─── 设备操作 ─────────────────────────────────────────────────────

const handleDeviceMore = (item: DeviceItem): void => {
    const isStopped = item.use_num >= 3;
    const actionText = isStopped ? "恢复使用" : "停止使用";

    uni.showActionSheet({
        itemList: [actionText],
        success: async ({ tapIndex }) => {
            if (tapIndex !== 0) return;
            try {
                uni.showLoading({ title: "操作中", mask: true });
                await updateMaterialStatus({
                    id: detailId.value,
                    device_code: item.device_code,
                    status: isStopped ? 0 : 3,
                });
                uni.showToast({ title: `${actionText}成功`, icon: "none", duration: 3000 });
                await getDetail();
            } catch (error: unknown) {
                const msg = typeof error === "string" ? error : `${actionText}失败，请重试`;
                uni.showToast({ title: msg, icon: "none", duration: 3000 });
            } finally {
                uni.hideLoading();
            }
        },
    });
};

// ─── 生成记录点击 ─────────────────────────────────────────────────

const handleRecordClick = (record: GenerateRecord): void => {
    // TODO: 跳转生成记录详情
    console.log("点击生成记录:", record.id);
};

// ─── 图片预览 ─────────────────────────────────────────────────────

const previewImage = (url: string): void => {
    if (!url) return;
    uni.previewImage({ urls: [url] });
};

const previewVideo = (url: string): void => {
    if (!url) return;

    showVideoPreview.value = true;
    videoUrl.value = url;
};

// ─── 数据获取 ─────────────────────────────────────────────────────

const geRecordList = async (): Promise<void> => {
    try {
        const { lists } = await getMaterialUsageRecord({
            persona_id: personaId.value,
            material_id: detailId.value,
            page_size: 25000,
        });
        records.value = lists;
    } finally {
        loading.value = false;
    }
};

const getDetail = async (): Promise<void> => {
    const { devicelist, material, devicenum } = await getMaterialDetail({
        id: detailId.value,
        persona_id: personaId.value,
    });
    devices.value = devicelist ?? [];
    detail.value = { ...material, limit: devicenum ?? 3 };
    if (material.publish_mode == 1) {
        geRecordList();
    }
};

const init = async (): Promise<void> => {
    try {
        await Promise.all([getDetail()]);
    } finally {
        loading.value = false;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad((options: any) => {
    detailId.value = options.id ?? "";
    personaId.value = options.persona_id ?? "";
    init();
});
</script>
