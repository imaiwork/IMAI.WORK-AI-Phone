<template>
    <popup width="700px" title="查看回复" ref="popRef" v-loading="loading">
        <div class="flex flex-col gap-4">
            <div v-if="[AgentTypeEnum.AGENT, AgentTypeEnum.COZE_AGENT].includes(agentType)">
                <div class="text-gray-500">用户提问内容：</div>
                <div class="text-black border p-2 rounded-md mt-2">
                    {{ message }}
                </div>
            </div>
            <div>
                <div class="text-gray-500">回复内容：</div>
                <div class="mt-2">
                    <Markdown :content="replyContent" />
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getCozeAgentRecordReply } from "@/api/ai_application/agent/coze";
import popup from "@/components/popup/index.vue";
import { AgentTypeEnum } from "../lists/components/enums";

const popRef = shallowRef();
const replyContent = ref("");
const message = ref("");
const loading = ref(false);
const agentType = ref();

const open = async (row: any) => {
    popRef.value?.open();
    const { content, type, reply, message_id } = row;
    agentType.value = type;
    if (type === AgentTypeEnum.AGENT) {
        replyContent.value = reply;
        message.value = content;
    } else {
        try {
            loading.value = true;
            const data = await getCozeAgentRecordReply({
                message_id,
            });
            message.value = content;
            if (data.length > 1) {
                replyContent.value = data[data.length - 1].content;
            } else {
                replyContent.value = "暂无回复";
            }
        } finally {
            loading.value = false;
        }
    }
};

defineExpose({ open });
</script>

<style scoped lang="scss"></style>
