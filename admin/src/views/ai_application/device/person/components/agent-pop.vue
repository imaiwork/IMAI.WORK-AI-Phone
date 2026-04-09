<template>
    <popup
        ref="popupRef"
        title="关联智能体"
        :async="true"
        width="660px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <div class="flex flex-col gap-3">
                <div
                    v-for="item in configList"
                    :key="item.id"
                    class="rounded-2xl p-4 shadow-sm"
                    style="background: #ffffff; border: 1px solid #f3f4f6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-lg"
                            :style="{ background: item.iconBg }">
                            <Icon :name="item.icon" :color="item.iconColor" />
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="text-sm font-extrabold" style="color: #1a1a1a">{{ item.title }}</span>
                            <span class="text-xs mt-0.5" style="color: #999999">{{ item.desc }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs flex-shrink-0" style="color: #666666">关联智能体：</span>
                        <el-select
                            v-model="item.agentId"
                            placeholder="请选择智能体"
                            clearable
                            filterable
                            class="flex-1"
                            @change="(val: any) => handleAgentChange(item, val)">
                            <el-option
                                v-for="agent in agentOptions"
                                :key="agent.id"
                                :label="agent.name"
                                :value="agent.id" />
                        </el-select>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getPersonAgent, updatePersonAgent } from "@/api/ai_application/device/person";
import { getAgentLists } from "@/api/ai_application/agent";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

// ─── 类型定义 ─────────────────────────────────────────────────────
interface PersonDetail {
    id: string;
    persona_name: string;
    [key: string]: any;
}

interface ConfigItem {
    id: string;
    title: string;
    desc: string;
    icon: any;
    iconBg: string;
    iconColor: string;
    agentIdField: string;
    agentNameField: string;
    agentId: string;
    agentName: string;
}

interface AgentOption {
    id: string;
    name: string;
}

// ─── 状态 ─────────────────────────────────────────────────────────
const loading = ref(false);
const saving = ref(false);
const personId = ref("");
const detail = ref<PersonDetail>({ id: "", persona_name: "" });
const agentDetail = ref<any>({});
const agentOptions = ref<AgentOption[]>([]);

const configList = ref<ConfigItem[]>([
    {
        id: "social_comment",
        title: "社媒评论区接管",
        desc: "抖音/小红书/快手评论区",
        icon: "el-icon-VideoPlay",
        iconBg: "#FFF0F0",
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
        icon: "el-icon-ChatDotRound",
        iconBg: "#FFF5F0",
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
        icon: "el-icon-ChatLineRound",
        iconBg: "#E6F8F3",
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
        icon: "el-icon-Connection",
        iconBg: "#E6F0FF",
        iconColor: "#0065FB",
        agentIdField: "moments_agent_id",
        agentNameField: "moments_agent_name",
        agentId: "",
        agentName: "",
    },
]);

// ─── 下拉选择变更 ─────────────────────────────────────────────────
const handleAgentChange = (item: ConfigItem, val: string) => {
    const matched = agentOptions.value.find((a) => a.id === val);
    item.agentName = matched?.name ?? "";
};

const checkConfig = (): boolean => {
    const errors = configList.value.filter((item) => item.agentId === "");
    if (errors.length > 0) {
        feedback.msgWarning(`请选择智能体：${errors.map((item) => item.title).join(",")}`);
        return false;
    }
    return true;
};
// ─── 保存 ────────────────────────────────────────────────────────
const handleSave = async () => {
    if (!checkConfig()) return;
    const agentParams = configList.value.reduce<Record<string, string>>((acc, item) => {
        acc[item.agentIdField] = item.agentId;
        return acc;
    }, {});
    await updatePersonAgent({
        id: agentDetail.value.id,
        persona_id: personId.value,
        ...agentParams,
    });
    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);

const close = () => {
    emit("close");
};

// ─── 初始化 ──────────────────────────────────────────────────────
const getDetail = async () => {
    loading.value = true;
    try {
        const [agentRes, agentListRes] = await Promise.all([
            getPersonAgent({ persona_id: personId.value }),
            getAgentLists({ source: 0 }),
        ]);
        agentDetail.value = agentRes;
        agentOptions.value = agentListRes.lists ?? [];
        configList.value.forEach((item) => {
            item.agentId = agentRes[item.agentIdField] || "";
            item.agentName = agentRes[item.agentNameField] || "";
        });
    } finally {
        loading.value = false;
    }
};

// ─── 对外暴露 ────────────────────────────────────────────────────
const open = (id: string) => {
    personId.value = id;
    getDetail();
    popupRef.value?.open();
};

defineExpose({ open });
</script>

<style scoped></style>
