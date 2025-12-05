<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="平台详情"
            title-bold
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="rounded-[20rpx] mx-[26rpx] mt-[12rpx] bg-white">
            <view class="grid grid-cols-4 bg-[#00000012] rounded-tl-[20rpx] rounded-tr-[20rpx]">
                <view
                    v-for="(item, index) in platformList"
                    class="platform-item"
                    :key="index"
                    :class="{ active: currentPlatform == item.type }"
                    @click="handlePlatformClick(item.type)">
                    <image :src="getPlatformLogo(item.type)" class="w-[48rpx] h-[48rpx]"></image>
                </view>
            </view>
            <view class="px-[36rpx] pt-5">
                <view class="flex justify-between items-center gap-x-2">
                    <view class="font-bold"> {{ currentPlatformItem?.name }}账号 </view>
                    <view
                        v-if="currentPlatformAccount.account"
                        class="flex items-center gap-x-1"
                        @click="handleUpdateAccount(DeviceEventAction.UPDATE_ACCOUNT)">
                        <u-icon name="reload" color="#0065FB"></u-icon>
                        <text class="text-primary">更新</text>
                    </view>
                </view>
                <template v-if="currentPlatformAccount.account">
                    <view class="py-5 flex gap-x-6">
                        <image
                            :src="currentPlatformAccount.avatar"
                            class="w-[140rpx] h-[140rpx] rounded-full flex-shrink-0"></image>
                        <view class="flex-1">
                            <view class="font-bold text-[30rpx]">{{ currentPlatformAccount.nickname }}</view>
                            <view class="text-xs text-[#0000004d] mt-[4rpx]"
                                >({{ currentPlatformAccount.account }})</view
                            >
                            <view class="flex items-center justify-between mt-[28rpx] w-[70%]">
                                <view class="flex flex-col text-center">
                                    <view class="text-[32rpx] font-bold">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.followers || 0)
                                    }}</view>
                                    <view class="text-xs text-[#0000004d] mt-1">关注</view>
                                </view>
                                <view class="flex flex-col text-center">
                                    <view class="text-[32rpx] font-bold">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.fans || 0)
                                    }}</view>
                                    <view class="text-xs text-[#0000004d] mt-1">粉丝</view>
                                </view>
                                <view class="flex flex-col text-center">
                                    <view class="text-[32rpx] font-bold">{{
                                        formatNumberToWanOrYi(currentPlatformAccount.thumbup_collect || 0)
                                    }}</view>
                                    <view class="text-xs text-[#0000004d] mt-1">点赞</view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <template v-if="currentPlatform == AppTypeEnum.XHS">
                        <view
                            class="flex items-center justify-between gap-2 h-[80rpx] border-[0] border-b border-t border-solid border-[#00000008]">
                            <view class="font-bold flex-shrink-0">私信开关</view>
                            <view class="">
                                <u-switch
                                    v-model="currentPlatformAccount.open_ai"
                                    :active-value="1"
                                    :inactive-value="0"
                                    :size="32"
                                    @change="handleOpenAiChange"></u-switch>
                            </view>
                        </view>
                        <template v-if="currentPlatformAccount.open_ai == 1">
                            <view class="flex items-center justify-between gap-2 h-[80rpx]">
                                <view class="font-bold flex-shrink-0">私信智能体</view>
                                <view
                                    class="flex-1 flex items-center justify-end gap-x-2"
                                    @click="showRobotPopup = true">
                                    <view
                                        class="line-clamp-1 break-all"
                                        :class="{ 'text-[#00000080]': currentPlatformAccount.robot_name }"
                                        >{{ currentPlatformAccount.robot_name || "未配置" }}</view
                                    >
                                    <u-icon name="arrow-right" color="#B2B2B2"></u-icon>
                                </view>
                            </view>
                        </template>
                    </template>
                    <view
                        class="flex items-center justify-between gap-x-2 py-[22rpx] border-[0] border-t border-solid border-[#00000008]">
                        <text class="text-xs text-[#0000004d]">
                            最后一次更新：{{ currentPlatformAccount.update_time }}</text
                        >
                        <view class="flex items-center gap-x-1" @click="showRemovePopup = true">
                            <image src="/static/images/icons/delete.svg" class="w-[24rpx] h-[24rpx]"></image>
                            <text class="text-xs">账号移除</text>
                        </view>
                    </view>
                </template>
                <view class="py-5" v-else>
                    <view class="text-center text-[#0000004d]">您还未获取平台账号</view>
                    <view
                        class="mx-auto w-[220rpx] h-[80rpx] flex items-center justify-center text-white bg-primary font-bold rounded-[20rpx] text-[30rpx] mt-[30rpx]"
                        @click="handleUpdateAccount(DeviceEventAction.ADD_ACCOUNT)">
                        立即获取
                    </view>
                </view>
            </view>
        </view>
        <view class="px-10 mt-5">
            <u-tabs
                bg-color="transparent"
                :current="currentTab"
                :list="getTabList"
                :is-scroll="false"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-2 py-[30rpx] mx-[26rpx]">
                    <template v-if="currentTab === 0">
                        <view v-for="(item, index) in dataList" :key="index" class="publish-item">
                            <view class="flex gap-x-[30rpx]">
                                <view class="absolute right-2 top-2">
                                    <view
                                        v-if="getPublishStatusText(item.status)"
                                        class="px-[20rpx] py-[6rpx] rounded-[12rpx] font-bold text-[22rpx]"
                                        :class="getPublishStatusStyle(item.status)">
                                        {{ getPublishStatusText(item.status) }}
                                    </view>
                                </view>
                                <view
                                    class="flex-shrink-0 relative w-[180rpx] h-[240rpx] rounded-[20rpx] overflow-hidden">
                                    <image
                                        :src="item.pic || item.material_url"
                                        class="w-full h-full"
                                        mode="aspectFill"
                                        @click="handlePreviewImage(item)"></image>
                                    <view
                                        class="w-full h-full flex items-center justify-center absolute top-0 left-0"
                                        v-if="item.material_type == 1">
                                        <view
                                            class="rounded-full bg-[#ffffff33] w-[68rpx] h-[68rpx]"
                                            style="backdrop-filter: blur(5px)"
                                            @click="handlePlayVideo(item)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                </view>
                                <view class="flex-1 flex flex-col justify-between">
                                    <view class="mr-14">
                                        <view class="font-bold text-[#000000e6] line-clamp-2">
                                            {{ item.material_title }}
                                        </view>
                                        <view class="text-[#00000080] mt-1 text-xs line-clamp-2">{{
                                            item.material_subtitle
                                        }}</view>
                                    </view>
                                    <view>
                                        <view class="flex flex-wrap items-center gap-2" v-if="item.material_tag">
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
                    <template v-if="currentTab === 1">
                        <view v-for="(item, index) in getPrivateChatRecordList" :key="index" class="private-item">
                            <view class="text-xs text-[#00000080] font-bold"> {{ item.date_text }} </view>
                            <view class="mt-[34rpx] flex flex-col gap-y-[36rpx]">
                                <view v-for="(val, key) in item.list" :key="key" class="private-item-content">
                                    <view class="mt-1 flex-shrink-0 relative">
                                        <view class="w-[14rpx] h-[14rpx] rounded-full bg-[#00C08E]"></view>
                                        <view
                                            class="absolute top-[38rpx] left-[6rpx] w-[2rpx] h-[90%] bg-[#0000000d]"></view>
                                    </view>
                                    <view>
                                        <view class="flex gap-x-1 text-xs">
                                            <view class="flex-shrink-0 text-[#00C08E] font-bold">客户：</view>
                                            <view>
                                                <view class="break-all">{{ val.message_content || "-" }}</view>
                                                <view class="text-[22rpx] text-[#00000033] mt-[4rpx]">{{
                                                    val.message_time || "-"
                                                }}</view>
                                            </view>
                                        </view>
                                        <view class="flex gap-x-1 text-xs mt-3">
                                            <view class="flex-shrink-0 text-[#00000033] font-bold">回复：</view>
                                            <view>
                                                <view class="break-all">{{ val.reply_content || "-" }}</view>
                                                <view class="text-[22rpx] text-[#00000033] mt-[4rpx]">{{
                                                    val.reply_time || "-"
                                                }}</view>
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
    <u-popup v-model="showUpdate" mode="center" border-radius="20" width="80%" @close="showUpdate = false">
        <view class="rounded-[20rpx] bg-white p-5">
            <view class="text-[30rpx] font-bold text-center">提示</view>
            <view class="text-xs text-[#00000080] mt-[32rpx] text-center">
                当前如果有任务执行中，该任务会中断并且不再执行，手机将等待下一时间段任务再开始执行，确认是否还要继续？
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold"
                    @click="showUpdate = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleAccountConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <u-popup
        v-model="showUpdateProgress"
        mode="center"
        border-radius="20"
        width="80%"
        :mask-close-able="false"
        @close="showUpdateProgress = false">
        <view class="rounded-[20rpx] bg-white px-5 py-[78rpx]">
            <view class="flex flex-col gap-y-3 w-[70%] mx-auto">
                <view v-for="(item, index) in updateAccountSteps" :key="index" class="step-item">
                    <view class="flex-shrink-0 mt-[4rpx] relative">
                        <view class="w-[28rpx] h-[28rpx]">
                            <view
                                v-if="item.status == 0"
                                class="w-full h-full rounded-full border border-solid border-[#0000001a]">
                            </view>
                            <view
                                v-if="item.status == 1"
                                class="w-full h-full rounded-full border border-solid border-primary-light-8 flex items-center justify-center">
                                <view class="w-[12rpx] h-[12rpx] rounded-full bg-primary"></view>
                            </view>
                            <view
                                v-if="item.status == 2"
                                class="w-full h-full rounded-full flex items-center justify-center border border-solid border-primary">
                                <u-icon name="checkmark" color="#0065FB" size="16"></u-icon>
                            </view>
                        </view>
                        <view
                            class="absolute top-[60%] left-[14rpx] w-[2rpx] h-[60%]"
                            :class="[item.status == 2 ? 'bg-primary' : 'bg-[#0000001a]']"
                            v-if="index !== updateAccountSteps.length - 1"></view>
                    </view>
                    <view class="h-[80rpx]">
                        <view
                            class="font-bold"
                            :class="{
                                'text-[#0000004d]': item.status == 0,
                            }">
                            {{ item.title }}
                        </view>
                        <view class="mt-1">
                            <text class="text-primary font-bold text-xs" v-if="item.status == 1">获取中...</text>
                            <text class="text-[#FF2442] font-bold text-xs" v-if="item.status == 3">获取失败</text>
                        </view>
                    </view>
                </view>
            </view>
            <view class="mt-2 flex flex-col gap-y-2">
                <u-button
                    v-if="isExecuteComplete"
                    type="primary"
                    :custom-style="{ height: '90rpx', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="showUpdateProgress = false"
                    >确认</u-button
                >
                <u-button
                    :custom-style="{ height: '90rpx', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="showUpdateProgress = false"
                    >取消</u-button
                >
            </view>
        </view>
    </u-popup>
    <popup-bottom
        :show="showRobotPopup"
        title="选择智能体"
        custom-class="bg-[#F9FAFB]"
        :show-close-btn="true"
        :is-disabled-touch="true"
        @close="showRobotPopup = false">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0">
                    <z-paging
                        ref="robotPagingRef"
                        v-model="robotList"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryRobotList">
                        <view class="flex flex-col gap-y-2 py-[30rpx] mx-[26rpx]">
                            <view
                                v-for="item in robotList"
                                :key="item.id"
                                class="robot-item"
                                :class="{ active: selectedRobotId == item.id }"
                                @click="handleSelectRobot(item)">
                                <view class="flex items-center gap-x-2">
                                    <image :src="item.image" class="w-[96rpx] h-[96rpx] rounded-full"></image>
                                </view>
                                <view class="flex-1">
                                    <view class="font-bold text-[#000000e6] text-[30rpx]">
                                        {{ item.name }}
                                    </view>
                                    <view class="text-xs text-[#00000080] mt-1">
                                        {{ item.intro }}
                                    </view>
                                </view>
                                <view
                                    class="w-5 h-5 rounded-full"
                                    :class="[
                                        selectedRobotId == item.id
                                            ? 'bg-primary flex items-center justify-center'
                                            : 'border border-solid border-[#00000033]',
                                    ]">
                                    <u-icon
                                        name="checkmark"
                                        color="#ffffff"
                                        size="20"
                                        v-if="selectedRobotId == item.id"></u-icon>
                                </view>
                            </view>
                        </view>
                    </z-paging>
                </view>
                <view class="px-4 pt-2 pb-5">
                    <u-button
                        type="primary"
                        :custom-style="{ height: '90rpx', fontWeight: 'bold', borderRadius: '20rpx' }"
                        :disabled="!selectedRobotId"
                        @click="handleBindRobotConfirm"
                        >确定绑定</u-button
                    >
                </view>
            </view>
        </template>
    </popup-bottom>
    <video-preview
        v-model:show="showVideoPreview"
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
// 导入API服务
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
// 导入枚举类型
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
// 导入工具函数
import { formatNumberToWanOrYi } from "@/utils/util";
// 导入自定义Hooks
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { DeviceEventAction } from "@/ai_modules/device/enums";

