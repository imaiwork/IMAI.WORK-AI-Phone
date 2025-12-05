<template>
    <popup
        ref="popupRef"
        async
        width="468px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <!-- 弹窗标题 -->
            <div class="text-2xl font-bold mb-5">{{ mode === "add" ? "添加" : "编辑" }}菜单</div>
            <!-- 表单 -->
            <ElForm :model="form" :rules="rules" ref="formRef" label-position="top">
                <ElFormItem label="关键词" prop="keyword">
                    <ElInput v-model="form.keyword" class="!h-11" />
                </ElFormItem>
                <ElFormItem label="回复内容" prop="content">
                    <ElInput v-model="form.content" type="textarea" :rows="4" show-word-limit maxlength="2000" />
                </ElFormItem>
                <ElFormItem label="上传图片" v-if="false">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <!-- 已上传图片列表 -->
                            <div v-for="(item, index) in form.images" :key="index" class="material-item">
                                <ElImage
                                    :src="item"
                                    :preview-src-list="[item]"
                                    fit="cover"
                                    class="w-full h-full rounded-md" />
                                <div class="absolute -top-2 -right-2 cursor-pointer" @click="handleDeleteImage(index)">
                                    <Icon name="local-icon-error_fill" color="#ffffff" />
                                </div>
                            </div>
                            <!-- 上传按钮 -->
                            <upload
                                v-if="form.images.length < 9"
                                multiple
                                show-progress
                                :limit="9 - form.images.length"
                                :show-file-list="false"
                                @success="getImageUploadSuccess">
                                <div class="material-item">
                                    <Icon name="local-icon-upload" :size="18" color="#0000004d" />
                                    <span class="text-[#0000004d] text-xs mt-2">上传图片</span>
                                </div>
                            </upload>
                        </div>
                        <div class="form-tips">图片最多9张</div>
                    </div>
                </ElFormItem>
            </ElForm>
            <!-- 操作按钮 -->
            <div class="flex">
                <ElButton class="!rounded-full flex-1 !h-[50px]" @click="close">取消</ElButton>
                <ElButton type="primary" class="!rounded-full flex-1 !h-[50px]" :loading="isLock" @click="lockFn">
                    保存
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
.material-item {
    @apply cursor-pointer relative w-20 h-20 rounded-md bg-[#fafafa] border border-[#0000001a] flex flex-col items-center justify-center;
}
</style>
