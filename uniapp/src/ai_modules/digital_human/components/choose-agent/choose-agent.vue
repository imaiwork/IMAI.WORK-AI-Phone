<template>
    <popup-bottom v-model="show" :is-disabled-touch="true" :mask-close-able="false">
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

                <view class="grow min-h-0">
                    <z-paging v-model="robotList" ref="pagingRef" :fixed="false" @query="queryList">
                        <view class="px-6">
                            <view
                                v-for="(item, index) in robotList"
                                :key="index"
                                class="flex items-center p-4 bg-[#ffffff] border border-solid border-[#e2e8f0] rounded-2xl mb-3 transition-all shadow-sm"
                                style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)"
                                @click="selectItem(item)">
                                <view class="w-11 h-11 rounded-full flex items-center justify-center shadow-md">
                                    <image
                                        :src="item.logo || item.avatar"
                                        class="w-full h-full rounded-full"
                                        mode="aspectFill"
                                        lazy-load />
                                </view>
                                <view class="content-info flex-1 ml-3">
                                    <view class="title text-base font-semibold text-[#1f2937] mb-1 leading-tight">
                                        {{ item.name }}
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
import config from "@/config";
import { getAgentList, getCozeAgentList, getSystemAgentList } from "@/api/agent";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    // 系统智能体显示的id
    systemAgentIds: {
        type: Array,
        default: () => [0, 1, 3, 4, 5, 6],
    },
    isSora: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "select", "tabChange", "agentChange"]);

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
const robotList = ref<any[]>([]);

const pagingRef = shallowRef();
const tabList = [
    { key: "generate", label: "文案生成", type: 1 },
    { key: "rewrite", label: "文案改写", type: 2 },
];

const agentList = ref([
    { key: 1, label: "系统内置", api: getSystemAgentList, params: { type: tabList[0].type } },
    { key: 2, label: "智能体", api: getAgentList, params: {} },
    { key: 3, label: "coze智能体", api: getCozeAgentList, params: { type: 1 } },
]);

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
                    props.systemAgentIds.length > 0 ? props.systemAgentIds.includes(item.id) : true
                )
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
    }
);

watch(
    () => [selectedAgent.value, activeTab.value],
    (newVal) => {
        pagingRef.value?.reload();
    }
);

const closePopup = () => {
    show.value = false;
};

const selectItem = (item: any) => {
    emit("select", {
        data: {
            agentId: item.id,
            name: item.name,
            type: activeTab.value,
            agentType: selectedAgent.value,
        },
    });
    closePopup();
};
</script>
