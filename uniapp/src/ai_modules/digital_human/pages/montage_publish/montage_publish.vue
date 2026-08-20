<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title="创建发布任务"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }" />

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[24rpx] pt-[16rpx] pb-[160rpx] flex flex-col gap-[16rpx]">
                    <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">基础设置</text>
                            <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                        </view>

                        <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[28rpx]">
                            <view>
                                <text class="text-xs font-semibold text-[#9CA3AF] block mb-[12rpx]">任务名称</text>
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[24rpx] h-[88rpx] flex items-center border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="formData.name"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入任务名称"
                                        maxlength="50" />
                                </view>
                            </view>

                            <view>
                                <text class="text-xs font-semibold text-[#9CA3AF] block mb-[12rpx]">发布账号</text>
                                <navigator
                                    :url="`/ai_modules/device/pages/account_choose/account_choose?account=${JSON.stringify(
                                        formData.accounts,
                                    )}`"
                                    hover-class="none">
                                    <view
                                        class="rounded-[16rpx] px-[24rpx] h-[88rpx] flex items-center justify-between border border-solid transition-all"
                                        :class="
                                            formData.accounts.length
                                                ? 'bg-[#EBF2FF] border-[#0065fb]/30'
                                                : 'bg-[#F7F9FC] border-[#E5E9F0]'
                                        ">
                                        <view class="flex items-center gap-[10rpx]">
                                            <u-icon
                                                name="account"
                                                :color="formData.accounts.length ? '#0065fb' : '#C0C4CC'"
                                                size="26" />
                                            <text
                                                class="font-semibold"
                                                :class="formData.accounts.length ? 'text-primary' : 'text-[#C0C4CC]'">
                                                {{
                                                    formData.accounts.length
                                                        ? `已选 ${formData.accounts.length} 个账号`
                                                        : "点击选择账号"
                                                }}
                                            </text>
                                        </view>
                                        <u-icon
                                            name="arrow-right"
                                            size="24"
                                            :color="formData.accounts.length ? '#0065fb' : '#C0C4CC'" />
                                    </view>
                                </navigator>
                            </view>

                            <view>
                                <text class="text-xs font-semibold text-[#9CA3AF] block mb-[16rpx]"
                                    >发布频率（每日）</text
                                >
                                <view class="flex items-center gap-[12rpx] flex-wrap">
                                    <view
                                        v-for="item in FREQUENCY_OPTIONS"
                                        :key="item"
                                        class="h-[72rpx] px-[32rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                        :class="
                                            formData.publish_frep === item
                                                ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                : 'bg-[#F0F2F5]'
                                        "
                                        @click="handleFrequency(item)">
                                        <text
                                            class="font-bold"
                                            :class="formData.publish_frep === item ? 'text-primary' : 'text-[#9CA3AF]'">
                                            {{ item }}条
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="formData.publish_frep > 0"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">发布时间</text>
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                            <view
                                class="flex items-center gap-[6rpx] bg-[#FFFBEB] rounded-full px-[16rpx] h-[48rpx] border border-solid border-[#FDE68A]">
                                <u-icon name="info-circle" color="#F59E0B" size="20" />
                                <text class="text-[20rpx] text-[#92400E] font-medium"
                                    >间隔 ≥ {{ TIME_INTERVAL_MIN }} 分钟</text
                                >
                            </view>
                        </view>

                        <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                            <view
                                v-for="(item, index) in formData.time_config"
                                :key="index"
                                class="rounded-[20rpx] overflow-hidden border border-solid"
                                :class="
                                    timeErrors[index]
                                        ? 'border-[#FECDD3] bg-[#FFF1F2]'
                                        : 'border-[#F0F2F5] bg-[#F7F9FC]'
                                ">
                                <view
                                    class="flex items-center gap-[10rpx] px-[20rpx] h-[64rpx] border-[0] border-b border-solid"
                                    :class="timeErrors[index] ? 'border-[#FECDD3]' : 'border-[#F0F2F5]'">
                                    <view
                                        class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                        :class="timeErrors[index] ? 'bg-[#FEE2E2]' : 'bg-[#EBF2FF]'">
                                        <text
                                            class="text-[20rpx] font-extrabold"
                                            :class="timeErrors[index] ? 'text-[#EF4444]' : 'text-primary'">
                                            {{ index + 1 }}
                                        </text>
                                    </view>
                                    <text
                                        class="text-xs font-semibold"
                                        :class="timeErrors[index] ? 'text-[#EF4444]' : 'text-[#374151]'">
                                        第 {{ index + 1 }} 个任务发布时间
                                    </text>
                                    <view v-if="timeErrors[index]" class="ml-auto flex items-center gap-[4rpx]">
                                        <u-icon name="info-circle-fill" color="#EF4444" size="20" />
                                        <text class="text-[20rpx] text-[#EF4444]">时间冲突</text>
                                    </view>
                                </view>

                                <view class="flex items-center px-[20rpx] py-[16rpx] gap-[12rpx]">
                                    <picker
                                        mode="time"
                                        class="flex-1"
                                        :value="item.start_time"
                                        @change="handleStartTimeChange($event, index)">
                                        <view
                                            class="h-[80rpx] rounded-[14rpx] flex items-center justify-between px-[20rpx] border border-solid"
                                            :class="
                                                timeErrors[index]?.start_time
                                                    ? 'bg-[#FEE2E2] border-[#FCA5A5]'
                                                    : item.start_time
                                                    ? 'bg-[#EBF2FF] border-[#0065fb]/30'
                                                    : 'bg-white border-[#E5E9F0]'
                                            ">
                                            <text
                                                class="font-semibold"
                                                :class="
                                                    timeErrors[index]?.start_time
                                                        ? 'text-[#EF4444]'
                                                        : item.start_time
                                                        ? 'text-primary'
                                                        : 'text-[#C0C4CC]'
                                                ">
                                                {{ item.start_time || "开始时间" }}
                                            </text>
                                            <u-icon
                                                name="clock"
                                                :color="
                                                    timeErrors[index]?.start_time
                                                        ? '#EF4444'
                                                        : item.start_time
                                                        ? '#0065fb'
                                                        : '#C0C4CC'
                                                "
                                                size="24" />
                                        </view>
                                    </picker>

                                    <view class="flex-shrink-0 flex items-center gap-[4rpx]">
                                        <view class="w-[12rpx] h-[2rpx] bg-[#D1D5DB] rounded-full" />
                                        <view class="w-[4rpx] h-[4rpx] rounded-full bg-[#D1D5DB]" />
                                        <view class="w-[12rpx] h-[2rpx] bg-[#D1D5DB] rounded-full" />
                                    </view>

                                    <picker
                                        mode="time"
                                        class="flex-1"
                                        :value="item.end_time"
                                        :disabled="!item.start_time"
                                        @change="handleEndTimeChange($event, index)"
                                        @click="handleEndTimeClick(index)">
                                        <view
                                            class="h-[80rpx] rounded-[14rpx] flex items-center justify-between px-[20rpx] border border-solid"
                                            :class="
                                                timeErrors[index]?.end_time
                                                    ? 'bg-[#FEE2E2] border-[#FCA5A5]'
                                                    : item.end_time
                                                    ? 'bg-[#EBF2FF] border-[#0065fb]/30'
                                                    : 'bg-white border-[#E5E9F0]'
                                            ">
                                            <text
                                                class="font-semibold"
                                                :class="
                                                    timeErrors[index]?.end_time
                                                        ? 'text-[#EF4444]'
                                                        : item.end_time
                                                        ? 'text-primary'
                                                        : 'text-[#C0C4CC]'
                                                ">
                                                {{ item.end_time || "结束时间" }}
                                            </text>
                                            <u-icon
                                                name="clock"
                                                :color="
                                                    timeErrors[index]?.end_time
                                                        ? '#EF4444'
                                                        : item.end_time
                                                        ? '#0065fb'
                                                        : '#C0C4CC'
                                                "
                                                size="24" />
                                        </view>
                                    </picker>
                                </view>
                            </view>

                            <view
                                v-if="Object.keys(timeErrors).length > 0"
                                class="flex items-center gap-[10rpx] bg-[#FFF1F2] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#FECDD3]">
                                <u-icon name="info-circle-fill" color="#F43F5E" size="26" />
                                <text class="text-xs text-[#F43F5E] font-medium"
                                    >时间配置存在冲突，请检查后重新设置</text
                                >
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="taskErrorMsg"
                        class="flex items-start gap-[12rpx] bg-[#FFF1F2] rounded-[20rpx] px-[24rpx] py-[20rpx] border border-solid border-[#FECDD3]">
                        <u-icon name="info-circle-fill" color="#F43F5E" size="28" class="flex-shrink-0 mt-[2rpx]" />
                        <view class="flex-1">
                            <text class="font-extrabold text-[#F43F5E] block mb-[6rpx]">任务冲突</text>
                            <text class="text-xs text-[#F43F5E] leading-relaxed">{{ taskErrorMsg }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-[24rpx] pt-[20rpx] pb-[40rpx]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] transition-all duration-200"
                :class="canCreateTask ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                :style="
                    canCreateTask
                        ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                        : 'background: #C0C4CC'
                "
                @click="createTask">
                <u-icon name="plus" color="#fff" size="24" />
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">立即创建任务</text>
            </view>
        </view>
    </view>

    <u-popup v-model="showCreate" mode="center" border-radius="48" width="90%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] overflow-hidden">
            <view
                class="h-[180rpx] flex items-center justify-center"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                <view
                    class="w-[96rpx] h-[96rpx] rounded-full bg-[#ffffff]/20 flex items-center justify-center shadow-[0_4rpx_16rpx_rgba(0,0,0,0.15)]">
                    <u-icon name="checkmark" color="#ffffff" size="40" />
                </view>
            </view>

            <view class="px-[40rpx] pt-[32rpx] pb-[40rpx] flex flex-col items-center gap-[8rpx]">
                <text class="text-[32rpx] font-extrabold text-[#0D1117]">创建成功</text>
                <text class="text-xs text-[#9CA3AF]">任务已创建，系统将按计划自动发布</text>

                <view
                    class="w-full h-[96rpx] rounded-[24rpx] flex items-center justify-center mt-[32rpx] shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleBackHome">
                    <text class="text-[30rpx] font-extrabold text-white">返回首页</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
// script 完全不变
import WechatOA from "@/utils/wechat";
import { createShanjianPublish, createSoraPublishTask, createMontagePublishTask } from "@/api/digital_human";
import { createHotWritePublishTask } from "@/api/hot_write";
import { isJson } from "@/utils/util";
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

interface TimeConfig {
    start_time: string;
    end_time: string;
}
interface AccountItem {
    account: string;
    type: number;
    id: number;
}
interface FormData {
    name: string;
    accounts: AccountItem[];
    publish_frep: number;
    video_ids: any[];
    time_config: TimeConfig[];
    media_type: number;
    data_type: number;
    task_type: number;
    scene: number | string;
}
type TimeErrors = Record<number, { start_time: boolean; end_time: boolean }>;

const TIME_INTERVAL_MIN = 30;
const FREQUENCY_OPTIONS = [1, 2, 3, 5, 10] as const;

const TASK_TYPE_API_MAP = new Map<MontageTypeEnum, (...args: any[]) => Promise<any>>([
    [MontageTypeEnum.STORYBOARD_MIX, createMontagePublishTask],
    [MontageTypeEnum.SORA_VIDEO, createSoraPublishTask],
    [MontageTypeEnum.REAL_PERSON_MIX, createShanjianPublish],
]);

const TASK_TYPE_VALUE_MAP = new Map<MontageTypeEnum, number>([
    [MontageTypeEnum.SORA_VIDEO, 4],
    [MontageTypeEnum.STORYBOARD_MIX, 5],
]);

const generateTimeConfig = (count: number): TimeConfig[] =>
    Array.from({ length: count }, (_, index) => {
        const base = new Date();
        base.setHours(9, 0, 0, 0);
        const start = new Date(base.getTime() + index * TIME_INTERVAL_MIN * 60_000);
        const end = new Date(start.getTime() + TIME_INTERVAL_MIN * 60_000);
        return {
            start_time: uni.$u.timeFormat(start, "hh:MM"),
            end_time: uni.$u.timeFormat(end, "hh:MM"),
        };
    });

const toMinutes = (t: string): number => {
    if (!t) return NaN;
    const [h, m] = t.split(":").map(Number);
    return h * 60 + m;
};

const validateSchedule = (list: TimeConfig[]): { valid: boolean; errors: TimeErrors } => {
    const errors: TimeErrors = {};
    const addError = (index: number, field: "start_time" | "end_time") => {
        if (!errors[index]) errors[index] = { start_time: false, end_time: false };
        errors[index][field] = true;
    };
    list.forEach((cur, i) => {
        if (!cur.start_time) addError(i, "start_time");
        if (!cur.end_time) addError(i, "end_time");
        const s = toMinutes(cur.start_time);
        const e = toMinutes(cur.end_time);
        if (isNaN(s) || isNaN(e)) return;
        if (s >= e) {
            addError(i, "start_time");
            addError(i, "end_time");
        }
        if (i > 0) {
            const prevE = toMinutes(list[i - 1].end_time);
            if (!isNaN(prevE) && s < prevE) {
                addError(i - 1, "end_time");
                addError(i, "start_time");
            }
        }
    });
    return { valid: Object.keys(errors).length === 0, errors };
};

const resolveCreateApi = (type: MontageTypeEnum, src: string) => {
    if (src === "hot_write") return createHotWritePublishTask;
    return TASK_TYPE_API_MAP.get(type) ?? createShanjianPublish;
};

const { on } = useEventBusManager();
const source = ref<string>("");
const taskType = ref<MontageTypeEnum>(MontageTypeEnum.REAL_PERSON_MIX);

const formData = reactive<FormData>({
    name: `混剪自动发布任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    accounts: [],
    publish_frep: 2,
    video_ids: [],
    time_config: generateTimeConfig(2),
    media_type: 1,
    data_type: 0,
    task_type: 2,
    scene: "",
});

