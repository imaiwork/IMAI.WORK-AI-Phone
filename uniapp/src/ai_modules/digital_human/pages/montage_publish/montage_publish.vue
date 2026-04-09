<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            title="创建发布任务"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }" />

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
                    <view>
                        <view class="flex items-center gap-x-1">
                            <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            <text class="font-medium">基础设置</text>
                        </view>
                        <view
                            class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                            <view>
                                <view class="text-[#7C7E80]">任务名称</view>
                                <view class="mt-[12rpx]">
                                    <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                        <u-input
                                            v-model="formData.name"
                                            placeholder-style="font-size: 24rpx;"
                                            placeholder="请输入任务名称"
                                            maxlength="50" />
                                    </view>
                                </view>
                            </view>

                            <view class="mt-[28rpx]">
                                <view class="text-[#7C7E80]">发布账号选择</view>
                                <view class="mt-[12rpx]">
                                    <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                        <navigator
                                            :url="`/ai_modules/device/pages/account_choose/account_choose?account=${JSON.stringify(
                                                formData.accounts
                                            )}`"
                                            class="flex items-center justify-between h-[70rpx]"
                                            hover-class="none">
                                            <text
                                                :class="
                                                    formData.accounts.length ? 'text-[#00B862]' : 'text-[#00000033]'
                                                ">
                                                {{
                                                    formData.accounts.length
                                                        ? `${formData.accounts.length}个账号`
                                                        : "选择账号"
                                                }}
                                            </text>
                                            <u-icon name="arrow-right" size="24" color="#00000033" />
                                        </navigator>
                                    </view>
                                </view>
                            </view>

                            <view class="mt-[28rpx]">
                                <view class="text-[#7C7E80]">发布频率（每日）</view>
                                <view class="mt-[28rpx] flex items-center gap-x-[36rpx]">
                                    <view
                                        v-for="item in FREQUENCY_OPTIONS"
                                        :key="item"
                                        class="prompt-length-item"
                                        :class="{ active: formData.publish_frep === item }"
                                        @click="handleFrequency(item)">
                                        {{ item }}条
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="mt-[32rpx]" v-if="formData.publish_frep > 0">
                        <view class="flex items-center gap-x-1">
                            <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            <text class="font-medium">发布时间</text>
                        </view>
                        <view class="mb-[28rpx]">
                            <u-notice-bar
                                mode="vertical"
                                padding="20rpx 0"
                                border-radius="8"
                                font-size="24rpx"
                                :autoplay="false"
                                :volume-icon="false"
                                :list="[`发布的间隔时间必须大于${TIME_INTERVAL_MIN}分钟`]" />
                        </view>
                        <view
                            class="mt-4 rounded-[16rpx] px-4 py-[28rpx] bg-white shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                            <view class="flex flex-col gap-y-[28rpx]">
                                <view v-for="(item, index) in formData.time_config" :key="index">
                                    <view class="text-[#7C7E80]">每天第{{ index + 1 }}个任务发布时间</view>
                                    <view class="mt-[12rpx] flex items-center gap-x-4">
                                        <!-- 开始时间 -->
                                        <view
                                            class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="item.start_time"
                                                @change="handleStartTimeChange($event, index)">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            timeErrors[index]?.start_time
                                                                ? 'text-[#FF3C26] font-medium'
                                                                : item.start_time
                                                                ? 'text-[#00B862] font-medium'
                                                                : 'text-[#00000033]',
                                                        ]">
                                                        {{ item.start_time || "开始时间" }}
                                                    </text>
                                                    <u-icon name="arrow-right" size="24" color="#00000033" />
                                                </view>
                                            </picker>
                                        </view>

                                        <view class="text-[#7C7E80]">至</view>

                                        <!-- 结束时间 -->
                                        <view
                                            class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="item.end_time"
                                                :disabled="!item.start_time"
                                                @change="handleEndTimeChange($event, index)"
                                                @click="handleEndTimeClick(index)">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            timeErrors[index]?.end_time
                                                                ? 'text-[#FF3C26] font-medium'
                                                                : item.end_time
                                                                ? 'text-[#00B862] font-medium'
                                                                : 'text-[#00000033]',
                                                        ]">
                                                        {{ item.end_time || "结束时间" }}
                                                    </text>
                                                    <u-icon name="arrow-right" size="24" color="#00000033" />
                                                </view>
                                            </picker>
                                        </view>
                                    </view>
                                </view>

                                <!-- 时间冲突提示 -->
                                <view v-if="Object.keys(timeErrors).length > 0" class="mt-2 text-[#FF3C26] text-xs">
                                    时间配置存在冲突，请检查
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-if="taskErrorMsg" class="mt-5">
                        <view class="font-medium">任务冲突</view>
                        <view class="text-[#FF2442] mt-[20rpx] text-xs">{{ taskErrorMsg }}</view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="flex-shrink-0 pb-5 pt-2">
            <view class="flex items-center justify-between px-4 gap-[48rpx]">
                <view
                    class="flex-1 flex items-center justify-center text-white rounded-[20rpx] h-[100rpx] font-medium"
                    :class="canCreateTask ? 'bg-black' : 'bg-[#787878CC]'"
                    @click="createTask">
                    立即创建任务
                </view>
            </view>
        </view>
    </view>

    <u-popup v-model="showCreate" mode="center" border-radius="48" width="90%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] p-[28rpx]">
            <view class="rounded-full w-[80rpx] h-[80rpx] mx-auto flex items-center justify-center bg-black mt-[40rpx]">
                <u-icon name="checkmark" color="#ffffff" size="28" />
            </view>
            <view class="mt-[28rpx] text-center">创建成功</view>
            <view
                class="w-full h-[100rpx] text-white flex items-center justify-center rounded-[50rpx] bg-black mt-[66rpx] shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)]"
                @click="handleBackHome">
                返回
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { createShanjianPublish, createSoraPublishTask, createMontagePublishTask } from "@/api/digital_human";
import { createHotWritePublishTask } from "@/api/hot_write";
import { isJson } from "@/utils/util";
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

// ─── 类型定义 ─────────────────────────────────────────────────────

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
}

type TimeErrors = Record<number, { start_time: boolean; end_time: boolean }>;

// ─── 常量 ─────────────────────────────────────────────────────────

const TIME_INTERVAL_MIN = 30;
const FREQUENCY_OPTIONS = [1, 2, 3, 5, 10] as const;

/**
 * 根据任务类型选择对应创建 API
 * 新增类型只需在此 Map 加一行，无需修改业务逻辑
 */
const TASK_TYPE_API_MAP = new Map<MontageTypeEnum, (...args: any[]) => Promise<any>>([
    [MontageTypeEnum.STORYBOARD_MIX, createMontagePublishTask],
    [MontageTypeEnum.SORA_VIDEO, createSoraPublishTask],
    [MontageTypeEnum.REAL_PERSON_MIX, createShanjianPublish],
]);

/**
 * 提交时 task_type 字段的值映射
 * 不在 formData 内副作用修改，提交时动态计算
 */
const TASK_TYPE_VALUE_MAP = new Map<MontageTypeEnum, number>([
    [MontageTypeEnum.SORA_VIDEO, 4],
    [MontageTypeEnum.STORYBOARD_MIX, 5],
]);

// ─── 工具函数 ─────────────────────────────────────────────────────

/**
 * 生成默认时间配置
 * 以早上 9 点为基准，每段间隔 TIME_INTERVAL_MIN 分钟
 */
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

/** 时间字符串 "HH:MM" → 分钟数 */
const toMinutes = (t: string): number => {
    if (!t) return NaN;
    const [h, m] = t.split(":").map(Number);
    return h * 60 + m;
};

/** 校验时间配置列表，返回错误索引 Map */
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

        // 开始时间 >= 结束时间
        if (s >= e) {
            addError(i, "start_time");
            addError(i, "end_time");
        }

        // 与上一段时间重叠
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

/**
 * 根据任务类型 + 来源选择创建 API
 *
 * 优先级：
 *   1. 来源为 hot_write → createHotWritePublishTask（覆盖所有类型）
 *   2. 按 taskType 从 Map 中取对应 API
 *   3. Map 无匹配 → 兜底 createShanjianPublish
 */
const resolveCreateApi = (type: MontageTypeEnum, src: string): ((...args: any[]) => Promise<any>) => {
    if (src === "hot_write") return createHotWritePublishTask;
    return TASK_TYPE_API_MAP.get(type) ?? createShanjianPublish;
};

// ─── 页面状态 ─────────────────────────────────────────────────────

const { on } = useEventBusManager();

const source = ref<string>("");
const taskType = ref<MontageTypeEnum>(MontageTypeEnum.REAL_PERSON_MIX);

const formData = reactive<FormData>({
    name: `混剪自动发布任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    accounts: [],
    publish_frep: 2,
    video_ids: [],
    time_config: generateTimeConfig(2), // 与 publish_frep 初始值保持一致
    media_type: 1,
    data_type: 0,
    task_type: 2,
});

