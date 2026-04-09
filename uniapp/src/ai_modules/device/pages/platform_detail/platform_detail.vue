<template>
    <view class="h-screen flex flex-col bg-[#F6F7F9]">
        <u-navbar
            title="平台详情"
            title-bold
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>

        <view class="rounded-[24rpx] mx-[30rpx] mt-[20rpx] bg-white shadow-[0_4rpx_16rpx_rgba(0,0,0,0.05)]">
            <view class="grid grid-cols-4 bg-[#F8F9FB] rounded-t-[24rpx] p-[16rpx]">
                <view
                    v-for="(item, index) in getSortedPlatform"
                    class="platform-item flex items-center justify-center h-[80rpx] rounded-[16rpx] transition-all duration-200"
                    :key="index"
                    :class="{ active: currentPlatform == item.type }"
                    @click="handlePlatformClick(item.type)">
                    <image :src="getPlatformLogo(item.type)" class="w-[48rpx] h-[48rpx]"></image>
                </view>
            </view>

            <view v-if="accountLoading" class="px-[30rpx] pt-[30rpx] pb-[24rpx] animate-pulse">
                <view class="flex items-center justify-between mb-[24rpx]">
                    <view class="w-[200rpx] h-[30rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[80rpx] h-[24rpx] bg-[#E5E7EB] rounded"></view>
                </view>
                <view class="flex items-start gap-[24rpx] mb-[32rpx]">
                    <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#E5E7EB]"></view>
                    <view class="flex-1">
                        <view class="w-[180rpx] h-[28rpx] bg-[#E5E7EB] rounded mb-[8rpx]"></view>
                        <view class="w-[120rpx] h-[22rpx] bg-[#E5E7EB] rounded"></view>
                        <view class="flex items-center justify-between mt-[20rpx] gap-[20rpx]">
                            <view class="flex-1 h-[80rpx] bg-[#E5E7EB] rounded-[12rpx]"></view>
                            <view class="flex-1 h-[80rpx] bg-[#E5E7EB] rounded-[12rpx]"></view>
                            <view class="flex-1 h-[80rpx] bg-[#E5E7EB] rounded-[12rpx]"></view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center justify-between h-[96rpx] border-t border-[#F0F0F0] pt-[20rpx]">
                    <view class="w-[100rpx] h-[26rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[60rpx] h-[32rpx] bg-[#E5E7EB] rounded-full"></view>
                </view>
                <view class="flex items-center justify-between h-[96rpx] border-t border-[#F0F0F0] pt-[20rpx]">
                    <view class="w-[120rpx] h-[26rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[160rpx] h-[24rpx] bg-[#E5E7EB] rounded"></view>
                </view>
                <view class="flex items-center justify-between mt-[24rpx]">
                    <view class="w-[200rpx] h-[22rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[100rpx] h-[22rpx] bg-[#E5E7EB] rounded"></view>
                </view>
            </view>

            <view v-else class="px-[30rpx] pt-[30rpx] pb-[24rpx]">
                <view class="flex items-center justify-between mb-[24rpx]">
                    <text class="text-[30rpx] font-semibold text-[#1A1A1A]">{{ currentPlatformItem?.name }}账号</text>
                    <view
                        v-if="currentPlatformAccount.account"
                        class="flex items-center gap-[8rpx] text-primary text-[24rpx] font-medium active:opacity-70"
                        @click="handleUpdateAccount(DeviceEventAction.UPDATE_ACCOUNT)">
                        <u-icon name="reload" color="#0065FB" size="22"></u-icon>
                        <text>更新</text>
                    </view>
                </view>

                <template v-if="currentPlatformAccount.account">
                    <view class="flex items-start gap-[24rpx] mb-[32rpx]">
                        <image
                            :src="currentPlatformAccount.avatar"
                            class="w-[120rpx] h-[120rpx] rounded-full flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)]"></image>
                        <view class="flex-1">
                            <text class="text-[28rpx] font-semibold text-[#1A1A1A]">{{
                                currentPlatformAccount.nickname
                            }}</text>
                            <text class="text-[22rpx] text-[#9CA3AF] block mt-[4rpx]"
                                >({{ currentPlatformAccount.account }})</text
                            >
                            <view class="flex items-center justify-between mt-[20rpx] gap-[20rpx]">
                                <view class="flex-1 text-center bg-[#F8F9FB] rounded-[12rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-semibold text-[#1A1A1A] block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.followers || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx] block">关注</text>
                                </view>
                                <view class="flex-1 text-center bg-[#F8F9FB] rounded-[12rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-semibold text-[#1A1A1A] block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.fans || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx] block">粉丝</text>
                                </view>
                                <view class="flex-1 text-center bg-[#F8F9FB] rounded-[12rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-semibold text-[#1A1A1A] block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.thumbup_collect || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx] block">点赞</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="flex items-center justify-between h-[96rpx] border-t border-[#F0F0F0] pt-[20rpx]">
                        <text class="text-[26rpx] font-medium text-[#1A1A1A]">私信开关</text>
                        <u-switch
                            v-model="currentPlatformAccount.open_ai"
                            :active-value="1"
                            :inactive-value="0"
                            :size="32"
                            @change="handleOpenAiChange"></u-switch>
                    </view>

                    <view
                        v-if="currentPlatformAccount.open_ai == 1"
                        class="flex items-center justify-between h-[96rpx] border-t border-[#F0F0F0] pt-[20rpx]"
                        @click="handleSelectAgent">
                        <text class="text-[26rpx] font-medium text-[#1A1A1A]">私信智能体</text>
                        <view class="flex items-center gap-[8rpx]">
                            <text
                                class="text-[24rpx] text-[#666666] max-w-[240rpx] truncate"
                                :class="{ 'text-[#9CA3AF]': !currentPlatformAccount.robot_name }">
                                {{ currentPlatformAccount.robot_name || "未配置" }}
                            </text>
                            <u-icon name="arrow-right" color="#B2B2B2" size="22"></u-icon>
                        </view>
                    </view>

                    <view class="flex items-center justify-between mt-[24rpx] text-[22rpx] text-[#9CA3AF]">
                        <text>最后一次更新：{{ currentPlatformAccount.update_time }}</text>
                        <view
                            class="flex items-center gap-[8rpx] text-[#EF4444] active:opacity-70"
                            @click="showRemovePopup = true">
                            <u-icon name="trash" color="#EF4444" size="22"></u-icon>
                            <text>账号移除</text>
                        </view>
                    </view>
                </template>

                <view v-else class="flex flex-col items-center py-[40rpx] gap-[24rpx]">
                    <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#F0F0F0] flex items-center justify-center">
                        <u-icon name="account" color="#D1D5DB" size="56"></u-icon>
                    </view>
                    <text class="text-[24rpx] text-[#9CA3AF]">您还未获取平台账号</text>
                    <view
                        class="w-[240rpx] h-[80rpx] flex items-center justify-center text-white bg-primary font-medium rounded-[40rpx] text-[26rpx] active:scale-95 transition-transform"
                        @click="handleUpdateAccount(DeviceEventAction.ADD_ACCOUNT)">
                        立即获取
                    </view>
                </view>
            </view>
        </view>

        <view class="px-[30rpx] mt-[30rpx]">
            <u-tabs
                bg-color="transparent"
                :current="currentTab"
                :list="getTabList"
                :is-scroll="false"
                active-color="#0065FB"
                inactive-color="#666666"
                bar-width="40"
                bar-height="4"
                :bar-style="{ background: '#0065FB' }"
                @change="handleTabChange"></u-tabs>
        </view>

        <view class="grow min-h-0 mt-[20rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-[20rpx] px-[30rpx] py-[20rpx]">
                    <template v-if="listLoading">
                        <view v-for="n in 3" :key="n" class="bg-white rounded-[20rpx] p-[24rpx] animate-pulse">
                            <view class="flex gap-[24rpx]">
                                <view class="w-[160rpx] h-[213rpx] rounded-[16rpx] bg-[#E5E7EB]"></view>
                                <view class="flex-1 flex flex-col justify-between">
                                    <view>
                                        <view class="w-[80%] h-[26rpx] bg-[#E5E7EB] rounded mb-[8rpx]"></view>
                                        <view class="w-[60%] h-[26rpx] bg-[#E5E7EB] rounded"></view>
                                        <view class="w-[90%] h-[22rpx] bg-[#E5E7EB] rounded mt-[8rpx]"></view>
                                        <view class="w-[70%] h-[22rpx] bg-[#E5E7EB] rounded mt-[4rpx]"></view>
                                    </view>
                                    <view class="mt-[16rpx]">
                                        <view class="flex gap-[8rpx] mb-[8rpx]">
                                            <view class="w-[80rpx] h-[24rpx] bg-[#E5E7EB] rounded-full"></view>
                                            <view class="w-[100rpx] h-[24rpx] bg-[#E5E7EB] rounded-full"></view>
                                        </view>
                                        <view class="w-[160rpx] h-[20rpx] bg-[#E5E7EB] rounded"></view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                    <template v-else-if="currentTab === 0">
                        <view
                            v-for="(item, index) in dataList"
                            :key="index"
                            class="publish-item relative bg-white rounded-[20rpx] p-[24rpx] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.04)]">
                            <view class="flex gap-[24rpx]">
                                <view
                                    class="flex-shrink-0 relative w-[160rpx] h-[213rpx] rounded-[16rpx] overflow-hidden">
                                    <image
                                        :src="item.pic || item.material_url"
                                        class="w-full h-full object-cover"
                                        mode="aspectFill"
                                        @click="handlePreviewImage(item)"></image>
                                    <view
                                        v-if="item.material_type == 1"
                                        class="absolute inset-0 flex items-center justify-center"
                                        @click="handlePlayVideo(item)">
                                        <view
                                            class="w-[64rpx] h-[64rpx] rounded-full bg-white/30 backdrop-blur-sm flex items-center justify-center pl-[4rpx] border border-white/40 active:scale-90 transition-transform">
                                            <u-icon name="play-right-fill" color="#ffffff" size="28"></u-icon>
                                        </view>
                                    </view>
                                </view>

                                <view class="flex-1 flex flex-col justify-between">
                                    <view>
                                        <text class="text-[26rpx] font-semibold text-[#1A1A1A] line-clamp-2">{{
                                            item.material_title
                                        }}</text>
                                        <text class="text-[22rpx] text-[#666666] mt-[8rpx] line-clamp-2">{{
                                            item.material_subtitle
                                        }}</text>
                                    </view>
                                    <view>
                                        <view
                                            class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                            v-if="item.material_tag">
                                            <view
                                                class="text-[22rpx] text-[#0000004d]"
                                                v-for="(topic, index) in item.material_tag"
                                                :key="index"
                                                >#{{ topic }}</view
                                            >
                                        </view>
                                        <view class="text-[22rpx] text-[#00000080] mt-1">
                                            发布时间：{{ item.publish_time }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view v-if="item.remark && item.status == 2" class="text-[#FF2442] text-xs mt-4 break-all">
                                失败原因：{{ item.remark }}
                            </view>
                        </view>
                    </template>

                    <template v-else-if="currentTab === 1">
                        <view
                            v-for="(group, index) in getPrivateChatRecordList"
                            :key="index"
                            class="private-group bg-white rounded-[20rpx] p-[24rpx] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.04)]">
                            <text class="text-[24rpx] text-[#9CA3AF] font-medium">{{ group.date_text }}</text>
                            <view class="mt-[24rpx] flex flex-col gap-[32rpx]">
                                <view v-for="(item, idx) in group.list" :key="idx" class="flex gap-[20rpx]">
                                    <view class="flex-shrink-0 relative mt-[4rpx]">
                                        <view class="w-[16rpx] h-[16rpx] rounded-full bg-[#10B981]"></view>
                                        <view
                                            v-if="idx < group.list.length - 1"
                                            class="absolute top-[16rpx] left-[8rpx] w-[2rpx] h-[calc(100%+32rpx)] bg-[#F0F0F0]"></view>
                                    </view>

                                    <view class="flex-1">
                                        <text class="text-[26rpx] font-semibold text-[#1A1A1A]">{{
                                            item.author_name
                                        }}</text>
                                        <view class="mt-[12rpx] bg-[#F8F9FB] rounded-[12rpx] p-[16rpx]">
                                            <view class="flex gap-[8rpx]">
                                                <text class="flex-shrink-0 text-[22rpx] text-[#10B981] font-medium"
                                                    >客户：</text
                                                >
                                                <view>
                                                    <text class="text-[22rpx] text-[#333333]">{{
                                                        item.message_content || "-"
                                                    }}</text>
                                                    <text class="text-[20rpx] text-[#9CA3AF] block mt-[4rpx]">{{
                                                        item.message_time || "-"
                                                    }}</text>
                                                </view>
                                            </view>
                                            <view class="flex gap-[8rpx] mt-[12rpx]">
                                                <text class="flex-shrink-0 text-[22rpx] text-[#9CA3AF] font-medium"
                                                    >回复：</text
                                                >
                                                <view>
                                                    <text class="text-[22rpx] text-[#333333]">{{
                                                        item.reply_content || "-"
                                                    }}</text>
                                                    <text class="text-[20rpx] text-[#9CA3AF] block mt-[4rpx]">{{
                                                        item.reply_time || "-"
                                                    }}</text>
                                                </view>
                                            </view>
                                        </view>
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

    <u-popup v-model="showUpdate" mode="center" border-radius="24" width="80%" @close="showUpdate = false">
        <view class="rounded-[24rpx] bg-white p-[40rpx]">
            <text class="text-[30rpx] font-semibold text-[#1A1A1A] block text-center">提示</text>
            <text class="text-[24rpx] text-[#666666] mt-[24rpx] text-center block">
                当前如果有任务执行中，该任务会中断并且不再执行，手机将等待下一时间段任务再开始执行，确认是否还要继续？
            </text>
            <view class="flex items-center gap-[20rpx] mt-[40rpx]">
                <view
                    class="flex-1 h-[80rpx] flex items-center justify-center rounded-[40rpx] bg-[#F0F0F0] text-[26rpx] font-medium text-[#333333] active:bg-[#E5E5E5]"
                    @click="showUpdate = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[80rpx] flex items-center justify-center rounded-[40rpx] bg-primary text-[26rpx] font-medium text-white active:scale-95 transition-transform"
                    @click="handleAccountConfirm">
                    确定
                </view>
            </view>
        </view>
    </u-popup>

    <u-popup
        v-model="showUpdateProgress"
        mode="center"
        border-radius="32"
        width="86%"
        :mask-close-able="false"
        @close="showUpdateProgress = false">
        <view class="rounded-[32rpx] bg-white overflow-hidden">
            <view class="px-[48rpx] pt-[48rpx] pb-[32rpx] flex flex-col items-center gap-[12rpx]">
                <view
                    class="w-[96rpx] h-[96rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                    :class="isExecuteComplete ? 'bg-[#ECFDF5]' : hasError ? 'bg-[#FEF2F2]' : 'bg-[#EEF4FF]'">
                    <u-icon v-if="isExecuteComplete" name="checkmark-circle" color="#10B981" size="52" />
                    <u-icon v-else-if="hasError" name="close-circle" color="#EF4444" size="52" />
                    <u-icon v-else name="reload" color="#0065FB" size="48" />
                </view>
                <text class="text-[32rpx] font-extrabold text-[#1A1A1A]">
                    {{ isExecuteComplete ? "更新完成" : hasError ? "更新失败" : "正在更新中..." }}
                </text>
                <text class="text-[24rpx] text-[#9CA3AF]">
                    {{
                        isExecuteComplete
                            ? "账号信息已成功获取"
                            : hasError
                            ? "部分步骤执行失败，请重试"
                            : "请保持手机屏幕常亮"
                    }}
                </text>
            </view>

            <view class="mx-[48rpx] h-[1rpx] bg-[#F3F4F6]"></view>

            <view class="px-[48rpx] py-[36rpx] flex flex-col gap-y-0">
                <view v-for="(item, index) in updateAccountSteps" :key="index" class="flex gap-[20rpx]">
                    <view class="flex flex-col items-center">
                        <view
                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-300"
                            :class="{
                                'bg-[#F3F4F6] border-[2rpx] border-[#E5E7EB]': item.status === 0,
                                'bg-[#EEF4FF] border-[2rpx] border-primary': item.status === 1,
                                'bg-primary': item.status === 2,
                                'bg-[#FEF2F2] border-[2rpx] border-[#EF4444]': item.status === 3,
                            }">
                            <view
                                v-if="item.status === 1"
                                class="w-[16rpx] h-[16rpx] rounded-full bg-primary animate-pulse">
                            </view>
                            <u-icon v-else-if="item.status === 2" name="checkmark" color="#ffffff" size="20" />
                            <u-icon v-else-if="item.status === 3" name="close" color="#EF4444" size="20" />
                        </view>

                        <view
                            v-if="index !== updateAccountSteps.length - 1"
                            class="w-[2rpx] flex-1 min-h-[32rpx] my-[4rpx] rounded-full transition-all duration-500"
                            :class="item.status === 2 ? 'bg-primary' : 'bg-[#E5E7EB]'">
                        </view>
                    </view>

                    <view
                        class="flex flex-col justify-center pb-[32rpx]"
                        :class="{ 'pb-0': index === updateAccountSteps.length - 1 }">
                        <text
                            class="text-[26rpx] font-semibold leading-[1.4] transition-all duration-300"
                            :class="{
                                'text-[#C4C9D4]': item.status === 0,
                                'text-primary': item.status === 1,
                                'text-[#1A1A1A]': item.status === 2,
                                'text-[#EF4444]': item.status === 3,
                            }">
                            {{ item.title }}
                        </text>
                        <view v-if="item.status === 1" class="flex items-center gap-[8rpx] mt-[6rpx]">
                            <view class="flex gap-[4rpx]">
                                <view
                                    class="w-[8rpx] h-[8rpx] rounded-full bg-primary animate-bounce"
                                    style="animation-delay: 0ms"></view>
                                <view
                                    class="w-[8rpx] h-[8rpx] rounded-full bg-primary animate-bounce"
                                    style="animation-delay: 150ms"></view>
                                <view
                                    class="w-[8rpx] h-[8rpx] rounded-full bg-primary animate-bounce"
                                    style="animation-delay: 300ms"></view>
                            </view>
                            <text class="text-[20rpx] text-primary">执行中</text>
                        </view>
                        <text v-if="item.status === 3" class="text-[20rpx] text-[#EF4444] mt-[6rpx]">
                            ⚠ 获取失败，请重试
                        </text>
                    </view>
                </view>
            </view>

            <view class="px-[48rpx] pb-[48rpx] flex flex-col gap-[16rpx]">
                <u-button
                    v-if="isExecuteComplete"
                    type="primary"
                    :custom-style="{
                        height: '88rpx',
                        width: '100%',
                        fontWeight: '700',
                        fontSize: '28rpx',
                        borderRadius: '44rpx',
                        boxShadow: '0 8rpx 24rpx rgba(0,101,251,0.25)',
                    }"
                    @click="showUpdateProgress = false">
                    完成
                </u-button>
                <u-button
                    :custom-style="{
                        height: '88rpx',
                        fontWeight: '600',
                        fontSize: '28rpx',
                        borderRadius: '44rpx',
                        background: '#F3F4F6',
                        color: '#666666',
                        border: 'none',
                    }"
                    @click="showUpdateProgress = false">
                    {{ isExecuteComplete ? "关闭" : "取消更新" }}
                </u-button>
            </view>
        </view>
    </u-popup>

    <choose-agent ref="chooseAgentRef" v-model="showAgentPopup" @confirm="handleBindAgentConfirm" />

    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :poster="previewVideo.pic"
        :video-url="previewVideo.url" />

    <confirm-dialog
        v-model="showRemovePopup"
        content="确定要删除账号吗？"
        center
        @confirm="handleAccountRemoveConfirm"></confirm-dialog>