const timeErrors = ref<TimeErrors>({});
const taskErrorMsg = ref<string>("");
const showCreate = ref<boolean>(false);

const canCreateTask = computed<boolean>(
    () => !!formData.name.trim() && formData.accounts.length > 0 && Object.keys(timeErrors.value).length === 0,
);

const handleFrequency = (item: number): void => {
    if (item === formData.publish_frep) return;
    formData.publish_frep = item;
    formData.time_config = generateTimeConfig(item);
    timeErrors.value = {};
};

const handleEndTimeClick = (index: number): void => {
    if (!formData.time_config[index].start_time) uni.$u.toast("请先选择开始时间");
};

const handleStartTimeChange = (e: any, index: number): void => {
    const value = e.detail.value as string;
    const data = formData.time_config[index];
    data.start_time = value;
    const end = new Date(`2000/01/01 ${value}`);
    end.setMinutes(end.getMinutes() + TIME_INTERVAL_MIN);
    data.end_time = uni.$u.timeFormat(end, "hh:MM");
    timeErrors.value = validateSchedule(formData.time_config).errors;
};

const handleEndTimeChange = (e: any, index: number): void => {
    const value = e.detail.value as string;
    const data = formData.time_config[index];
    if (value <= data.start_time) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const diffMs = new Date(`2000/01/01 ${value}`).getTime() - new Date(`2000/01/01 ${data.start_time}`).getTime();
    if (diffMs < TIME_INTERVAL_MIN * 60_000) {
        uni.$u.toast(`结束时间与开始时间间隔不能小于 ${TIME_INTERVAL_MIN} 分钟`);
        return;
    }
    data.end_time = value;
    timeErrors.value = validateSchedule(formData.time_config).errors;
};

