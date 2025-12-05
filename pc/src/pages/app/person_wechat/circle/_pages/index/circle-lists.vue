<template>
    <div class="h-full">
        <template v-if="circleList.length > 0">
            <DynamicScroller
                class="dynamic-scroller h-full py-9"
                ref="circleScrollbarRef"
                :items="circleList"
                :min-item-size="100"
                key-field="CircleId"
                @scroll="handleScroll">
                <template #default="{ item, index, active }">
                    <DynamicScrollerItem
                        :item="item"
                        :active="active"
                        :size-dependencies="[
                            item.Content.Text,
                            item.Content.Images,
                            item.Content.Video,
                            item.Content.Link,
                            item.Content.Ext,
                            item.Likes,
                            item.Comments,
                        ]"
                        :data-index="index">
                        <div class="px-6 flex gap-x-4">
                            <div>
                                <ElAvatar shape="square" :size="42" :icon="UserFilled" />
                            </div>
                            <div class="flex-1">
                                <div class="text-[#586B93] text-lg">
                                    {{ item.WeChatId }}
                                </div>
                                <div class="mt-2 flex flex-col gap-y-2">
                                    <!-- 文本内容 -->
                                    <div v-if="item.Content.Text">
                                        <span v-html="formatMessage(item.Content.Text)"></span>
                                    </div>
                                    <!-- 图片网格 (非链接卡片时显示) -->
                                    <div
                                        v-if="item.Content.Images?.length && !hasLink(item.Content.Link)"
                                        :class="[
                                            item.Content.Images.length > 1 ? 'grid grid-cols-3 gap-1' : ' max-w-[50%]',
                                        ]">
                                        <div
                                            v-for="(image, imageIdx) in item.Content.Images"
                                            :key="image.mediaId"
                                            class="leading-[0] cursor-pointer"
                                            :class="[item.Content.Images.length > 1 ? 'h-[150px] w-full' : '']"
                                            @click="
                                                previewImage({
                                                    ...item,
                                                    imageIdx,
                                                })
                                            ">
                                            <img
                                                :src="image.ThumbImg"
                                                class="h-full w-auto max-h-[300px]"
                                                :class="[
                                                    item.Content.Images.length == 1
                                                        ? 'w-auto object-contain'
                                                        : 'w-full object-cover',
                                                ]" />
                                        </div>
                                    </div>
                                    <!-- 视频内容 -->
                                    <div v-if="hasVideo(item.Content.Video)" class="w-fit h-full relative">
                                        <template v-if="item.Content.Video.Url">
                                            <video
                                                v-if="item.Content.Video.Url"
                                                class="max-h-[400px]"
                                                :src="item.Content.Video.Url"></video>
                                            <div
                                                class="absolute top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%]"
                                                @click="emit('previewVideo', item.Content.Video.Url)">
                                                <play-btn />
                                            </div>
                                        </template>

                                        <div
                                            v-else
                                            class="w-[200px] h-[200px] bg-gray-200 flex justify-center items-center cursor-pointer"
                                            @click="previewVideo({ data: item })">
                                            <ElButton>点击获取视频</ElButton>
                                        </div>
                                    </div>
                                    <!-- 链接卡片 -->
                                    <div
                                        v-if="hasLink(item.Content.Link)"
                                        class="flex items-center gap-x-2 p-2 bg-[#F7F7F7] cursor-pointer"
                                        @click="jumpLink(item.Content.Link)">
                                        <div class="flex-shrink-0" v-if="item.Content.Images?.length">
                                            <img
                                                class="w-12 h-12 object-cover"
                                                :src="item.Content.Images[0].ThumbImg" />
                                        </div>
                                        <div class="line-clamp-2">
                                            {{ item.Content.Link.Description }}
                                        </div>
                                    </div>
                                    <div v-if="item.Content.Ext" v-html="formatExtMessage(item.Content.Ext)"></div>
                                </div>
                                <!-- 元信息与操作按钮 -->
                                <div class="flex items-center justify-between my-[10px]">
                                    <div class="flex items-center gap-x-2">
                                        <div class="text-[#9E9E9E] text-xs">
                                            {{ item.publishTime }}
                                        </div>
                                        <ElTooltip
                                            v-if="item.WeChatId == currentWechat.wechat_id"
                                            placement="right"
                                            content="删除">
                                            <div class="leading-[0] cursor-pointer" @click="handleDelete(item)">
                                                <Icon name="el-icon-Delete"></Icon>
                                            </div>
                                        </ElTooltip>
                                    </div>
                                    <ElTooltip trigger="click" placement="left" :show-arrow="false">
                                        <template #content>
                                            <div class="flex">
                                                <ElButton link class="!p-0" @click="handleLike(item)">
                                                    <template v-if="isLike(item)">
                                                        <Icon
                                                            name="local-icon-heart_fill"
                                                            color="#F82B2B"
                                                            :size="18"></Icon>
                                                        <span class="text-white ml-2">取消</span>
                                                    </template>
                                                    <template v-else>
                                                        <Icon name="local-icon-heart" color="#ffffff" :size="18"></Icon>
                                                        <span class="text-white ml-2">点赞</span>
                                                    </template>
                                                </ElButton>
                                                <ElButton link class="!p-0" @click="handleComment(item, index)">
                                                    <Icon name="local-icon-comment" color="#ffffff" :size="18"></Icon>
                                                    <span class="text-white ml-2">评论</span>
                                                </ElButton>
                                            </div>
                                        </template>
                                        <div
                                            class="flex items-center justify-center gap-x-1 bg-[#F7F7F7] rounded h-6 w-9 cursor-pointer">
                                            <div
                                                class="w-1 h-1 rounded-full bg-[#576B95]"
                                                v-for="i in 2"
                                                :key="i"></div>
                                        </div>
                                    </ElTooltip>
                                </div>
                                <!-- 点赞与评论区 -->
                                <div class="bg-[#F7F7F7] rounded">
                                    <!-- 点赞列表 -->
                                    <div
                                        v-if="item.Likes?.length"
                                        class="flex items-center gap-2 py-2 px-3 border-b border-gray-200">
                                        <Icon name="local-icon-heart" color="#586B93"></Icon>
                                        <div class="flex-1">
                                            <span class="text-[#586B93]">
                                                {{ item.Likes.map((like: any) => like.NickName).join("，") }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- 评论列表 -->
                                    <div v-if="item.Comments?.length" class="flex flex-col">
                                        <div
                                            v-for="(comment, commentIdx) in item.Comments"
                                            :key="comment.CommentId"
                                            class="cursor-pointer hover:bg-[#e5e5e5] px-3 min-h-8 flex items-center"
                                            @click="handleReply(item, index, commentIdx)">
                                            <div v-if="comment.ToWeChatId">
                                                <span class="text-[#586B93]">{{ comment.FromName }}</span>
                                                <span class="mx-1">回复</span>
                                                <span class="text-[#586B93]">{{ comment.ToWeChatId }}：</span>
                                                <span v-html="formatMessage(comment.Content)"></span>
                                            </div>
                                            <div v-else class="break-all">
                                                <span class="text-[#586B93]">{{ comment.FromName }}:</span>
                                                <span v-html="formatMessage(comment.Content)"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- 评论输入框 -->
                                    <div
                                        v-if="commentState.activeIndex === index"
                                        class="relative py-2 px-3"
                                        v-on-click-outside="[closeCommentBox, { ignore: ['.el-loading-mask'] }]">
                                        <ElInput
                                            ref="commentContentRef"
                                            v-model="commentState.content[index]"
                                            type="textarea"
                                            resize="none"
                                            :autosize="{ minRows: 3, maxRows: 7 }"
                                            :placeholder="commentState.placeholder" />
                                        <div class="absolute bottom-3 right-6 flex items-center gap-x-2">
                                            <ElPopover
                                                placement="bottom"
                                                width="466"
                                                trigger="click"
                                                :show-arrow="false"
                                                :popper-style="{ padding: 0 }"
                                                @show="commentState.showEmoji = true"
                                                @hide="commentState.showEmoji = false">
                                                <template #reference>
                                                    <div
                                                        class="rounded-lg hover:bg-token-sidebar-surface-secondary p-2 cursor-pointer">
                                                        <Icon name="local-icon-phiz" :size="24" />
                                                    </div>
                                                </template>
                                                <div>
                                                    <EmojiContainer @chooseEmoji="handleChooseEmoji" />
                                                </div>
                                            </ElPopover>
                                            <ElButton
                                                color="#24C168"
                                                class="!text-white"
                                                @click="handleSendComment(item, index)"
                                                >发送</ElButton
                                            >
                                        </div>
                                    </div>
                                </div>
                                <ElDivider class="!my-4" />
                            </div>
                        </div>
                    </DynamicScrollerItem>
                </template>
            </DynamicScroller>
        </template>
        <div v-else class="flex justify-center items-center h-full">
            <ElEmpty description="暂无动态" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, shallowRef, nextTick } from "vue";
