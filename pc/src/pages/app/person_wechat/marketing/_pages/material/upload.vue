<template>
    <popup
        ref="popupRef"
        async
        width="560px"
        :confirm-loading="isSubmitting"
        @confirm="submitWithLock"
        @close="handleClose"
        custom-class="modern-material-popup">
        <div class="px-2 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-x-3">
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center text-primary">
                    <Icon :name="getCategoryIcon" :size="20" />
                </div>
                <h3 class="text-[18px] font-black text-tx-primary leading-tight">{{ popupTitle }}</h3>
            </div>
        </div>

        <div class="px-2">
            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                <template v-if="isVisualType">
                    <ElFormItem label="上传素材" prop="urls">
                        <upload
                            drag
                            show-progress
                            :limit="uploadLimit"
                            :type="uploadType"
                            :show-file-list="false"
                            class="w-full"
                            @success="handleFileUploadSuccess">
                            <div class="upload-drop-zone">
                                <div class="icon-box"><Icon name="local-icon-upload_cloud" :size="32" /></div>
                                <div class="text-sm font-medium text-tx-regular mt-3">
                                    拖拽{{ materialTypeName }}至此 或 <span class="text-primary">点击上传</span>
                                </div>
                                <div class="text-xs text-tx-placeholder mt-1">{{ uploadTypeHint }}，最多支持 9 个</div>
                            </div>
                        </upload>

                        <div class="grid grid-cols-4 gap-3 w-full mt-4">
                            <div v-for="(file, index) in formData.urls" :key="file.url" class="preview-card group">
                                <img
                                    v-if="currentCate === MaterialTypeEnum.IMAGE"
                                    :src="file.url"
                                    class="preview-media" />
                                <video v-else :src="file.url" class="preview-media" />

                                <div class="delete-overlay" @click="handleDeleteFile(index)">
                                    <Icon name="el-icon-Delete" :size="16" />
                                </div>
                            </div>
                        </div>
                    </ElFormItem>
                </template>

                <template v-else-if="currentCate === MaterialTypeEnum.LINK">
                    <div class="bg-[#f8fafc]/50 p-5 rounded-[20px] border border-slate-100 mb-6 space-y-4">
                        <ElFormItem label="链接地址" prop="link">
                            <ElInput v-model="formData.link" placeholder="https://" class="custom-input" />
                        </ElFormItem>
                        <ElFormItem label="标题" prop="link_title">
                            <ElInput
                                v-model="formData.link_title"
                                placeholder="显示在对话框中的标题"
                                class="custom-input" />
                        </ElFormItem>
                        <ElFormItem label="描述内容" prop="link_desc">
                            <ElInput
                                v-model="formData.link_desc"
                                type="textarea"
                                resize="none"
                                placeholder="简介信息..."
                                :rows="3"
                                class="custom-textarea" />
                        </ElFormItem>
                    </div>
                </template>

                <template v-else-if="currentCate === MaterialTypeEnum.MINI_PROGRAM">
                    <div class="bg-[#f8fafc]/50 p-5 rounded-[20px] border border-slate-100 mb-6 space-y-4">
                        <ElFormItem label="小程序标题" prop="mini_program_name">
                            <ElInput
                                v-model="formData.mini_program_name"
                                placeholder="请输入标题"
                                class="custom-input" />
                        </ElFormItem>
                        <div class="grid grid-cols-2 gap-4">
                            <ElFormItem label="AppID" prop="mini_program_appid">
                                <ElInput
                                    v-model="formData.mini_program_appid"
                                    placeholder="wx..."
                                    class="custom-input" />
                            </ElFormItem>
                            <ElFormItem label="页面路径" prop="mini_program_path">
                                <ElInput
                                    v-model="formData.mini_program_path"
                                    placeholder="pages/index"
                                    class="custom-input" />
                            </ElFormItem>
                        </div>
                    </div>
                </template>

                <template v-else-if="currentCate === MaterialTypeEnum.FILE">
                    <ElFormItem label="上传文件" prop="urls">
                        <upload
                            :limit="uploadLimit"
                            drag
                            type="file"
                            list-type="text"
                            @success="handleFileUploadSuccess"
                            @remove="handleFileRemove"
                            class="w-full">
                            <div class="upload-drop-zone h-[120px]">
                                <Icon name="local-icon-upload_cloud" :size="32" color="var(--color-primary)" />
                                <div class="text-[13px] font-medium mt-2">点击上传办公文档</div>
                                <div class="text-[11px] text-tx-placeholder">支持 PDF、Word、Excel、TXT 等</div>
                            </div>
                        </upload>
                    </ElFormItem>
                </template>

                <template v-if="isComplexType">
                    <ElFormItem label="展示封面图" prop="pic">
                        <div class="flex items-start gap-4">
                            <div class="cover-upload-box group relative">
                                <upload
                                    class="h-full w-full"
                                    :limit="1"
                                    drag
                                    :show-file-list="false"
                                    @success="handleCoverUploadSuccess">
                                    <div class="cover-placeholder">
                                        <img
                                            v-if="formData.pic"
                                            :src="formData.pic"
                                            class="w-full h-full object-cover" />
                                        <template v-else>
                                            <Icon name="el-icon-Plus" :size="20" />
                                            <span class="text-[11px] mt-1 font-medium">上传封面</span>
                                        </template>
                                    </div>
                                </upload>
                                <div v-if="formData.pic" class="cover-delete" @click="formData.pic = ''">
                                    <Icon name="el-icon-Close" :size="12" />
                                </div>
                            </div>
                            <div class="flex-1 text-xs text-tx-placeholder pt-2 leading-relaxed">
                                建议尺寸 500x500 像素，<br />支持 JPG/PNG，图片将作为对话消息的缩略图展示。
                            </div>
                        </div>
                    </ElFormItem>
                </template>

                <ElFormItem :label="`保存到分组`" prop="group_ids" class="mt-6">
                    <ElSelect
                        v-model="formData.group_ids"
                        placeholder="请选择分组"
                        multiple
                        filterable
                        clearable
                        class="custom-select"
                        :show-arrow="false">
                        <template #prefix><Icon name="el-icon-Folder" /></template>
                        <ElOption
                            v-for="item in availableCateLists"
                            :key="item.id"
                            :label="item.group_name"
                            :value="item.id" />
                    </ElSelect>
                </ElFormItem>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import { useCate, useFile } from "../../_hooks/useMaterial";