// 初始化WebSocket服务
const { send, onEvent, close } = useDeviceWs();

// 获取平台Logo配置
const { platformLogo } = useDevice();

// 设备唯一标识码
const deviceCode = ref<string>("");

// 当前事件动作类型
const eventAction = ref<DeviceEventAction | null>();

// 平台列表数据
const platformList = [
    {
        name: "微信",
        type: AppTypeEnum.WECHAT,
    },
    {
        name: "小红书",
        type: AppTypeEnum.XHS,
    },
    {
        name: "抖音",
        type: AppTypeEnum.DOUYIN,
    },
    {
        name: "快手",
        type: AppTypeEnum.KUAISHOU,
    },
];

// 当前选中的平台类型
const currentPlatform = ref<AppTypeEnum>(AppTypeEnum.WECHAT);
// 当前平台账号信息
const currentPlatformAccount = ref<any>({});
// 根据当前平台类型计算出对应的平台项
const currentPlatformItem = computed(() => {
    return platformList.find((item) => item.type == currentPlatform.value);
});

// 控制机器人选择弹窗的显示
const showRobotPopup = ref<boolean>(false);
// 机器人列表的分页引用
const robotPagingRef = shallowRef();
// 机器人列表数据
const robotList = ref<any[]>([]);
// 当前选中的机器人ID
const selectedRobotId = ref<string>("");

