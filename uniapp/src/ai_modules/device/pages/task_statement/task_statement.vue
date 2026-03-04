<template>
    <view class="h-screen flex flex-col bg-[#F2F3F7]">
        <u-navbar :border-bottom="false" :background="{ background: '#FFFFFF' }">
            <view class="ml-[100rpx] mr-[64rpx] w-full">
                <u-tabs
                    :list="tabList"
                    :is-scroll="false"
                    :current="currTab"
                    active-color="#000000"
                    inactive-color="#00000060"
                    bg-color="transparent"
                    @change="handleTabChange">
                </u-tabs>
            </view>
        </u-navbar>

        <view class="bg-white border-[0] border-b border-solid border-[#F0F0F0]">
            <scroll-view scroll-x>
                <view class="flex gap-[16rpx] whitespace-nowrap px-[32rpx] py-[20rpx]">
                    <view
                        v-for="(item, index) in platformTabs"
                        :key="index"
                        class="px-[28rpx] py-[10rpx] rounded-[50rpx] text-[26rpx] font-semibold transition-all"
                        :class="
                            currPlatformTab === index
                                ? 'bg-black text-white'
                                : 'bg-white text-[#333333] shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)]'
                        "
                        @tap="handlePlatformTabChange(index)">
                        {{ item.name }}
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] pt-[24rpx] pb-[40rpx] flex flex-col gap-[32rpx]">
                    <template v-if="currTaskStatementTab == 0">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="flex items-center gap-[16rpx] mb-[16rpx]">
                                <view class="flex-1 h-[1rpx] bg-[#E5E5E5]"></view>
                                <text class="text-[22rpx] text-[#00000040] font-medium whitespace-nowrap">
                                    {{ formatDate(item.update_time) }} {{ item.start_time }}
                                </text>
                                <view class="flex-1 h-[1rpx] bg-[#E5E5E5]"></view>
                            </view>

                            <view class="bg-white rounded-[20rpx] overflow-hidden">
                                <view
                                    class="flex items-center gap-[24rpx] px-[32rpx] py-[28rpx] border-b border-[#F5F5F5]">
                                    <image
                                        :src="item.account?.avatar"
                                        class="w-[88rpx] h-[88rpx] rounded-[16rpx] border-[2rpx] border-[#F0F0F0] shrink-0"
                                        mode="aspectFill">
                                    </image>
                                    <view class="flex-1 min-w-0">
                                        <view class="flex items-center gap-[10rpx] mb-[6rpx]">
                                            <text
                                                class="text-[30rpx] font-semibold text-[#1A1A1A] truncate max-w-[240rpx]">
                                                {{ item.account?.nickname }}
                                            </text>
                                            <view
                                                class="flex items-center gap-[6rpx] bg-[#F5F5F5] rounded-[30rpx] px-[14rpx] py-[4rpx] shrink-0">
                                                <image
                                                    :src="getPlatformIcon(item.account?.type)"
                                                    class="w-[24rpx] h-[24rpx]"></image>
                                                <text class="text-[20rpx] text-[#666666]">{{
                                                    getPlatformName(item.account?.type)
                                                }}</text>
                                            </view>
                                        </view>
                                        <text class="text-[22rpx] text-[#00000050]">
                                            {{ getPlatformName(item.account?.type) }}：{{ item.account?.account }}
                                        </text>
                                    </view>
                                    <view
                                        class="shrink-0 px-[18rpx] py-[6rpx] rounded-[30rpx] text-[22rpx] font-bold"
                                        :class="
                                            item.status == 2
                                                ? 'bg-[#E8F5E9] text-[#2E7D32]'
                                                : 'bg-[#FFEBEE] text-[#C62828]'
                                        ">
                                        {{ item.status == 2 ? "成功" : "失败" }}
                                    </view>
                                </view>

                                <view class="px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                    <view class="flex items-center gap-[12rpx] mb-[16rpx]">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center shrink-0">
                                            <image :src="TaskFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <text class="text-[26rpx] font-semibold text-[#333333]">任务及状态</text>
                                    </view>
                                    <view
                                        class="bg-[#FAFAFA] rounded-[12rpx] px-[20rpx] py-[16rpx] flex items-start justify-between gap-[16rpx]">
                                        <text class="flex-1 text-[24rpx] font-semibold text-primary leading-relaxed">
                                            {{ item.task_name }}
                                        </text>
                                        <view
                                            class="shrink-0 flex items-center gap-[6rpx] px-[16rpx] py-[6rpx] rounded-[30rpx]"
                                            :class="item.status == 2 ? 'bg-[#E8F5E9]' : 'bg-[#FFEBEE]'">
                                            <view
                                                class="w-[10rpx] h-[10rpx] rounded-full shrink-0"
                                                :class="item.status == 2 ? 'bg-[#4CAF50]' : 'bg-[#FF2442]'">
                                            </view>
                                            <text
                                                class="text-[22rpx] font-bold"
                                                :class="item.status == 2 ? 'text-[#2E7D32]' : 'text-[#C62828]'">
                                                执行{{ item.status == 2 ? "成功" : "失败" }}
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <template v-if="item.task_type == TaskTypeEnum.CLUES">
                                    <view
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]"
                                        @tap="handleTaskStatementDetail(item, 'keyword')">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskClueIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">线索词</text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]">
                                            共线索词
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.keyword_number || 0
                                            }}</text>
                                            条
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]"
                                        @tap="handleTaskStatementDetail(item, 'clue')">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskUserSearchIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">获取线索</text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]">
                                            共获取线索
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.clues_number || 0
                                            }}</text>
                                            条
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                </template>

                                <view
                                    v-if="item.task_type == TaskTypeEnum.TAKEOVER"
                                    class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <text class="text-[26rpx] font-semibold text-[#333333]">私信回复</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]">
                                        私信消息
                                        <text class="text-[28rpx] font-bold text-primary">{{
                                            item.data_info?.reply_number || 0
                                        }}</text>
                                        条
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                    v-if="item.task_type == TaskTypeEnum.WECHAT_CIRCLE">
                                    <view class="flex items-center gap-x-2">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TaskPostIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <text class="font-medium">发布朋友圈</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]">
                                        共发布内容
                                        <text class="text-[28rpx] font-bold text-primary">{{
                                            item.data_info?.publish_number || 0
                                        }}</text>
                                        条
                                    </view>
                                </view>
                                <template v-if="item.task_type == TaskTypeEnum.WECHAT_CIRCLE_THUMB_closure">
                                    <view
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskPraiseIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">{{
                                                item.data_info?.type == 1
                                                    ? "点赞"
                                                    : item.data_info?.type == 2
                                                    ? "评论"
                                                    : "点赞评论"
                                            }}</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]"
                                            @click.stop="handleTaskStatementDetail(item, 'circle')">
                                            共点赞/评论
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.like_comment_number || 0
                                            }}</text>
                                            次
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                </template>

                                <view
                                    v-if="[TaskTypeEnum.CLUES_WECHAT, TaskTypeEnum.FRIENDS].includes(item.task_type)"
                                    class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TaskWechatIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <text class="text-[26rpx] font-semibold text-[#333333]">自动加微</text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]"
                                        @click.stop="handleTaskStatementDetail(item, 'add_wechat')">
                                        共自动加好友
                                        <text class="text-[28rpx] font-bold text-primary">{{
                                            item.data_info?.add_wechat_number || 0
                                        }}</text>
                                        人
                                        <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                    </view>
                                </view>

                                <template v-if="item.task_type == TaskTypeEnum.TOUCH">
                                    <view
                                        v-if="item.task_scene == 1"
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]"
                                        @tap="handleTaskStatementDetail(item, 'closure')">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskMsgIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">评论区回复</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]"
                                            @click.stop="handleTaskStatementDetail(item, 'closure')">
                                            评论消息
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.comment_number || 0
                                            }}</text>
                                            人
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                    <view
                                        v-if="item.task_scene == 2"
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">评论区私信</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]"
                                            @click.stop="handleTaskStatementDetail(item, 'closure')">
                                            私信消息
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.comment_number || 0
                                            }}</text>
                                            条
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                    <view
                                        v-if="item.task_scene == 3"
                                        class="flex items-center justify-between px-[32rpx] py-[24rpx] border-[0] border-t border-solid border-[#F5F5F5]">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view
                                                class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#F5F5F5] flex items-center justify-center">
                                                <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <text class="text-[26rpx] font-semibold text-[#333333]">关注/点赞</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] text-[#00000060] text-[24rpx]"
                                            @click.stop="handleTaskStatementDetail(item, 'closure')">
                                            共关注/点赞
                                            <text class="text-[28rpx] font-bold text-primary">{{
                                                item.data_info?.comment_number || 0
                                            }}</text>
                                            条
                                            <u-icon name="arrow-right" size="20" color="#BDBDBD"></u-icon>
                                        </view>
                                    </view>
                                </template>
                            </view>
                        </view>
                    </template>

                    <template v-if="currTaskStatementTab == 1">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="flex items-center gap-[16rpx] mb-[16rpx]">
                                <view class="flex-1 h-[1rpx] bg-[#E5E5E5]"></view>
                                <text class="text-[22rpx] text-[#00000040] font-medium whitespace-nowrap"
                                    >12月25日 04:44</text
                                >
                                <view class="flex-1 h-[1rpx] bg-[#E5E5E5]"></view>
                            </view>
                            <view
                                class="bg-white rounded-[20rpx] overflow-hidden shadow-[0_2rpx_16rpx_rgba(0,0,0,0.05)]">
                                <view
                                    class="flex items-center gap-[12rpx] px-[32rpx] py-[24rpx] border-b border-[#F5F5F5]">
                                    <view
                                        class="w-[48rpx] h-[48rpx] rounded-[12rpx] bg-[#FFF3E0] flex items-center justify-center">
                                        <image :src="TokensFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                    <text class="text-[28rpx] font-bold text-[#1A1A1A]">2025.12.25 算力统计</text>
                                </view>
                                <view class="px-[32rpx] py-[16rpx] flex flex-col gap-[16rpx]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-[10rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TokensFlagIcon" class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <text class="text-[24rpx] text-[#00000060] font-medium">执行任务：</text>
                                        <text class="text-[24rpx] font-semibold text-[#1A1A1A]">30 个</text>
                                    </view>
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-[10rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TokensTimeIcon" class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <text class="text-[24rpx] text-[#00000060] font-medium">开始时间：</text>
                                        <text class="text-[24rpx] font-semibold text-[#1A1A1A]">2025.12.25 04:44</text>
                                    </view>
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-[10rpx] bg-[#F5F5F5] flex items-center justify-center">
                                            <image :src="TokensTimeIcon" class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <text class="text-[24rpx] text-[#00000060] font-medium">结束时间：</text>
                                        <text class="text-[24rpx] font-semibold text-[#1A1A1A]">2025.12.25 06:00</text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[12rpx] bg-[#FFF8F0] rounded-[12rpx] px-[16rpx] py-[14rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-[10rpx] bg-[#FFE0B2] flex items-center justify-center">
                                            <image :src="TokensIcon" class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <text class="text-[24rpx] text-[#00000060] font-medium">算力总消耗：</text>
                                        <text class="text-[26rpx] font-bold text-primary">300,000 算力</text>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between px-[32rpx] py-[24rpx] border-t border-[#F5F5F5]"
                                    @tap="handleTokensDetail(item)">
                                    <text class="text-[26rpx] font-semibold text-[#333333]">算力消耗明细</text>
                                    <view
                                        class="w-[48rpx] h-[48rpx] rounded-full bg-[#F5F5F5] flex items-center justify-center">
                                        <u-icon name="arrow-right" size="20" color="#999999"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>

                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>

    <popup-bottom
        v-model="showTokensDetailPopup"
        title="算力消耗明细"
        custom-class="bg-[#F2F3F7]"
        @close="showTokensDetailPopup = false">
        <template #content>
            <view class="h-full flex flex-col py-[32rpx]">
                <view
                    class="mx-[32rpx] mb-[24rpx] bg-[#FFF8F0] rounded-[16rpx] px-[28rpx] py-[20rpx] flex items-center gap-[12rpx]">
                    <text class="text-[26rpx] font-semibold text-[#333333]">算力总消耗：</text>
                    <text class="text-[28rpx] font-bold text-primary">300,000 算力</text>
                </view>
                <scroll-view scroll-y class="grow min-h-0">
                    <view class="mx-[32rpx]">
                        <view class="bg-white rounded-[20rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.05)]">
                            <view
                                class="flex items-center justify-between px-[32rpx] py-[28rpx] border-b border-[#F5F5F5]">
                                <text class="text-[26rpx] font-semibold text-[#333333]">视频号获客</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[28rpx] font-bold text-primary">30</text>
                                    <text class="text-[24rpx] text-[#00000060]">算力</text>
                                </view>
                            </view>
                            <view
                                class="flex items-center justify-between px-[32rpx] py-[28rpx] border-b border-[#F5F5F5]">
                                <text class="text-[26rpx] font-semibold text-[#333333]">视频号获客</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[28rpx] font-bold text-primary">30</text>
                                    <text class="text-[24rpx] text-[#00000060]">算力</text>
                                </view>
                            </view>
                            <view class="flex items-center justify-between px-[32rpx] py-[28rpx]">
                                <text class="text-[26rpx] font-semibold text-[#333333]">视频号获客</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[28rpx] font-bold text-primary">30</text>
                                    <text class="text-[24rpx] text-[#00000060]">算力</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceTaskDashboard } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import TaskClueIcon from "@/ai_modules/device/static/icons/task_clue.svg";
import TaskEmailIcon from "@/ai_modules/device/static/icons/task_email.svg";
import TaskFlagIcon from "@/ai_modules/device/static/icons/task_flag.svg";
import TaskMsgIcon from "@/ai_modules/device/static/icons/task_msg.svg";
import TaskUserSearchIcon from "@/ai_modules/device/static/icons/task_user_search.svg";
import TaskWechatIcon from "@/ai_modules/device/static/icons/task_wechat.svg";
import TaskPraiseIcon from "@/ai_modules/device/static/icons/task_praise.svg";
import TaskPostIcon from "@/ai_modules/device/static/icons/task_post.svg";
import TokensFlagIcon from "@/ai_modules/device/static/icons/tokens_flag.svg";
import TokensTimeIcon from "@/ai_modules/device/static/icons/tokens_time.svg";
import TokensIcon from "@/ai_modules/device/static/icons/tokens.svg";

enum TaskTypeEnum {
    PUBLISH = 1,
    TAKEOVER = 2,
    ACTIVE = 3,
    CLUES = 4,
    FRIENDS = 5,
    TOUCH = 6,
    WECHAT_CIRCLE = 7,
    WECHAT_CIRCLE_THUMB_closure = 8,
    CLUES_WECHAT = 9,
}

const { platform } = useDevice();
const deviceCode = ref("");

const tabList = ref<any[]>([
    { name: "24h任务", type: 1 },
    { name: "手动任务", type: 2 },
]);
const currTab = ref(0);
const currTaskStatementTab = ref(0);

const platformTabs = [
    { name: "全部", type: 0 },
    { name: "微信", type: 2 },
    { name: "抖音", type: 4 },
    { name: "小红书", type: 3 },
    { name: "快手", type: 5 },
    { name: "视频号", type: 1 },
];
const currPlatformTab = ref(0);

const dataList = ref<any[]>([]);
const pagingRef = shallowRef<any>();
const showTokensDetailPopup = ref(false);

const handleTabChange = (index: number) => {
    currTab.value = index;
    pagingRef.value?.reload();
};

const handlePlatformTabChange = (index: number) => {
    currPlatformTab.value = index;
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceTaskDashboard({
            device_code: deviceCode.value,
            auto_type: currTab.value === 0 ? 1 : 0,
            page_no,
            page_size,
            account_type: platformTabs[currPlatformTab.value].type,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleTokensDetail = (item: any) => {
    showTokensDetailPopup.value = true;
};

const handleTaskStatementDetail = (
    item: any,
    type: "keyword" | "clue" | "closure" | "private_message" | "circle" | "add_wechat"
) => {
    uni.$u.route({
        url: "/ai_modules/device/pages/task_statement_detail/task_statement_detail",
        params: { task_id: item.id, sub_id: item.sub_task_id, type, data_info: JSON.stringify(item.data_info) },
    });
};

const formatDate = (date: string) => uni.$u.timeFormat(date, "yyyy年mm月dd日");

const getPlatformIcon = (app_type: number) => platform.value[app_type as keyof typeof platform.value]?.activeIcon;

const getPlatformName = (app_type: number) => platform.value[app_type as keyof typeof platform.value]?.name;

onLoad((options: any) => {
    if (options.device_code) deviceCode.value = options.device_code;
});
</script>

<style scoped lang="scss">
:deep(.u-tab-item) {
    font-weight: bold;
}
</style>
