<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[200rpx]" v-if="!loading">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="关联智能体" title-bold>
        </u-navbar>

        <view class="px-[30rpx] pt-2">
            <view class="flex items-center gap-2 mb-5 px-2">
                <u-icon name="account-fill" color="#9CA3AF" size="28"></u-icon>
                <text class="text-[#999999]">当前IP：</text>
                <text class="font-bold text-primary">{{ detail.persona_name }}</text>
            </view>

            <view class="flex flex-col gap-4">
                <view
                    v-for="item in configList"
                    :key="item.id"
                    class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                    <view class="flex items-center gap-3">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                            :class="item.iconBg">
                            <image v-if="item.customIcon" :src="item.customIcon" class="w-[36rpx] h-[36rpx]"> </image>
                            <u-icon v-else :name="item.icon" :color="item.iconColor" size="36"> </u-icon>
                        </view>
                        <view class="flex flex-col justify-center">
                            <text class="text-[30rpx] font-extrabold text-[#1A1A1A]">{{ item.title }}</text>
                            <text class="text-xs text-[#999999] mt-1">{{ item.desc }}</text>
                        </view>
                    </view>
                    <view class="flex items-center justify-between mt-6">
                        <view class="flex items-center gap-2">
                            <text class="text-[#666666]">关联智能体:</text>
                            <view v-if="item.agentId" class="bg-[#F0F5FF] px-3.5 py-1 rounded-full">
                                <text class="text-xs font-bold text-primary">{{ item.agentName }}</text>
                            </view>
                            <text v-else class="text-xs text-[#999999]">未关联</text>
                        </view>
                        <view class="flex items-center text-[#999999]" @click="openChooseAgent(item)">
                            <text class="text-xs mr-0.5">更换</text>
                            <u-icon name="arrow-right" size="22"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] z-50">
            <u-button
                type="primary"
                shape="circle"
                :ripple="true"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                }"
                @click="handleSaveConfig">
                保存配置
            </u-button>
            <view class="text-center mt-2.5">
                <text class="text-[22rpx] text-[#b4b4b4]">配置自动同步至关联设备</text>
            </view>
        </view>

        <choose-agent v-model="showChooseAgent" ref="chooseAgentRef" @confirm="handleChangeAgent" />
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, getAgentDetail, updateAgent } from "@/api/person";
import ChooseAgent from "@/ai_modules/person/components/choose-agent/choose-agent.vue";

// ─── 类型定义 ─────────────────────────────────────────────────────

interface PersonDetail {
    id: string;
    persona_name: string;
    [key: string]: any;
}

interface ConfigItem {
    /** 唯一标识，用于 v-for key */
    id: string;
    title: string;
    desc: string;
    icon: string;
    customIcon?: string;
    iconBg: string;
    iconColor: string;
    /** 接口中 agent id 对应的字段名，用于动态构造保存参数 */
    agentIdField: string;
    /** 接口中 agent name 对应的字段名 */
    agentNameField: string;
    agentId: string;
    agentName: string;
}

interface AgentOption {
    id: string;
    name: string;
}

// ─── 页面状态 ─────────────────────────────────────────────────────

const personId = ref<string>("");
// 初始值为 true，防止数据未就绪时渲染空页面
const loading = ref<boolean>(true);

const detail = ref<PersonDetail>({
    id: "",
    persona_name: "",
});
const agentDetail = ref<any>({});

const configList = ref<ConfigItem[]>([
    {
        id: "social_comment",
        title: "社媒评论区接管",
        desc: "抖音/小红书/快手评论区",
        icon: "play-circle-fill",
        iconBg: "bg-[#FFF0F0]",
        iconColor: "#FF4D4F",
        agentIdField: "comment_agent_id",
        agentNameField: "comment_agent_name",
        agentId: "",
        agentName: "",
    },
    {
        id: "social_dm",
        title: "社媒私信接管",
        desc: "抖音/小红书/快手私信",
        icon: "chat-fill",
        iconBg: "bg-[#FFF5F0]",
        iconColor: "#FF8C00",
        agentIdField: "dm_agent_id",
        agentNameField: "dm_agent_name",
        agentId: "",
        agentName: "",
    },
    {
        id: "wechat_1v1",
        title: "微信1V1私聊接管",
        desc: "个微自动回复聊天",
        icon: "weixin-fill",
        iconBg: "bg-[#E6F8F3]",
        iconColor: "#00C08E",
        agentIdField: "wechat_chat_agent_id",
        agentNameField: "wechat_chat_agent_name",
        agentId: "",
        agentName: "",
    },
    {
        id: "moments_interact",
        title: "朋友圈互动接管",
        desc: "朋友圈自动点赞/评论",
        icon: "moments",
        iconBg: "bg-[#E6F0FF]",
        iconColor: "#0065FB",
        agentIdField: "moments_agent_id",
        agentNameField: "moments_agent_name",
        agentId: "",
        agentName: "",
    },
]);

// ─── 弹窗状态 ─────────────────────────────────────────────────────

/** 当前正在操作的配置项，直接持有 configList 中的引用 */
const currentItem = ref<ConfigItem | null>(null);
/** 传给弹窗的当前已选 agentId，用于回显 */
const chooseAgentRef = shallowRef<InstanceType<typeof ChooseAgent>>();
const showChooseAgent = ref<boolean>(false);

const openChooseAgent = (item: ConfigItem): void => {
    currentItem.value = item;
    showChooseAgent.value = true;
    chooseAgentRef.value?.setChooseLists([{ id: item.agentId }]);
};

// ─── 更换智能体 ───────────────────────────────────────────────────
/**
 * currentItem 持有 configList 元素的引用，直接赋值即可同步到列表
 * 无需再遍历 configList 做二次查找
 */
const handleChangeAgent = (agent: AgentOption): void => {
    if (!currentItem.value) return;
    currentItem.value.agentId = agent.id;
    currentItem.value.agentName = agent.name;
};

// ─── 保存配置 ─────────────────────────────────────────────────────

const handleSaveConfig = async (): Promise<void> => {
    try {
        uni.showLoading({ title: "保存中...", mask: true });

        // 用 reduce 构造参数，消除 @ts-ignore
        const agentParams = configList.value.reduce<Record<string, string>>((acc, item) => {
            acc[item.agentIdField] = item.agentId;
            return acc;
        }, {});

        await updateAgent({
            id: agentDetail.value.id,
            persona_id: personId.value,
            ...agentParams,
        });

        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch {
        uni.showToast({ title: "保存失败，请重试", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

// ─── 数据获取 ─────────────────────────────────────────────────────

const getDetail = async (): Promise<void> => {
    try {
        // 两个接口无依赖关系，并行请求
        const [personRes, agentRes] = await Promise.all([
            getPersonDetail({ id: personId.value }),
            getAgentDetail({ persona_id: personId.value }),
        ]);

        detail.value = personRes;
        agentDetail.value = agentRes;
        configList.value.forEach((item) => {
            item.agentId = agentRes[item.agentIdField] ?? "";
            item.agentName = agentRes[item.agentNameField] ?? "";
        });
    } finally {
        loading.value = false;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad((options: any) => {
    personId.value = options.id ?? "";
    getDetail();
});
</script>

<style scoped>
button::after {
    border: none;
}
</style>
