<template>
    <popup-bottom v-model="show" :is-disabled-touch="true" :mask-close-able="false" :z-index="zIndex">
        <template #header>
            <view
                class="header flex justify-between items-center px-6 py-5 border-[0] border-b border-solid border-[#f3f4f6]">
                <view class="tabs flex bg-[#f8fafc] rounded-xl p-1">
                    <view
                        v-for="tab in tabList"
                        :key="tab.key"
                        class="tab-item px-5 py-2.5 rounded-lg text-sm font-medium transition-all cursor-pointer"
                        :class="activeTab === tab.key ? 'bg-primary text-[#ffffff]' : 'bg-[transparent] text-[#6b7280]'"
                        @click="handleTabChange(tab.key)">
                        {{ tab.label }}
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full flex flex-col">
                <view class="p-4">
                    <view class="flex gap-2">
                        <view
                            v-for="agent in agentList"
                            :key="agent.key"
                            class="flex items-center p-2 rounded-xl text-sm font-medium transition-all cursor-pointer border active:scale-98"
                            :class="
                                selectedAgent === agent.key
                                    ? 'bg-[#1f2937] text-[#ffffff] border-[#1f2937]'
                                    : 'bg-[#f8fafc] text-[#4b5563] border-[#e5e7eb]'
                            "
                            @click="selectedAgent = agent.key">
                            <text>{{ agent.label }}</text>
                        </view>
                    </view>
                </view>
                <view v-if="selectedAgent === 1" class="px-4">
                    <view class="text-sm font-medium text-[#374151] mb-3">选择生成引擎</view>
                    <view class="flex gap-3">
                        <view
                            v-for="engine in engineList"
                            :key="engine.key"
                            class="flex-1 flex flex-col p-3 rounded-xl border border-solid cursor-pointer transition-all active:scale-98"
                            :class="
                                selectedEngine === engine.key
                                    ? 'bg-[#eff6ff] border-[#3b82f6]'
                                    : 'bg-[#f8fafc] border-[#e5e7eb]'
                            "
                            @click="handleEngineChange(engine.key)">
                            <view class="flex items-center justify-between mb-1.5">
                                <view class="flex items-center gap-1.5">
                                    <text class="text-base">{{ engine.icon }}</text>
                                    <text
                                        class="text-sm font-semibold"
                                        :class="selectedEngine === engine.key ? 'text-[#1d4ed8]' : 'text-[#1f2937]'">
                                        {{ engine.label }}
                                    </text>
                                </view>
                                <view
                                    class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                    :class="
                                        selectedEngine === engine.key
                                            ? 'border-[#3b82f6] bg-[#3b82f6]'
                                            : 'border-[#d1d5db] bg-[#ffffff]'
                                    ">
                                    <view
                                        v-if="selectedEngine === engine.key"
                                        class="w-1.5 h-1.5 rounded-full bg-[#ffffff]" />
                                </view>
                            </view>
                            <text class="text-xs text-[#6b7280] leading-relaxed">{{ engine.desc }}</text>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <z-paging v-model="robotList" ref="pagingRef" :fixed="false" @query="queryList">
                        <view class="px-6">
                            <view
                                v-for="(item, index) in robotList"
                                :key="index"
                                class="flex items-center p-4 bg-[#ffffff] border border-solid border-[#e2e8f0] rounded-2xl mb-3 transition-all shadow-sm"
                                :class="{ 'opacity-60': isAgentUnavailable(item) }"
                                style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)"
                                @click="selectItem(item)">
                                <view class="w-11 h-11 rounded-full flex items-center justify-center shadow-md">
                                    <image
                                        :src="item.logo || item.image"
                                        class="w-full h-full rounded-full"
                                        mode="aspectFill"
                                        lazy-load />
                                </view>
                                <view class="content-info flex-1 ml-3">
                                    <view class="agent-title-row">
                                        <text class="agent-title-text">
                                            {{ item.name }}
                                        </text>
                                        <text
                                            v-if="shouldRenderAgentAccessTag(item)"
                                            class="agent-access-tag"
                                            :class="getAgentAccessTagClass(item)">
                                            {{ getAgentAccessTagText(item) }}
                                        </text>
                                    </view>
                                    <view class="description text-xs text-[#6b7280] leading-relaxed line-clamp-2">
                                        {{ item.introduced }}
                                    </view>
                                </view>
                                <view class="arrow ml-3 text-[#d1d5db] text-base"> → </view>
                            </view>
                        </view>
                        <template #empty>
                            <view class="text-center py-10 px-5">
                                <text class="text-5xl mb-4 block">📝</text>
                                <view class="text-base text-[#6b7280] mb-2">暂无可用模板</view>
                            </view>
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getAgentList, getCozeAgentList, getSystemAgentList } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessStatus,
    getAgentAccessTagText as getAgentPermissionTagText,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    systemAgentIds: {
        type: Array,
        default: () => [0, 1, 3, 4, 5, 6],
    },
    isSora: {
        type: Boolean,
        default: false,
    },
    zIndex: {
        type: Number,
        default: 1000,
    },
});