import type { FormInstance, FormRules } from "element-plus";

const emit = defineEmits(["close", "success"]);

// --- 核心 Hooks ---
const { currentCate, cateLists, getCateLists } = useCate();
const { handleAddMaterial, handleEditMaterial } = useFile();

// --- 表单状态管理 ---
const formRef = ref<FormInstance>();

/**
 * 创建一个干净的、默认的表单数据对象
 * @returns 默认表单数据
 */
const createDefaultFormData = () => ({
    id: "",
    group_ids: [],
    // 用于图片、视频、文件类型
    urls: [] as { url: string; name: string }[],
    // 用于链接类型
    link: "",
    link_title: "",
    link_desc: "",
    // 用于小程序类型
    mini_program_name: "",
    mini_program_appid: "",
    mini_program_path: "",
    // 用于链接、小程序封面图
    pic: "",
});

let formData = reactive(createDefaultFormData());

const rules = reactive<FormRules>({
    group_ids: [{ required: true, message: "请选择分组", trigger: "change" }],
    urls: [{ required: true, message: "请上传素材", trigger: "change", type: "array" }],
    link: [{ required: true, message: "请输入链接地址", trigger: "blur" }],
    link_title: [{ required: true, message: "请输入链接标题", trigger: "blur" }],
    link_desc: [{ required: true, message: "请输入链接描述", trigger: "blur" }],
    pic: [{ required: true, message: "请上传封面图", trigger: "change" }],
    mini_program_name: [{ required: true, message: "请输入小程序标题", trigger: "blur" }],
    mini_program_appid: [{ required: true, message: "请输入小程序APPID", trigger: "blur" }],
    mini_program_path: [{ required: true, message: "请输入小程序路径", trigger: "blur" }],
});

// --- 计算属性，用于模板渲染 ---

const isEditMode = computed(() => !!formData.id);
const popupTitle = computed(() => `${isEditMode.value ? "编辑" : "上传"}${materialTypeName.value}素材`);

const materialTypeName = computed(() => {
    const names: Record<number, string> = {
        [MaterialTypeEnum.IMAGE]: "图片",
        [MaterialTypeEnum.VIDEO]: "视频",
        [MaterialTypeEnum.LINK]: "链接",
        [MaterialTypeEnum.MINI_PROGRAM]: "小程序",
        [MaterialTypeEnum.FILE]: "文件",
    };
    return names[currentCate.value] || "";
});