import { UserFilled } from "@element-plus/icons-vue";
import { type InputInstance } from "element-plus";
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import { setRangeText } from "@/utils/dom";
import EmojiContainer from "../../../_components/emoji.vue";
import useHandle from "../../../_hooks/useHandle";
import { HandleEventEnum } from "../../../_enums";
import { vOnClickOutside } from "@vueuse/components";

// ==================================================================================================
// Props & Emits
// ==================================================================================================
const props = withDefaults(
    defineProps<{
        circleList: any[];
    }>(),
    {
        circleList: () => [],
    }
);

const emit = defineEmits<{
    (e: "bottom"): void;
    (e: "previewVideo", url: string): void;
}>();

// ==================================================================================================
// Composables
// ==================================================================================================
const { triggerHandleEvent, previewImage, previewVideo, currentWechat, emojis } = useHandle();

// ==================================================================================================
// Refs
// ==================================================================================================
const circleScrollbarRef = ref<InstanceType<typeof DynamicScroller> | null>(null);
const commentContentRef = shallowRef<InputInstance>();

// ==================================================================================================
// State Management
// ==================================================================================================
// 评论功能的状态管理
const commentState = reactive({
    activeIndex: -1, // 当前激活评论框的朋友圈索引
    replyToIndex: -1, // 如果是回复，则为被回复评论的索引
    content: {} as Record<number, string>, // 各个朋友圈的评论内容
    type: "comment" as "comment" | "reply", // 'comment' 或 'reply'
    placeholder: "发表评论",
    showEmoji: false,
});

