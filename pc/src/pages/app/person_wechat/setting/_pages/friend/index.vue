<template>
    <div class="h-full flex flex-col gap-y-3 overflow-hidden">
        <div
            class="bg-white h-[72px] flex items-center justify-between px-8 rounded-[20px] border border-br flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary/10 text-primary mr-3">
                    <Icon name="el-icon-UserFilled" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] text-[#1E293B] font-black tracking-tight">自动同意好友申请</div>
                    <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                        Auto Friend Acceptance
                    </div>
                </div>
            </div>
            <ElButton
                type="primary"
                class="w-[180px] !h-[50px] !rounded-xl !text-[16px] !font-medium"
                :loading="isLock"
                @click="lockConfirm">
                <Icon name="el-icon-CircleCheck" />
                <span class="ml-2"> 保存生效 </span>
            </ElButton>
        </div>

        <div class="grow min-h-0 flex gap-x-3 overflow-hidden">
            <ElScrollbar class="w-full">
                <div class="flex flex-wrap lg:flex-nowrap gap-3 pb-6">
                    <div
                        class="flex-1 min-w-[450px] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                        <div
                            class="px-8 py-6 border-b border-[#F8FAFC] flex items-center justify-between bg-[#0065fb]/[0.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-[4px] h-[18px] bg-primary rounded-full"></div>
                                <span class="text-[16px] font-black text-[#1E293B]">基础执行策略</span>
                            </div>
                            <ElSwitch
                                v-model="formData.is_enable"
                                :active-value="1"
                                :inactive-value="0"
                                inline-prompt
                                active-text="服务已开启"
                                inactive-text="服务已关闭" />
                        </div>

                        <div class="p-8 space-y-7">
                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">每日添加上限</span>
                                    <span class="sub-label">单个账号每天最多自动通过的好友数量</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.accept_numbers"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">个 / 每天</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">操作间隔时间</span>
                                    <span class="sub-label">每通过一个申请后，随机等待的冷却时间</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.add_interval_time"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">分钟 / 间隔</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">执行微信号</span>
                                    <span class="sub-label">选择需要开启此项自动化策略的微信号</span>
                                </div>
                                <div class="mt-3">
                                    <ElSelect
                                        v-model="formData.wechat_ids"
                                        multiple
                                        collapse-tags
                                        collapse-tags-tooltip
                                        filterable
                                        placeholder="点击选择执行账号"
                                        class="!w-full custom-select"
                                        :show-arrow="false">
                                        <template #prefix
                                            ><Icon name="local-icon-wechat" color="var(--green-500)"
                                        /></template>
                                        <ElOption
                                            v-for="item in optionsData.wechatLists"
                                            :key="item.id"
                                            :label="item.wechat_nickname"
                                            :value="item.wechat_id" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex-1 min-w-[450px] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                        <div
                            class="px-8 py-6 border-b border-[#F8FAFC] flex items-center justify-between bg-[#0065fb]/[0.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-[4px] h-[18px] bg-emerald-500 rounded-full"></div>
                                <span class="text-[16px] font-black text-[#1E293B]">好友来源过滤</span>
                            </div>
                            <div class="text-xs text-emerald-600 font-medium bg-[#d1fae5]/50 px-3 py-1 rounded-lg">
                                智能过滤中
                            </div>
                        </div>

                        <div class="p-8 space-y-7">
                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">自动同意范围</span>
                                    <span class="sub-label">只有满足以下来源的好友申请才会被自动通过</span>
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-3">
                                    <div
                                        v-for="item in acceptSourceOptions"
                                        :key="item.id"
                                        @click="
                                            acceptSource = item.id;
                                            handleAcceptSourceChange(item.id);
                                        "
                                        :class="['source-card', acceptSource === item.id ? 'active' : '']">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-3">
                                                <div class="check-box">
                                                    <Icon v-if="acceptSource === item.id" name="el-icon-Check" />
                                                </div>
                                                <span class="text-[14px] font-medium">{{ item.label }}</span>
                                            </div>
                                            <span class="opacity-20 leading-[0]">
                                                <Icon :name="getSourceIcon(item.id)" :size="20" />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>
<script setup lang="ts">
import { autoFriendPassStrategy, autoFriendPassStrategyInfo, getWeChatLists } from "@/api/person_wechat";
import { isEqual } from "lodash-es";

const formData = reactive({
    is_enable: 1, //是否开启
    accept_numbers: 0, //每日添加数量
    add_interval_time: 0, //每添加一个间隔时间
    wechat_ids: [], //执行微信号
    accept_type: 0, //同意申请的好友类型
    accept_source: [], //命中消息来源
});

const acceptSource = ref<number>();

const { optionsData } = useDictOptions<{
    wechatLists: any[];
}>({
    wechatLists: {
        api: getWeChatLists,
        params: { page_size: 999 },
        transformData: (data: any) => data.lists,
    },
});

const acceptSourceOptions = [
    {
        id: 0,
        label: "不限",
        value: [],
    },
    {
        id: 1,
        value: ["1000003"],
        label: "通过搜索微信号添加",
    },
    {
        id: 2,
        value: ["1000008", "1000014"],
        label: "通过群聊添加",
    },
    {
        id: 3,
        value: ["1000015"],
        label: "通过搜索手机号添加",
    },
    {
        id: 4,
        value: ["1000017"],
        label: "通过名片分享添加",
    },
    {
        id: 5,
        value: ["1000030"],
        label: "通过扫一扫添加",
    },
];

const getSourceIcon = (id: number) => {
    const icons = {
        0: "el-icon-Compass",
        1: "el-icon-Search",
        2: "el-icon-ChatLineRound",
        3: "el-icon-Iphone",
        4: "el-icon-Postcard",
        5: "el-icon-FullScreen",
    };
    return icons[id as keyof typeof icons] || "el-icon-Link";
};

const handleAcceptSourceChange = (value: number) => {
    const item = acceptSourceOptions.find((item) => item.id === value);
    formData.accept_source = item?.value || [];
};

const handleConfirm = async () => {
    try {
        await autoFriendPassStrategy(formData);
        feedback.msgSuccess("保存成功");
        getRobotReplyStrategyFn();
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};
const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getRobotReplyStrategyFn = async () => {
    const data = await autoFriendPassStrategyInfo();
    setFormData(data, formData);

    const item = acceptSourceOptions.find((item) => isEqual(data.accept_source, item.value));
    if (item) {
        acceptSource.value = item.id;
        handleAcceptSourceChange(item.id);
    }
};

onMounted(() => {
    getRobotReplyStrategyFn();
});
</script>

<style scoped lang="scss">
.strategy-item {
    @apply flex flex-col;
    .label-group {
        @apply flex flex-col gap-0.5;
        .main-label {
            @apply text-[14px] font-black text-[#1E293B];
        }
        .sub-label {
            @apply text-xs text-[#94A3B8] font-medium;
        }
    }
}

.source-card {
    @apply p-4 rounded-xl border border-slate-100 bg-[#f8fafc]/50 cursor-pointer transition-all flex items-center;
    &:hover {
        @apply border-emerald-200 bg-[#ecfdf5]/30;
    }

    .check-box {
        @apply w-5 h-5 rounded-md border border-slate-200 bg-white flex items-center justify-center text-white transition-all text-xs;
    }

    &.active {
        @apply border-emerald-500 bg-emerald-50 text-emerald-700 ring-2 ring-[#10b981]/10;
        .check-box {
            @apply bg-emerald-500 border-emerald-500;
        }
    }
}
</style>
