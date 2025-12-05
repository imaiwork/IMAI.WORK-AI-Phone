<template>
    <popup-bottom v-model:show="show" title="任务详情" custom-class="bg-[#F6F6F6]" :show-footer="false" height="80%">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 pb-5">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[26rpx] pt-3">
                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center justify-between">
                                    <view class="font-bold text-[30rpx]">{{ detailData.detail?.name }}</view>
                                    <view
                                        class="flex-shrink-0 px-[12rpx] py-[6rpx] rounded-[12rpx] font-bold text-[22rpx]"
                                        :class="getTaskStatusStyle(detailData.status)">
                                        {{ getTaskStatusText(detailData.status) }}
                                    </view>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] my-3"></view>
                                <view class="flex flex-col gap-y-2">
                                    <view class="">
                                        <text class="text-[#0000004d]">任务类型：</text>
                                        <text>{{ detailData.task_category }}</text>
                                    </view>
                                    <view class="">
                                        <text class="text-[#0000004d]">执行设备：</text>
                                        <text>{{
                                            detailData.device_info?.device_name ||
                                            detailData.device_info?.device_code ||
                                            "-"
                                        }}</text>
                                    </view>
                                    <view class="">
                                        <text class="text-[#0000004d]">任务时间：</text>
                                        <text
                                            >{{ detailData.day }} {{ detailData.start_time }}-{{
                                                detailData.end_time
                                            }}</text
                                        >
                                    </view>
                                    <view>
                                        <text class="text-[#0000004d]">任务账号：</text>
                                        <view
                                            class="mt-2 bg-[#F3F3F3] rounded-[10rpx] px-5 py-[24rpx] flex items-center gap-x-3">
                                            <view class="relative">
                                                <image
                                                    :src="
                                                        detailData.account_info?.avatar ||
                                                        detailData.account_info?.wechat_avatar
                                                    "
                                                    class="w-[88rpx] h-[88rpx] rounded-full"
                                                    mode="aspectFill"></image>
                                                <image
                                                    v-if="detailData.account_type"
                                                    :src="platformLogo[detailData.account_type as keyof typeof platformLogo].activeIcon"
                                                    class="w-[32rpx] h-[32rpx] absolute bottom-0 right-0"></image>
                                            </view>
                                            <view class="flex-1 text-[#00000080]"
                                                >用户名：{{
                                                    detailData.account_info?.nickname ||
                                                    detailData.account_info?.wechat_nickname ||
                                                    "-"
                                                }}</view
                                            >
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <template
                                v-if="[TaskTypeEnum.CUSTOMER, TaskTypeEnum.PUBLISH].includes(detailData.task_type)">
                                <template v-if="detailData.detail">
                                    <view
                                        class="bg-white rounded-[20rpx] p-5 mt-3"
                                        v-if="detailData.task_type == TaskTypeEnum.CUSTOMER">
                                        <view class="font-bold">
                                            线索词（{{ detailData.detail?.keywords?.length || 0 }} 个）
                                        </view>
                                        <view class="flex flex-wrap gap-2 mt-3">
                                            <view v-for="item in detailData.detail?.keywords" :key="item">
                                                <view
                                                    class="px-[18rpx] py-[8rpx] text-xs rounded-[12rpx] bg-[#00000005] text-[#00000080]"
                                                    >{{ item }}</view
                                                >
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        class="bg-white rounded-[20rpx] p-5 mt-3 flex gap-x-3"
                                        v-if="detailData.task_type == TaskTypeEnum.PUBLISH">
                                        <view
                                            class="flex-shrink-0 relative w-[180rpx] h-[240rpx] rounded-[20rpx] overflow-hidden">
                                            <image
                                                :src="detailData.detail?.pic"
                                                class="w-full h-full"
                                                mode="aspectFill"
                                                @click="handlePreviewImage"></image>
                                            <view
                                                class="w-full h-full flex items-center justify-center absolute top-0 left-0"
                                                v-if="detailData.detail?.material_type == 1">
                                                <view
                                                    class="rounded-full bg-[#ffffff33] w-[68rpx] h-[68rpx]"
                                                    style="backdrop-filter: blur(5px)"
                                                    @click="handlePlayVideo">
                                                    <image
                                                        src="/static/images/icons/play.svg"
                                                        class="w-full h-full"></image>
                                                </view>
                                            </view>
                                        </view>
                                        <view class="flex-1 flex flex-col justify-between">
                                            <view class="mr-14">
                                                <view class="font-bold text-[#000000e6] line-clamp-2">
                                                    {{ detailData.detail?.material_title }}
                                                </view>
                                                <view class="text-[#00000080] mt-1 text-xs line-clamp-2">
                                                    {{ detailData.detail?.material_subtitle }}
                                                </view>
                                            </view>
                                            <view>
                                                <view
                                                    class="flex flex-wrap items-center gap-2"
                                                    v-if="detailData.detail?.material_tag">
                                                    <view
                                                        class="text-[22rpx] text-[#0000004d]"
                                                        v-for="item in detailData.detail?.material_tag"
                                                        :key="item"
                                                        >#{{ item }}</view
                                                    >
                                                </view>
                                                <view class="text-[22rpx] text-[#00000080] mt-1">
                                                    发布时间：{{ detailData.detail?.publish_time }}
                                                </view>
                                            </view>
                                        </view>
                                    </view></template
                                >
                                <view v-else class="bg-white rounded-[20rpx] p-5 mt-3">
                                    <empty text="内容还在生成中，请耐心等待..." :size="240" />
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>
                <view class="px-[26rpx] pb-5">
                    <view
                        class="h-[98rpx] flex items-center justify-center bg-error text-white font-bold rounded-[20rpx]"
                        @click="showConfirmDialog = true"
                        >删除任务</view
                    >
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
    <video-preview
        v-model:show="showVideoPreview"
        title="视频预览"
        :poster="detailData.detail?.pic"
        :video-url="detailData.detail?.material_url" />
</template>

<script setup lang="ts">
import { deleteDeviceTaskCalendar, getDeviceTaskSubtasks } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";

enum TaskTypeEnum {
    UNKNOWN = 0,
    PUBLISH = 1,
    TAKE_OVER = 2,
    CARE = 3,
    CUSTOMER = 4,
    WECHAT = 5,
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

const { platformLogo, getTaskStatusStyle, getTaskStatusText } = useDevice();

const handleDeleteTask = async () => {
    showConfirmDialog.value = false;
    uni.showLoading({
        title: "删除中...",
        mask: true,
    });
    try {
        await deleteDeviceTaskCalendar({
            id: detailData.value.task_id,
            sub_task_id: detailData.value.sub_task_id,
            source: detailData.value.source,
        });
        uni.hideLoading();
        uni.showToast({
            title: "删除成功",
            icon: "none",
            duration: 3000,
        });
        show.value = false;
        emit("delete");
    } catch (error) {
        uni.hideLoading();
        uni.showToast({
            title: "删除失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handlePlayVideo = () => {
    showVideoPreview.value = true;
};

const handlePreviewImage = () => {
    const { material_url } = detailData.value.detail;
    if (!material_url) return;
    const pics = material_url.split(",");
    console.log("pics", pics);
    uni.previewImage({
        urls: pics,
    });
};

const getDetail = async (data: any) => {
    const res = await getDeviceTaskSubtasks({
        id: data.id,
        sub_task_id: data.sub_task_id,
        source: data.source,
    });
    detailData.value = {
        ...res,
        task_id: data.id,
        sub_task_id: data.sub_task_id,
        source: data.source,
    };
};

defineExpose({
    getDetail,
});
</script>

<style scoped></style>
