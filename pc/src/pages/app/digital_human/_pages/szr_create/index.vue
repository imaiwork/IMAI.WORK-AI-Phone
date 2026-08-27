<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 flex gap-6 flex-col overflow-hidden bg-white rounded-[20px] border border-br py-6 min-h-0">
            <div class="w-full flex flex-col px-6">
                <div class="upload-container" v-if="!formData.url">
                    <div class="upload-content">
                        <div class="upload-title">领先的定制数字人形象</div>
                        <div class="text-[14px] text-[#ffffff]/90 mt-[12px] font-medium tracking-wide">
                            开始创作，打造您的专属数字人分身
                        </div>
                        <ElButton
                            type="primary"
                            class="mt-8 !h-[54px] !w-[220px] !rounded-full !text-base !font-black shadow-2xl hover:scale-105 transition-all active:scale-95"
                            @click="toAnchorCreate">
                            定制形象
                        </ElButton>
                    </div>
                </div>

                <div v-else class="w-full h-[450px] relative rounded-[24px] overflow-hidden border border-br bg-black">
                    <video :src="formData.url" class="w-full h-full object-contain" controls />
                </div>
            </div>

            <div class="grow min-h-0 flex flex-col">
                <div class="flex items-center justify-between px-8 mb-2 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-lg font-[900] text-[#1E293B]">选择数字人形象</span>
                            <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                        </div>
                        <div class="flex items-center p-1 bg-slate-100 rounded-xl shrink-0">
                            <button
                                v-for="item in cloneModeTabs"
                                :key="item.value"
                                type="button"
                                class="h-8 px-3 rounded-lg text-xs font-black transition-all flex items-center gap-1.5"
                                :class="
                                    currCloneMode === item.value
                                        ? item.value === CloneModeEnum.PRO
                                            ? 'bg-white text-[#7B61FF] shadow-sm'
                                            : 'bg-white text-primary shadow-sm'
                                        : 'text-slate-400 hover:text-slate-600'
                                "
                                @click="handleSelectCloneMode(item.value)">
                                <span>{{ item.name }}</span>
                            </button>
                        </div>
                    </div>
                    <ElTooltip content="刷新形象库">
                        <ElButton icon="el-icon-Refresh" circle @click="resetAnchorPage"> </ElButton>
                    </ElTooltip>
                </div>

                    <div class="grow min-h-0">
                        <ElScrollbar :distance="20" @end-reached="loadMoreAnchor">
                            <div
                                v-if="anchorListLoading"
                                class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                                <div
                                    v-for="i in 8"
                                    :key="i"
                                    class="aspect-[4/5] rounded-[24px] bg-slate-100 animate-pulse" />
                            </div>
                            <div v-else class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                                <div
                                    class="aspect-[4/5] rounded-[24px] border-2 border-dashed border-slate-200 bg-[#f8fafc]/50 hover:border-primary hover:bg-[#0065fb]/5 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 group"
                                    @click="toCloneAnchor()">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-white shadow-light flex items-center justify-center group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
                                        <Icon name="el-icon-Plus" :size="24" />
                                    </div>
                                    <span class="text-[13px] font-black text-slate-500 group-hover:text-primary"
                                        >形象克隆</span
                                    >
                                </div>

                                <div
                                    class="aspect-[4/5] cursor-pointer rounded-[24px] overflow-hidden relative border-2 transition-all group"
                                    v-for="(item, index) in anchorPager.lists"
                                    :key="item.id"
                                    :class="
                                        currentAnchorIndex === index ? 'border-primary ' : 'border-[transparent]'
                                    "
                                    @click="handleSelectAnchor(index)">
                                    <ElImage :src="item.pic" fit="cover" lazy class="w-full h-full" />

                                    <div
                                        class="w-7 h-7 flex items-center justify-center absolute bottom-2 right-2 z-[10] rounded-full"
                                        @click.stop="openVideo(item.result_url)">
                                        <play-btn :icon-size="24" />
                                    </div>
                                    <div
                                        v-if="currentAnchorIndex == index"
                                        class="absolute top-3 right-3 w-7 h-7 bg-primary rounded-full flex items-center justify-center border-2 border-white z-20 animate-in zoom-in duration-300">
                                        <Icon name="el-icon-Check" color="#fff" :size="16" />
                                    </div>
                                    <div
                                        class="absolute inset-0 bg-[#000000]/60 backdrop-blur-[1px] z-[20] flex items-center justify-center p-2 text-center"
                                        v-if="item.status == 0">
                                        <span
                                            class="bg-primary text-[10px] font-black text-white px-2 py-1 rounded-full animate-pulse"
                                            >训练中</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <load-text
                                :is-load="anchorPager.isLoad"
                                v-if="!anchorListLoading && anchorPager.lists.length > 0"></load-text>
                        </ElScrollbar>
                    </div>
                </div>
        </div>
        <div
            class="basis-[35%] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br overflow-hidden"
            v-spin="{ show: loading, text: '加载中...' }">
            <header class="mb-5">
                <h2 class="text-[24px] font-medium text-slate-800 tracking-tight">生成设置</h2>
                <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
            </header>
            <div class="px-5 py-2 rounded-2xl flex items-center gap-x-3 bg-slate-50 border border-br mb-6">
                <div class="text-[13px] font-black text-[#64748B]">视频名称</div>
                <div class="w-[1px] h-3 bg-[#E2E8F0]"></div>
                <div class="flex-1">
                    <ElInput
                        v-model="formData.name"
                        class="custom-input"
                        placeholder="请输入名称"
                        maxlength="20"
                        :input-style="{ textAlign: 'right', fontSize: '15px', fontWeight: '900', color: '#1E293B' }"
                        clearable />
                </div>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar class="pr-2">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-[15px] font-[900] text-[#1E293B] flex items-center gap-2">
                                <Icon name="el-icon-Document" color="var(--el-color-primary)" /> 口播来源
                            </div>
                            <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl">
                                <button
                                    type="button"
                                    class="h-8 px-4 rounded-lg text-xs font-black transition-all"
                                    :class="
                                        formData.audio_type === CreateTypeEnum.TEXT
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600'
                                    "
                                    @click="handleAudioTypeChange(CreateTypeEnum.TEXT)">
                                    文案
                                </button>
                                <button
                                    type="button"
                                    class="h-8 px-4 rounded-lg text-xs font-black transition-all"
                                    :class="
                                        formData.audio_type === CreateTypeEnum.AUDIO
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600'
                                    "
                                    @click="handleAudioTypeChange(CreateTypeEnum.AUDIO)">
                                    音频
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="formData.audio_type === CreateTypeEnum.TEXT"
                            class="border border-br rounded-2xl p-4 bg-slate-50 group">
                            <ElInput
                                v-model="formData.msg"
                                class="custom-textarea"
                                type="textarea"
                                placeholder="请输入您的文案..."
                                resize="none"
                                :maxlength="textLimit"
                                :rows="10" />
                            <div class="flex items-center justify-between mt-4">
                                <div class="flex gap-2">
                                    <button
                                        @click="handleRandomCopywriter"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-br rounded-2xl text-xs font-medium hover:border-primary hover:text-primary transition-all">
                                        随机
                                    </button>
                                    <button
                                        @click="openGeneratePrompt"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-[#0065FB]/5 text-primary rounded-2xl text-xs font-black hover:bg-[#0065FB]/10 transition-all">
                                        AI 生成
                                    </button>
                                </div>
                                <div class="text-[11px] font-medium text-[#CBD5E1]">
                                    {{ formData.msg.length }}/{{ textLimit }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="border border-br rounded-2xl bg-slate-50 overflow-hidden">
                            <div
                                v-if="formData.audio_url"
                                class="flex items-center gap-3 p-4 bg-white border-b border-slate-100">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#0065FB]/5 flex items-center justify-center text-primary">
                                    <Icon name="el-icon-Headset" :size="18" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-black text-slate-700 truncate">
                                        {{ formData.audio_name || "已上传音频" }}
                                    </div>
                                    <audio :src="formData.audio_url" controls class="w-full h-8 mt-2"></audio>
                                </div>
                                <button
                                    class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-red-50 hover:text-red-500 flex items-center justify-center text-slate-400 transition-all"
                                    @click="handleRemoveAudio">
                                    <Icon name="el-icon-Delete" :size="14" />
                                </button>
                            </div>
                            <Upload
                                ref="audioUploadRef"
                                class="w-full"
                                type="audio"
                                drag
                                show-progress
                                :limit="1"
                                :accept="UPLOAD_AUDIO_ACCEPT"
                                :show-file-list="false"
                                :max-size="UPLOAD_AUDIO_MAX_SIZE"
                                @success="handleAudioUploadSuccess">
                                <div
                                    class="w-full min-h-[132px] flex items-center justify-center p-4 cursor-pointer group">
                                    <div class="flex flex-col items-center gap-2">
                                        <div
                                            class="w-11 h-11 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-300 group-hover:text-primary group-hover:border-primary/30 transition-all">
                                            <Icon name="el-icon-UploadFilled" :size="20" />
                                        </div>
                                        <div class="text-sm font-black text-slate-500 group-hover:text-primary">
                                            {{ formData.audio_url ? "重新上传口播音频" : "点击上传口播音频" }}
                                        </div>
                                        <div class="text-xs text-slate-300">支持 MP3 / WAV / M4A · 最大 50MB</div>
                                    </div>
                                </div>
                            </Upload>
                        </div>
                    </div>

                    <div class="mb-6" v-if="showDriveModel">
                        <div class="text-[15px] font-[900] text-[#1E293B] mb-3">驱动模型</div>
                        <ElTooltip :disabled="canChangeDriveModel" :content="driveModelTip" placement="top">
                            <div>
                                <ElSelect
                                    class="w-full drive-model-select"
                                    :model-value="formData.model_version || undefined"
                                    placeholder="请选择驱动模型"
                                    :disabled="!canChangeDriveModel"
                                    @change="handleDriveModelChange">
                                    <ElOption
                                        v-for="item in driveModelOptions"
                                        :key="item.model_version"
                                        :label="item.name"
                                        :value="item.model_version">
                                        <div class="flex items-center gap-2">
                                            <ElImage
                                                v-if="item.logo"
                                                :src="item.logo"
                                                fit="cover"
                                                class="w-5 h-5 rounded-md shrink-0" />
                                            <span>{{ item.name }}</span>
                                        </div>
                                    </ElOption>
                                </ElSelect>
                            </div>
                        </ElTooltip>
                        <div class="text-[11px] text-slate-400 mt-2">不同驱动模型的合成效果与算力消耗不同</div>
                    </div>

                    <div class="mb-6">
                        <div class="text-[15px] font-[900] text-[#1E293B] mb-3">音色选择</div>
                        <button
                            type="button"
                            class="w-full min-h-[54px] px-4 rounded-2xl border border-br bg-slate-50 hover:border-primary/40 hover:bg-[#0065fb]/5 transition-all flex items-center justify-between gap-3 text-left"
                            @click="openToneDialog">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0"
                                    :class="
                                        isOriginalToneSelected
                                            ? 'bg-[#F0FDF4] border-[#BBF7D0] text-[#16A34A]'
                                            : 'bg-white border-slate-100 text-primary'
                                    ">
                                    <Icon name="el-icon-Microphone" :size="17" />
                                </div>
                                <div class="min-w-0">
                                    <div
                                        class="text-sm font-black truncate"
                                        :class="
                                            isOriginalToneSelected
                                                ? 'text-[#16A34A]'
                                                : formData.voice_id
                                                ? 'text-slate-700'
                                                : 'text-slate-400'
                                        ">
                                        {{ formData.voice_name || "请选择声音" }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ isOriginalToneSelected ? originalToneDesc : "选择音色库进行合成" }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <ElTag
                                    v-if="isOriginalToneSelected"
                                    size="small"
                                    round
                                    effect="light"
                                    type="success">
                                    原声
                                </ElTag>
                                <ElTag v-else-if="formData.voice_id" size="small" round effect="light">
                                    {{ formData.voice_type === 0 ? "系统" : "定制" }}
                                </ElTag>
                                <Icon name="el-icon-ArrowRight" color="#94a3b8" :size="16" />
                            </div>
                        </button>
                    </div>

                    <div class="bg-slate-50 border border-br rounded-2xl p-5 mb-6" v-if="clipConfig.is_open">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-[15px] font-[900] text-[#1E293B]">视频包装</div>
                                <div class="text-[11px] text-slate-400 mt-1">生成时自动完成视频包装处理</div>
                            </div>
                            <ElSwitch v-model="formData.ai_clip_enabled" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-br rounded-2xl p-5 mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-[15px] font-[900] text-[#1E293B]">声音音量</div>
                                <div class="text-[11px] text-slate-400 mt-1">调节合成配音音量</div>
                            </div>
                            <span class="text-[13px] font-black text-primary">
                                {{ Math.round(formData.extra.volume * 100) }}%
                            </span>
                        </div>
                        <ElSlider
                            v-model="formData.extra.volume"
                            :min="0.1"
                            :max="1"
                            :step="0.1"
                            :show-tooltip="false" />
                    </div>
                </ElScrollbar>
            </div>

            <div class="mt-6 pt-6 border-t border-br">
                <CreatePanel
                    :form-data="formData"
                    :text-limit="textLimit"
                    @success="handleCreateSuccess"
                    @error="handleCreateError" />
            </div>
        </div>
    </div>
    <upload-form
        v-if="showUpload"
        ref="uploadFormRef"
        @create="handleAnchorCreate"
        @close="showUpload = false"
        @play-video="openVideo"></upload-form>
    <preview-video
        v-if="showExampleVideo"
        ref="videoPreviewPlayerRef"
        @close="showExampleVideo = false"></preview-video>
    <choose-tone
        v-if="showToneDialog"
        ref="chooseToneRef"
        :limit="1"
        :model-version="toneListModelVersion"
        :active-tone="activeTone"
        :show-free-tone="false"
        show-original-tone
        :original-tone-title="originalToneTitle"
        :original-tone-desc="originalToneDesc"
        :original-selected="isOriginalToneSelected"
        @confirm="handleToneConfirm"
        @original="handleChooseOriginalTone"
        @close="showToneDialog = false" />
    <generate-prompt
        v-if="showGeneratePrompt"
        ref="generatePromptRef"
        :show-title="false"
        :prompt-type="CreateVideoTypeEnum.DIGITAL_HUMAN"
        :max-size="textLimit"
        @close="showGeneratePrompt = false"
        @use-content="getGenerateContent"></generate-prompt>
</template>
<script setup lang="ts">
import { dayjs, ElInput } from "element-plus";
import { getPublicAnchorList } from "@/api/digital_human";
import { getClipConfig } from "@/api/app";
import {
    ModeTypeEnum,
    CreateTypeEnum,
    CloneModeEnum,
    DigitalHumanModelVersionEnum,
    SidebarTypeEnum,
    CreateVideoTypeEnum,
    AnchorListProFilterEnum,
    cloneModeToIsPro,
    DIGITAL_HUMAN_DRIVE_MODEL_VERSIONS,
    CHANJING_ORIGINAL_VOICE_ID,
    SPEECH_TEXT_LIMIT,
} from "@/pages/app/digital_human/_enums";
import { useAppStore } from "@/stores/app";
import GeneratePrompt from "@/pages/app/digital_human/_components/generate-prompt.vue";
import ChooseTone from "@/pages/app/digital_human/_components/choose-tone.vue";
import Upload from "@/components/upload/index.vue";
import UploadForm from "./_components/upload-form.vue";
import CreatePanel from "./_components/create-panel.vue";

const router = useRouter();
const appStore = useAppStore();

const nameInputRef = shallowRef<InstanceType<typeof ElInput>>();
const audioUploadRef = shallowRef<InstanceType<typeof Upload>>();
const UPLOAD_AUDIO_ACCEPT = ".mp3,.wav,.m4a,.MP3,.WAV,.M4A";
const UPLOAD_AUDIO_MAX_SIZE = 50;

const formData = reactive<Record<string, any>>({
    url: "",
    name: dayjs().format("YYYYMMDDHHmmss").substring(2) + "数字人口播",
    anchor_name: "",
    anchor_id: "",
    pic: "",
    model_version: "",
    audio_type: CreateTypeEnum.TEXT,
    voice_id: "",
    voice_name: "",
    audio_name: "",
    msg: "",
    audio_duration: 0,
    audio_url: "",
    voice_type: 1,
    ai_clip_enabled: 1,
    extra: {
        video_count: 1,
        volume: 0.3,
    },
});

const randomCopywriter = [
    `你是不是也有过这样的时刻？很想放弃，但又不甘心；很累很累，却还在硬撑。没人知道你经历了什么，但你知道，你不是为了谁在坚持，而是为了不辜负自己。别怕慢，只要不退，就已经很勇敢了。`,
    `总有人说你太敏感、太情绪化，可你只是太真诚了。你把别人放心上，却常常忽略了自己。没关系，慢慢来，允许自己不完美，也允许偶尔情绪失控。别总苛求自己坚强，温柔一点，你值得被好好对待。`,
    `夜深了，是不是又睡不着？回想白天的种种，总觉得哪里没做好。但你已经尽力了，真的。别再为过去懊悔，也别为未来焦虑，此刻的你，只需要好好休息。熬过去，天亮之后，一切都会好起来的。`,
];

const clipConfig = reactive({
    is_open: false,
});

const toCloneAnchor = () => {
    navigateTo(`/app/digital_human?type=${SidebarTypeEnum.ANCHOR_CLONE}`);
};

// 打开示例视频
const showExampleVideo = ref(false);
const videoPreviewPlayerRef = shallowRef();
const openVideo = async (url: string) => {
    showExampleVideo.value = true;
    await nextTick();
    videoPreviewPlayerRef.value?.open();
    videoPreviewPlayerRef.value?.setUrl(url);
};

/** 形象操作 Start */

// 标准版→is_pro=1，优质版→is_pro=2（创建仍用 clone_mode 2/3）
const cloneModeTabs = [
    {
        value: CloneModeEnum.FAST,
        name: "标准版",
        sub: `${SPEECH_TEXT_LIMIT.DEFAULT}字`,
        max: SPEECH_TEXT_LIMIT.DEFAULT,
    },
    {
        value: CloneModeEnum.PRO,
        name: "优质版",
        sub: `${SPEECH_TEXT_LIMIT.DEFAULT}字`,
        max: SPEECH_TEXT_LIMIT.DEFAULT,
    },
] as const;
const currCloneMode = ref<CloneModeEnum>(CloneModeEnum.FAST);
const anchorListLoading = ref(false);

// 当前形象索引
const currentAnchorIndex = ref<number>(-1);
const anchorQueryParams = reactive({
    status: 1,
    filter: 2,
    page_no: 1,
    page_size: 20,
    is_pro: AnchorListProFilterEnum.NORMAL as number,
});
const {
    pager: anchorPager,
    getLists: getAnchorLists,
    resetPage: resetAnchorPage,
} = usePaging({
    fetchFun: getPublicAnchorList,
    params: anchorQueryParams,
    isScroll: true,
});

const clearSelectedAnchor = () => {
    currentAnchorIndex.value = -1;
    formData.url = "";
    formData.pic = "";
    formData.anchor_id = "";
    formData.anchor_name = "";
    formData.width = 0;
    formData.height = 0;
    if (isOriginalVoice.value) {
        formData.voice_id = "";
        formData.voice_name = "";
    }
};

const selectDefaultAnchor = () => {
    if (!anchorPager.lists.length) {
        clearSelectedAnchor();
        return;
    }
    const index = anchorPager.lists.findIndex((item: any) => getAnchorStatus(item.status, item.source_type) == 1);
    if (index != -1) {
        handleSelectAnchor(index, false);
    } else {
        handleSelectAnchor(0, false);
    }
};

const handleSelectCloneMode = async (mode: CloneModeEnum) => {
    if (currCloneMode.value === mode || anchorListLoading.value) return;
    currCloneMode.value = mode;
    anchorQueryParams.is_pro = cloneModeToIsPro(mode);
    clearSelectedAnchor();
    // 优质版固定闪剪驱动模型
    if (mode === CloneModeEnum.PRO) {
        formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
    }
    trimMsgByLimit();
    const tab = cloneModeTabs.find((item) => item.value === mode);
    if (tab) {
        feedback.msgSuccess(`已切换到${tab.name}，最长 ${textLimit.value} 字`);
    }
    anchorListLoading.value = true;
    try {
        await resetAnchorPage();
        selectDefaultAnchor();
    } finally {
        anchorListLoading.value = false;
    }
};

const loadMoreAnchor = async (e) => {
    if (e == "bottom") {
        if (!anchorPager.isLoad || anchorPager.loading) return;
        anchorQueryParams.page_no++;
        await getAnchorLists();
    }
};

const getAnchorStatus = (status: number, source_type: string) => {
    const anchorStatusMapping: Record<string, any> = {
        human_anchor: {
            1: 1,
            2: 2,
            default: 0,
        },
        public_anchor: {
            1: 0,
            2: 1,
            3: 2,
            default: 0,
        },
    };
    return anchorStatusMapping[source_type][status] || anchorStatusMapping[source_type]?.["default"];
};

// 选择形象
const handleSelectAnchor = (index: number, isMsg = true) => {
    // 更新当前选中的形象索引
    const { status, name, result_url, pic, width, height, source_type, model_version } = anchorPager.lists[index];
    const anchorStatus = getAnchorStatus(status, source_type);
    if (anchorStatus != 1 && isMsg) {
        feedback.msgWarning("该形象正在克隆中，请稍后再试");
        return;
    }
    currentAnchorIndex.value = index;

    const prevModelVersion = formData.model_version;
    if (currCloneMode.value === CloneModeEnum.PRO) {
        // 优质版固定闪剪
        formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
    } else if (Number(model_version) === 0) {
        // 公共形象可切换驱动模型，未选时默认闪剪
        if (!DIGITAL_HUMAN_DRIVE_MODEL_VERSIONS.includes(Number(formData.model_version))) {
            formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
        }
    } else {
        formData.model_version = Number(model_version);
    }

    formData.anchor_name = name;
    formData.url = result_url;
    formData.pic = pic;
    formData.width = width;
    formData.height = height;
    applyAnchorId();

    // 模型变化或未手动选过音色时，按当前驱动模型重置音色
    if (prevModelVersion !== formData.model_version || isOriginalVoice.value) {
        resetToneByModel();
    }
    if (prevModelVersion !== formData.model_version) {
        trimMsgByLimit();
    }
};

const localAnchorLists = ref<any[]>([]);
const handleAnchorCreate = async (data?: any) => {
    const { modelType } = data;
    if (modelType === ModeTypeEnum.VIDEO) {
        const anchorData = { ...data.formData, is_vanish: true };
        localAnchorLists.value.unshift(anchorData);
        anchorPager.lists.unshift(anchorData);
        currentAnchorIndex.value = 0;
        setFormData(anchorData, formData);
    } else if (modelType === ModeTypeEnum.FIGURE) {
        await resetAnchorPage();
        anchorPager.lists.unshift(...localAnchorLists.value);
        currentAnchorIndex.value = 0;
        setFormData(anchorPager.lists[0], formData);
    }
    if (isOriginalVoice.value) {
        applyOriginalTone(anchorPager.lists[0]);
    }
};

/** 形象操作 End */

/** 驱动模型 Start */

const driveModelOptions = computed(() => {
    const humanModels = appStore.getAiModels.humanModels || [];
    return humanModels
        .filter((item: any) => DIGITAL_HUMAN_DRIVE_MODEL_VERSIONS.includes(Number(item.model_version)))
        .map((item: any) => ({
            model_version: Number(item.model_version),
            name: item.name,
            logo: item.logo,
        }));
});

// 优质版固定闪剪，不展示驱动模型选择
const showDriveModel = computed(() => currCloneMode.value !== CloneModeEnum.PRO && driveModelOptions.value.length > 0);

// 仅公共形象（model_version=0）可切换驱动模型，私有形象跟随形象自身通道
const canChangeDriveModel = computed(() => {
    const anchor = anchorPager.lists[currentAnchorIndex.value];
    return !!anchor && Number(anchor.model_version) === 0;
});

const driveModelTip = computed(() => {
    if (currentAnchorIndex.value === -1) return "请先选择数字人形象";
    return "该形象无法更改驱动模型";
});

// 公共形象在各通道有独立 anchor_id，需按当前驱动模型取对应值
const applyAnchorId = () => {
    const anchor = anchorPager.lists[currentAnchorIndex.value];
    if (!anchor) return;
    const { anchor_id, anchor_ids: { chanjing_anchor_id, shanjian_anchor_id } = {} as any } = anchor;
    if (Number(anchor.model_version) !== 0) {
        formData.anchor_id = anchor_id;
        return;
    }
    formData.anchor_id =
        formData.model_version === DigitalHumanModelVersionEnum.CHANJING
            ? chanjing_anchor_id || anchor_id
            : shanjian_anchor_id || anchor_id;
};

const handleDriveModelChange = (modelVersion: number) => {
    if (formData.model_version === modelVersion) return;
    formData.model_version = modelVersion;
    applyAnchorId();
    resetToneByModel();
    trimMsgByLimit();
};

/** 驱动模型 End */

const handleRandomCopywriter = () => {
    formData.audio_type = CreateTypeEnum.TEXT;
    formData.msg = randomCopywriter[Math.floor(Math.random() * randomCopywriter.length)];
};

/** 文案操作 Start */
const showGeneratePrompt = ref(false);
const generatePromptRef = shallowRef<InstanceType<typeof GeneratePrompt>>();
const openGeneratePrompt = async () => {
    if (!formData.model_version) {
        feedback.msgWarning("请先选择形象~");
        return;
    }
    showGeneratePrompt.value = true;
    await nextTick();
    generatePromptRef.value?.open();
};

const getGenerateContent = (res: any[]) => {
    if (res.length > 0) {
        formData.audio_type = CreateTypeEnum.TEXT;
        formData.msg = res[0].content;
    }
};

/** 文案操作 End */

const handleAudioTypeChange = (type: CreateTypeEnum) => {
    formData.audio_type = type;
};

const handleAudioUploadSuccess = (res: any) => {
    const { uri, url, name, duration } = res.data || {};
    const audioUrl = uri || url;
    if (!audioUrl) return;
    formData.audio_type = CreateTypeEnum.AUDIO;
    formData.audio_url = audioUrl;
    formData.audio_name = name || "口播音频";
    formData.audio_duration = duration || 0;
};

const handleRemoveAudio = () => {
    formData.audio_url = "";
    formData.audio_name = "";
    formData.audio_duration = 0;
};

const uploadFormRef = shallowRef<InstanceType<typeof UploadForm>>();
const showUpload = ref(false);

const showToneDialog = ref(false);
const chooseToneRef = shallowRef<InstanceType<typeof ChooseTone>>();

// 是否使用形象原声（默认原声，手动选音色后关闭，切换形象时原声跟随更新）
const isOriginalVoice = ref(true);
const isChanjing = computed(() => formData.model_version === DigitalHumanModelVersionEnum.CHANJING);
// 蝉镜原声是「视频原音」，用占位 voice_id 表示
const isChanjingOriginalTone = computed(
    () => isChanjing.value && formData.voice_id == CHANJING_ORIGINAL_VOICE_ID,
);
const isOriginalToneSelected = computed(
    () => isChanjingOriginalTone.value || (isOriginalVoice.value && !!formData.voice_id),
);

const applyOriginalTone = (anchor?: any) => {
    const voiceId = anchor?.extra_info?.shanjian_voice_id;
    if (voiceId) {
        formData.voice_id = voiceId;
        formData.voice_name = "形象原声";
    } else {
        formData.voice_id = "";
        formData.voice_name = "";
    }
    formData.voice_type = 1;
};

const originalToneTitle = computed(() => (isChanjing.value ? "使用视频原音" : "使用形象原声"));
const originalToneDesc = computed(() =>
    isChanjing.value ? "使用形象视频中的原始声音" : "使用当前形象的原始声音",
);

const applyChanjingOriginalTone = () => {
    formData.voice_id = CHANJING_ORIGINAL_VOICE_ID;
    formData.voice_name = "视频原音";
    formData.voice_type = 1;
};

// 蝉镜用视频原音，闪剪用形象原声
const resetToneByModel = () => {
    isOriginalVoice.value = true;
    if (isChanjing.value) {
        applyChanjingOriginalTone();
        return;
    }
    applyOriginalTone(anchorPager.lists[currentAnchorIndex.value]);
};

const activeTone = computed(() => {
    // 原声模式下不回显到音色列表，避免高亮到同 id 的普通音色
    if (!formData.voice_id || isOriginalVoice.value) return null;
    return {
        voice_id: formData.voice_id,
        name: formData.voice_name,
        builtin: formData.voice_type,
        model_version: formData.model_version,
    };
});
const openToneDialog = async () => {
    showToneDialog.value = true;
    await nextTick();
    chooseToneRef.value?.open();
};
const handleToneConfirm = (tone: any) => {
    if (!tone) {
        // 原声模式下未选择任何音色直接确定，保持原声不变
        if (isOriginalVoice.value) return;
        formData.voice_id = "";
        formData.voice_name = "";
        formData.voice_type = 1;
        return;
    }
    formData.voice_id = tone.voice_id || tone.code || tone.id || "";
    formData.voice_name = tone.name;
    formData.voice_type = tone.builtin === 0 ? 0 : 1;
    isOriginalVoice.value = false;
};

const handleChooseOriginalTone = () => {
    const anchor = anchorPager.lists[currentAnchorIndex.value];
    if (!anchor) {
        feedback.msgWarning("请先选择形象");
        return;
    }
    if (isChanjing.value) {
        isOriginalVoice.value = true;
        applyChanjingOriginalTone();
        return;
    }
    if (!anchor.extra_info?.shanjian_voice_id) {
        feedback.msgWarning("当前形象暂无原声，请选择其他音色");
        return;
    }
    isOriginalVoice.value = true;
    applyOriginalTone(anchor);
};

// 文案字数：蝉镜无包装 4000，蝉镜有包装 / 标准版 / 优质版 1500
const textLimit = computed(() => {
    if (formData.model_version == DigitalHumanModelVersionEnum.CHANJING) {
        return clipConfig.is_open && formData.ai_clip_enabled == 1
            ? SPEECH_TEXT_LIMIT.DEFAULT
            : SPEECH_TEXT_LIMIT.CHANJING_NO_PACK;
    }
    return cloneModeTabs.find((item) => item.value === currCloneMode.value)?.max ?? SPEECH_TEXT_LIMIT.DEFAULT;
});

const trimMsgByLimit = () => {
    if (formData.msg?.length > textLimit.value) {
        formData.msg = formData.msg.slice(0, textLimit.value);
    }
};

watch([() => clipConfig.is_open, () => formData.ai_clip_enabled], () => {
    trimMsgByLimit();
});

// 驱动模型音色 + MiniMax（蝉镜/闪剪均支持）
const toneListModelVersion = computed(() => {
    if (!formData.model_version) return "";
    return [
        formData.model_version,
        DigitalHumanModelVersionEnum.MINIMAX_HD,
        DigitalHumanModelVersionEnum.MINIMAX_TURBO,
    ].join(",");
});

// 创建成功
const handleCreateSuccess = () => {
    useNuxtApp().$confirm({
        title: "任务已提交",
        message: "创建成功，请在历史记录查看",
        confirmButtonText: "前往查看",
        cancelButtonText: "取消",
        onConfirm: () => {
            navigateTo(`/app/digital_human?type=${SidebarTypeEnum.MY_WORKS}`);
        },
        onCancel: () => {
            window.location.reload();
        },
    });
};

// 创建失败
const handleCreateError = (error: any) => {
    const { type } = error;
    switch (type) {
        case "name":
            nameInputRef.value?.focus();
            break;
    }
};

const getClipConfigData = async () => {
    const { code } = await getClipConfig();
    clipConfig.is_open = code == 10000;
    formData.ai_clip_enabled = clipConfig.is_open ? 1 : 0;
};

const toAnchorCreate = () => {
    navigateTo(`/app/digital_human?type=${SidebarTypeEnum.ANCHOR_CLONE}`);

    router.push({
        path: "/app/digital_human",
        query: {
            type: SidebarTypeEnum.ANCHOR_CLONE,
        },
    });
};

// 外部模块(如 GEO)带文案跳转过来时,把口播稿填进文案输入框。
// 必须放在 selectDefaultAnchor 之后:textLimit 依赖已选形象的档位,
// 提前填会按默认上限截断。
const route = useRoute();
const { takeOnce } = useCopywritingHandoff();
const applyHandoffCopywriting = () => {
    const payload = takeOnce(route.query.prefill as string);
    if (!payload?.content) return;
    if (payload.content.length > textLimit.value) {
        feedback.msgWarning(`内容过长，将截取前${textLimit.value}字`);
    }
    formData.msg = payload.content;
    trimMsgByLimit();
    feedback.msgSuccess(payload.from ? `已带入${payload.from}的口播文案` : "已带入口播文案");
};

const loading = ref(true);
const init = async () => {
    try {
        anchorListLoading.value = true;
        // 驱动模型列表通常已随全局配置加载，这里只做兜底，失败不阻断页面
        if (!driveModelOptions.value.length) {
            await appStore.getAiModelsData().catch(() => {});
        }
        await getAnchorLists();
        selectDefaultAnchor();
        await getClipConfigData();
        applyHandoffCopywriting();
    } finally {
        anchorListLoading.value = false;
        loading.value = false;
    }
};

init();
</script>
<style scoped lang="scss">
.upload-container {
    @apply h-[450px] w-full flex flex-col items-center justify-center bg-no-repeat bg-center bg-cover rounded-[24px] relative overflow-hidden;
    background-image: url("@/pages/app/digital_human/_assets/images/upload_bg.png");

    &::before {
        content: "";
        @apply absolute inset-0 bg-[#000000]/5 z-0;
    }

    .upload-content {
        @apply relative z-10 flex flex-col items-center;
    }
}

.upload-title {
    background: linear-gradient(90deg, #fff 24.36%, var(--el-color-primary) 65.91%, #e02188 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 36px;
    font-weight: 900;
    letter-spacing: -0.01em;
}

:deep(.el-scrollbar__bar.is-vertical) {
    @apply w-1;
}

.drive-model-select {
    :deep(.el-select__wrapper) {
        @apply min-h-[54px] rounded-2xl bg-slate-50 border border-br;
        box-shadow: none;
    }

    :deep(.el-select__selected-item) {
        @apply text-sm font-black text-slate-700;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}
</style>
