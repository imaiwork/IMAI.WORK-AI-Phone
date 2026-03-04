<template>
    <view class="bg-white rounded-[20rpx]">
        <view class="px-[32rpx] pt-[40rpx] pb-[28rpx] flex gap-[24rpx] items-center">
            <view class="relative shrink-0">
                <image
                    :src="item.avatar"
                    lazy-load
                    class="w-[88rpx] h-[88rpx] rounded-full border-[4rpx] border-[#FFE0B2]" />
                <image
                    :src="getPlatformLogo(item.account_type)"
                    lazy-load
                    class="absolute bottom-[-4rpx] right-[-4rpx] w-[36rpx] h-[36rpx] rounded-full border-[3rpx] border-white" />
            </view>
            <view class="flex-1 min-w-0">
                <view class="text-[30rpx] font-semibold text-[#1A1A1A] truncate">{{ item.account_name }}</view>
                <view class="mt-[10rpx] flex items-center gap-[12rpx] flex-wrap">
                    <view
                        class="inline-flex items-center bg-[#FFF3E0] border-[1rpx] border-[#FFD180] rounded-[30rpx] px-[18rpx] py-[5rpx]"
                        v-if="item.industry_keyword">
                        <text class="text-[22rpx] font-medium text-[#BA6F0D]">{{ item.industry_keyword }}</text>
                    </view>
                    <view
                        class="inline-flex items-center rounded-[30rpx] px-[18rpx] py-[5rpx]"
                        :class="isTrace ? 'bg-[#E8F5E9] border-[1rpx] border-[#A5D6A7]' : ''"
                        :style="!isTrace ? 'background: linear-gradient(to right, #FF8C42, #FF5C5C)' : ''">
                        <text class="text-[22rpx] font-semibold" :class="isTrace ? 'text-[#388E3C]' : 'text-white'">
                            {{ isTrace ? "留痕获客" : "截流获客" }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <view class="mx-[32rpx] mb-[28rpx] rounded-[20rpx] overflow-hidden bg-[#FAFAFA] flex">
            <view
                v-for="(stat, index) in stats"
                :key="stat.label"
                class="flex-1 flex flex-col items-center py-[20rpx] gap-[6rpx]"
                :class="index < stats.length - 1 ? 'border-r-[1rpx] border-[#F0F0F0]' : ''">
                <text class="text-[32rpx] font-bold text-[#1A1A1A]">{{ stat.value }}</text>
                <text class="text-[22rpx] text-[#AAAAAA]">{{ stat.label }}</text>
            </view>
        </view>

        <view
            class="mx-[32rpx] mb-[24rpx] rounded-[20rpx] border-[1rpx] border-[#F0F0F0] bg-[#FAFAFA] p-[24rpx] flex gap-[20rpx] items-start">
            <image
                :src="getPlatformLogo(item.account_type)"
                lazy-load
                class="w-[64rpx] h-[64rpx] rounded-[14rpx] shrink-0" />
            <view class="flex-1 min-w-0">
                <text class="text-[20rpx] text-[#AAAAAA] block mb-[6rpx]">来源笔记</text>
                <text class="text-[24rpx] text-[#444444] leading-relaxed" :class="noteExpanded ? '' : 'line-clamp-2'">{{
                    item.note_title
                }}</text>
                <view class="mt-[8rpx] flex items-center gap-[6rpx]" @click="noteExpanded = !noteExpanded">
                    <text class="text-[22rpx] text-[#FF8C42]">{{ noteExpanded ? "收起" : "展开" }}</text>
                    <view
                        class="transition-transform duration-300 origin-center leading-[0]"
                        :style="{ transform: noteExpanded ? 'rotate(180deg)' : 'rotate(0deg)' }">
                        <u-icon name="arrow-down" color="#FF8C42" size="11"></u-icon>
                    </view>
                </view>
            </view>
        </view>

        <view
            class="mx-[32rpx] mb-[28rpx] rounded-[20rpx] overflow-hidden border-[1rpx] border-[#FFE0B2]"
            style="background: linear-gradient(180deg, #fff8f0 0%, #fdf3e3 100%)">
            <view class="flex items-center justify-between px-[28rpx] py-[22rpx]" @click="toggleCollapse">
                <view class="flex items-center gap-[14rpx]">
                    <view
                        class="w-[6rpx] h-[32rpx] rounded-full"
                        style="background: linear-gradient(to bottom, #ff8c42, #ff5c5c)"></view>
                    <text class="text-[24rpx] font-semibold text-[#BA6F0D]">{{ collapseTypeLabel }}</text>
                    <view v-if="item.filter_keyword" class="bg-[#FFE0B2] rounded-[20rpx] px-[14rpx] py-[4rpx]">
                        <text class="text-[20rpx] text-[#BA6F0D] font-medium">#{{ item.filter_keyword }}</text>
                    </view>
                </view>
                <view class="flex items-center gap-[8rpx]">
                    <text class="text-[22rpx] text-[#BA6F0D]">{{ isExpanded ? "收起" : "展开" }}</text>
                    <view
                        class="transition-transform duration-300 origin-center leading-[0]"
                        :style="{ transform: isExpanded ? 'rotate(180deg)' : 'rotate(0deg)' }">
                        <u-icon name="arrow-down" color="#BA6F0D" size="12"></u-icon>
                    </view>
                </view>
            </view>

            <view class="mx-[28rpx] h-[1rpx] bg-[#FFE0B2] opacity-60"></view>

            <view v-show="isExpanded" class="px-[28rpx] py-[28rpx] flex flex-col gap-[24rpx]">
                <template v-if="blocks.theirComment">
                    <view class="flex items-center gap-[12rpx]">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full border-[2rpx] border-[#FFCC80] bg-[#FFF3E0] flex items-center justify-center shrink-0">
                            <text class="text-[20rpx] font-bold text-[#BA6F0D]">1</text>
                        </view>
                        <text class="text-[24rpx] font-semibold text-[#BA6F0D]">Ta 的评论</text>
                    </view>
                    <view class="flex gap-[16rpx] items-start pl-[12rpx]">
                        <image :src="item.avatar" lazy-load class="w-[60rpx] h-[60rpx] rounded-full shrink-0" />
                        <view class="flex-1 min-w-0">
                            <text class="text-[22rpx] font-semibold text-[#555555] block mb-[8rpx]">{{
                                item.account_name
                            }}</text>
                            <view class="bg-[#F2F2F2] rounded-[8rpx_28rpx_28rpx_28rpx] px-[20rpx] py-[16rpx]">
                                <text class="text-[24rpx] text-[#333333] leading-relaxed">{{ item.content }}</text>
                            </view>
                        </view>
                    </view>
                    <view class="flex items-center gap-[12rpx] pl-[30rpx]">
                        <view
                            class="w-[2rpx] h-[28rpx] rounded-full shrink-0"
                            style="background: linear-gradient(to bottom, #ffd180, #ff8c42)"></view>
                        <text class="text-[20rpx] text-[#CCAA88]">{{ blocks.actionLabel }}</text>
                    </view>
                </template>

                <template v-if="blocks.commentReply">
                    <view class="flex items-center gap-[12rpx]">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full border-[2rpx] border-[#FFCC80] bg-[#FFF3E0] flex items-center justify-center shrink-0">
                            <text class="text-[20rpx] font-bold text-[#BA6F0D]">1</text>
                        </view>
                        <text class="text-[24rpx] font-semibold text-[#BA6F0D]">Ta 的内容</text>
                    </view>
                    <view class="flex gap-[16rpx] items-start pl-[12rpx]">
                        <image :src="item.avatar" lazy-load class="w-[60rpx] h-[60rpx] rounded-full shrink-0" />
                        <view class="flex-1 min-w-0">
                            <text class="text-[22rpx] font-semibold text-[#555555] block mb-[8rpx]"
                                >{{ item.account_name }}（Ta）</text
                            >
                            <view class="bg-[#F2F2F2] rounded-[8rpx_28rpx_28rpx_28rpx] px-[20rpx] py-[16rpx]">
                                <text class="text-[24rpx] text-[#333333] leading-relaxed">{{ item.content }}</text>
                            </view>
                        </view>
                    </view>
                    <view class="flex items-center gap-[12rpx] pl-[30rpx]">
                        <view
                            class="w-[2rpx] h-[28rpx] rounded-full shrink-0"
                            style="background: linear-gradient(to bottom, #ffd180, #ff8c42)"></view>
                        <text class="text-[20rpx] text-[#CCAA88]">↓ 评论回复</text>
                    </view>
                    <view class="flex items-center gap-[12rpx]">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center shrink-0"
                            style="background: linear-gradient(135deg, #ff8c42, #ff5c5c)">
                            <text class="text-[20rpx] font-bold text-white">2</text>
                        </view>
                        <text class="text-[24rpx] font-semibold text-[#FF6B35]">已评论了 Ta</text>
                    </view>
                    <view class="flex gap-[16rpx] items-start flex-row-reverse pl-[12rpx]">
                        <image :src="item.execute_avatar" lazy-load class="w-[60rpx] h-[60rpx] rounded-full shrink-0" />
                        <view class="flex-1 min-w-0">
                            <text class="text-[22rpx] font-semibold text-[#FF8C42] block mb-[8rpx] text-right"
                                >{{ item.execute_name }}（我）</text
                            >
                            <view
                                class="rounded-[28rpx_8rpx_28rpx_28rpx] px-[20rpx] py-[16rpx] text-right"
                                style="background: linear-gradient(135deg, #ff8c42, #ff5c5c)">
                                <text class="text-[24rpx] text-white leading-relaxed">{{ item.comment_content }}</text>
                            </view>
                        </view>
                    </view>
                </template>

                <template v-if="blocks.touchContent">
                    <view class="bg-white rounded-[12rpx] px-[20rpx] py-[16rpx] border-l-[4rpx] border-[#FFD180]">
                        <text class="text-[20rpx] text-[#AAAAAA] block mb-[8rpx]">被评论作品</text>
                        <text class="text-[22rpx] text-[#888888] leading-relaxed">{{
                            item.touch_content || "--"
                        }}</text>
                    </view>
                </template>

                <template v-if="blocks.myAction">
                    <view class="flex items-center gap-[12rpx]">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center shrink-0"
                            style="background: linear-gradient(135deg, #ff8c42, #ff5c5c)">
                            <text class="text-[20rpx] font-bold text-white">{{ blocks.theirComment ? "2" : "1" }}</text>
                        </view>
                        <text class="text-[24rpx] font-semibold text-[#FF6B35]">{{ blocks.myActionTitle }}</text>
                    </view>
                    <view class="flex gap-[16rpx] items-start flex-row-reverse pl-[12rpx]">
                        <image :src="item.execute_avatar" lazy-load class="w-[60rpx] h-[60rpx] rounded-full shrink-0" />
                        <view class="flex-1 min-w-0">
                            <text class="text-[22rpx] font-semibold text-[#FF8C42] block mb-[8rpx] text-right"
                                >{{ item.execute_name }}（我）</text
                            >
                            <view
                                class="rounded-[28rpx_8rpx_28rpx_28rpx] px-[20rpx] py-[16rpx] text-right"
                                style="background: linear-gradient(135deg, #ff8c42, #ff5c5c)">
                                <text class="text-[24rpx] text-white leading-relaxed">{{
                                    item.comment_content || "--"
                                }}</text>
                            </view>
                        </view>
                    </view>
                </template>

                <template v-if="badges.length">
                    <view class="pt-[16rpx] border-[0] border-t-[1rpx] border-dashed border-[#FFE0B2]">
                        <text class="text-[20rpx] text-[#CCAA88] block mb-[16rpx]">附加操作</text>
                        <view class="flex flex-wrap gap-[12rpx]">
                            <view
                                v-for="badge in badges"
                                :key="badge.label"
                                class="flex items-center gap-[8rpx] bg-[#FFF8F0] border-[1rpx] border-[#FFE0B2] rounded-[30rpx] px-[20rpx] py-[8rpx]">
                                <text class="text-[22rpx]">{{ badge.icon }}</text>
                                <text class="text-[22rpx] text-[#FF8C42] font-medium">{{ badge.label }}</text>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>

        <view class="px-[32rpx] pb-[28rpx] flex items-center gap-[12rpx]">
            <view class="w-[8rpx] h-[8rpx] rounded-full bg-[#D0D0D0] shrink-0"></view>
            <text class="text-[22rpx] text-[#AAAAAA]">执行：{{ item.execute_name }}（{{ item.execute_account }}）</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useDevice } from "@/ai_modules/device/hooks/useDevice";

const props = defineProps<{
    item: any;
}>();

const { platform } = useDevice();

const getPlatformLogo = (type: number) => platform.value[type as keyof typeof platform.value]?.activeIcon;

const isExpanded = ref(true);
const noteExpanded = ref(false);

const toggleCollapse = () => {
    isExpanded.value = !isExpanded.value;
};

const isCityType = computed(() => props.item.industry_type === 1);
const isTrace = computed(() => props.item.task_type == 3);
const isComment = computed(() => props.item.task_type == 1);

const blocks = computed(() => {
    const { task_type } = props.item;
    const city = isCityType.value;

    const theirComment = !city && (task_type == 2 || task_type == 3);
    const commentReply = !city && task_type == 1;
    const touchContent = !city && task_type == 3;
    const myAction = !commentReply;

    const myActionTitle = task_type == 2 ? "已私信了 Ta" : "已在 Ta 的作品下评论";
    const actionLabel = task_type == 2 ? "↓ 私信触达" : "↓ 评论触达";

    return { theirComment, commentReply, touchContent, myAction, myActionTitle, actionLabel };
});

const stats = computed(() => [
    { label: "关注", value: props.item.follows ?? 0 },
    { label: "粉丝", value: props.item.fans ?? 0 },
    { label: "点赞", value: props.item.likes ?? 0 },
]);

const badges = computed(() => {
    const { is_like, is_follow, marker_method } = props.item;
    const list: { icon: string; label: string }[] = [];
    if (is_like == 1) list.push({ icon: "👍", label: "已点赞作品" });
    if (is_follow == 1) list.push({ icon: "❤️", label: "已点赞评论" });
    if (marker_method?.includes(2)) list.push({ icon: "➕", label: "已关注 Ta" });
    if (marker_method?.includes(4)) list.push({ icon: "💬", label: "已评论作品" });
    if (marker_method?.includes(5)) list.push({ icon: "⭐", label: "已收藏作品" });
    return list;
});

const collapseTypeLabel = computed(() => {
    const prefix = isCityType.value ? "同城获客" : "自由获客";
    if (isTrace.value) return prefix;
    return `${prefix} · ${isComment.value ? "评论" : "私信"}`;
});
</script>

<style scoped></style>
