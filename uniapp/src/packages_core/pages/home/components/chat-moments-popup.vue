<template>
    <popup-bottom
        v-model="visibleProxy"
        height="88%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true">
        <template #header>
            <view
                class="bg-white rounded-t-[32rpx] pt-[24rpx] pb-[28rpx] px-[40rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[24rpx] bg-[#e5e7eb] rounded-full" />
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-[16rpx]">
                        <view class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-[#fdf2f8] flex items-center justify-center">
                            <image :src="headerIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">
                                {{ overview.title || "帮我发朋友圈" }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center"
                        @click="visibleProxy = false">
                        <u-icon name="close" size="28" color="#6b7280"></u-icon>
                    </view>
                </view>
                <view class="flex gap-[16rpx] mt-[24rpx]">
                    <view class="flex-1 bg-[#fdf2f8] rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-[#ec4899]">{{
                            summary.today_interaction_count
                        }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">今日互动人数</text>
                    </view>
                    <view class="flex-1 bg-[#fef2f2] rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-[#f87171]">{{ summary.auto_like_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">自动点赞</text>
                    </view>
                    <view class="flex-1 bg-[#faf5ff] rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-[#9333ea]">{{ summary.auto_comment_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">自动评论</text>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <z-paging
                ref="pagingRef"
                v-model="items"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="p-[32rpx] pb-[40rpx]">
                    <view class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            v-for="item in items"
                            :key="item.id"
                            class="px-[32rpx] py-[28rpx] border-b-[2rpx] border-[#f9fafb] last:border-b-0 flex flex-col gap-[16rpx]">
                            <!-- 头部：动作 + 朋友圈作者 + 时间 -->
                            <view class="flex items-start justify-between gap-[16rpx]">
                                <view class="flex items-center gap-[16rpx] flex-1 min-w-0">
                                    <view
                                        class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                        :class="hasComment(item) ? 'bg-[#faf5ff]' : 'bg-[#fef2f2]'">
                                        <image
                                            :src="hasComment(item) ? messagePurpleIcon : heartRedIcon"
                                            mode="aspectFit"
                                            class="w-[28rpx] h-[28rpx]" />
                                    </view>
                                    <view class="flex-1 min-w-0">
                                        <text class="text-xs text-[#374151] leading-[40rpx]">
                                            <text>{{ actionLabel(item) }} </text>
                                            <text class="font-semibold text-[#1f2937]">
                                                {{ item.nickname || item.account_name || item.account || "客户" }}
                                            </text>
                                            <text> 的朋友圈</text>
                                        </text>
                                    </view>
                                </view>
                                <text class="text-[20rpx] text-[#9ca3af] flex-shrink-0 pt-[4rpx]">
                                    {{ item.exec_time || item.create_time }}
                                </text>
                            </view>

                            <!-- 朋友圈发布内容 -->
                            <view v-if="item.content" class="bg-[#f9fafb] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                <text class="block text-[20rpx] text-[#9ca3af] mb-[8rpx]">朋友圈内容</text>
                                <text class="block text-xs text-[#4b5563] leading-[36rpx] line-clamp-3">
                                    {{ item.content }}
                                </text>
                            </view>

                            <!-- 评论内容 -->
                            <view v-if="commentText(item)">
                                <text class="block text-[20rpx] text-[#9ca3af] mb-[8rpx]">评论内容</text>
                                <text
                                    class="block text-xs text-[#374151] bg-white border-[2rpx] border-[#f3f4f6] rounded-[16rpx] px-[20rpx] py-[16rpx] leading-[36rpx]">
                                    {{ commentText(item) }}
                                </text>
                            </view>

                            <view v-if="item.screenshot_url" class="flex justify-end">
                                <view
                                    class="flex items-center gap-[6rpx] text-[22rpx] text-primary bg-primary-light-9 px-[16rpx] py-[6rpx] rounded-full"
                                    @click="emit('preview', item.screenshot_url)">
                                    <image :src="imageIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                                    <text>查看截图</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getWechatCircleInteraction } from "@/api/person";
import config from "@/config";

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
}>();
const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "preview", url: string): void;
}>();

const visibleProxy = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const overview = reactive({ title: "" });
const summary = reactive({
    today_interaction_count: 0,
    auto_like_count: 0,
    auto_comment_count: 0,
});
const items = ref<any[]>([]);
const pagingRef = shallowRef<any>();

// 我方评论内容：优先 comment，其次 comments 数组，最后兼容 comment_content
const commentText = (item: any) => {
    if (item.comment) return item.comment;
    if (Array.isArray(item.comments) && item.comments.length) return item.comments.join("；");
    return item.comment_content || "";
};
const hasComment = (item: any) => !!commentText(item);
// 动作文案：优先后端 type_text/type_desc（如「点赞+评论」），否则按是否有评论兜底
const actionLabel = (item: any) =>
    item.type_text || item.type_desc || (hasComment(item) ? "评论" : "点赞");

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getWechatCircleInteraction({
            persona_id: props.personaId,
            page_no,
            page_size,
        });
        const ext = data?.extend || {};
        if (ext.title) overview.title = ext.title;
        const s = ext.summary || {};
        summary.today_interaction_count = Number(s.today_interaction_count) || 0;
        summary.auto_like_count = Number(s.auto_like_count) || 0;
        summary.auto_comment_count = Number(s.auto_comment_count) || 0;
        const lists = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(lists);
    } catch (error) {
        console.warn("getWechatCircleInteraction failed", error);
        pagingRef.value?.complete(false);
    }
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) nextTick(() => pagingRef.value?.reload());
    },
);

const headerIcon = config.baseUrl + "static/images/mp/workflow_image_pink.svg";
const heartRedIcon = config.baseUrl + "static/images/mp/workflow_heart_red.svg";
const messagePurpleIcon = config.baseUrl + "static/images/mp/workflow_message_circle_purple.svg";
const imageIcon = config.baseUrl + "static/images/mp/workflow_image.svg";
</script>