const isVisualType = computed(() => [MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(currentCate.value));
const isComplexType = computed(() =>
    [MaterialTypeEnum.LINK, MaterialTypeEnum.MINI_PROGRAM].includes(currentCate.value)
);

const uploadType = computed(() => {
    const types: Record<number, "image" | "video"> = {
        [MaterialTypeEnum.IMAGE]: "image",
        [MaterialTypeEnum.VIDEO]: "video",
    };
    return types[currentCate.value];
});

const uploadTypeHint = computed(() =>
    currentCate.value === MaterialTypeEnum.IMAGE ? "支持JPG、PNG格式" : "支持MP4、MOV格式"
);

const uploadLimit = computed(() => {
    const limits: Record<number, number> = {
        [MaterialTypeEnum.IMAGE]: 9,
        [MaterialTypeEnum.VIDEO]: 9,
        [MaterialTypeEnum.FILE]: 9,
    };
    const limit = limits[currentCate.value] || 0;
    return limit - formData.urls.length;
});

const availableCateLists = computed(() => cateLists.value.filter((item: any) => item.id !== 0));

// --- 文件上传处理 ---

const handleFileUploadSuccess = (result: any) => {
    const {
        data: { uri, name },
    } = result;
    formData.urls.push({ url: uri, name });
};

const handleCoverUploadSuccess = (result: any) => {
    formData.pic = result.data.uri;
};

const handleFileRemove = (result: any) => {
    const removedUri = result.data.uri;
    formData.urls = formData.urls.filter((item) => item.url !== removedUri);
};

const handleDeleteFile = (index: number) => {
    formData.urls.splice(index, 1);
};

// --- 表单提交逻辑 ---

/**
 * 准备链接类型素材的提交参数
 */
const prepareLinkParams = () => {
    const params: Record<string, any> = {
        group_ids: formData.group_ids,
        ext_info: {
            link: formData.link,
            link_desc: formData.link_desc,
        },
    };
    if (isEditMode.value) {
        params.id = formData.id;
        params.file_name = formData.link_title;
        params.file_url = formData.pic;
    } else {
        params.files = [{ url: formData.pic, name: formData.link_title }];
    }
    return params;
};

/**
 * 准备小程序类型素材的提交参数
 */
const prepareMiniProgramParams = () => {
    const params: Record<string, any> = {
        group_ids: formData.group_ids,
        ext_info: {
            mini_program_appid: formData.mini_program_appid,
            mini_program_path: formData.mini_program_path,
        },
    };
    if (isEditMode.value) {
        params.id = formData.id;
        params.file_name = formData.mini_program_name;
        params.file_url = formData.pic;
    } else {
        params.files = [{ url: formData.pic, name: formData.mini_program_name }];
    }
    return params;
};

/**
 * 提交表单
 */
const submitForm = async () => {
    await formRef.value?.validate();

    let params: Record<string, any> = {
        id: formData.id,
        files: formData.urls,
        group_ids: formData.group_ids,
        ext_info: {},
    };

    switch (currentCate.value) {
        case MaterialTypeEnum.LINK:
            params = prepareLinkParams();
            break;
        case MaterialTypeEnum.MINI_PROGRAM:
            params = prepareMiniProgramParams();
            break;
    }

    await (isEditMode.value ? handleEditMaterial(params) : handleAddMaterial(params));
    emit("success");
    popupRef.value?.close();
};

const { isLock: isSubmitting, lockFn: submitWithLock } = useLockFn(submitForm);

// --- 弹窗控制与数据设置 ---
const popupRef = ref<InstanceType<typeof Popup>>();

const open = () => {
    // 重置表单以防数据残留
    Object.assign(formData, createDefaultFormData());
    nextTick(() => {
        formRef.value?.clearValidate();
    });
    popupRef.value?.open();
};

const handleClose = () => {
    emit("close");
};

const getCategoryIcon = computed(() => {
    const icons: Record<number, string> = {
        [MaterialTypeEnum.IMAGE]: "el-icon-Picture",
        [MaterialTypeEnum.VIDEO]: "el-icon-VideoCamera",
        [MaterialTypeEnum.LINK]: "el-icon-Link",
        [MaterialTypeEnum.MINI_PROGRAM]: "el-icon-Connection",
        [MaterialTypeEnum.FILE]: "el-icon-Document",
    };
    return icons[currentCate.value] || "el-icon-Folder";
});

/**
 * 设置表单数据用于编辑
 * @param data 后端返回的素材数据
 */
const setFormDataForEdit = (data: any) => {
    // 关键：先重置表单，再用新数据填充
    Object.assign(formData, createDefaultFormData());
    Object.assign(formData, data);
};

defineExpose({
    open,
    setFormData: setFormDataForEdit,
});

// 初始化时获取分类列表
getCateLists();
</script>

<style scoped lang="scss">
/* 动态标题隐藏 */
:deep(.modern-material-popup .el-dialog__header) {
    @apply hidden;
}

.upload-drop-zone {
    @apply flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-[20px] bg-[#f8fafc]/50 py-8 transition-all;
    &:hover {
        @apply border-[#0065fb]/40 bg-[#0065fb]/[0.02];
    }
    .icon-box {
        @apply text-[#0065fb]/40 transition-transform;
    }
    &:hover .icon-box {
        @apply text-primary scale-110;
    }
}

.preview-card {
    @apply h-[100px] bg-slate-100 rounded-xl relative overflow-hidden shadow-light;
    .preview-media {
        @apply w-full h-full object-cover;
    }
    .delete-overlay {
        @apply absolute inset-0 bg-[#000000]/40 flex items-center justify-center text-white opacity-0 transition-opacity cursor-pointer;
    }
    &:hover .delete-overlay {
        @apply opacity-100;
    }
}

.cover-upload-box {
    @apply rounded-xl border border-slate-100 bg-slate-50;
    .cover-placeholder {
        @apply w-[120px] h-[120px] flex flex-col items-center justify-center text-slate-400;
    }

    .cover-delete {
        @apply absolute -top-1.5 -right-1.5 w-5 h-5 bg-error text-white rounded-full flex items-center justify-center shadow-light cursor-pointer z-10;
    }
}
:deep(.el-upload-dragger) {
    @apply p-0 border-none bg-[transparent];
}
</style>