// ==================================================================================================
// Helper Functions
// ==================================================================================================
/**
 * 检查朋友圈条目是否包含有效的链接内容
 * @param link - 链接对象
 */
const hasLink = (link: any): boolean => {
    return !!(link && link.Url);
};

/**
 * 检查朋友圈条目是否包含有效的视频内容
 * @param video - 视频对象
 */
const hasVideo = (video: any): boolean => {
    return !!video && video.MediaId;
};

/**
 * 跳转链接
 * @param link - 链接对象
 */
const jumpLink = (link: any) => {
    if (link.Url) {
        window.open(link.Url, "_blank");
    }
};

// ==================================================================================================
// Event Handlers
// ==================================================================================================
/**
 * 处理滚动事件，用于无限加载
 * @param e - 滚动事件对象
 */
const handleScroll = (e: Event) => {
    const target = e.target as HTMLElement;
    // 触底判断
    if (target.scrollHeight - target.clientHeight - target.scrollTop < 1) {
        emit("bottom");
    }
};

/**
 * 处理点赞
 * @param item - 朋友圈条目
 */
const handleLike = (item: any) => {
    triggerHandleEvent("action", {
        type: HandleEventEnum.Like,
        data: {
            ...item,
            isLike: isLike(item),
        },
    });
};

/**
 * 检查是否点赞
 * @param item - 朋友圈条目
 */
const isLike = (item: any) => {
    return item.Likes.some((like: any) => like.FriendId == currentWechat.value?.wechat_id);
};

/**
 * 处理删除
 * @param item - 朋友圈条目
 */
const handleDelete = (item: any) => {
    triggerHandleEvent("action", {
        type: HandleEventEnum.DeleteCircle,
        data: item,
    });
};

/**
 * 关闭评论输入框 (v-on-click-outside 使用)
 */
const closeCommentBox = () => {
    if (commentState.showEmoji) return; // 如果表情选择器是打开的，则不关闭
    commentState.activeIndex = -1;
    commentState.replyToIndex = -1;
};

/**
 * 聚焦到评论输入框
 */
