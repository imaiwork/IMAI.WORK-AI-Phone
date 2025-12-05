<template>
    <popup
        ref="popupRef"
        async
        width="415px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        body-class="coze-edit-body !p-0 "
        style="padding: 0"
        :show-close="false">
        <div class="">
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="top" :style="{ backgroundImage: `url(${formData.bg_image || CozeBg})` }">
                <div class="mt-10">
                    <agent-logo v-model="formData.avatar" />
                </div>
                <div class="mt-[25px]">
                    <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                        <div
                            class="w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white">
                            更换背景
                        </div>
                    </upload>
                </div>
            </div>
            <div class="p-8">
                <div class="text-lg font-bold">新增Coze智能体</div>
                <div class="text-xs text-[#0000004d] mt-2">快速搭建对话式智能体</div>
                <el-form ref="formRef" :model="formData" :rules="rules" label-position="top" class="mt-6">
                    <el-form-item label="智能体名称" prop="name">
                        <el-input v-model="formData.name" class="!h-10" placeholder="请输入名称" />
                    </el-form-item>
                    <el-form-item label="智能体介绍" prop="introduced">
                        <el-input
                            v-model="formData.introduced"
                            type="textarea"
                            placeholder="请输入智能体的说明"
                            resize="none"
                            :rows="6" />
                    </el-form-item>
                    <el-form-item label="智能体分类" prop="agent_cate_id">
                        <el-select
                            v-model="formData.agent_cate_id"
                            class="!h-10"
                            placeholder="请选择智能体分类"
                            filter-method
                            remote
                            :remote-method="getCategoryList">
                            <el-option :label="item.name" :value="item.id" v-for="item in categoryList" :key="item.id">
                            </el-option>
                        </el-select>
                        <router-link
                            class="text-primary"
                            :to="getRoutePath('ai_application.agent/cate')"
                            target="_blank">
                            去创建分类
                        </router-link>
                    </el-form-item>
                    <el-form-item label="Coze智能体ID" prop="coze_id">
                        <el-input v-model="formData.coze_id" class="!h-10" placeholder="请输入Coze智能体ID" />
                    </el-form-item>
                    <el-form-item label="输出方式">
                        <el-radio-group v-model="formData.stream">
                            <el-radio label="流式输出" :value="1"></el-radio>
                            <el-radio label="直接返回" :value="0"></el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="扣费方式">
                        <el-radio-group v-model="formData.deduction">
                            <el-radio label="Token扣费" :value="0" disabled></el-radio>
                            <el-radio label="按次数扣费" :value="1"></el-radio>
                        </el-radio-group>
                        <el-input-number
                            v-model="formData.tokens"
                            :min="0"
                            controls-position="right"
                            disabled-scientific>
                            <template #append>算力/次</template>
                        </el-input-number>
                    </el-form-item>
                </el-form>
                <div class="flex justify-center">
                    <el-button
                        color="#000000"
                        class="!rounded-full !h-[50px] w-[310px] shadow-[0_6px_12px_0px_#0065FB33]"
                        :loading="isLock"
                        @click="lockFn()"
                        >保存</el-button
                    >
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getCateList } from "@/api/ai_application/agent/cate";
import { cozeAgentAdd, cozeAgentUpdate } from "@/api/ai_application/agent/coze";
import { uploadFile } from "@/api/app";
import CozeBg from "@/assets/images/coze_bg.png";
import AgentLogo from "./agent-logo.vue";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";
import { urlToFile, setFormData } from "@/utils/util";
import { getCozeAgentDetail } from "@/api/ai_application/agent/coze";
import { getRoutePath } from "@/router";
const emit = defineEmits(["close", "success"]);

const popupRef = shallowRef();

const formData = reactive({
    id: "",
    name: "",
    type: 1,
    introduced: "",
    bg_image: "",
    coze_id: "",
    stream: 1,
    deduction: 1,
    avatar: "",
    permissions: 0,
    agent_cate_id: "",
    tokens: 0,
});
const rules = {
    name: [{ required: true, message: "请输入智能体名称" }],
    introduced: [{ required: true, message: "请输入智能体介绍" }],
    coze_id: [{ required: true, message: "请输入Coze智能体ID" }],
    agent_cate_id: [{ required: true, message: "请选择类目" }],
};
const formRef = shallowRef();

const categoryList = ref<any[]>([]);
const getCategoryList = async (query?: any) => {
    const { lists } = await getCateList({
        name: query,
        type: 2,
        page_size: 25000,
    });
    categoryList.value = lists;
};

const getBgSuccessImage = (res: any) => {
    const { uri } = res;
    formData.bg_image = uri;
};

// 自动上传默认背景图片
const uploadDefaultBackground = async () => {
    try {
        // 将CozeBg转换为File对象
        const file = await urlToFile(CozeBg, "coze_bg.png");

        // 调用上传接口
        const { uri } = await uploadFile("image", { file });
        formData.bg_image = uri;
    } catch (error) {
        console.error("上传默认背景失败:", error);
    }
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.avatar) {
        feedback.msgError("请上传智能体头像");
        return;
    }
    await formRef.value?.validate();
    // 等待DOM更新后再检查是否需要上传默认背景
    await nextTick();

    // 如果没有背景图片，自动上传默认背景
    if (!formData.bg_image) {
        await uploadDefaultBackground();
    }
    formData.id ? await cozeAgentUpdate(formData) : await cozeAgentAdd(formData);
    close();
    emit("success");
});

const open = async () => {
    popupRef.value.open();
    getCategoryList();
};

const close = () => {
    emit("close");
};
const getDetail = async (id: any) => {
    const res = await getCozeAgentDetail({ id });
    setFormData(res, formData);
};

defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
    getDetail,
});
</script>

<style lang="scss">
.coze-edit-body {
    .top {
        @apply w-full h-[235px] bg-no-repeat bg-cover   flex flex-col justify-center items-center;
    }
}
</style>