const emit = defineEmits(["update:modelValue", "select", "tabChange", "agentChange", "engineChange"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const activeTab = ref("generate");
const selectedAgent = ref(1);
const selectedEngine = ref<number>(1);
const robotList = ref<any[]>([]);
const pagingRef = shallowRef();
const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const tabList = [
    { key: "generate", label: "文案生成", type: 1 },
    { key: "rewrite", label: "文案改写", type: 2 },
];

// 生成引擎列表
const engineList: any = [
    { key: 1, label: "普通版", icon: "⚡", desc: "极速出稿，适合日常铺量" },
    { key: 2, label: "高级版", icon: "✨", desc: "深度思考，爆款网感文案" },
];

const agentList = ref([
    { key: 1, label: "系统内置", api: getSystemAgentList, params: { type: tabList[0].type } },
    { key: 2, label: "智能体", api: getAgentList, params: {} },
    { key: 3, label: "coze智能体", api: getCozeAgentList, params: { type: 1 } },
]);

const shouldCheckAgentAccess = computed(() => selectedAgent.value !== 1);

const canUseCurrentAgent = (item: any) => !shouldCheckAgentAccess.value || canUseAgent(item, userInfo.value);

const isAgentUnavailable = (item: any) => !canUseCurrentAgent(item);

const shouldRenderAgentAccessTag = (item: any) => shouldCheckAgentAccess.value && shouldShowAgentAccessTag(item);

const getAgentAccessTagText = (item: any) => getAgentPermissionTagText(item, userInfo.value);

const getAgentAccessTagClass = (item: any) =>
    getAgentAccessStatus(item, userInfo.value) === "free" ? "agent-access-tag--free" : "agent-access-tag--member";

const handleEngineChange = (key: number) => {
    selectedEngine.value = key;
    emit("engineChange", key);
};

const handleTabChange = (key: string) => {
    activeTab.value = key;
    agentList.value[0].params.type = tabList.find((item) => item.key === key)?.type;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const currAgent = agentList.value.find((item) => item.key === selectedAgent.value);
        if (props.isSora && selectedAgent.value === 1) {
            delete currAgent?.params?.type;
        }
        const res = await currAgent?.api?.({
            page_no,
            page_size,
            ...currAgent?.params,
        });
        if (selectedAgent.value === 1) {
            pagingRef.value?.complete(
                res.filter((item: any) =>
                    props.systemAgentIds.length > 0 ? props.systemAgentIds.includes(item.id) : true,
                ),
            );
        } else {
            pagingRef.value?.complete(res?.lists);
        }
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

watch(
    () => props.modelValue,
    (newVal) => {
        show.value = newVal;
    },
);

watch(
    () => [selectedAgent.value, activeTab.value],
    () => {
        pagingRef.value?.reload();
    },
);

const closePopup = () => {
    show.value = false;
};

const selectItem = (item: any) => {
    if (isAgentUnavailable(item)) {
        uni.$u.toast(AGENT_UNAVAILABLE_TIP);
        return;
    }
    emit("select", {
        data: {
            agentId: item.id,
            name: item.name,
            type: activeTab.value,
            agentType: selectedAgent.value,
            engine: selectedAgent.value === 1 ? selectedEngine.value : null,
        },
    });
    closePopup();
};
</script>

<style lang="scss" scoped>
.agent-title-row {
    @apply mb-1 flex min-w-0 items-center gap-x-[8rpx];
}

.agent-title-text {
    @apply min-w-0 flex-1 line-clamp-1 text-base font-semibold leading-tight text-[#1f2937];
}

.agent-access-tag {
    @apply shrink-0 rounded-full border border-solid px-[12rpx] py-[4rpx] text-[20rpx] font-semibold leading-none;
}

.agent-access-tag--free {
    @apply border-[#BBF7D0] bg-[#F0FDF4] text-[#16A34A];
}

.agent-access-tag--member {
    @apply border-[#DDD6FE] bg-[#F5F3FF] text-[#8B5CF6];
}
</style>
