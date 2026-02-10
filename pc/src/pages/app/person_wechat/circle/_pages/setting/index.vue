<template>
    <div class="h-full flex flex-col gap-y-3 overflow-hidden">
        <div
            class="bg-white h-[72px] flex items-center justify-between px-8 rounded-[20px] border border-br flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary/10 text-primary mr-3">
                    <Icon name="el-icon-Setting" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] text-[#1E293B] font-black tracking-tight">朋友圈自动化策略</div>
                    <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                        Automation Settings
                    </div>
                </div>
            </div>
            <ElButton
                type="primary"
                class="!w-[180px] !h-[50px] !rounded-xl !text-[16px] !font-medium"
                :loading="isLock"
                @click="lockConfirm">
                保存策略配置
            </ElButton>
        </div>

        <div class="grow min-h-0 flex gap-x-3 overflow-hidden">
            <ElScrollbar class="w-full">
                <div class="flex flex-wrap lg:flex-nowrap gap-3 pb-6">
                    <div
                        class="flex-1 min-w-[450px] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                        <div
                            class="px-8 py-6 border-b border-[#F8FAFC] flex items-center justify-between bg-primary/[0.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-[4px] h-[18px] bg-primary rounded-full"></div>
                                <span class="text-[16px] font-black text-[#1E293B]">自动评论策略</span>
                            </div>
                            <ElSwitch
                                v-model="formData.is_enable_reply"
                                :active-value="1"
                                :inactive-value="0"
                                inline-prompt
                                active-text="已开启"
                                inactive-text="已关闭" />
                        </div>

                        <div class="p-8 space-y-7">
                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">响应延迟时间</span>
                                    <span class="sub-label">朋友发布动态后，间隔多久触发评论</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.reply_interval_time"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">分钟后执行</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">每日频率上限</span>
                                    <span class="sub-label">单个好友每天最多自动评论的条数</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.reply_numbers"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">条 / 天</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">接管评论智能体</span>
                                    <span class="sub-label">选择负责回复内容的 AI 机器人</span>
                                </div>
                                <div class="mt-3">
                                    <ElSelect
                                        v-model="formData.reply_robot_id"
                                        filterable
                                        clearable
                                        remote
                                        :show-arrow="false"
                                        :loading="agentLoading"
                                        :remote-method="getAgentFn"
                                        placeholder="请选择 AI 智能体"
                                        class="!w-full custom-select">
                                        <template #prefix
                                            ><Icon name="local-icon-robot" color="var(--color-primary)"
                                        /></template>
                                        <ElOption
                                            v-for="item in agentLists"
                                            :key="item.id"
                                            :label="item.name"
                                            :value="item.id" />
                                    </ElSelect>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">作用好友标签</span>
                                    <span class="sub-label">仅对拥有以下标签的好友生效（留空为全部）</span>
                                </div>
                                <div class="mt-3">
                                    <ElSelect
                                        v-model="formData.reply_tag_ids"
                                        filterable
                                        multiple
                                        collapse-tags
                                        collapse-tags-tooltip
                                        placeholder="选择好友标签范围"
                                        :show-arrow="false"
                                        class="!w-full custom-select">
                                        <ElOption
                                            v-for="item in tagLists"
                                            :key="item.id"
                                            :label="item.tag_name"
                                            :value="item.tag_id" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex-1 min-w-[450px] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                        <div
                            class="px-8 py-6 border-b border-[#F8FAFC] flex items-center justify-between bg-orange-50/30">
                            <div class="flex items-center gap-3">
                                <div class="w-[4px] h-[18px] bg-orange-500 rounded-full"></div>
                                <span class="text-[16px] font-black text-[#1E293B]">自动点赞策略</span>
                            </div>
                            <ElSwitch
                                v-model="formData.is_enable_like"
                                :active-value="1"
                                :inactive-value="0"
                                inline-prompt
                                active-text="已开启"
                                inactive-text="已关闭"
                                style="--el-switch-on-color: #f97316" />
                        </div>

                        <div class="p-8 space-y-7">
                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">响应延迟时间</span>
                                    <span class="sub-label">朋友发布动态后，间隔多久触发点赞</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.like_interval_time"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number orange" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">分钟后执行</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">每日频率上限</span>
                                    <span class="sub-label">单个好友每天最多自动点赞的条数</span>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <ElInputNumber
                                        v-model="formData.like_numbers"
                                        :min="0"
                                        controls-position="right"
                                        class="custom-number orange" />
                                    <span class="text-[13px] font-medium text-tx-placeholder">条 / 天</span>
                                </div>
                            </div>

                            <div class="strategy-item">
                                <div class="label-group">
                                    <span class="main-label">作用好友标签</span>
                                    <span class="sub-label">仅对拥有以下标签的好友生效</span>
                                </div>
                                <div class="mt-3">
                                    <ElSelect
                                        v-model="formData.like_tag_ids"
                                        filterable
                                        multiple
                                        collapse-tags
                                        collapse-tags-tooltip
                                        placeholder="选择好友标签范围"
                                        :show-arrow="false"
                                        class="!w-full custom-select">
                                        <ElOption
                                            v-for="item in tagLists"
                                            :key="item.id"
                                            :label="item.tag_name"
                                            :value="item.tag_id" />
                                    </ElSelect>
                                </div>
                            </div>

                            <div class="strategy-item opacity-0 select-none">
                                <div class="label-group"><span class="main-label">Placeholder</span></div>
                                <div class="mt-3 h-[40px]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>
<script setup lang="ts">
import { circleStrategySet, circleStrategyInfo, tagLists as tagListsApi } from "@/api/person_wechat";
import { getAgentList } from "@/api/agent";

const formData = reactive({
    id: "",
    is_enable_reply: 1,
    reply_interval_time: 3,
    reply_numbers: 5,
    reply_prompt: "",
    reply_tag_ids: [],
    is_enable_like: 1,
    like_interval_time: 3,
    like_numbers: 4,
    like_tag_ids: [],
    reply_robot_id: "",
});

const tagLists = ref<any[]>([]);

const agentLists = ref<any[]>([]);
const agentLoading = ref(false);
const getAgentFn = async (query?: string) => {
    agentLoading.value = true;
    const data = await getAgentList({ keyword: query, source: 1 });
    agentLists.value = data.lists;
    agentLoading.value = false;
};

const handleConfirm = async () => {
    try {
        await circleStrategySet(formData);
        feedback.msgSuccess("保存成功");
        getRobotReplyStrategyFn();
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};

const getRobotReplyStrategyFn = async () => {
    const data = await circleStrategyInfo();
    setFormData(data, formData);
    formData.reply_tag_ids = data.reply_tag_ids?.map((item: any) => parseInt(item));
    formData.like_tag_ids = data.like_tag_ids?.map((item: any) => parseInt(item));
};

const getTagList = async (query?: string) => {
    const { lists } = await tagListsApi({ page_no: 1, page_size: 9999, tag_name: query });
    tagLists.value = lists;
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

onMounted(() => {
    getRobotReplyStrategyFn();
    getAgentFn();
    getTagList();
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
</style>
