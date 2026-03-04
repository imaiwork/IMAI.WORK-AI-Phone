<template>
    <popup
        ref="popupRef"
        top="8vh"
        width="860px"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false">
        <div class="relative">
            <div class="absolute right-2 top-2 w-8 h-8 z-20" @click="close">
                <close-btn />
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center text-primary">
                    <Icon :name="mode === 'add' ? 'local-icon-add_circle' : 'local-icon-edit3'" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">{{ modeText }}分段数据</div>
                    <div class="text-xs font-medium text-[#94A3B8]">手动维护知识库的分段内容及关联多媒体素材</div>
                </div>
            </div>

            <ElScrollbar max-height="65vh">
                <div class="pr-3">
                    <div class="grid grid-cols-2 gap-5 mb-6">
                        <div class="editor-container">
                            <div class="label-box">
                                <span class="dot"></span>
                                <span class="title">文档内容</span>
                                <span class="sub">会遇到的提问</span>
                            </div>
                            <ElInput
                                v-model="formData.question"
                                type="textarea"
                                resize="none"
                                placeholder="请输入文档核心内容或模拟提问..."
                                :rows="8"
                                class="custom-textarea" />
                        </div>
                        <div class="editor-container">
                            <div class="label-box">
                                <span class="dot bg-[#10B981]"></span>
                                <span class="title">说明内容</span>
                                <span class="sub">如何回复/详情</span>
                            </div>
                            <ElInput
                                v-model="formData.answer"
                                type="textarea"
                                resize="none"
                                placeholder="请输入对应的回答或补充说明信息..."
                                :rows="8"
                                class="custom-textarea" />
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-[24px] border border-br p-6 space-y-6">
                        <div class="text-[14px] font-[900] text-[#1E293B] flex items-center gap-2 mb-2">
                            <Icon name="el-icon-Files" :size="20" /> 附加素材内容
                        </div>

                        <div class="asset-group">
                            <div class="asset-label">
                                上传图片 <span class="count">{{ formData.images.length }}/9</span>
                            </div>
                            <div class="flex flex-wrap gap-3 mt-3">
                                <div
                                    v-for="(item, index) in formData.images"
                                    :key="index"
                                    class="upload-preview-card group">
                                    <ElImage
                                        :src="item.url"
                                        fit="cover"
                                        class="w-full h-full"
                                        :preview-src-list="formData.images.map((i) => i.url)" />
                                    <div class="delete-mask" @click="handleDeleteImage(index)">
                                        <Icon name="el-icon-Delete" color="#ffffff" :size="16" />
                                    </div>
                                </div>
                                <upload
                                    v-if="formData.images.length < 9"
                                    show-progress
                                    :limit="9 - formData.images.length"
                                    :show-file-list="false"
                                    multiple
                                    @success="getImageUploadSuccess">
                                    <div class="upload-trigger-btn">
                                        <Icon name="el-icon-Plus" :size="20" />
                                        <span>上传图片</span>
                                    </div>
                                </upload>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="asset-group">
                                <div class="asset-label">关联视频 <span class="tip">MP4, 小于20MB</span></div>
                                <div class="mt-3">
                                    <upload
                                        class="w-full"
                                        :limit="1"
                                        type="video"
                                        accept="video/mp4"
                                        show-progress
                                        :max-size="20"
                                        :show-file-list="false"
                                        @success="getVideoUploadSuccess">
                                        <div class="h-[150px] w-full">
                                            <div v-if="!formData.video.length" class="video-trigger">
                                                <Icon name="el-icon-VideoPlay" :size="24" />
                                                <span class="text-xs font-medium mt-1">上传视频</span>
                                            </div>
                                            <div v-else class="video-preview-box group h-full">
                                                <video
                                                    :src="formData.video[0].url"
                                                    class="w-full h-full object-cover" />
                                                <div
                                                    class="absolute inset-0 bg-[#000000]/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all;">
                                                    <div
                                                        class="absolute inset-0 bg-[#000000]/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                                        <div
                                                            class="w-10 h-10 rounded-full bg-[#ffffff]/20 backdrop-blur-md flex items-center justify-center text-white cursor-pointer hover:scale-110 transition-all"
                                                            @click.stop="handlePlayVideo">
                                                            <Icon name="el-icon-CaretRight" :size="24" />
                                                        </div>
                                                        <div
                                                            class="absolute top-2 right-2 w-6 h-6 rounded-lg bg-danger text-white flex items-center justify-center cursor-pointer;"
                                                            @click.stop="handleDeleteVideo(0)">
                                                            <Icon name="el-icon-Delete" :size="14" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </upload>
                                </div>
                            </div>

                            <div class="asset-group">
                                <div class="asset-label">相关附件 <span class="tip">PDF, Docx, MD...</span></div>
                                <div class="mt-3">
                                    <upload type="file" :limit="1" :show-file-list="false" @success="getUploadSuccess">
                                        <div class="file-upload-btn">
                                            <Icon name="el-icon-Upload" />
                                            <span>选取附件文件</span>
                                        </div>
                                    </upload>
                                    <div class="mt-2 space-y-2">
                                        <div v-for="(file, idx) in formData.files" :key="idx" class="file-item">
                                            <Icon name="el-icon-Document" class="text-primary" />
                                            <span class="name">{{ file.name }}</span>
                                            <span
                                                class="close leading-[0] cursor-pointer"
                                                @click.stop="handleUploadRemove(idx)">
                                                <Icon name="el-icon-Close" />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>

            <div class="mt-8 flex gap-4">
                <ElButton class="footer-btn !bg-[#F1F5F9] !text-[#64748B]" @click="close()">取消</ElButton>
                <ElButton type="primary" class="footer-btn is-save" :loading="isLock" @click="lockFn()">
                    提交保存
                </ElButton>
            </div>
        </div>
    </popup>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import {
    vectorKnowledgeBaseFileChunkAdd,
    vectorKnowledgeBaseFileChunkEdit,
    vectorKnowledgeBaseFileChunkDetail,
} from "@/api/knowledge_base";
import { ThemeEnum } from "@/enums/appEnums";
const emit = defineEmits<{ (e: "close"): void; (e: "success"): void }>();
const popupRef = ref<any>(null);
const mode = ref<"add" | "edit">("add");
const modeText = computed(() => {
    return mode.value === "add" ? "新增" : "编辑";
});

