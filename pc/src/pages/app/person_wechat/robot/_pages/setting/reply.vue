<template>
    <div class="h-full flex flex-col bg-white rounded-lg overflow-hidden">
        <div class="h-[48px] bg-primary text-white text-2xl flex items-center px-4">策略设置</div>
        <div class="grow min-h-0 px-10 mt-5">
            <div class="flex flex-wrap gap-x-[60px] w-full">
                <div class="w-[32%]">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">短时间内多条信息回复策略</span>
                        </div>
                        <div class="text-[#9E9E9E] mt-2">AI回复策略：</div>
                        <div class="mt-2">
                            <ElRadioGroup v-model="formData.multiple_type">
                                <ElRadio :value="0">逐条回复</ElRadio>
                                <ElRadio :value="1">
                                    <ElTooltip content="当用户在两分钟以外，没有继续发送消息，则将多条信息合并回复">
                                        <div class="flex items-center gap-2">
                                            <span>合并回复</span>
                                            <Icon name="el-icon-WarningFilled" color="#9E9E9E" />
                                        </div>
                                    </ElTooltip>
                                </ElRadio>
                                <ElRadio :value="2">
                                    <ElTooltip content="当用户在两分钟以外，没有继续发送消息，只回复最后一条信息">
                                        <div class="flex items-center gap-2">
                                            <span> 只回复最后一条 </span>
                                            <Icon name="el-icon-WarningFilled" color="#9E9E9E" />
                                        </div>
                                    </ElTooltip>
                                </ElRadio>
                            </ElRadioGroup>
                        </div>
                    </div>
                </div>
                <div class="w-[28%]">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">聊天轮数策略</span>
                        </div>
                        <div class="text-[#9E9E9E] mt-2">AI读取聊天的轮数：</div>
                        <div class="mt-2 flex items-center gap-2">
                            <ElInputNumber
                                v-model="formData.number_chat_rounds"
                                :precision="0"
                                :min="3"
                                :max="50"
                                size="small" />条
                        </div>
                    </div>
                </div>
                <div class="w-[28%]">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">语音消息回复策略</span>
                        </div>
                        <div class="text-[#9E9E9E] mt-2">Ai是否开启回复：</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.voice_enable" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>
                </div>
                <div class="w-[32%] mt-8">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">图片消息回复策略</span>
                        </div>
                        <div class="text-[#9E9E9E] mt-2">Ai是否自动停止回复：</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.image_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-4">
                            <div class="text-[#9E9E9E]">检测到图片信息后回复内容：</div>
                            <div class="mt-2">
                                <ElInput
                                    v-model="formData.image_reply"
                                    type="textarea"
                                    :rows="6"
                                    placeholder="点击输入您要回复的内容" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-[36%] mt-8">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">停止回复策略</span>
                        </div>
                        <div class="text-[#9E9E9E] mt-2">Ai是否自动停止回复：</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.stop_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-4">
                            <div class="text-[#9E9E9E]">用户触发停止回复词：</div>
                            <div class="mt-2">
                                <ElInput
                                    v-model="formData.stop_keywords"
                                    type="textarea"
                                    :rows="6"
                                    placeholder="点击输入您要检测的词语" />
                            </div>
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
import { saveRobotReplyStrategy, getRobotReplyStrategy } from "@/api/person_wechat";

const formData = reactive({
    multiple_type: 0, //多轮回复类型 0: 逐条回复 1: 合并回复 2：只回复最后一条
    number_chat_rounds: 3, //聊天轮数策略
    voice_enable: 0, //语音消息回复策略 0：关闭 1：开启
    image_enable: 0, //图片消息回复策略 0：关闭 1：开启
    image_reply: "", //图片消息回复内容
    stop_enable: 0, //停止回复策略 0：关闭 1：开启
    stop_keywords: "", //停止回复关键词 用英文;分割
});

const handleConfirm = async () => {
    if (formData.image_enable == 1 && !formData.image_reply) {
        feedback.msgError("请输入图片消息回复内容");
        return;
    }
    if (formData.stop_enable == 1 && !formData.stop_keywords) {
        feedback.msgError("请输入停止回复关键词");
        return;
    }
    try {
        await saveRobotReplyStrategy(formData);
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};
const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getRobotReplyStrategyFn = async () => {
    const data = await getRobotReplyStrategy();
    setFormData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
};

onMounted(() => {
    getRobotReplyStrategyFn();
});
</script>

<style scoped></style>