const createTask = async (): Promise<void> => {
    if (!formData.name.trim()) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    if (formData.accounts.length === 0) {
        uni.$u.toast("请选择发布账号");
        return;
    }
    if (!validateSchedule(formData.time_config).valid) {
        uni.$u.toast("时间配置存在冲突，请检查");
        return;
    }
    uni.showLoading({ title: "创建中...", mask: true });
    taskErrorMsg.value = "";
    try {
        const createApi = resolveCreateApi(taskType.value, source.value);
        const task_type = TASK_TYPE_VALUE_MAP.get(taskType.value) ?? formData.task_type;
        await createApi({
            ...formData,
            task_type,
            time_config: formData.time_config.map(({ start_time, end_time }) => `${start_time}-${end_time}`),
        });
        showCreate.value = true;
        WechatOA.notify();
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "创建失败";
        taskErrorMsg.value = msg;
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

const handleBackHome = (): void => {
    uni.$u.route({ url: "/ai_modules/digital_human/pages/index/index", type: "reLaunch" });
};

onLoad((options: any) => {
    source.value = options.source ?? "";
    formData.scene = options.scene ?? "";
    formData.video_ids = isJson(options.task_id) ? JSON.parse(options.task_id) : options.task_id;
    taskType.value = parseInt(options.type) as MontageTypeEnum;
    on("confirm", (result: any) => {
        const { type, data } = result;
        if (type !== ListenerTypeEnum.CHOOSE_ACCOUNT || !data?.length) return;
        formData.accounts = data.map((item: any) => ({
            account: item.account,
            type: item.type,
            id: item.id,
        }));
    });
});
</script>

<style scoped></style>