const formData = reactive({
    kb_id: "",
    fd_id: "",
    question: "",
    answer: "",
    images: [],
    files: [],
    video: [],
    uuid: "",
});

const getImageUploadSuccess = (res: any) => {
    const { uri, name } = res.data;
    formData.images.push({
        name,
        url: uri,
    });
};

const getVideoUploadSuccess = (res: any) => {
    const { uri, name } = res.data;
    formData.video = [
        {
            url: uri,
            name,
        },
    ];
};

const showPreviewVideo = ref(false);
const previewVideoRef = ref<any>(null);
const handlePlayVideo = async () => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value.open();
    previewVideoRef.value.setUrl(formData.video[0].url);
};

const handleDeleteImage = (index: number) => {
    formData.images.splice(index, 1);
};

const handleDeleteVideo = (index: number) => {
    formData.video.splice(index, 1);
};

const handleUploadRemove = (index: number) => {
    console.log(index);
    formData.files.splice(index, 1);
};

const getUploadSuccess = (res: any) => {
    const { uri, name } = res.data;
    formData.files.push({
        name,
        url: uri,
    });
};

const open = (type: "add" | "edit" = "add") => {
    mode.value = type;
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.question) {
        feedback.msgError("请输入文档内容");
        return;
    }
    try {
        mode.value == "edit"
            ? await vectorKnowledgeBaseFileChunkEdit(formData)
            : await vectorKnowledgeBaseFileChunkAdd(formData);
        close();
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
});

const getDetail = async (uuid: string) => {
    const res = await vectorKnowledgeBaseFileChunkDetail({
        uuid,
    });
    setFormData(res, formData);
};

defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
    getDetail,
});
</script>

<style scoped lang="scss">
.editor-container {
    @apply flex flex-col gap-3;
    .label-box {
        @apply flex items-center gap-2 pl-1;
        .dot {
            @apply w-2 h-2 rounded-full bg-primary;
        }
        .title {
            @apply text-[14px] font-[900] text-[#1E293B];
        }
        .sub {
            @apply text-[11px] font-medium text-[#94A3B8];
        }
    }
}

.asset-group {
    .asset-label {
        @apply text-xs font-black text-[#64748B] flex items-center justify-between px-1;
        .count {
            @apply text-primary;
        }
        .tip {
            @apply font-medium text-[#94A3B8] scale-90;
        }
    }
}

.upload-trigger-btn {
    @apply w-20 h-20 rounded-xl border-2 border-dashed border-br bg-white flex flex-col items-center justify-center text-[#94A3B8] transition-all cursor-pointer;
    span {
        @apply text-[10px] font-black mt-1;
    }
    &:hover {
        @apply border-primary text-primary;
    }
}

.upload-preview-card {
    @apply w-20 h-20 rounded-xl overflow-hidden border border-br relative;
    .delete-mask {
        @apply absolute inset-0 bg-[#000000]/40 flex items-center justify-center opacity-0 transition-opacity cursor-pointer z-10;
    }
    &:hover .delete-mask {
        @apply opacity-100;
    }
}

.video-trigger {
    @apply h-[100px] w-full rounded-2xl border-2 border-dashed border-br bg-white flex flex-col items-center justify-center text-[#94A3B8] hover:border-primary hover:text-primary transition-all cursor-pointer;
}

.video-preview-box {
    @apply w-full rounded-2xl overflow-hidden relative border border-br;
}

.file-upload-btn {
    @apply h-10 px-4 rounded-xl border border-br bg-white flex items-center gap-2 text-xs font-black text-[#64748B] hover:border-primary hover:text-primary transition-all cursor-pointer;
}
.file-item {
    @apply flex items-center gap-2 px-3 py-2 bg-white border border-[#F1F5F9] rounded-xl text-xs font-medium text-[#1E293B];
    .name {
        @apply flex-1 truncate;
    }
    .close {
        @apply text-[#94A3B8] cursor-pointer hover:text-danger;
    }
}

.footer-btn {
    @apply flex-1 h-12 rounded-xl font-black text-[15px] border-none transition-all;
}
</style>
