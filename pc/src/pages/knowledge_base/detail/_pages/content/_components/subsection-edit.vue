<template>
    <popup
        ref="popupRef"
        top="10vh"
        width="800px"
        cancel-button-text=""
        confirm-button-text=""
        async
        style="padding: 0"
        :show-close="false">
        <div class="-my-4">
            <div class="absolute top-[18px] right-[18px] w-6 h-6" @click="close">
                <close-btn :theme="ThemeEnum.LIGHT" />
            </div>
            <div class="p-[18px]">
                <div class="font-bold text-[20px]">{{ modeText }}分段</div>
                <div class="border-b border-[#0000001a] my-3"></div>
                <div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <div class="text-[#00000080]">文档内容（可理解成会遇到的提问）</div>
                            <div class="mt-3">
                                <ElInput
                                    v-model="formData.question"
                                    type="textarea"
                                    resize="none"
                                    placeholder="请输入文档内容"
                                    :rows="10" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-[#00000080]">说明内容（可理解如何回复）</div>
                            <div class="mt-3">
                                <ElInput
                                    v-model="formData.answer"
                                    type="textarea"
                                    resize="none"
                                    placeholder="请输入相关内容"
                                    :rows="10" />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-[#000000cc]">附加内容：</div>
                        <div class="mt-2">
                            <div>
                                <div class="text-[#00000080]">上传图片：(最多9张)</div>
                                <div class="mt-2">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(item, index) in formData.images"
                                            :key="index"
                                            class="material-item">
                                            <ElImage
                                                :src="item.url"
                                                :preview-src-list="[item.url]"
                                                fit="cover"
                                                class="w-full h-full rounded-md" />
                                            <div class="absolute -top-2 -right-2" @click="handleDeleteImage(index)">
                                                <Icon name="local-icon-error_fill" color="#ffffff" />
                                            </div>
                                        </div>
                                        <upload
                                            :limit="9"
                                            multiple
                                            show-progress
                                            :show-file-list="false"
                                            @success="getImageUploadSuccess">
                                            <div class="material-item">
                                                <Icon name="local-icon-upload" :size="18" color="#0000004d" />
                                                <span class="text-[#0000004d] text-xs mt-2">上传图片</span>
                                            </div>
                                        </upload>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="text-[#00000080]">上传视频：(格式为MP4，大小不能超过20M)</div>
                                <div class="mt-2">
                                    <upload
                                        :limit="1"
                                        type="video"
                                        show-progress
                                        accept="video/mp4"
                                        :max-size="20"
                                        :show-file-list="false"
                                        @success="getVideoUploadSuccess">
                                        <div class="material-item">
                                            <template v-if="!formData.video.length">
                                                <Icon name="local-icon-upload" :size="18" color="#0000004d" />
                                                <span class="text-[#0000004d] text-xs mt-2">上传视频</span>
                                            </template>
                                            <div class="w-full h-full z-10 relative" v-else>
                                                <div
                                                    class="absolute -top-2 -right-2"
                                                    @click.stop="handleDeleteVideo(0)">
                                                    <Icon name="local-icon-error_fill" color="#ffffff" />
                                                </div>
                                                <video
                                                    :src="formData.video[0].url"
                                                    class="w-full h-full object-cover rounded-md" />
                                                <div
                                                    class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                                    <div @click.stop="handlePlayVideo">
                                                        <Icon
                                                            name="local-icon-play"
                                                            :size="40"
                                                            color="#0000004d"></Icon>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </upload>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="text-[#00000080]">上传附件：(支持上传PDF、docx、excel、等文件格式)</div>
                                <div class="mt-2">
                                    <upload
                                        type="file"
                                        accept=".pdf,.docx,.xlsx,.doc,.csc,.txt,.md"
                                        list-type="text"
                                        @remove="handleUploadRemove"
                                        @success="getUploadSuccess">
                                        <ElButton>上传附件</ElButton>
                                    </upload>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex">
                    <ElButton class="flex-1 !rounded-full !h-[50px] w-[98px]" @click="close()">取消</ElButton>
                    <ElButton
                        type="primary"
                        class="flex-1 !rounded-full !h-[50px] w-[98px]"
                        :loading="isLock"
                        @click="lockFn()">
                        保存
                    </ElButton>
                </div>
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

const handleUploadRemove = (file: any) => {
    formData.files.splice(file.uid, 1);
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
.material-item {
    @apply cursor-pointer relative w-20 h-20 rounded-md bg-[#fafafa] border border-[#0000001a] flex flex-col items-center justify-center;
}
:deep(.el-upload-list) {
    .el-upload-list__item {
        @apply h-11 flex items-center shadow-[0_0_0_1px_#EFEFEF];
    }
    .el-progress {
        @apply top-[34px] left-0;
    }
}
</style>
