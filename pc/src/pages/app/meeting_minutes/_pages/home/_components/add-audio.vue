<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="上传本地会议音频文件"
            :async="true"
            width="848px"
            confirm-button-text=""
            cancel-button-text=""
            @close="close">
            <div class="flex gap-4">
                <div
                    class="w-[388px] flex-shrink-0 rounded-2xl bg-primary-light-9 h-[400px] border border-dashed border-token-primary-2 cursor-pointer"
                    :class="{
                        'hover:border-primary': fileLists.length === 0,
                    }">
                    <div
                        class="flex flex-col items-center justify-center w-full h-full"
                        v-if="fileLists.length == 0"
                        @click="handleUpload">
                        <img class="w-[130px] object-cover" src="../../../_assets/images/upload.png" />
                        <div class="font-bold text-lg mt-"><span class="text-primary">点击上传</span>本地音频文件</div>
                        <div class="w-full overflow-hidden text-[#737373] text-xs px-[30px] mt-4">
                            <div>·单个文件最长3小时，单次只能上传{{ fileLimit }}个。</div>
                            <div class="break-words">·音频格式支持：{{ getAccept }}，单个最大{{ maxFileSize }}M。</div>
                        </div>
                    </div>
                    <div class="flex flex-col h-full" v-else>
                        <div class="flex justify-between items-center px-4 mt-4">
                            <div class="text-[#737373] text-xs">
                                文件数量：{{ fileLists.length }}<span class="text-[#c3c3d0]">/{{ fileLimit }}</span>
                            </div>
                            <div v-if="fileLists.length < fileLimit">
                                <ElButton type="primary" link size="small" @click="handleUpload">继续添加</ElButton>
                            </div>
                        </div>
                        <div class="grow min-h-0">
                            <ElScrollbar>
                                <div class="flex flex-col gap-4 p-4">
                                    <div
                                        class="flex justify-between items-center gap-2"
                                        v-for="(item, index) in fileLists"
                                        :key="index">
                                        <div class="flex-1 flex items-center gap-3">
                                            <img src="../../../_assets/images/audio.svg" class="h-[32px]" />
                                            <div class="flex-1 w-0">
                                                <div class="line-clamp-1">
                                                    {{ item.file.name }}
                                                </div>
                                                <div class="flex gap-2">
                                                    <div class="text-xs text-[#9492ad]">
                                                        {{ formatFileSize(item.file.size) }}
                                                    </div>
                                                    <div class="text-xs text-[#9492ad]">{{ item.duration }}秒</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div v-if="item.status == 1" @click="handleDeleteFile(index)">
                                                <Icon name="el-icon-Close" :size="18"></Icon>
                                            </div>
                                            <div v-else>
                                                <Icon name="local-icon-loading" :size="18"></Icon>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                </div>
                <div class="grow flex flex-col">
                    <div class="grow min-h-0 flex flex-col gap-6">
                        <div>
                            <div class="text-[#737373] text-lg">音频文件的语音</div>
                            <div class="flex flex-wrap gap-4 mt-6">
                                <div
                                    v-for="(item, index) in languageList"
                                    :key="index"
                                    class="bg-[#F5F5F5] rounded-lg px-6 py-1.5 cursor-pointer"
                                    :class="{
                                        'bg-[#f1f6ff] text-primary': formData.language === item.code,
                                    }"
                                    @click="formData.language = item.code">
                                    {{ item.name }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#737373] text-lg">翻译目标语言</div>
                            <div class="mt-6">
                                <ElSelect v-model="formData.translation" placeholder="请选择">
                                    <ElOption
                                        v-for="(item, index) in targetLanguageList"
                                        :key="index"
                                        :label="item.name"
                                        :value="item.code"></ElOption>
                                </ElSelect>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#737373] text-lg">区分发言人</div>
                            <div class="flex flex-wrap gap-4 mt-6">
                                <div
                                    v-for="(item, index) in speakerOptions"
                                    :key="index"
                                    class="bg-[#F5F5F5] rounded-lg px-6 py-1.5 cursor-pointer"
                                    :class="{
                                        'bg-primary-light-9 text-primary': formData.speaker === item.value,
                                    }"
                                    @click="formData.speaker = item.value">
                                    {{ item.label }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between gap-x-4">
                        <div class="flex-1">
                            <ElTooltip placement="top">
                                <ElButton
                                    class="w-full !h-[39px]"
                                    type="primary"
                                    :disabled="!isAllUploadSuccess || getTokensValue <= 0"
                                    :loading="isLock"
                                    @click="lockSubmit">
                                    <div class="flex items-center gap-1">
                                        开始转写<template v-if="getTokensValue">({{ getTokensValue }}算力)</template>
                                        <Icon name="el-icon-Warning"></Icon>
                                    </div>
                                </ElButton>
                                <template #content>
                                    <div>
                                        <div>
                                            {{ tokensValue.score }}
                                            {{ tokensValue.unit }}
                                        </div>
                                    </div>
                                </template>
                            </ElTooltip>
                        </div>
                        <ElButton class="flex-1 !h-[39px]" @click="close">取消</ElButton>
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
import { Plus, Delete } from "@element-plus/icons-vue";
import { formatFileSize } from "@/utils/util";
import Popup from "@/components/popup/index.vue";
import useHandleApi from "../../../_hooks/useHandleApi";
import { useUserStore } from "@/stores/user";

const router = useRouter();

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