</template>

<script setup lang="ts">
import {
    addDeviceAccount,
    updateDeviceAccount,
    deleteDeviceAccount,
    getDeviceAccountList,
    getDevicePublishRecordList,
    getDevicePrivateChatRecordList,
    changeAccountStatus,
} from "@/api/device";
import { getAgentList } from "@/api/agent";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
import { formatNumberToWanOrYi } from "@/utils/util";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { useDeviceStore } from "@/ai_modules/device/stores/device";
import { DeviceEventAction } from "@/ai_modules/device/enums";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import ChooseAgent from "@/ai_modules/device/components/choose-agent/choose-agent.vue";

const { send, onEvent, close } = useDeviceWs();
const deviceStore = useDeviceStore();
const { getSortedPlatform } = storeToRefs(deviceStore);
const deviceCode = ref<string>("");
const eventAction = ref<DeviceEventAction | null>();
const selectedAgent = ref<{ id: string; name: string }>({ id: "", name: "" });
const currentPlatform = ref<AppTypeEnum>(AppTypeEnum.WECHAT);
const currentPlatformAccount = ref<any>({});
const currentPlatformItem = computed(() => {
    return getSortedPlatform.value.find((item) => item.type == currentPlatform.value);
});

const showAgentPopup = ref<boolean>(false);
const showRemovePopup = ref<boolean>(false);
const chooseAgentRef = shallowRef();
const getTabList = computed(() => {
    const commonTabs = {
        name: "发布详情",
        key: "publish_detail",
    };
    if (currentPlatform.value != AppTypeEnum.WECHAT) {
        return [
            commonTabs,
            {
                name: "私信详情",
                key: "private_detail",
            },
        ];
    }
    return [commonTabs];
});