const focusCommentInput = async () => {
    await nextTick();
    commentContentRef.value?.focus();
};

/**
 * 打开评论框
 * @param item - 朋友圈条目
 * @param index - 朋友圈索引
 */
const handleComment = (item: any, index: number) => {
    if (commentState.activeIndex === index && commentState.type === "comment") {
        closeCommentBox();
        return;
    }
    commentState.type = "comment";
    commentState.placeholder = "发表评论";
    commentState.activeIndex = index;
    commentState.replyToIndex = -1;
    focusCommentInput();
};

/**
 * 打开回复框
 * @param item - 朋友圈条目
 * @param itemIndex - 朋友圈索引
 * @param commentIndex - 被回复的评论索引
 */
const handleReply = (item: any, itemIndex: number, commentIndex: number) => {
    const repliedComment = item.Comments[commentIndex];

    if (repliedComment.FromWeChatId == currentWechat.value?.wechat_id) {
        useNuxtApp().$confirm({
            message: "确定删除这条评论吗？",
            onConfirm: () => {
                triggerHandleEvent("action", {
                    type: HandleEventEnum.DeleteComment,
                    data: {
                        ...item,
                        deleteComment: repliedComment,
                    },
                });
            },
        });
        return;
    }
    if (
        commentState.activeIndex === itemIndex &&
        commentState.type === "reply" &&
        commentState.replyToIndex === commentIndex
    ) {
        closeCommentBox();
        return;
    }
    commentState.type = "reply";
    commentState.placeholder = `回复${repliedComment.FromName}:`;
    commentState.activeIndex = itemIndex;
    commentState.replyToIndex = commentIndex;
    focusCommentInput();
};

/**
 * 发送评论或回复
 * @param item - 朋友圈条目
 * @param index - 朋友圈索引
 */
const handleSendComment = (item: any, index: number) => {
    const content = commentState.content[index];
    if (!content || !content.trim()) {
        feedback.msgWarning("请输入内容");
        return;
    }

    const data: any = {
        ...item,
        msg: content,
    };

    if (commentState.type === "reply") {
        data.reply = item.Comments[commentState.replyToIndex];
    }

    triggerHandleEvent("action", {
        type: HandleEventEnum.SendComment,
        data,
    });

    // 清空并关闭评论框
    commentState.content[index] = "";
    closeCommentBox();
};

/**
 * 处理选择表情
 * @param emoji - 选中的表情对象
 */
const handleChooseEmoji = ({ emoji }: { emoji: string }) => {
    if (commentContentRef.value?.textarea) {
        const newContent = setRangeText(commentContentRef.value.textarea, emoji);
        commentState.content[commentState.activeIndex] = newContent;
    }
};

/**
 * 处理表情
 * @param message - 朋友圈条目
 */
const formatMessage = (message: string) => {
    const emojiRegex = /\[(.*?)\]/g;
    return message.replace(emojiRegex, (match) => {
        const emoji = emojis.find((emoji) => emoji.name === match);
        if (emoji) {
            return `<img src="${emoji.src}" class="w-[18px] h-[18px] inline-block mx-1 align-text-bottom" />`;
        }
        return match;
    });
};

const formatExtMessage = (message: string) => {
    const data = JSON.parse(message);
    if (data.liveId) {
        return `
    <div class="">
        <div>
            <img src="${data.cover}" class="max-h-[300px] object-contain cursor-pointer" />
        </div>
        <div class="text-[#586B93] mt-1">公众号·${data.nickname}</div>
    </div>`;
    } else {
        return `
    <div class="">
        <div>
            <img src="${data.mediaList[0].thumb}" class="max-h-[300px] object-contain cursor-pointer" />
        </div>
        <div class="text-[#586B93] mt-1">视频号·${data.nickname}</div>
    </div>`;
    }
};

// ==================================================================================================
// Exposed Methods
// ==================================================================================================
defineExpose({
    // 可根据需要暴露方法给父组件
});
</script>

<style scoped lang="scss">
:deep(.el-textarea__inner) {
    padding-bottom: 50px;
    &:focus {
        box-shadow: 0 0 0 1px #24c168;
    }
    &::-webkit-scrollbar {
        width: 0;
    }
}
</style>
