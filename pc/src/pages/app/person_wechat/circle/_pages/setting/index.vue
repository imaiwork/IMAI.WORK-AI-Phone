<template>
    <div class="h-full flex flex-col bg-white rounded-xl overflow-hidden">
        <div class="h-[48px] bg-primary text-white text-2xl flex items-center px-4">策略设置</div>
        <div class="grow min-h-0 px-10 mt-5">
            <div class="flex flex-wrap gap-x-[60px] w-full">
                <div class="w-[45%]">
                    <div class="flex flex-col gap-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">自动评论策略设置</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">是否开启</div>
                            <ElSwitch v-model="formData.is_enable_reply" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">朋友发完朋友圈后多少分钟进行评论：</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber
                                    v-model="formData.reply_interval_time"
                                    :precision="0"
                                    :min="0"
                                    size="small" />分钟后
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">一个好友当天发送多条朋友圈评论多少条：</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber
                                    v-model="formData.reply_numbers"
                                    :precision="0"
                                    :min="0"
                                    size="small" />条
                            </div>
                        </div>
                        <div class="mt-2 relative">
                            <div class="text-[#9E9E9E]">接管评论的智能体：</div>
                            <div class="mt-2">
                                <ElSelect
                                    v-model="formData.reply_robot_id"
                                    class="!w-[70%]"
                                    filterable
                                    clearable
                                    remote
                                    :loading="agentLoading"
                                    :remote-method="getAgentFn">
                                    <ElOption
                                        v-for="item in agentLists"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id"></ElOption>
                                </ElSelect>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">自动评论的好友标签：</div>
                            <ElSelect v-model="formData.reply_tag_ids" class="!w-[70%]" filterable multiple clearable>
                                <ElOption
                                    v-for="item in tagLists"
                                    :key="item.id"
                                    :label="item.tag_name"
                                    :value="item.id"></ElOption>
                            </ElSelect>
                        </div>
                    </div>
                </div>
                <div class="w-[45%]">
                    <div class="flex flex-col gap-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">自动点赞策略设置</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">是否开启自动点赞：</div>
                            <ElSwitch v-model="formData.is_enable_like" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">朋友发完朋友圈后多少分钟进行点赞：</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber
                                    v-model="formData.like_interval_time"
                                    :precision="0"
                                    :min="0"
                                    size="small" />分钟后
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">一个好友当天发送多条朋友圈点赞多少条：</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber v-model="formData.like_numbers" :precision="0" :min="0" size="small" />条
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">自动点赞的好友标签：</div>
                            <ElSelect v-model="formData.like_tag_ids" class="!w-[70%]" filterable multiple clearable>
                                <ElOption
                                    v-for="item in tagLists"
                                    :key="item.id"
                                    :label="item.tag_name"
                                    :value="item.id"></ElOption>
                            </ElSelect>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center my-4">
            <ElButton type="primary" class="w-[120px] !h-[40px]" :loading="isLock" @click="lockConfirm">
                保存
            </ElButton>
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

<style scoped></style>