const currentTab = ref<number>(0);
const pagingRef = shallowRef();
const dataList = ref<any[]>([]);
const showUpdate = ref<boolean>(false);
const showUpdateProgress = ref<boolean>(false);
const updateAccountSteps = ref<any[]>([
    {
        title: "正在发送指令",
        status: 1,
        type: "send",
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "手机正在处理指令",
        status: 0,
        type: DeviceCmdEnum.APP_EXEC,
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "正在打开目标应用",
        status: 0,
        type: DeviceCmdEnum.OPEN_APP,
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "正在切换到个人中心",
        status: 0,
        type: DeviceCmdEnum.OPEN_PERSON_CENTER,
        errorCode: DeviceCmdCodeEnum.OPEN_PERSON_CENTER_ERROR,
    },
    {
        title: "正在获取账号信息",
        status: 0,
        type: DeviceCmdEnum.GET_ACCOUNT_INFO,
        errorCode: DeviceCmdCodeEnum.GET_ACCOUNT_INFO_ERROR,
    },
    {
        title: "正在等待数据返回",
        status: 0,
        type: DeviceCmdEnum.DATA_SEND,
        errorCode: DeviceCmdCodeEnum.DATA_SEND_ERROR,
    },
    {
        title: "已完成",
        status: 0,
        type: DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE,
    },
]);
const currentStep = ref<number>(0);