// 控制移除账号确认弹窗的显示
const showRemovePopup = ref<boolean>(false);

// 根据当前平台类型计算出Tab列表
const getTabList = computed(() => {
    const commonTabs = {
        name: "发布详情",
        key: "publish_detail",
    };
    if (currentPlatform.value == AppTypeEnum.XHS) {
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

// 当前选中的Tab索引
const currentTab = ref<number>(0);

// 列表分页引用
const pagingRef = shallowRef();
// 列表数据
const dataList = ref<any[]>([]);

// 控制更新弹窗的显示
const showUpdate = ref<boolean>(false);
// 控制更新进度弹窗的显示
const showUpdateProgress = ref<boolean>(false);
// 更新账号的步骤列表
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
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
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
// 记录当前正在执行的步骤索引
const currentStep = ref<number>(0);

// 控制视频预览弹窗的显示
const showVideoPreview = ref(false);
// 预览视频的信息
const previewVideo = reactive({
    url: "",
    pic: "",
});

// 判断所有更新步骤是否已完成
const isExecuteComplete = computed(() => {
    return updateAccountSteps.value.every((item) => item.status === 2);
});

// 将私信记录按日期分组并格式化
const getPrivateChatRecordList = computed(() => {
    const groupList: any = [];
    const weekList = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
    dataList.value.forEach((item: any) => {
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

// 监听WebSocket成功事件
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
    }
});

// 监听WebSocket错误事件
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

// 查询机器人列表
const queryRobotList = async (pageNo: number, pageSize: number) => {
    try {
        const { lists } = await getAgentList({
            page: pageNo,
            page_size: pageSize,
            source: 1,
        });
        robotPagingRef.value?.complete(lists);
    } catch (error) {
        robotPagingRef.value?.complete([]);
    }
};

// 处理机器人选择
const handleSelectRobot = (item: any) => {
    selectedRobotId.value = selectedRobotId.value === item.id ? "" : item.id;
};

// 获取平台Logo图片
const getPlatformLogo = (type: AppTypeEnum) => {
    const data = platformLogo[type as keyof typeof platformLogo];
    return currentPlatform.value == type ? data.activeIcon : data.icon;
};

// 切换平台
const handlePlatformClick = async (type: AppTypeEnum) => {
    if (currentPlatform.value === type) return;
    currentPlatform.value = type;
    currentTab.value = 0;
    await getDeviceAccount();
    pagingRef.value?.reload();
};

// 切换Tab
const handleTabChange = (index: number) => {
    if (currentTab.value === index) return;
    currentTab.value = index;
    pagingRef.value?.reload();
};

// 处理更新账号操作
const handleUpdateAccount = (event: DeviceEventAction) => {
    if (event == DeviceEventAction.ADD_ACCOUNT) {
        handleAccountConfirm();
    } else {
        showUpdate.value = true;
    }
    eventAction.value = event;
    // 重置所有步骤状态
    updateAccountSteps.value.forEach((item) => {
        item.status = 0;
    });
    currentStep.value = 0;
};

// 确认账号更新/添加
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

// 确认移除账号
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

// 处理私信接管开关状态变更
const handleOpenAiChange = async (value: boolean) => {
    uni.showLoading({
        title: "更新中...",
        mask: true,
    });
    try {
        await changeAccountStatus({
            account: currentPlatformAccount.value.account,
            open_ai: value ? 1 : 0,
        });
        uni.hideLoading();
        uni.showToast({
            title: "更新成功",
            icon: "none",
            duration: 3000,
        });
        getDeviceAccount();
    } catch (error) {
        uni.hideLoading();
        uni.showToast({
            title: "更新失败",
            icon: "none",
            duration: 3000,
        });
    }
};

// 确认绑定机器人
const handleBindRobotConfirm = async () => {
    uni.showLoading({
        title: "绑定中...",
        mask: true,
    });
    try {
        await changeAccountStatus({
            account: currentPlatformAccount.value.account,
            robot_id: selectedRobotId.value,
            takeover_mode: 1, // 接管模式
            open_ai: currentPlatformAccount.value.open_ai,
        });
        uni.hideLoading();
        uni.showToast({
            title: "绑定成功",
            icon: "none",
            duration: 3000,
        });
        getDeviceAccount();
        showRobotPopup.value = false;
    } catch (error) {
        uni.hideLoading();
        uni.showToast({
            title: "绑定失败",
            icon: "none",
            duration: 3000,
        });
    }
};

// 获取设备账号信息
const getDeviceAccount = async () => {
    const { lists } = await getDeviceAccountList({
        device_code: deviceCode.value,
        type: currentPlatform.value,
    });
    if (lists && lists.length > 0) {
        currentPlatformAccount.value = lists[0];
        selectedRobotId.value = lists[0].robot_id;
    } else {
        currentPlatformAccount.value = {};
    }
};

// 查询发布/私信列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        let lists: any[] = [];
        if (currentTab.value === 0) {
            // 查询发布记录
            const { lists: publishLists } = await getDevicePublishRecordList({
                device_code: deviceCode.value,
                account_type: currentPlatform.value,
                account: currentPlatformAccount.value.account,
                page: pageNo,
                page_size: pageSize,
                task_type: 3,
            });
            lists = publishLists || [];
        } else {
            // 查询私信记录
            const { lists: privateChatLists } = await getDevicePrivateChatRecordList({
                device_code: deviceCode.value,
                page: pageNo,
                page_size: pageSize,
                type: currentPlatform.value,
            });
            lists = privateChatLists || [];
        }
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

// 根据发布状态获取对应的样式
const getPublishStatusStyle = (status: number) => {
    switch (status) {
        case 0:
            return "text-primary bg-primary-light-9"; // 未发布
        case 1:
            return "text-[#00C08E] bg-[rgba(0,192,142,0.1)]"; // 已发布
        case 2:
            return "text-[#FF2442] bg-[rgba(255,36,66,0.1)]"; // 发布失败
        default:
            return "text-primary bg-primary-light-9";
    }
};

// 根据发布状态获取对应的文本
const getPublishStatusText = (status: number) => {
    const statusMap: { [key: number]: string } = {
        0: "未发布",
        1: "已发布",
        2: "发布失败",
        3: "发布中",
    };
    return statusMap[status] || "未知状态"; // 添加默认值处理未知状态
};

// 处理视频播放
const handlePlayVideo = (item: any) => {
    showVideoPreview.value = true;
    previewVideo.pic = item.pic;
    previewVideo.url = item.material_url;
};

// 处理图片预览
const handlePreviewImage = (item: any) => {
    const { pic } = item;
    uni.previewImage({
        urls: [pic],
    });
};

// 页面加载生命周期钩子
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
    @apply h-[90rpx] flex items-center justify-center;
    &.active {
        @apply bg-white rounded-tl-[20rpx] rounded-tr-[20rpx];
    }
}

.publish-item {
    @apply bg-white rounded-[20rpx] px-4 py-[30rpx] relative;
}
.private-item {
    @apply bg-white rounded-[20rpx] px-4 py-[30rpx];
}
.private-item-content {
    @apply flex gap-x-3;
}
.step-item {
    @apply flex  gap-x-[28rpx];
}
.robot-item {
    @apply bg-white rounded-[20rpx] px-4 py-[30rpx]  gap-x-[30rpx] relative flex items-center;
    &.active {
        @apply shadow-[0_0_0_1rpx_var(--color-primary)];
    }
}
</style>
