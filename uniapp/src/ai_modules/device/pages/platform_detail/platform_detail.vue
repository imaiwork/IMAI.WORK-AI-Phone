<template>
    <view class="h-screen flex flex-col bg-[#F4F6FB]">
        <u-navbar
            title="平台详情"
            title-bold
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }" />

        <view
            class="mx-[32rpx] mt-[16rpx] bg-white rounded-[32rpx] shadow-sm border border-solid border-[#f9f9f9] overflow-hidden">
            <view
                class="grid bg-[#F4F6FB] m-[16rpx] rounded-[20rpx] p-[8rpx]"
                :style="{ gridTemplateColumns: `repeat(${getSortedPlatform.length}, 1fr)` }">
                <view
                    v-for="(item, index) in getSortedPlatform"
                    :key="index"
                    class="flex items-center justify-center h-[80rpx] rounded-[16rpx] transition-all"
                    :class="currentPlatform == item.type ? 'bg-white shadow-sm' : ''"
                    @click="handlePlatformClick(item.type)">
                    <image :src="getPlatformLogo(item.type)" class="w-[48rpx] h-[48rpx]" />
                </view>
            </view>

            <view v-if="accountLoading" class="px-[32rpx] pt-[8rpx] pb-[28rpx] animate-pulse">
                <view class="flex items-center justify-between mb-[24rpx]">
                    <view class="w-[200rpx] h-[30rpx] bg-[#EEF2FF] rounded-full"></view>
                    <view class="w-[80rpx] h-[24rpx] bg-[#EEF2FF] rounded-full"></view>
                </view>
                <view class="flex items-start gap-[24rpx] mb-[32rpx]">
                    <view class="w-[100rpx] h-[100rpx] rounded-full bg-[#EEF2FF]"></view>
                    <view class="flex-1">
                        <view class="w-[180rpx] h-[28rpx] bg-[#EEF2FF] rounded-full mb-[12rpx]"></view>
                        <view class="w-[120rpx] h-[22rpx] bg-[#EEF2FF] rounded-full"></view>
                        <view class="flex gap-[16rpx] mt-[20rpx]">
                            <view class="flex-1 h-[72rpx] bg-[#EEF2FF] rounded-[20rpx]"></view>
                            <view class="flex-1 h-[72rpx] bg-[#EEF2FF] rounded-[20rpx]"></view>
                            <view class="flex-1 h-[72rpx] bg-[#EEF2FF] rounded-[20rpx]"></view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center justify-between h-[72rpx] border-t border-gray-50">
                    <view class="w-[100rpx] h-[26rpx] bg-[#EEF2FF] rounded-full"></view>
                    <view class="w-[60rpx] h-[32rpx] bg-[#EEF2FF] rounded-full"></view>
                </view>
                <view class="flex items-center justify-between h-[72rpx] border-t border-gray-50">
                    <view class="w-[120rpx] h-[26rpx] bg-[#EEF2FF] rounded-full"></view>
                    <view class="w-[160rpx] h-[24rpx] bg-[#EEF2FF] rounded-full"></view>
                </view>
            </view>

            <view v-else class="px-[32rpx] pt-[8rpx] pb-[28rpx]">
                <view class="flex items-center justify-between mb-[20rpx]">
                    <view class="flex items-center gap-x-[10rpx]">
                        <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full"></view>
                        <text class="text-[28rpx] font-bold text-[#212121]">{{ currentPlatformItem?.name }}账号</text>
                    </view>
                    <view
                        v-if="currentPlatformAccount.account"
                        class="flex items-center gap-[8rpx] bg-[#EEF4FF] px-[20rpx] py-[8rpx] rounded-full active:opacity-70"
                        @click="handleUpdateAccount(DeviceEventAction.UPDATE_ACCOUNT)">
                        <u-icon name="reload" color="#0065fb" size="22" />
                        <text class="text-[22rpx] text-primary font-medium">更新</text>
                    </view>
                </view>

                <template v-if="currentPlatformAccount.account">
                    <view class="flex items-start gap-[24rpx] mb-[24rpx]">
                        <image
                            :src="currentPlatformAccount.avatar"
                            class="w-[100rpx] h-[100rpx] rounded-full flex-shrink-0 shadow-sm" />
                        <view class="flex-1">
                            <text class="text-[28rpx] font-bold text-[#212121] block">{{
                                currentPlatformAccount.nickname
                            }}</text>
                            <text class="text-[22rpx] text-[#676767] block mt-[4rpx]"
                                >({{ currentPlatformAccount.account }})</text
                            >
                            <view class="flex gap-[12rpx] mt-[16rpx]">
                                <view class="flex-1 text-center bg-[#F4F6FB] rounded-[20rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-bold text-[#212121] block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.followers || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#676767] mt-[4rpx] block">关注</text>
                                </view>
                                <view class="flex-1 text-center bg-[#F4F6FB] rounded-[20rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-bold text-primary block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.fans || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#676767] mt-[4rpx] block">粉丝</text>
                                </view>
                                <view class="flex-1 text-center bg-[#F4F6FB] rounded-[20rpx] py-[12rpx]">
                                    <text class="text-[26rpx] font-bold text-[#212121] block">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.thumbup_collect || 0)
                                    }}</text>
                                    <text class="text-[20rpx] text-[#676767] mt-[4rpx] block">点赞</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <template v-if="currentPlatform == AppTypeEnum.WECHAT">
                        <view
                            class="flex items-center justify-between py-[20rpx] border-[0] border-t border-solid border-[#f9f9f9]">
                            <text class="text-[26rpx] font-medium text-gray-700">私信开关</text>
                            <u-switch
                                v-model="currentPlatformAccount.open_ai"
                                :active-value="1"
                                :inactive-value="0"
                                :size="32"
                                @change="handleOpenAiChange" />
                        </view>
                        <view
                            v-if="currentPlatformAccount.open_ai == 1"
                            class="flex items-center justify-between py-[20rpx] border-[0] border-t border-solid border-[#f9f9f9]"
                            @click="handleSelectAgent">
                            <text class="text-[26rpx] font-medium text-[#424242]">私信智能体</text>
                            <view class="flex items-center gap-[8rpx]">
                                <text
                                    class="text-[22rpx] max-w-[240rpx] truncate"
                                    :class="currentPlatformAccount.robot_name ? 'text-[#676767]' : 'text-[#999999]'">
                                    {{ currentPlatformAccount.robot_name || "未配置" }}
                                </text>
                                <u-icon name="arrow-right" color="#CBD5E1" size="22" />
                            </view>
                        </view>
                    </template>

                    <view
                        class="flex items-center justify-between mt-[16rpx] pt-[16rpx] border-[0] border-t border-solid border-[#f9f9f9]">
                        <text class="text-[20rpx] text-[#676767]"
                            >最后更新：{{ currentPlatformAccount.update_time }}</text
                        >
                        <view
                            class="flex items-center gap-[8rpx] bg-[#FEF2F2] px-[20rpx] py-[8rpx] rounded-full active:opacity-70"
                            @click="showRemovePopup = true">
                            <u-icon name="trash" color="#EF4444" size="20" />
                            <text class="text-[22rpx] text-[#EF4444]">移除账号</text>
                        </view>
                    </view>
                </template>

                <view v-else class="flex flex-col items-center py-[40rpx] gap-[20rpx]">
                    <view class="w-[100rpx] h-[100rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center">
                        <u-icon name="account" color="#CBD5E1" size="52" />
                    </view>
                    <text class="text-[24rpx] text-[#676767]">您还未获取平台账号</text>
                    <view
                        class="h-[80rpx] px-[48rpx] flex items-center justify-center text-white bg-primary font-medium rounded-full shadow-sm active:opacity-90"
                        @click="handleUpdateAccount(DeviceEventAction.ADD_ACCOUNT)">
                        立即获取
                    </view>
                </view>
            </view>
        </view>

        <view class="px-[32rpx] mt-[24rpx]">
            <u-tabs
                bg-color="transparent"
                :current="currentTab"
                :list="tabs"
                :is-scroll="false"
                bar-width="40"
                bar-height="4"
                @change="handleTabChange" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-[20rpx] px-[32rpx] pb-[32rpx]">
                    <template v-if="listLoading">
                        <view v-for="n in 3" :key="n" class="bg-white rounded-[28rpx] p-[24rpx] animate-pulse">
                            <view class="flex gap-[24rpx]">
                                <view class="w-[160rpx] h-[213rpx] rounded-[20rpx] bg-[#EEF2FF]"></view>
                                <view class="flex-1 flex flex-col justify-between">
                                    <view>
                                        <view class="w-[80%] h-[26rpx] bg-[#EEF2FF] rounded-full mb-[12rpx]"></view>
                                        <view class="w-[60%] h-[26rpx] bg-[#EEF2FF] rounded-full mb-[8rpx]"></view>
                                        <view class="w-[90%] h-[22rpx] bg-[#EEF2FF] rounded-full"></view>
                                    </view>
                                    <view>
                                        <view class="flex gap-[8rpx] mb-[8rpx]">
                                            <view class="w-[80rpx] h-[24rpx] bg-[#EEF2FF] rounded-full"></view>
                                            <view class="w-[100rpx] h-[24rpx] bg-[#EEF2FF] rounded-full"></view>
                                        </view>
                                        <view class="w-[160rpx] h-[20rpx] bg-[#EEF2FF] rounded-full"></view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>

                    <template v-else-if="currentTab === 0">
                        <view
                            v-for="(item, index) in dataList"
                            :key="index"
                            class="bg-white rounded-[28rpx] p-[24rpx] shadow-sm border border-solid border-[#f9f9f9]">
                            <view class="flex gap-[24rpx]">
                                <view
                                    class="flex-shrink-0 relative w-[160rpx] h-[213rpx] rounded-[20rpx] overflow-hidden">
                                    <image
                                        :src="item.pic || item.material_url"
                                        class="w-full h-full object-cover"
                                        mode="aspectFill"
                                        @click="handlePreviewImage(item)" />
                                    <view
                                        v-if="item.material_type == 1"
                                        class="absolute inset-0 flex items-center justify-center"
                                        @click="handlePlayVideo(item)">
                                        <view
                                            class="w-[64rpx] h-[64rpx] rounded-full bg-[#000000]/30 flex items-center justify-center pl-[4rpx] border border-[#ffffff]/40 active:scale-90">
                                            <u-icon name="play-right-fill" color="#ffffff" size="28" />
                                        </view>
                                    </view>
                                </view>

                                <view class="flex-1 flex flex-col justify-between">
                                    <view>
                                        <text class="text-[28rpx] font-bold text-gray-800 line-clamp-2 block">{{
                                            item.material_title
                                        }}</text>
                                        <text class="text-[22rpx] text-gray-400 mt-[8rpx] line-clamp-2 block">{{
                                            item.material_subtitle
                                        }}</text>
                                    </view>
                                    <view>
                                        <view
                                            class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-[8rpx]"
                                            v-if="item.material_tag">
                                            <text
                                                class="text-[20rpx] text-primary"
                                                v-for="(topic, ti) in item.material_tag"
                                                :key="ti"
                                                >#{{ topic }}</text
                                            >
                                        </view>
                                        <text class="text-[20rpx] text-[#676767]"
                                            >发布时间：{{ item.publish_time }}</text
                                        >
                                    </view>
                                </view>
                            </view>
                            <view
                                v-if="item.remark && item.status == 2"
                                class="text-[#EF4444] text-[22rpx] mt-[16rpx] break-all bg-[#FEF2F2] rounded-[16rpx] px-[16rpx] py-[12rpx]">
                                失败原因：{{ item.remark }}
                            </view>
                        </view>
                    </template>

                    <template v-else-if="currentTab === 1">
                        <view
                            v-for="(group, index) in getPrivateChatRecordList"
                            :key="index"
                            class="bg-white rounded-[28rpx] p-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                            <view class="flex items-center gap-x-[12rpx] mb-[24rpx]">
                                <view class="w-[6rpx] h-[24rpx] bg-primary rounded-full"></view>
                                <text class="text-[22rpx] text-[#676767] font-medium">{{ group.date_text }}</text>
                            </view>

                            <view class="flex flex-col gap-[24rpx]">
                                <view v-for="(item, idx) in group.list" :key="idx" class="flex gap-[20rpx]">
                                    <view class="flex flex-col items-center flex-shrink-0 mt-[4rpx]">
                                        <view class="w-[16rpx] h-[16rpx] rounded-full bg-[#10B981]"></view>
                                        <view
                                            v-if="idx < group.list.length - 1"
                                            class="w-[2rpx] flex-1 min-h-[32rpx] my-[4rpx] bg-[#f9f9f9] rounded-full"></view>
                                    </view>

                                    <view class="flex-1">
                                        <text class="text-[26rpx] font-bold text-[#212121] block mb-[12rpx]">{{
                                            item.author_name
                                        }}</text>
                                        <view class="bg-[#F4F6FB] rounded-[20rpx] p-[20rpx]">
                                            <view class="flex gap-[8rpx] mb-[12rpx]">
                                                <text class="flex-shrink-0 text-[22rpx] text-primary font-semibold"
                                                    >客户：</text
                                                >
                                                <view>
                                                    <text class="text-[22rpx] text-[#424242] block">{{
                                                        item.message_content || "-"
                                                    }}</text>
                                                    <text class="text-[20rpx] text-[#676767] block mt-[4rpx]">{{
                                                        item.message_time || "-"
                                                    }}</text>
                                                </view>
                                            </view>
                                            <view class="flex gap-[8rpx]">
                                                <text class="flex-shrink-0 text-[22rpx] text-[#676767] font-semibold"
                                                    >回复：</text
                                                >
                                                <view>
                                                    <text class="text-[22rpx] text-[#424242] block">{{
                                                        item.reply_content || "-"
                                                    }}</text>
                                                    <text class="text-[20rpx] text-[#676767] block mt-[4rpx]">{{
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

    <u-popup v-model="showUpdate" mode="center" border-radius="32" width="80%" @close="showUpdate = false">
        <view class="bg-white rounded-[32rpx] p-[40rpx]">
            <view class="flex items-center gap-x-[12rpx] mb-[20rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full"></view>
                <text class="text-[30rpx] font-bold text-[#212121]">提示</text>
            </view>
            <text class="text-[24rpx] text-[#676767] block leading-relaxed mb-[40rpx]">
                当前如果有任务执行中，该任务会中断并且不再执行，手机将等待下一时间段任务再开始执行，确认是否还要继续？
            </text>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                    @click="showUpdate = false">
                    <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-primary shadow-sm active:opacity-90"
                    @click="handleAccountConfirm">
                    <text class="text-[28rpx] font-semibold text-white">确定</text>
                </view>
            </view>
        </view>
    </u-popup>

    <account-update-progress
        v-model="showUpdateProgress"
        :steps="updateAccountSteps"
        :error="progressError"
        :error-msg="progressErrorMsg"
        @close="handleAccountProgressClose"
        @retry="handleAccountRetry" />

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
        @confirm="handleAccountRemoveConfirm" />
</template>

<script setup lang="ts">
import {
    fetchDeviceAccount,
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
import { applyAccountFetchError } from "@/ai_modules/device/hooks/apply-account-fetch-error";
import AccountUpdateProgress from "@/ai_modules/device/components/account-update-progress/account-update-progress.vue";
import { useDeviceStore } from "@/ai_modules/device/stores/device";
import { DeviceEventAction } from "@/ai_modules/device/enums";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import ChooseAgent from "@/ai_modules/device/components/choose-agent/choose-agent.vue";

const { onEvent, close } = useDeviceWs();
const deviceStore = useDeviceStore();
const { getSortedPlatform } = storeToRefs(deviceStore);
const deviceCode = ref<string>("");
const eventAction = ref<DeviceEventAction | null>();
/** 已下发 fetch，等待 appCompleted 后刷新账号 */
const isAccountFetching = ref(false);
const isAccountRefreshing = ref(false);
const selectedAgent = ref<{ id: string; name: string }>({ id: "", name: "" });
const currentPlatform = ref<AppTypeEnum>(AppTypeEnum.WECHAT);
const currentPlatformAccount = ref<any>({});
const currentPlatformItem = computed(() => {
    return getSortedPlatform.value.find((item) => item.type == currentPlatform.value);
});

const showAgentPopup = ref<boolean>(false);
const showRemovePopup = ref<boolean>(false);
const chooseAgentRef = shallowRef();

const tabs = [
    {
        name: "发布详情",
        key: "publish_detail",
    },
    {
        name: "私信详情",
        key: "private_detail",
    },
];
const currentTab = ref<number>(0);
const pagingRef = shallowRef();
const dataList = ref<any[]>([]);
const showUpdate = ref<boolean>(false);
const showUpdateProgress = ref<boolean>(false);
const progressError = ref(false);
const progressErrorMsg = ref("");
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
        errorCode: DeviceCmdCodeEnum.GET_ACCOUNT_INFO_COMPLETE_ERROR,
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

/** appCompleted 后轮询账号，等待服务端落库 */
const ACCOUNT_FETCH_REFRESH_DELAY = 800;
const ACCOUNT_FETCH_REFRESH_MAX_RETRY = 5;

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

const finishAccountFetch = async () => {
    if (isAccountRefreshing.value) return;
    isAccountRefreshing.value = true;
    const expectNewLogin = eventAction.value === DeviceEventAction.ADD_ACCOUNT;
    try {
        for (let i = 0; i < ACCOUNT_FETCH_REFRESH_MAX_RETRY; i++) {
            await sleep(ACCOUNT_FETCH_REFRESH_DELAY);
            await getDeviceAccount();
            pagingRef.value?.reload();
            if (!expectNewLogin || currentPlatformAccount.value?.account) break;
        }
    } finally {
        isAccountFetching.value = false;
        isAccountRefreshing.value = false;
        eventAction.value = null;
        showUpdate.value = false;
        uni.hideLoading();
    }
};

onEvent("success", async (data: any) => {
    const { type, appType } = data;

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

    if (
        type === DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE &&
        isAccountFetching.value &&
        (appType == null || appType === currentPlatform.value)
    ) {
        await finishAccountFetch();
    }
});

onEvent("error", (error: any) => {
    const { type } = error;
    uni.hideLoading();

    const msg = applyAccountFetchError(updateAccountSteps.value, error);

    const isCurrentPlatform = error.appType == null || error.appType === currentPlatform.value;
    if ((isAccountFetching.value || showUpdateProgress.value) && isCurrentPlatform) {
        isAccountFetching.value = false;
        isAccountRefreshing.value = false;
        progressError.value = true;
        progressErrorMsg.value = msg;
        showUpdateProgress.value = true;
        return;
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
    eventAction.value = event;
    updateAccountSteps.value.forEach((item) => {
        item.status = 0;
    });
    currentStep.value = 0;
    if (event == DeviceEventAction.ADD_ACCOUNT) {
        handleAccountConfirm();
    } else {
        showUpdate.value = true;
    }
};

const handleAccountProgressClose = () => {
    progressError.value = false;
    progressErrorMsg.value = "";
    isAccountFetching.value = false;
    isAccountRefreshing.value = false;
    showUpdateProgress.value = false;
};

const handleAccountRetry = () => {
    progressError.value = false;
    progressErrorMsg.value = "";
    updateAccountSteps.value.forEach((item) => {
        item.status = 0;
    });
    currentStep.value = 0;
    handleAccountConfirm();
};

const handleAccountConfirm = async () => {
    showUpdate.value = false;
    progressError.value = false;
    progressErrorMsg.value = "";
    if (currentPlatform.value != AppTypeEnum.WECHAT || showUpdateProgress.value) {
        showUpdateProgress.value = true;
    } else {
        uni.showLoading({
            title: "更新中...",
            mask: true,
        });
    }
    updateAccountSteps.value[0].status = 1; // 设置第一步为进行中

    try {
        await fetchDeviceAccount({
            device_code: deviceCode.value,
            type: currentPlatform.value,
        });
        isAccountFetching.value = true;
    } catch (error: any) {
        isAccountFetching.value = false;
        showUpdateProgress.value = false;
        uni.hideLoading();
        uni.showToast({
            title: typeof error === "string" ? error : "下发获取账号指令失败",
            icon: "none",
            duration: 3000,
        });
    }
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
