<template>
    <popup
        ref="popupRef"
        title="自动招聘（RPA）设置"
        :async="true"
        width="768px"
        destroy-on-close
        :confirm-loading="isLock"
        @confirm="lockFn"
        @cancel="close">
        <div>
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-xl">通用回复（自动回复）</span>
                        <ElSwitch v-model="formData.auto_open" :active-value="1" :inactive-value="0" />
                    </div>
                    <div>
                        <ElButton type="primary" @click="handleSetReplayLink">放置链接</ElButton>
                    </div>
                </div>
                <div class="mt-3">
                    <ElInput
                        ref="replayLinkRef"
                        v-model="formData.reply_link"
                        type="textarea"
                        maxlength="100"
                        minlength="2"
                        :rows="6"></ElInput>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center">
                    <div class="flex items-center">
                        <span class="text-xl">牛人打招呼（特定招呼）</span>
                        <ElSwitch v-model="formData.niu_open" :active-value="1" :inactive-value="0" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="flex items-center gap-4">
                            <div class="whitespace-nowrap">经验要求</div>
                            <ElSelect v-model="formData.work_years">
                                <ElOption label="不限" value="不限"></ElOption>
                                <ElOption label="在校/应届" value="在校/应届"></ElOption>
                                <ElOption label="24年毕业" value="24年毕业"></ElOption>
                                <ElOption label="25年毕业" value="25年毕业"></ElOption>
                                <ElOption label="25后年毕业" value="25后年毕业"></ElOption>
                                <ElOption label="1年以内" value="1年以内"></ElOption>
                                <ElOption label="1-3年" value="1-3年"></ElOption>
                                <ElOption label="3-5年" value="3-5年"></ElOption>
                                <ElOption label="5-10年" value="5-10年"></ElOption>
                            </ElSelect>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="whitespace-nowrap">学历要求</div>
                            <ElSelect v-model="formData.degree">
                                <ElOption label="不限" value="不限"></ElOption>
                                <ElOption label="初中及以下" value="初中及以下"></ElOption>
                                <ElOption label="中专/中技" value="中专/中技"></ElOption>
                                <ElOption label="高中" value="高中"></ElOption>
                                <ElOption label="大专" value="大专"></ElOption>
                                <ElOption label="本科" value="本科"></ElOption>
                                <ElOption label="硕士" value="硕士"></ElOption>
                                <ElOption label="博士" value="博士"></ElOption>
                            </ElSelect>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="whitespace-nowrap">求职意向</div>
                            <ElSelect v-model="formData.intention">
                                <ElOption label="不限" value="不限"></ElOption>
                                <ElOption label="离职-随时到岗" value="离职-随时到岗"></ElOption>
                                <ElOption label="在职-暂不考虑" value="在职-暂不考虑"></ElOption>
                                <ElOption label="在职-考虑机会" value="在职-考虑机会"></ElOption>
                                <ElOption label="在职-考虑机会" value="在职-考虑机会"></ElOption>
                                <ElOption label="在职-月内到岗" value="在职-月内到岗"></ElOption>
                            </ElSelect>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="whitespace-nowrap">薪资待遇</div>
                            <ElSelect v-model="formData.salary">
                                <ElOption label="不限" value="不限"></ElOption>
                                <ElOption label="3K以下" value="3K以下"></ElOption>
                                <ElOption label="3K-5K" value="3K-5K"></ElOption>
                                <ElOption label="5K-10K" value="5K-10K"></ElOption>
                                <ElOption label="10K-20K" value="10K-20K"></ElOption>
                                <ElOption label="20K-50K" value="20K-50K"></ElOption>
                                <ElOption label="50K以上" value="50K以上"></ElOption>
                            </ElSelect>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-end">
                            <ElButton type="primary" @click="handleSetNiuLink">放置链接</ElButton>
                        </div>
                        <div class="mt-3">
                            <ElInput
                                ref="niuLinkRef"
                                v-model="formData.niu_link"
                                type="textarea"
                                maxlength="100"
                                minlength="2"
                                :rows="6"></ElInput>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { generateJobLink, editJobRpa } from "@/api/interview";
import { setRangeText } from "@/utils/dom";
import { type InputInstance } from "element-plus";

const emit = defineEmits<{
    (event: "close"): void;
    (event: "success"): void;
}>();

const formData = reactive({
    job_id: "",
    auto_open: 1,
    reply_link: "",
    niu_open: 1,
    niu_link: "",
    degree: "不限",
    intention: "不限",
    work_years: "不限",
    salary: "不限",
});

const rules = {
    reply_link: [{ minlength: 2, maxlength: 100 }],
    niu_link: [{ minlength: 2, maxlength: 100 }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const replayLinkRef = ref<InputInstance>();
const niuLinkRef = ref<InputInstance>();

const handleSetReplayLink = async () => {
    formData.reply_link = setRangeText(replayLinkRef.value?.textarea!, jobLink.value);
};

const handleSetNiuLink = async () => {
    formData.niu_link = setRangeText(niuLinkRef.value?.textarea!, jobLink.value);
};

const { lockFn, isLock } = useLockFn(async () => {
    try {
        await editJobRpa(formData);
        emit("success");
        popupRef.value?.close();
        feedback.msgSuccess("设置成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const jobLink = ref<string>("");
const getJobLink = async () => {
    try {
        const { url } = await generateJobLink({ job_id: formData.job_id });
        jobLink.value = url;
    } catch (error) {
        feedback.msgError(error);
    } finally {
        feedback.closeLoading();
    }
};

const setFormData = async (row: any) => {
    for (const key in formData) {
        if (row[key] != null && row[key] != undefined) {
            //@ts-ignore
            formData[key] = row[key];
        }
    }
    getJobLink();
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
