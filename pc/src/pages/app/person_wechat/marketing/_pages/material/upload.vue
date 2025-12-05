<template>
    <popup
        ref="popupRef"
        async
        width="500px"
        :title="popupTitle"
        :confirm-loading="isSubmitting"
        @confirm="submitWithLock"
        @close="handleClose">
        <div>
            <ElForm ref="formRef" :model="formData" :rules="rules" label-width="100px" label-position="top">
                <!-- 区域：图片/视频上传表单 -->
                <template v-if="isVisualType">
                    <ElFormItem label="" prop="urls">
                        <upload
                            drag
                            show-progress
                            :limit="uploadLimit"
                            :type="uploadType"
                            :show-file-list="false"
                            class="w-full"
                            @success="handleFileUploadSuccess">
                            <div class="w-full h-[60px] flex flex-col items-center justify-center">
                                <Icon name="local-icon-upload_cloud" :size="40" />
                                <div class="text-[#575757] text-xs leading-5 mt-2">
                                    <div>
                                        将{{ materialTypeName }}拖拽到此处 或 <span class="text-primary">点击上传</span>
                                    </div>
                                    <div>{{ uploadTypeHint }}</div>
                                </div>
                            </div>
                        </upload>
                        <div class="grid grid-cols-5 gap-2 w-full mt-4">
                            <div
                                v-for="(file, index) in formData.urls"
                                :key="file.url"
                                class="h-[90px] bg-[#F7F7F7] rounded-lg p-1 relative">
                                <img
                                    v-if="currentCate === MaterialTypeEnum.IMAGE"
                                    :src="file.url"
                                    class="w-full h-full object-cover rounded-lg"
                                    alt="上传的图片" />
                                <video
                                    v-else-if="currentCate === MaterialTypeEnum.VIDEO"
                                    :src="file.url"
                                    class="w-full h-full object-cover rounded-lg" />
                                <div class="absolute -top-2 -right-2 cursor-pointer" @click="handleDeleteFile(index)">
                                    <Icon name="local-icon-close_circle_fill" :size="20" color="#D43030" />
                                </div>
                            </div>
                        </div>
                    </ElFormItem>
                </template>

                <!-- 区域：链接表单 -->
                <template v-else-if="currentCate === MaterialTypeEnum.LINK">
                    <ElFormItem label="链接地址" prop="link">
                        <ElInput v-model="formData.link" placeholder="请输入链接地址" />
                    </ElFormItem>
                    <ElFormItem label="链接标题" prop="link_title">
                        <ElInput v-model="formData.link_title" placeholder="请输入链接标题" />
                    </ElFormItem>
                    <ElFormItem label="链接描述" prop="link_desc">
                        <ElInput
                            v-model="formData.link_desc"
                            type="textarea"
                            resize="none"
                            placeholder="请输入链接描述"
                            :rows="5" />
                    </ElFormItem>
                </template>

                <!-- 区域：小程序表单 -->
                <template v-else-if="currentCate === MaterialTypeEnum.MINI_PROGRAM">
                    <ElFormItem label="小程序标题" prop="mini_program_name">
                        <ElInput v-model="formData.mini_program_name" placeholder="请输入小程序标题" />
                    </ElFormItem>
                    <ElFormItem label="小程序APPID" prop="mini_program_appid">
                        <ElInput v-model="formData.mini_program_appid" placeholder="请输入小程序APPID" />
                    </ElFormItem>
                    <ElFormItem label="小程序路径" prop="mini_program_path">
                        <ElInput v-model="formData.mini_program_path" placeholder="请输入小程序路径" />
                    </ElFormItem>
                </template>

                <!-- 区域：文件上传表单 -->
                <template v-else-if="currentCate === MaterialTypeEnum.FILE">
                    <ElFormItem label="" prop="urls">
                        <upload
                            :limit="uploadLimit"
                            drag
                            type="file"
                            class="w-full"
                            list-type="text"
                            @success="handleFileUploadSuccess"
                            @remove="handleFileRemove">
                            <div class="w-full h-[60px] flex flex-col items-center justify-center">
                                <Icon name="local-icon-upload_cloud" :size="40" />
                                <div class="text-[#575757] text-xs leading-5 mt-2">
                                    <div>
                                        将{{ materialTypeName }}拖拽到此处 或 <span class="text-primary">点击上传</span>
                                    </div>
                                    <div>支持txt、docx、xml、excel等格式</div>
                                </div>
                            </div>
                        </upload>
                    </ElFormItem>
                </template>

                <!-- 区域：链接/小程序封面图 -->
                <template v-if="isComplexType">
                    <ElFormItem label="封面图" prop="pic">
                        <div class="link-upload-box relative">
                            <upload
                                :limit="1"
                                drag
                                show-progress
                                :show-file-list="false"
                                @success="handleCoverUploadSuccess">
                                <div class="w-[150px] h-[150px] flex flex-col items-center justify-center relative">
                                    <img
                                        v-if="formData.pic"
                                        :src="formData.pic"
                                        class="w-full h-full object-cover rounded-lg"
                                        alt="封面图" />
                                    <template v-else>
                                        <Icon name="local-icon-upload_cloud" :size="28" />
                                        <div class="text-[#575757] text-xs leading-5 mt-2">
                                            <div><span class="text-primary">点击上传</span></div>
                                            <div>支持JPG、PNG格式</div>
                                        </div>
                                    </template>
                                </div>
                            </upload>
                            <div
                                v-if="formData.pic"
                                class="absolute -top-2 -right-2 cursor-pointer"
                                @click="formData.pic = ''">
                                <Icon name="local-icon-close_circle_fill" :size="20" color="#D43030" />
                            </div>
                        </div>
                    </ElFormItem>
                </template>

                <!-- 公共区域：分组选择器 -->
                <ElFormItem :label="`选择该${materialTypeName}的分组`" prop="group_ids">
                    <ElSelect v-model="formData.group_ids" placeholder="请选择" multiple filterable clearable>
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
import { ref, reactive, computed, nextTick } from "vue";
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
:deep(.el-upload-list--picture) {
    max-height: 500px;
    overflow-y: auto;
}

:deep(.link-upload-box) {
    .el-upload-dragger {
        padding: 0;
    }
}
</style>
