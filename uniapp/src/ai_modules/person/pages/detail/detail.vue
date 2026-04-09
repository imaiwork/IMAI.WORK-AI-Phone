<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[80rpx] relative">
        <view
            class="absolute top-0 left-0 right-0 h-[320rpx] rounded-b-[60rpx] z-0"
            style="background: linear-gradient(180deg, #0048ce 0%, #0065fb 50%, #4facfe 100%)"></view>

        <u-navbar
            :border-bottom="false"
            :background="{ background: navBgColor }"
            title="人设详情"
            :title-color="navColor"
            :back-icon-color="navColor"
            title-bold>
        </u-navbar>

        <template v-if="loading">
            <view class="px-[30rpx] pt-2 relative z-10">
                <view
                    class="bg-white rounded-[36rpx] px-[28rpx] py-[24rpx] shadow-[0_16rpx_40rpx_rgba(0,101,251,0.08)] mt-2 animate-pulse">
                    <view class="flex items-center gap-x-3">
                        <view class="w-[100rpx] h-[100rpx] rounded-full bg-[#F3F4F6] flex-shrink-0"></view>
                        <view class="flex flex-col gap-[14rpx] flex-1">
                            <view class="flex items-center gap-[8rpx]">
                                <view class="h-[36rpx] w-[160rpx] bg-[#F3F4F6] rounded-full"></view>
                                <view class="w-[28rpx] h-[28rpx] bg-[#F3F4F6] rounded-full"></view>
                            </view>
                            <view class="h-[48rpx] w-[140rpx] bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                    <view class="mt-4 flex gap-[12rpx]">
                        <view class="flex-1 h-[72rpx] bg-[#F3F4F6] rounded-[16rpx]"></view>
                        <view class="flex-1 h-[72rpx] bg-[#F3F4F6] rounded-[16rpx]"></view>
                    </view>
                </view>

                <view class="mt-6 animate-pulse">
                    <view class="flex items-center gap-2 mb-3 px-2">
                        <view class="w-[6rpx] h-[32rpx] bg-[#F3F4F6] rounded-full"></view>
                        <view class="h-[30rpx] w-[160rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="h-[76rpx] bg-[#F3F4F6] rounded-[16rpx] mb-4"></view>
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-x-3">
                                <view class="w-[88rpx] h-[88rpx] bg-[#F3F4F6] rounded-[24rpx] flex-shrink-0"></view>
                                <view class="flex flex-col gap-2">
                                    <view class="h-[30rpx] w-[140rpx] bg-[#F3F4F6] rounded-full"></view>
                                    <view class="h-[24rpx] w-[200rpx] bg-[#F3F4F6] rounded-full"></view>
                                </view>
                            </view>
                            <view class="h-[28rpx] w-[80rpx] bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                </view>

                <view v-for="g in 2" :key="g" class="mt-6 animate-pulse">
                    <view class="flex items-center gap-2 mb-3 px-2">
                        <view class="w-[6rpx] h-[32rpx] bg-[#F3F4F6] rounded-full"></view>
                        <view class="h-[30rpx] w-[180rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-2 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view
                            v-for="i in g === 1 ? 2 : 3"
                            :key="i"
                            class="flex items-center justify-between p-2"
                            :class="i < (g === 1 ? 2 : 3) ? 'border-b border-[#F3F4F6]' : ''">
                            <view class="flex items-center gap-x-3">
                                <view class="w-[88rpx] h-[88rpx] bg-[#F3F4F6] rounded-[24rpx] flex-shrink-0"></view>
                                <view class="flex flex-col gap-2">
                                    <view class="h-[30rpx] w-[120rpx] bg-[#F3F4F6] rounded-full"></view>
                                    <view class="h-[24rpx] w-[180rpx] bg-[#F3F4F6] rounded-full"></view>
                                </view>
                            </view>
                            <view class="w-[48rpx] h-[48rpx] bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                </view>

                <view class="mt-6 mb-4 animate-pulse">
                    <view class="flex items-center gap-2 mb-3 px-2">
                        <view class="w-[6rpx] h-[32rpx] bg-[#F3F4F6] rounded-full"></view>
                        <view class="h-[30rpx] w-[140rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="h-[96rpx] bg-[#F3F4F6] rounded-[24rpx]"></view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="px-[30rpx] pt-2 relative z-10">
                <view
                    class="bg-white rounded-[36rpx] px-[28rpx] py-[24rpx] shadow-[0_16rpx_40rpx_rgba(0,101,251,0.08)] gap-[24rpx] relative mt-2 border-[2rpx] border-white">
                    <view class="flex items-center gap-x-3">
                        <view class="flex items-center">
                            <view class="flex items-center relative flex-shrink-0">
                                <avatar-upload
                                    :avatar="detail.avatar_url"
                                    :size="100"
                                    :icon-size="40"
                                    @update:avatar="handleAvatarUpdate" />
                                <view
                                    class="absolute inset-0 rounded-full border-[4rpx] border-solid border-primary opacity-20 scale-110 pointer-events-none"></view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center gap-[8rpx] cursor-pointer" @click="handleEditName">
                                <text
                                    class="text-[34rpx] font-extrabold text-[#212121] tracking-wide line-clamp-1 break-all">
                                    {{ detail.persona_name }}
                                </text>
                                <u-icon name="edit-pen" size="28" color="#9CA3AF" class="flex-shrink-0" />
                            </view>
                            <view
                                class="mt-1 rounded-full w-fit pl-[16rpx] pr-[8rpx] py-[6rpx] flex items-center gap-[8rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.2)] active:scale-95 transition-transform"
                                style="background: linear-gradient(90deg, #0065fb 0%, #4facfe 100%)"
                                @click="handleEditType">
                                <text class="text-white text-[22rpx] font-medium tracking-wider">
                                    {{ PersonTypeMap[detail.persona_type as keyof typeof PersonTypeMap] }}
                                </text>
                                <view
                                    class="w-[36rpx] h-[36rpx] flex items-center justify-center bg-[#ffffff]/20 rounded-full">
                                    <u-icon name="arrow-rightward" size="20" color="#ffffff" />
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-4 flex gap-[12rpx]">
                        <view
                            class="flex-1 flex items-center gap-[10rpx] bg-[#F4F7FA] rounded-[16rpx] px-[16rpx] py-[12rpx] active:scale-95 transition-transform"
                            @click="handleNavigate('/ai_modules/person/pages/video_record/video_record')">
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-[14rpx] flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(90deg, #e6f0ff 0%, #f0f5ff 100%)">
                                <image
                                    src="@/ai_modules/person/static/icons/video_primary.svg"
                                    class="w-[28rpx] h-[28rpx]" />
                            </view>
                            <view class="flex-1 flex items-center justify-between min-w-0">
                                <text class="text-[24rpx] font-bold text-[#212121]">生成记录</text>
                                <u-icon name="arrow-right" color="#C0C4CC" size="20"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex-1 flex items-center gap-[10rpx] bg-[#F4F7FA] rounded-[16rpx] px-[16rpx] py-[12rpx] active:scale-95 transition-transform"
                            @click="
                                handleNavigate(
                                    `/ai_modules/person/pages/analysis/analysis?id=${personId}&type=${detail.persona_type}&mode=edit`
                                )
                            ">
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-[14rpx] flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(90deg, #fff0e6 0%, #fff5f0 100%)">
                                <image src="@/ai_modules/person/static/icons/light.svg" class="w-[28rpx] h-[28rpx]" />
                            </view>
                            <view class="flex-1 flex items-center justify-between min-w-0">
                                <text class="text-[24rpx] font-bold text-[#212121]">运营策略</text>
                                <u-icon name="arrow-right" color="#C0C4CC" size="20"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mt-6">
                    <view class="flex items-center justify-between mb-3 px-2">
                        <view class="flex items-center gap-2">
                            <view class="w-1.5 h-4 bg-primary rounded-full"></view>
                            <text class="text-[30rpx] font-extrabold text-[#212121]">配置素材库</text>
                        </view>
                        <config-status-badge :configured="configStatus.material_config === 1" />
                    </view>
                    <view class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="flex flex-col gap-[16rpx]">
                            <view
                                v-for="(tab, tIndex) in materialTabs"
                                :key="tIndex"
                                class="flex items-center justify-between rounded-[24rpx] px-[28rpx] py-[24rpx] transition-all"
                                :style="
                                    detail.publish_mode === tab.key
                                        ? 'background: linear-gradient(135deg, #EBF2FF 0%, #F0F6FF 100%); border: 2rpx solid #C5D9FF;'
                                        : 'background: #FFFFFF; border: 2rpx solid #F0F0F0;'
                                "
                                @click="handleSelectPublishMode(tab.key)">
                                <view class="flex flex-col gap-[8rpx] flex-1 min-w-0 pr-4">
                                    <view class="flex items-center gap-[12rpx]">
                                        <text
                                            class="text-[30rpx] font-bold leading-tight"
                                            :class="
                                                detail.publish_mode === tab.key ? 'text-primary' : 'text-[#212121]'
                                            ">
                                            {{ tab.label }}
                                        </text>
                                        <view
                                            v-if="detail.publish_mode === tab.key"
                                            class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center flex-shrink-0 bg-primary">
                                            <u-icon name="checkmark" color="#ffffff" size="18"></u-icon>
                                        </view>
                                    </view>
                                    <text class="text-[24rpx] text-[#9CA3AF] leading-relaxed">{{ tab.desc }}</text>
                                </view>
                                <view
                                    v-if="detail.publish_mode === tab.key"
                                    class="flex items-center gap-[4rpx] flex-shrink-0"
                                    @click.stop="
                                        handleNavigate('/ai_modules/person/pages/material_library/material_library')
                                    ">
                                    <text class="text-[26rpx] text-primary font-medium">去配置</text>
                                    <u-icon name="arrow-right" color="#0065fb" size="22"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view v-for="(group, gIndex) in menuGroups" :key="gIndex" class="mt-6">
                    <view class="flex items-center gap-2 mb-3 px-2">
                        <view class="w-1.5 h-4 bg-primary rounded-full"></view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">{{ group.title }}</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-2 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view
                            v-for="(item, iIndex) in group.items"
                            :key="iIndex"
                            class="flex items-center justify-between p-2 rounded-[24rpx] active:bg-[#F8F9FD] transition-colors"
                            :class="iIndex < group.items.length - 1 ? 'border-b border-[#F7F8FA]' : ''"
                            @click="handleNavigate(item.path)">
                            <view class="flex items-center gap-x-3">
                                <view
                                    class="w-[88rpx] h-[88rpx] flex items-center justify-center bg-[#F4F7FA] rounded-[24rpx]">
                                    <image :src="getIconPath(item.icon)" class="w-[44rpx] h-[44rpx]" />
                                </view>
                                <view class="flex flex-col justify-center">
                                    <text class="text-[30rpx] font-bold text-[#212121]">{{ item.title }}</text>
                                    <text v-if="item.desc" class="text-[24rpx] text-[#b4b4b4] mt-1">{{
                                        item.desc
                                    }}</text>
                                </view>
                            </view>
                            <view class="flex items-center gap-x-2 shrink-0">
                                <config-status-badge
                                    v-if="item.configKey"
                                    :configured="configStatus[item.configKey] === 1" />
                                <view
                                    v-if="item.tag"
                                    class="px-3 py-1 rounded-full text-[22rpx] font-bold"
                                    :class="
                                        item.tagType === 'success'
                                            ? 'text-[#00C08E] bg-[#E6F8F3]'
                                            : 'text-primary bg-[#E6F0FF]'
                                    ">
                                    {{ item.tag }}
                                </view>
                                <view
                                    class="w-[48rpx] h-[48rpx] flex items-center justify-center bg-[#f9f9f9] rounded-full">
                                    <u-icon name="arrow-right" color="#9CA3AF" size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mt-6 mb-4">
                    <view class="flex items-center gap-2 mb-3 px-2">
                        <view class="w-1.5 h-4 bg-primary rounded-full"></view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">关联设备</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view
                            class="border-2 border-dashed border-[#0065fb]/30 bg-[#F2F7FF] rounded-[24rpx] h-[96rpx] flex items-center justify-center gap-2 active:bg-[#E6F0FF] transition-colors"
                            @click="handleSelectDevice">
                            <u-icon name="plus" color="#0065fb" size="32"></u-icon>
                            <text class="text-[30rpx] font-bold text-primary">添加关联设备</text>
                        </view>
                        <view class="flex flex-col gap-3 mt-4" v-if="deviceList.length > 0">
                            <view
                                v-for="(device, dIndex) in deviceList"
                                :key="dIndex"
                                class="flex items-center justify-between p-3 bg-[#F8F9FD] rounded-[24rpx] border border-solid border-[#ececec]">
                                <view class="flex items-center gap-x-4">
                                    <view
                                        class="w-[88rpx] h-[88rpx] flex items-center justify-center bg-white rounded-[24rpx] shadow-sm">
                                        <image
                                            src="@/ai_modules/person/static/icons/device.svg"
                                            class="w-[50rpx] h-[50rpx]" />
                                    </view>
                                    <view class="flex flex-col">
                                        <text class="text-[30rpx] font-bold text-[#212121]">{{
                                            device.device_name
                                        }}</text>
                                        <view class="flex items-center gap-1.5 mt-1.5">
                                            <view
                                                class="w-2 h-2 rounded-full"
                                                :style="{
                                                    backgroundColor: getDeviceStatusStyle(device.status).dotColor,
                                                }">
                                            </view>
                                            <text
                                                class="text-[22rpx] font-medium"
                                                :style="{ color: getDeviceStatusStyle(device.status).dotColor }">
                                                {{ getDeviceStatusStyle(device.status).label }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="w-[72rpx] h-[72rpx] flex items-center justify-center rounded-full bg-white shadow-sm transition-colors"
                                    @click.stop="handleDeviceSetting(device)">
                                    <u-icon name="more-dot-fill" color="#6B7280" size="36"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <name-edit ref="nameEditRef" v-model="showNameEdit" title="编辑人设名称" @confirm="handleNameEditConfirm" />
        <choose-device ref="chooseDeviceRef" v-model="showChooseDevice" @confirm="handleChooseDeviceConfirm" />
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, getPersonConfigStatus, updatePerson, getPersonDeviceList } from "@/api/person";
import { removeDevicePersona, updateDevice } from "@/api/device";
import { PersonTypeMap } from "@/enums/appEnums";
import VideoIcon from "@/ai_modules/person/static/icons/video.svg";
import LightIcon from "@/ai_modules/person/static/icons/light.svg";
import FileIcon from "@/ai_modules/person/static/icons/file.svg";
import BookIcon from "@/ai_modules/person/static/icons/book.svg";
import AiIcon from "@/ai_modules/person/static/icons/ai.svg";
import LocationIcon from "@/ai_modules/person/static/icons/location.svg";
import ClickIcon from "@/ai_modules/person/static/icons/click.svg";
import RobotIcon from "@/ai_modules/person/static/icons/robot.svg";
import AvatarUpload from "@/ai_modules/person/components/avatar-upload/avatar-upload.vue";
import NameEdit from "@/ai_modules/person/components/keywords-edit/keywords-edit.vue";
import ConfigStatusBadge from "./components/config-status-badge.vue";
import { setFormData } from "@/utils/util";

// ─── 类型定义 ─────────────────────────────────────────────────────
enum TaskStatusEnum {
    OFFLINE = 0,
    IDLE = 1,
    WORKING = 2,
}

enum PublishModeEnum {
    AUTO = 1,
    MANUAL = 2,
}

interface PersonDetail {
    id: string;
    persona_name: string;
    persona_type: keyof typeof PersonTypeMap | undefined;
    avatar_url: string;
    publish_mode: 1 | 2;
}

interface DeviceItem {
    id: string;
    device_name: string;
    status: TaskStatusEnum;
    device_code: string;
}

// ─── 常量 ─────────────────────────────────────────────────────────
const iconMap: Record<string, string> = {
    video: VideoIcon,
    light: LightIcon,
    file: FileIcon,
    book: BookIcon,
    ai: AiIcon,
    location: LocationIcon,
    click: ClickIcon,
    robot: RobotIcon,
};

const STATUS_STYLE_MAP: Record<TaskStatusEnum, { dotColor: string; label: string }> = {
    [TaskStatusEnum.OFFLINE]: { dotColor: "#FF2442", label: "已离线" },
    [TaskStatusEnum.IDLE]: { dotColor: "#00C08E", label: "空闲" },
    [TaskStatusEnum.WORKING]: { dotColor: "#0065FB", label: "执行中" },
};

// ─── 页面状态 ─────────────────────────────────────────────────────
const loading = ref<boolean>(true);
const initialized = ref<boolean>(false);
const personId = ref<string>("");
const navColor = ref<string>("#ffffff");
const navBgColor = ref<string>("transparent");

const detail = reactive<PersonDetail>({
    id: "",
    persona_name: "",
    persona_type: undefined,
    avatar_url: "",
    publish_mode: 1,
});

const deviceList = ref<DeviceItem[]>([]);

const configStatus = ref<Record<string, any>>({
    digital_config: 0,
    material_config: 0,
    persona_agent_config: 0,
    traffic_config: 0,
    wechat_interaction_config: 0,
});

// ─── 弹窗状态 ─────────────────────────────────────────────────────
const showNameEdit = ref<boolean>(false);
const showChooseDevice = ref<boolean>(false);
const nameEditRef = shallowRef<InstanceType<typeof NameEdit>>();
const chooseDeviceRef = shallowRef();

// ─── 素材库 Tab ───────────────────────────────────────────────────
const materialTabs = ref([
    { key: PublishModeEnum.AUTO, label: "自动生成内容", desc: "素材池里的任务内容，AI自动生成" },
    { key: PublishModeEnum.MANUAL, label: "指定素材内容", desc: "手动指定素材内容进行生成" },
]);

// ─── 菜单组配置（✅ 新增 configKey 字段）────────────────────────
const menuGroups = ref([
    {
        title: "知识库与数字人",
        items: [
            {
                icon: "book",
                title: "关联知识库",
                desc: "产品图片/产品介绍等",
                tag: "",
                tagType: "primary",
                configKey: "", // 无对应 configStatus 字段，不显示角标
                path: "/ai_modules/person/pages/knowledge_config/knowledge_config",
            },
            {
                icon: "ai",
                title: "数字人设置",
                desc: "数字人形象/音色",
                tag: "",
                tagType: "",
                configKey: "digital_config",
                path: "/ai_modules/person/pages/digital_human_config/digital_human_config",
            },
        ],
    },
    {
        title: "自动化运营",
        items: [
            {
                icon: "location",
                title: "获客与截流设置",
                desc: "公域截流与主动私信话术",
                tag: "",
                tagType: "",
                configKey: "traffic_config",
                path: "/ai_modules/person/pages/traffic_config/traffic_config",
            },
            {
                icon: "click",
                title: "私域与运营设置",
                desc: "私信接管与平台自动回复",
                tag: "",
                tagType: "",
                configKey: "wechat_interaction_config",
                path: "/ai_modules/person/pages/interaction_config/interaction_config",
            },
            {
                icon: "robot",
                title: "关联智能体",
                desc: "微信自动通过/备注/朋友圈互动",
                tag: "",
                tagType: "",
                configKey: "persona_agent_config",
                path: "/ai_modules/person/pages/agent_config/agent_config",
            },
        ],
    },
]);

// ─── 工具函数 ─────────────────────────────────────────────────────
const getIconPath = (iconName: string): string => iconMap[iconName] ?? "";

const getDeviceStatusStyle = (status: TaskStatusEnum) =>
    STATUS_STYLE_MAP[status] ?? STATUS_STYLE_MAP[TaskStatusEnum.IDLE];

const withSaving = async (fn: () => Promise<void>, successMsg = "修改成功"): Promise<void> => {
    uni.showLoading({ title: "修改中...", mask: true });
    try {
        await fn();
        uni.hideLoading();
        uni.showToast({ title: successMsg, icon: "none", duration: 3000 });
    } catch {
        uni.hideLoading();
        uni.showToast({ title: "操作失败，请重试", icon: "none", duration: 3000 });
    }
};

const handleAvatarUpdate = (imageUrl: string): void => {
    withSaving(async () => {
        await updatePerson({ id: personId.value, avatar_url: imageUrl });
        detail.avatar_url = imageUrl;
    }, "头像已更新");
};

const handleEditName = async (): Promise<void> => {
    showNameEdit.value = true;
    await nextTick();
    nameEditRef.value?.setFormData(detail.persona_name);
};

const handleNameEditConfirm = (newName: string): void => {
    withSaving(async () => {
        await updatePerson({ id: personId.value, persona_name: newName });
        detail.persona_name = newName;
        showNameEdit.value = false;
    });
};

const handleEditType = (): void => {
    uni.navigateTo({ url: `/ai_modules/person/pages/create/create?mode=edit&id=${personId.value}` });
};

const handleSelectPublishMode = async (key: PublishModeEnum): Promise<void> => {
    if (detail.publish_mode === key) return;
    uni.showModal({
        title: "提示",
        content: "确定要更新素材库模式吗？",
        success: ({ confirm }) => {
            if (!confirm) return;
            withSaving(async () => {
                await updatePerson({ id: personId.value, publish_mode: key });
                detail.publish_mode = key;
            });
        },
    });
};

const handleSelectDevice = (): void => {
    showChooseDevice.value = true;
    chooseDeviceRef.value?.setDisabledLists(deviceList.value);
};

const handleChooseDeviceConfirm = async (device: any): Promise<void> => {
    uni.showLoading({ title: "绑定中...", mask: true });
    try {
        await updateDevice({ device_code: device.device_code, persona_id: personId.value });
        uni.hideLoading();
        uni.showToast({ title: "设备已绑定", icon: "none", duration: 3000 });
        getDeviceList();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const handleDeviceSetting = (device: DeviceItem): void => {
    uni.showActionSheet({
        itemList: ["设备详情", "解除绑定"],
        itemColor: "#333333",
        success: ({ tapIndex }) => {
            if (tapIndex === 0) {
                uni.navigateTo({ url: `/ai_modules/device/pages/setting/setting?device_code=${device.device_code}` });
                return;
            }
            uni.showModal({
                title: "解除绑定",
                content: `确定要解除绑定「${device.device_name}」吗？`,
                confirmColor: "#FF4D4F",
                success: async ({ confirm }) => {
                    if (!confirm) return;
                    try {
                        await removeDevicePersona({ device_code: device.device_code });
                        deviceList.value = deviceList.value.filter((d) => d.id !== device.id);
                        uni.showToast({ title: "已解绑", icon: "success" });
                    } catch (error: any) {
                        uni.showToast({ title: error, icon: "none", duration: 3000 });
                    }
                },
            });
        },
    });
};

const handleNavigate = (path: string): void => {
    if (!path) return;
    uni.$u.route({ url: path, params: { id: personId.value } });
};

const getDeviceList = async () => {
    const { devices } = await getPersonDeviceList({ persona_id: personId.value });
    deviceList.value = devices ?? [];
};

const getDetail = async () => {
    const detailResult = await getPersonDetail({ id: personId.value });
    setFormData(detailResult, detail);
};

const getConfigStatus = async () => {
    const res = await getPersonConfigStatus({ id: personId.value });
    setFormData(res, configStatus.value);
};

const init = async (): Promise<void> => {
    loading.value = true;
    try {
        await Promise.allSettled([getDetail(), getDeviceList(), getConfigStatus()]);
    } finally {
        loading.value = false;
        initialized.value = true;
    }
};

onShow(() => {
    if (!initialized.value) return;
    getDetail();
    getConfigStatus();
});

onLoad((options: any) => {
    personId.value = options.id ?? "";
    init();
});

onPageScroll(({ scrollTop }: { scrollTop: number }) => {
    navColor.value = scrollTop > 100 ? "#000000" : "#ffffff";
    navBgColor.value = scrollTop > 100 ? "#ffffff" : "transparent";
});
</script>