const timeErrors = ref<TimeErrors>({});
const taskErrorMsg = ref<string>("");
const showCreate = ref<boolean>(false);

// ─── 计算属性 ─────────────────────────────────────────────────────

/**
 * 统一的可提交判断
 * 模板禁用态 和 createTask 内部校验 共用同一份逻辑
 */
const canCreateTask = computed<boolean>(
    () => !!formData.name.trim() && formData.accounts.length > 0 && Object.keys(timeErrors.value).length === 0
);

// ─── 频率 & 时间配置 ──────────────────────────────────────────────

const handleFrequency = (item: number): void => {
    if (item === formData.publish_frep) return;
    formData.publish_frep = item;
    formData.time_config = generateTimeConfig(item);
    timeErrors.value = {};
};

const handleEndTimeClick = (index: number): void => {
    if (!formData.time_config[index].start_time) {
        uni.$u.toast("请先选择开始时间");
    }
};

const handleStartTimeChange = (e: any, index: number): void => {
    const value = e.detail.value as string;
    const data = formData.time_config[index];
    data.start_time = value;
    // 自动推算结束时间 = 开始 + TIME_INTERVAL_MIN 分钟
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

// ─── 创建任务 ─────────────────────────────────────────────────────

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

        // task_type 提交时动态计算，不污染 formData 原始状态
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

// ─── 返回首页 ─────────────────────────────────────────────────────

const handleBackHome = (): void => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/index/index",
        type: "reLaunch",
    });
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad((options: any) => {
    source.value = options.source ?? "";
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

<style scoped lang="scss">
.prompt-length-item {
    @apply flex items-center justify-center bg-[#F7F8FC] w-[114rpx] h-[72rpx] text-[#7C7E80] relative rounded-[16rpx];
    &.active {
        @apply font-medium text-black;
        &::before {
            @apply absolute top-[-4rpx] left-[-4rpx] w-[100%] h-[100%] p-[4rpx] rounded-[16rpx] content-[''];
            background: conic-gradient(#47d59f, #37cced);
            -webkit-mask: linear-gradient(#47d59f 0 100%) content-box, linear-gradient(#37cced 0 100%);
            -webkit-mask-composite: xor;
        }
    }
}
</style>
