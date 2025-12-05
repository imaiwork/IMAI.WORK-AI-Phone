<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">配置信息</div>
            <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
                <el-form-item label="智能对话交互" prop="live_chat">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_chat" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:每轮对话消耗的算力点数，每轮对话携带2组聊天记录上下文。</div>
                    </div>
                </el-form-item>
                <el-form-item label="直播脚本创作" prop="live_script">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_script" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:AI大模型创作话术</div>
                    </div>
                </el-form-item>
                <el-form-item label="评论控场回复" prop="live_chat_comment">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_chat_comment" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:获取到用户内容并评论</div>
                    </div>
                </el-form-item>
                <el-form-item label="直播脚本润色" prop="live_script_rich">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_script_rich" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:AI对脚本创作中文案创作、直播话术变换等场景、话术脚本进行润色</div>
                    </div>
                </el-form-item>
                <el-form-item label="知识归纳总结" prop="live_knowledge">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_knowledge" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:通过用户提供的文案自动总结相关知识点</div>
                    </div>
                </el-form-item>
                <el-form-item label="整篇脚本文案" prop="live_script_sentence">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_script_sentence" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:需要AI大模型创作整篇话术脚本</div>
                    </div>
                </el-form-item>
                <el-form-item label="文案智能断句" prop="live_ai_sentence">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_ai_sentence" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:已有直播脚本通过AI智能断句</div>
                    </div>
                </el-form-item>
                <el-form-item label="直播脚本合成" prop="live_script_synthesize">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_script_synthesize" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:话术通过语音模型将文字转换为语音</div>
                    </div>
                </el-form-item>
                <el-form-item label="智能回复合成" prop="live_chat_synthesize">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_chat_synthesize" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:生成回复语音</div>
                    </div>
                </el-form-item>
                <el-form-item label="直播控场回复" prop="live_chat_comment">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_chat_comment" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:关键词语音回复</div>
                    </div>
                </el-form-item>
                <el-form-item label="语音感谢欢迎" prop="live_voice">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_voice" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:欢迎用户进入直播间</div>
                    </div>
                </el-form-item>
                <el-form-item label="直播报时报人数" prop="live_report">
                    <div class="flex flex-col">
                        <el-input v-model="formData.live_report" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:直播整点报时/报当前直播间人数</div>
                    </div>
                </el-form-item>
                <el-form-item label="抖音验证码" prop="dy_code">
                    <div class="flex flex-col">
                        <el-input v-model="formData.dy_code" class="w-[300px]" type="number" disabled />
                        <div class="form-tips">提示:直播触发行所接收的验证码</div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <!-- <footer-btns>
        <el-button type="primary" @click="lockSubmit" :loading="isLock"> 保存 </el-button>
    </footer-btns> -->
</template>

<script setup lang="ts">
import { useLockFn } from "@/hooks/useLockFn";
import type { FormInstance } from "element-plus";

const formRef = shallowRef<FormInstance>();

const formData = reactive<Record<string, any>>({
    live_chat: 4,
    live_script: 1,
    live_chat_comment: 10,
    live_script_rich: 2,
    live_knowledge: 15,
    live_script_sentence: 24,
    live_ai_sentence: 20,
    live_script_synthesize: 100,
    live_chat_synthesize: 100,
    live_voice: 10,
    live_report: 10,
    dy_code: 20,
});
const rules = {};

const submit = async () => {
    // await formRef.value?.validate();
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);
</script>

<style scoped lang="scss">
.banner-upload {
    :deep(.upload-btn) {
        width: 500px !important;
        height: 145px !important;
    }
    :deep(.file-item) {
        width: 500px !important;
        height: 145px !important;
    }
}
</style>
