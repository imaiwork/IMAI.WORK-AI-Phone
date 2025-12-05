<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">配置信息</div>
            <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
                <!-- <el-form-item label="通用对话版图设置" prop="banner">
                    <div class="flex flex-col gap-2 banner-upload">
                        <material-picker v-model="formData.banner" :limit="1" />
                        <div class="form-tips">图片尺寸推荐500*345px</div>
                    </div>
                </el-form-item> -->
                <el-form-item label="通用对话聊天头像" prop="logo">
                    <material-picker v-model="formData.logo" :limit="1" />
                </el-form-item>
                <el-form-item label="用户文件拼接词设置" prop="file_prompt">
                    <el-input
                        v-model="formData.file_prompt"
                        class="w-[500px]"
                        type="textarea"
                        maxlength="1000"
                        :rows="4" />
                </el-form-item>
                <el-form-item label="新建对话提示词设置" prop="new_chat_prompt">
                    <el-input
                        v-model="formData.new_chat_prompt"
                        class="w-[500px]"
                        type="textarea"
                        maxlength="1000"
                        :rows="4" />
                </el-form-item>
                <!-- <el-form-item label="对话开场白">
                    <div class="w-[500px]">
                        <div class="flex mb-4">
                            <el-button type="primary" @click="addDol"> 新增开场白 </el-button>
                        </div>
                        <el-table :data="formData.preliminary_ask">
                            <el-table-column label="图标">
                                <template #default="{ row }">
                                    <div class="flex justify-center">
                                        <image-contain :src="row.logo" height="30" width="30" fit="cover" />
                                    </div>
                                </template>
                            </el-table-column>
                            <el-table-column prop="value" label="开场白"></el-table-column>
                            <el-table-column label="操作">
                                <template #default="{ row, $index }">
                                    <el-button type="primary" link @click="editDol(row, $index)"> 编辑 </el-button>
                                    <el-button type="danger" link @click="delDol($index)"> 删除 </el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </el-form-item> -->
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perm="['ai_application.chat/setConfig']" type="primary" @click="lockSubmit" :loading="isLock">
            保存
        </el-button>
    </footer-btns>
    <add-greeting v-if="showAddGreeting" ref="addGreetingRef" @success="getGreeting" @close="showAddGreeting = false" />
</template>

<script setup lang="ts">
import { getCommonChatConfig, updateCommonChatConfig } from "@/api/ai_application/chat/setting";
import { useLockFn } from "@/hooks/useLockFn";
import type { FormInstance } from "element-plus";
import AddGreeting from "./add_greeting.vue";
import { cloneDeep } from "lodash-es";
import feedback from "@/utils/feedback";

const formRef = shallowRef<FormInstance>();

const formData = reactive<Record<string, any>>({
    banner: "",
    logo: "",
    file_prompt: "",
    new_chat_prompt: "",
    preliminary_ask: [],
});
const rules = {
    banner: [{ required: true, message: "请上传图片", trigger: "blur" }],
    logo: [{ required: true, message: "请上传图片", trigger: "blur" }],
    file_prompt: [{ required: true, message: "请输入拼接词", trigger: "blur" }],
    new_chat_prompt: [{ required: true, message: "请输入新对话提示词", trigger: "blur" }],
};

const showAddGreeting = ref(false);
const addGreetingRef = shallowRef<InstanceType<typeof AddGreeting>>();

const addDol = async () => {
    showAddGreeting.value = true;
    await nextTick();
    addGreetingRef.value?.open("add");
};

const greetingIndex = ref(-1);
const editDol = async (row: any, index: number) => {
    greetingIndex.value = index;
    showAddGreeting.value = true;
    await nextTick();
    addGreetingRef.value?.open("edit", row);
};

const delDol = (index: number) => {
    const laseIndex = formData.preliminary_ask.length - 1;
    if (formData.preliminary_ask[laseIndex].value != "") {
        formData.preliminary_ask.push({ value: "" });
    }
    formData.preliminary_ask.splice(index, 1);
};

const getGreeting = (result: any) => {
    if (formData.preliminary_ask.length > 5) {
        feedback.msgError("开场白最多只能设置6条");
        return;
    }
    const { mode, data } = result;
    if (mode == "add") {
        formData.preliminary_ask.push(data);
    } else {
        formData.preliminary_ask[greetingIndex.value] = data;
    }
    greetingIndex.value = -1;
    showAddGreeting.value = false;
};

const submit = async () => {
    await formRef.value?.validate();
    await updateCommonChatConfig(formData);
    await getConfig();
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);

const getConfig = async () => {
    const data = await getCommonChatConfig();
    Object.keys(formData).map((item) => {
        formData[item] = data[item];
    });
};
getConfig();
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