const showVideoPreview = ref(false);
const previewVideo = reactive({
    url: "",
    pic: "",
});

const accountLoading = ref(true);
const listLoading = ref(true);

const isExecuteComplete = computed(() => {
    return updateAccountSteps.value.every((item) => item.status === 2);
});

const hasError = computed(() => updateAccountSteps.value.some((item) => item.status === 3));

const getPrivateChatRecordList = computed(() => {
    const groupList: any = [];
    const weekList = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
    dataList.value.forEach((item: any) => {
        if (!item.message_time) return;
        const date = item.message_time.split(" ")[0];
        const group = groupList.find((group: any) => group.date === date);
        if (!group) {
            groupList.push({
                date,
                date_text: `${date.split("-")[0]}.${date.split("-")[1]}.${date.split("-")[2]} ${
                    weekList[new Date(date).getDay()]
                }`,
                list: [item],
            });
        } else {
            group.list.push(item);
        }
    });
    return groupList.sort((a: any, b: any) => new Date(b.date).getTime() - new Date(a.date).getTime());
});

onEvent("success", async (data: any) => {
    const { type, content, deviceId, appType } = data;

    if (currentPlatform.value != AppTypeEnum.WECHAT) {
        const isStep = updateAccountSteps.value.find((item) => item.type === type);
        if (isStep) {
            for (let index = 0; index < updateAccountSteps.value.length; index++) {
                const item = updateAccountSteps.value[index];
                if (type == DeviceCmdEnum.APP_EXEC) {
                    updateAccountSteps.value[0].status = 2;
                }
                if (item.type === type) {
                    currentStep.value = index; // 定位到匹配类型的当前步骤
                    item.status = 1;
                    if (type == DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE) {
                        updateAccountSteps.value[updateAccountSteps.value.length - 1].status = 2;
                    }
                    break; // 匹配成功后跳出循环
                } else {
                    item.status = currentStep.value >= index ? 2 : 0;
                }
            }
        }
    }
    if (type === DeviceCmdEnum.GET_USER_INFO) {
        const { account, account_no, extra, avatar, nickname } = content;
        const params = {
            account,
            account_no,
            avatar,
            device_code: deviceId,
            type: appType,
            nickname,
            extra: JSON.stringify(extra),
        };
        try {
            if (eventAction.value === DeviceEventAction.ADD_ACCOUNT) {
                await addDeviceAccount(params);
                uni.hideLoading();
            } else if (eventAction.value === DeviceEventAction.UPDATE_ACCOUNT) {
                await updateDeviceAccount({ ...params, id: currentPlatformAccount.value.id });
            }
            eventAction.value = null;
            showUpdate.value = false;
            uni.hideLoading();
            getDeviceAccount();
            pagingRef.value.reload();
        } catch (error: any) {
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    }
});

onEvent("error", (error: any) => {
    const { type, code } = error;
    uni.hideLoading();

    for (const item of updateAccountSteps.value) {
        if (item.type === type && code === item.errorCode) {
            item.status = 3; // 设置为失败状态
            break;
        }
    }

    if (type === DeviceCmdEnum.GET_USER_INFO) {
        uni.showToast({
            title: error.error,
            icon: "none",
            duration: 3000,
        });
    }
});

const getPlatformLogo = (type: AppTypeEnum) => {
    const data = getSortedPlatform.value.find((item) => item.type == type) || ({} as any);
    return currentPlatform.value == type ? data.activeIcon : data.icon;
};

const handlePlatformClick = async (type: AppTypeEnum) => {
    if (currentPlatform.value === type) return;
    currentPlatform.value = type;
    currentTab.value = 0;
    accountLoading.value = true;
    listLoading.value = true;
    await getDeviceAccount();
    pagingRef.value?.reload();
};

const handleTabChange = (index: number) => {
    if (currentTab.value === index) return;
    currentTab.value = index;
    listLoading.value = true;
    pagingRef.value?.reload();
};

const handleUpdateAccount = (event: DeviceEventAction) => {
    if (event == DeviceEventAction.ADD_ACCOUNT) {
        handleAccountConfirm();
    } else {
        showUpdate.value = true;
    }
    eventAction.value = event;
    updateAccountSteps.value.forEach((item) => {
        item.status = 0;
    });
    currentStep.value = 0;
};

const handleAccountConfirm = () => {
    showUpdate.value = false;
    if (currentPlatform.value != AppTypeEnum.WECHAT) {
        showUpdateProgress.value = true;
    } else {
        uni.showLoading({
            title: "更新中...",
            mask: true,
        });
    }
    updateAccountSteps.value[0].status = 1; // 设置第一步为进行中

    send({
        type: DeviceCmdEnum.GET_USER_INFO,
        content: { deviceId: deviceCode.value },
        deviceId: deviceCode.value,
        appType: currentPlatform.value,
    });
};

const handleAccountRemoveConfirm = async () => {
    showRemovePopup.value = false;
    uni.showLoading({
        title: "删除中...",
        mask: true,
    });
    try {
        await deleteDeviceAccount({
            id: currentPlatformAccount.value.id,
        });
        uni.hideLoading();
        uni.showToast({
            title: "移除账号成功",
            icon: "none",
            duration: 3000,
        });
        getDeviceAccount();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "移除账号失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleOpenAiChange = async (value: boolean) => {
    uni.showLoading({
        title: "更新中...",
        mask: true,
    });
    try {
        await changeAccountStatus({
            account: currentPlatformAccount.value.account,
            open_ai: value ? 1 : 0,
            account_type: currentPlatform.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "更新成功",
            icon: "none",
            duration: 3000,
        });
        getDeviceAccount();
    } catch (error) {
        currentPlatformAccount.value.open_ai = value ? 1 : 0;
        uni.hideLoading();
        uni.showToast({
            title: "更新失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleSelectAgent = () => {
    showAgentPopup.value = true;
    chooseAgentRef.value?.setChooseLists([{ id: selectedAgent.value.id, name: selectedAgent.value.name }]);
};

const handleBindAgentConfirm = async (row: any) => {
    uni.showLoading({
        title: "绑定中...",
        mask: true,
    });
    try {
        await changeAccountStatus({
            account: currentPlatformAccount.value.account,
            robot_id: row.id,
            takeover_mode: 1, // 接管模式
            open_ai: currentPlatformAccount.value.open_ai,
            account_type: currentPlatform.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "绑定成功",
            icon: "none",
            duration: 3000,
        });
        getDeviceAccount();
        showAgentPopup.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "绑定失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDeviceAccount = async () => {
    try {
        accountLoading.value = true;
        const { lists } = await getDeviceAccountList({
            device_code: deviceCode.value,
            type: currentPlatform.value,
        });
        if (lists && lists.length > 0) {
            currentPlatformAccount.value = lists[0];
            selectedAgent.value = { id: lists[0].robot_id, name: lists[0].robot_name };
        } else {
            currentPlatformAccount.value = {};
        }
    } catch (error) {
        console.error(error);
    } finally {
        accountLoading.value = false;
    }
};

const queryList = async (page_no: number, page_size: number) => {
    listLoading.value = true;
    try {
        let lists: any[] = [];
        if (currentTab.value === 0) {
            // 查询发布记录
            const { lists: publishLists } = await getDevicePublishRecordList({
                device_code: deviceCode.value,
                account_type: currentPlatform.value,
                account: currentPlatformAccount.value.account,
                page_no,
                page_size,
                task_type: 3,
            });
            lists = publishLists || [];
        } else {
            // 查询私信记录
            const { lists: privateChatLists } = await getDevicePrivateChatRecordList({
                device_code: deviceCode.value,
                page_no,
                page_size,
                type: currentPlatform.value,
            });
            lists = privateChatLists || [];
        }
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    } finally {
        listLoading.value = false;
    }
};

const getPublishStatusStyle = (status: number) => {
    switch (status) {
        case 0:
            return "text-[#0065FB] bg-[#EBF2FF]"; // 未发布
        case 1:
            return "text-[#10B981] bg-[#E7F9F2]"; // 已发布
        case 2:
            return "text-[#EF4444] bg-[#FFF5F5]"; // 发布失败
        default:
            return "text-[#0065FB] bg-[#EBF2FF]";
    }
};

const getPublishStatusText = (status: number) => {
    const statusMap: { [key: number]: string } = {
        0: "未发布",
        1: "已发布",
        2: "发布失败",
        3: "发布中",
    };
    return statusMap[status] || "待处理"; // 添加默认值处理未知状态
};

const getStepIconClass = (status: number) => {
    switch (status) {
        case 0:
            return "border-[2rpx] border-[#E5E7EB]";
        case 1:
            return "border-[2rpx] border-primary flex items-center justify-center";
        case 2:
            return "bg-primary";
        case 3:
            return "border-[2rpx] border-[#EF4444]";
        default:
            return "";
    }
};

const handlePlayVideo = (item: any) => {
    showVideoPreview.value = true;
    previewVideo.pic = item.pic;
    previewVideo.url = item.material_url;
};

const handlePreviewImage = (item: any) => {
    const { pic } = item;
    uni.previewImage({
        urls: [pic],
    });
};

onLoad((options: any) => {
    const { device_code, app_type } = options;
    if (device_code) {
        currentPlatform.value = app_type || AppTypeEnum.WECHAT;
        deviceCode.value = device_code;
        getDeviceAccount();
    }
});

// 页面卸载生命周期钩子
onUnload(() => {
    close(); // 关闭WebSocket连接
});
</script>

<style scoped lang="scss">
.platform-item {
    &.active {
        background: white;
        box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
    }
}

.publish-item,
.private-group,
.robot-item {
    transition: all 0.2s ease-in-out;
}

.robot-item.active {
    border: 2rpx solid var(--color-primary);
    box-shadow: 0 4rpx 12rpx rgba(0, 101, 251, 0.1);
}
</style>
