<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            width="900px"
            top="10vh"
            style="padding: 0; background-color: #ffffff"
            confirm-button-text=""
            cancel-button-text=""
            header-class="!p-0"
            footer-class="!p-0"
            :show-close="false"
            @close="close">
            <div class="p-4">
                <div class="flex items-center justify-between gap-2 border-b border-br-extra-light pb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                            <Icon name="el-icon-Mic" color="var(--color-primary)" :size="20" />
                        </div>
                        <span class="text-gray-950 text-lg font-black tracking-tight">导入会议音频</span>
                    </div>
                    <div class="w-8 h-8" @click="close">
                        <close-btn />
                    </div>
                </div>
                <div class="flex h-[520px] mt-4">
                    <div class="w-[420px] bg-[#f9f9f9]/50 border-r border-br-extra-light flex flex-col p-6">
                        <div
                            class="relative grow rounded-2xl border-2 border-dashed transition-all duration-300 flex flex-col overflow-hidden"
                            :class="[
                                fileLists.length === 0
                                    ? 'border-br hover:border-primary hover:bg-white cursor-pointer'
                                    : 'border-transparent bg-white shadow-lighter',
                            ]"
                            @click="fileLists.length === 0 && handleUpload()">
                            <div
                                v-if="fileLists.length === 0"
                                class="flex flex-col items-center justify-center h-full p-8 text-center">
                                <div class="w-20 h-20 rounded-full bg-primary/5 flex items-center justify-center mb-6">
                                    <Icon name="el-icon-UploadFilled" color="var(--color-primary)" :size="40" />
                                </div>
                                <div class="font-black text-gray-950 text-lg">点击或拖拽上传音频</div>
                                <div class="mt-4 space-y-1.5 text-[11px] text-tx-placeholder font-medium">
                                    <p>• 支持格式: {{ getAccept.replace(/\./g, "").toUpperCase() }}</p>
                                    <p>• 单个文件不超过 {{ maxFileSize }}MB / {{ maxDuration }}小时</p>
                                    <p>• 单次最多支持上传 {{ fileLimit }} 个文件</p>
                                </div>
                            </div>

                            <div v-else class="flex flex-col h-full">
                                <div class="px-5 py-4 flex justify-between items-center border-b border-gray-50">
                                    <div class="text-xs font-black text-gray-950">
                                        待处理队列 <span class="text-primary ml-1">{{ fileLists.length }}</span
                                        ><span class="text-tx-placeholder">/{{ fileLimit }}</span>
                                    </div>
                                    <div
                                        v-if="fileLists.length < fileLimit"
                                        class="text-[11px] font-black text-primary px-2 py-1 rounded-md hover:bg-primary/5 cursor-pointer flex items-center gap-1"
                                        @click.stop="handleUpload">
                                        <Icon name="el-icon-Plus" color="var(--color-primary)" :size="12" />
                                        继续添加
                                    </div>
                                </div>

                                <div class="grow min-h-0">
                                    <ElScrollbar>
                                        <div class="p-3 space-y-3">
                                            <div
                                                v-for="(item, index) in fileLists"
                                                :key="index"
                                                class="group flex items-center gap-3 p-3 rounded-xl border border-br-extra-light hover:border-primary/30 hover:shadow-lighter transition-all bg-white">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center shrink-0">
                                                    <Icon
                                                        v-if="item.status === 1"
                                                        name="el-icon-Headset"
                                                        color="var(--color-primary)"
                                                        :size="20" />
                                                    <div v-else class="upload-spin-icon"></div>
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <div class="text-[13px] font-black text-gray-950 truncate mb-0.5">
                                                        {{ item.file.name }}
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-2 text-[10px] font-medium text-tx-placeholder">
                                                        <span>{{ formatFileSize(item.file.size) }}</span>
                                                        <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                        <span>{{ item.duration }}s</span>
                                                    </div>
                                                </div>

                                                <div
                                                    class="w-7 h-7 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-50 group-hover:opacity-100 opacity-40 transition-all"
                                                    @click.stop="handleDeleteFile(index)">
                                                    <Icon name="el-icon-Close" color="#F56C6C" :size="16" />
                                                </div>
                                            </div>
                                        </div>
                                    </ElScrollbar>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grow flex flex-col p-8 bg-white">
                        <div class="grow space-y-8">
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                                    <span class="text-sm font-black text-gray-950 italic">STEP 1. 确认音频语种</span>
                                </div>
                                <div class="flex flex-wrap gap-2.5">
                                    <div
                                        v-for="item in languageList"
                                        :key="item.code"
                                        class="px-5 py-2 rounded-xl border transition-all cursor-pointer text-xs font-black"
                                        :class="[
                                            formData.language === item.code
                                                ? 'bg-primary border-primary text-white shadow-primary/20'
                                                : 'bg-white border-br text-tx-regular hover:border-primary/50',
                                        ]"
                                        @click="formData.language = item.code">
                                        {{ item.name }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                                    <span class="text-sm font-black text-gray-950 italic">STEP 2. 转写翻译选项</span>
                                </div>
                                <div class="flex gap-4">
                                    <ElSelect
                                        v-model="formData.translation"
                                        class="custom-select"
                                        placeholder="选择翻译目标语言"
                                        :show-arrow="false">
                                        <ElOption
                                            v-for="item in targetLanguageList"
                                            :key="item.code"
                                            :label="item.name"
                                            :value="item.code" />
                                    </ElSelect>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                                    <span class="text-sm font-black text-gray-950 italic">STEP 3. 智能区分发言人</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        v-for="item in speakerOptions"
                                        :key="item.value"
                                        class="p-3 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all"
                                        :class="[
                                            formData.speaker === item.value
                                                ? 'bg-primary/5 border-primary text-primary shadow-sm'
                                                : 'bg-gray-50/50 border-br text-tx-secondary hover:bg-white',
                                        ]"
                                        @click="formData.speaker = item.value">
                                        <Icon
                                            :name="item.value === 0 ? 'el-icon-User' : 'el-icon-UserFilled'"
                                            :size="16" />
                                        <span class="text-xs font-black">{{ item.label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5 flex items-center gap-4">
                            <ElTooltip placement="top">
                                <template #content>
                                    <div class="p-1">
                                        <p class="font-medium mb-1">计费详情:</p>
                                        <p class="text-[11px] opacity-80">
                                            {{ tokensValue.score }} {{ tokensValue.unit }} / 每分钟
                                        </p>
                                    </div>
                                </template>
                                <ElButton
                                    class="!h-14 grow !rounded-2xl !text-base !font-black !border-none shadow-primary/20"
                                    type="primary"
                                    :disabled="!isAllUploadSuccess || getTokensValue <= 0"
                                    :loading="isLock"
                                    @click="lockSubmit">
                                    <div class="flex items-center gap-2">
                                        <span>开始智能转写</span>
                                        <div
                                            v-if="getTokensValue"
                                            class="px-2 py-0.5 bg-white/20 rounded-md text-[13px]">
                                            {{ getTokensValue }} 算力
                                        </div>
                                        <Icon name="el-icon-Right" :size="18" />
                                    </div>
                                </ElButton>
                            </ElTooltip>
                            <ElButton
                                class="!h-14 !px-8 !rounded-2xl !font-black !bg-gray-100 !border-none text-tx-regular hover:!bg-gray-200"
                                @click="close">
                                取消
                            </ElButton>
                        </div>
                    </div>
                </div>
            </div>
            <input type="file" class="hidden" ref="fileInputRef" :accept="getAccept" multiple @change="changeFile" />
        </popup>
    </div>
</template>

<script lang="ts" setup>
import { meetingMinutesBatchCreate } from "@/api/meeting_minutes";
import { uploadFile } from "@/api/app";
import { formatFileSize } from "@/utils/util";
import Popup from "@/components/popup/index.vue";
import useHandleApi from "../_hooks/useHandleApi";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

const formData = reactive<any>({
    language: "cn",
    speaker: 0,
    translation: 0,
});

const { tokensValue, speakerOptions, languageList, targetLanguageList } = useHandleApi();

const getTokensValue = computed(() => {
    let duration = fileLists.value.reduce((acc, item) => acc + item.duration, 0);
    duration = duration / 60;
    return tokensValue.value.score * Math.ceil(duration);
});

const changeTranslation = (value: string) => {
    if (value == "none") {
        formData.translation = "";
    } else {
        formData.translation = value;
    }
};

// 支持单轨或双轨的mp3、wav、m4a、wma、aac、ogg、amr、flac、aiff格式的音频文件和mp4、wmv、m4v、flv、rmvb、dat、mov、mkv、webm、avi、mpeg、3gp、ogg格式的视频文件
// .m4a
const getAccept = computed(() => {
    return ".mp3,.wav,.wma,.aac,.ogg,.amr,.flac,.aiff";
});

const fileLists = ref<any[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const fileLimit = 10;
const maxFileSize = 500;

const maxDuration = 3; // h

// 判断fileList不能为空和全部上传成功
const isAllUploadSuccess = computed(() => {
    return fileLists.value.length > 0 && fileLists.value.every((item) => item.status === 1);
});

const handleUpload = () => {
    fileInputRef.value?.click();
};

const changeFile = async (e: Event) => {
    const target = event.target as HTMLInputElement;
    const files: any = Array.from(target.files || []);
    // 文件单个大小限制500M
    const maxSize = maxFileSize * 1024 * 1024;
    if (files.some((item) => item.size > maxSize)) {
        feedback.msgError(`单个文件最大${maxFileSize}M,已过滤超出限制的文件`);
    }
    const filterFiles = files.filter((item) => item.size < maxSize);
    if (filterFiles.length > fileLimit - fileLists.value.length) {
        feedback.msgError(`上传文件超出限制,最多可上传${fileLimit}个音频文件`);
        return;
    }

    const getAudioDuration = (file: File): Promise<number> => {
        return new Promise((resolve, reject) => {
            const audio = new Audio();
            audio.src = URL.createObjectURL(file);
            audio.onloadedmetadata = () => {
                resolve(audio.duration);
                URL.revokeObjectURL(audio.src);
            };
            audio.onerror = (error) => {
                reject(error);
            };
        });
    };

    for (const item of filterFiles) {
        try {
            const reader = new FileReader();
            const duration = await getAudioDuration(item);
            // 大于3小时过滤
            if (duration > maxDuration * 60 * 60) {
                feedback.msgError(`单个文件最长${maxDuration}小时，已过滤超出限制的文件`);
                continue;
            }

            const fileItem = reactive({
                url: "",
                loading: true,
                file: item,
                status: 2,
                duration: Math.floor(duration), // 添加音频时长
            });
            reader.onload = () => {
                fileItem.url = reader.result as string;
            };
            reader.readAsDataURL(item);

            fileLists.value.push(fileItem);
        } catch (error) {
            feedback.msgError(`无法上传“${item.name}”`);
        }
    }
    await handleUploadFile();
    fileInputRef.value && (fileInputRef.value.value = null);
};

const handleUploadFile = async () => {
    const uploadPromises = fileLists.value.map((item, index) => submitFileUpload(item, index));
    await Promise.allSettled(uploadPromises);
    fileLists.value = fileLists.value.filter((item) => item.status === 1);
};

const submitFileUpload = async (item: any, index: number) => {
    if (item.status != 2) return;
    try {
        item.loading = true;
        const fileRes = await uploadFile({
            file: item.file,
        });
        item.audio_id = fileRes.audio_id;
        item.loading = false;
        item.status = 1;
        item.url = fileRes.uri;
        fileLists.value[index] = item;
    } catch (error) {
        feedback.msgError(`无法上传“${item.file.name}”`);
        item.loading = false;
        fileLists.value = fileLists.value.splice(index, 1);
    }
};

const handleDeleteFile = (index: number) => {
    fileLists.value.splice(index, 1);
};

const handleFileSuccess = (data: any) => {
    formData.audio_id = data.audio_id;
};

const handleSubmit = async () => {
    if (fileLists.value.length == 0) {
        feedback.msgError("请上传音频文件");
        return;
    }
    if (userTokens.value <= tokensValue.value.score * fileLists.value.length) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        let params: any = [];
        fileLists.value.forEach((item) => {
            params.push({
                ...formData,
                url: item.url,
                name: item.file.name,
                translation: formData.translation == 0 ? "" : formData.translation,
            });
        });
        await meetingMinutesBatchCreate(params);
        userStore.getUser();
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError(error || "创建失败");
    }
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

const open = () => {
    popupRef.value?.open();
};
const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.upload-spin-icon {
    width: 18px;
    height: 18px;
    border: 2px solid #e2e8f0;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
