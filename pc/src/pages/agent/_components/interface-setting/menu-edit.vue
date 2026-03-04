<template>
    <popup
        ref="popupRef"
        async
        width="480px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        style="padding: 0"
        :show-close="false">
        <div class="relative bg-[#FFFFFF] rounded-[24px] p-4">
            <div class="absolute right-3 top-3 w-8 h-8" @click="close">
                <close-btn />
            </div>

            <div class="flex items-center gap-[10px] mb-[28px]">
                <div class="w-[5px] h-[20px] bg-[#0065fb] rounded-full"></div>
                <h3 class="text-[20px] font-[900] text-[#0F172A]">{{ mode === "add" ? "创建新" : "修改" }}快捷菜单</h3>
            </div>

            <ElForm :model="form" :rules="rules" ref="formRef" label-position="top" class="custom-popup-form">
                <ElFormItem label="触发关键词" prop="keyword">
                    <ElInput
                        v-model="form.keyword"
                        placeholder="请输入用户点击时显示的关键词..."
                        class="custom-input" />
                </ElFormItem>

                <ElFormItem label="自动回复内容" prop="content">
                    <ElInput
                        v-model="form.content"
                        type="textarea"
                        class="custom-textarea"
                        :rows="6"
                        show-word-limit
                        maxlength="2000"
                        placeholder="请输入菜单对应的自动回复消息..." />
                </ElFormItem>

                <ElFormItem label="上传附件图片" v-if="false">
                    <div class="flex flex-wrap gap-[12px]">
                        <div v-for="(item, index) in form.images" :key="index" class="material-item group">
                            <ElImage
                                :src="item"
                                :preview-src-list="[item]"
                                fit="cover"
                                class="w-full h-full rounded-[8px]" />
                            <div
                                class="absolute -top-[8px] -right-[8px] w-[20px] h-[20px] bg-[#EF4444] rounded-[full] flex items-center justify-center cursor-pointer shadow-[0_2px_4px_rgba(0,0,0,0.2)] opacity-0 group-hover:opacity-100 transition-opacity"
                                @click="handleDeleteImage(index)">
                                <Icon name="el-icon-Close" color="#ffffff" :size="12" />
                            </div>
                        </div>
                        <upload
                            v-if="form.images.length < 9"
                            multiple
                            :limit="9 - form.images.length"
                            @success="getImageUploadSuccess">
                            <div
                                class="material-item border-dashed hover:border-[#0065fb] hover:bg-[#F1F6FF] transition-all">
                                <Icon name="el-icon-Plus" :size="20" color="#94A3B8" />
                                <span class="text-[#94A3B8] text-xs mt-[4px]">添加图片</span>
                            </div>
                        </upload>
                    </div>
                </ElFormItem>
            </ElForm>

            <div class="flex gap-[16px] mt-[36px]">
                <ElButton class="footer-btn btn-secondary" @click="close">取消</ElButton>
                <ElButton type="primary" class="footer-btn btn-primary" :loading="isLock" @click="lockFn">
                    确认保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>
<script setup lang="ts">
const emit = defineEmits<{
    (e: "close"): void;
    (e: "success", data: { type: string; data: any }): void;
}>();

const popupRef = shallowRef();

// 弹窗模式：add 或 edit
const mode = ref("add");

// 表单数据
const form = reactive({
    keyword: "",
    content: "",
    images: [] as string[],
});
const formRef = shallowRef();

// 表单验证规则
const rules = reactive({
    keyword: [{ required: true, message: "请输入菜单名称", trigger: "blur" }],
    content: [{ required: true, message: "请输入回复内容", trigger: "blur" }],
});

/**
 * @description 图片上传成功回调
 * @param res - 上传接口返回的数据
 */
const getImageUploadSuccess = (res: any) => {
    const { uri } = res.data;
    if (form.images.length < 9) {
        form.images.push(uri);
    }
};

/**
 * @description 删除已上传的图片
 * @param index - 图片在数组中的索引
 */
const handleDeleteImage = (index: number) => {
    form.images.splice(index, 1);
};

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value.validate();
    close();
    emit("success", {
        type: mode.value,
        data: form,
    });
});

/**
 * @description 打开弹窗
 * @param type - 弹窗模式
 */
const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

// 暴露方法给父组件
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, form),
});
</script>
<style scoped lang="scss">
.btn-secondary {
    @apply bg-[#FFFFFF] border-[#F1F5F9] text-[#64748B] hover:bg-slate-50 hover:border-[#E2E8F0];
}

.material-item {
    @apply relative w-[80px] h-[80px] rounded-[12px] bg-slate-50 border-[1px] border-[#E2E8F0] flex flex-col items-center justify-center overflow-visible;
}
</style>
