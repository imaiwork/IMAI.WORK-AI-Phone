<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 flex gap-6 flex-col overflow-hidden bg-white rounded-[20px] border border-br py-6">
            <div class="w-full flex flex-col px-6">
                <div class="upload-container" v-if="!formData.url">
                    <div class="upload-content">
                        <div class="upload-title">领先的定制数字人形象</div>
                        <div class="text-[14px] text-white/90 mt-[12px] font-medium tracking-wide">
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
                <div class="flex items-center justify-between px-8 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-[900] text-[#1E293B]">选择数字人形象</span>
                        <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                    </div>
                    <ElTooltip content="刷新形象库">
                        <ElButton icon="el-icon-Refresh" circle @click="resetAnchorPage"> </ElButton>
                    </ElTooltip>
                </div>

                <div class="grow min-h-0">
                    <ElScrollbar :distance="20" @end-reached="loadMoreAnchor">
                        <div class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
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
                                :class="currentAnchorIndex === index ? 'border-primary ' : 'border-[transparent]'"
                                @click="handleSelectAnchor(index)">
                                <ElImage :src="item.pic" fit="cover" lazy class="w-full h-full" />

                                <div
                                    class="w-7 h-7 flex items-center justify-center absolute bottom-2 right-2 z-[10] rounded-full bg-black/20 backdrop-blur-md text-white hover:bg-primary transition-colors"
                                    @click.stop="openVideo(item.result_url)">
                                    <Icon name="local-icon-play2" :size="28"></Icon>
                                </div>
                                <div
                                    v-if="currentAnchorIndex == index"
                                    class="absolute top-3 right-3 w-7 h-7 bg-primary rounded-full flex items-center justify-center border-2 border-white z-20 animate-in zoom-in duration-300">
                                    <Icon name="el-icon-Check" color="#fff" :size="16" />
                                </div>
                                <div
                                    class="absolute inset-0 bg-black/60 backdrop-blur-[1px] z-[20] flex items-center justify-center p-2 text-center"
                                    v-if="item.status == 0">
                                    <span
                                        class="bg-primary text-[10px] font-black text-white px-2 py-1 rounded-full animate-pulse"
                                        >训练中</span
                                    >
                                </div>
                            </div>
                        </div>

                        <load-text :is-load="anchorPager.isLoad" v-if="anchorPager.lists.length > 0"></load-text>
                    </ElScrollbar>
                </div>
            </div>
        </div>
        <div
            class="w-[380px] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br overflow-hidden"
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
                        <div class="text-[15px] font-[900] text-[#1E293B] mb-3 flex items-center gap-2">
                            <Icon name="el-icon-Document" color="var(--el-color-primary)" /> 文案输入
                        </div>
                        <div class="border border-br rounded-2xl p-4 bg-slate-50 group">
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
                    </div>

                    <div class="mb-6">
                        <div class="text-[15px] font-[900] text-[#1E293B] mb-3">训练模型</div>
                        <ElSelect
                            v-model="formData.model_version"
                            class="w-full custom-select"
                            placeholder="请选择训练模型"
                            :show-arrow="false"
                            :disabled="!isPublicAnchor"
                            @change="handleModelChange">
                            <ElOption v-for="item in modelChannel" :key="item.id" :value="item.id" :label="item.name">
                                <div class="flex items-center gap-2">
                                    <img :src="item.icon" class="w-4 h-4" />
                                    <span class="font-medium">{{ item.name }}</span>
                                </div>
                            </ElOption>
                        </ElSelect>
                        <div v-if="!isPublicAnchor" class="text-[11px] text-orange-400 font-medium mt-2 ml-1">
                            提示：当前形象仅支持固定模型
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="text-[15px] font-[900] text-[#1E293B] mb-3">音色选择</div>
                        <ElSelect
                            v-model="voiceId"
                            class="w-full custom-select"
                            placeholder="请选择声音"
                            :show-arrow="false"
                            @change="handleVoiceChange">
                            <ElOption
                                v-for="item in voiceList"
                                :key="item.id"
                                :value="item.id"
                                :label="item.name"
                                :show-arrow="false">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium">{{ item.name }}</span>
                                    <ElTag size="small" :type="item.id == -1 ? 'success' : 'info'" round effect="light">
                                        {{ item.builtin === 0 ? "系统" : item.id == -1 ? "原生" : "定制" }}
                                    </ElTag>
                                </div>
                            </ElOption>
                        </ElSelect>
                    </div>

                    <div class="bg-slate-50 border border-br rounded-2xl p-5" v-if="clipConfig.is_open">
                        <div class="flex justify-between items-center">
                            <div class="text-[15px] font-[900] text-[#1E293B]">AI 智能剪辑</div>
                            <ElSwitch v-model="formData.automatic_clip" active-value="1" inactive-value="0" />
                        </div>

                        <div class="mt-4 pt-4 border-t border-[#E2E8F0]" v-if="false">
                            <div class="text-xs font-black text-[#94A3B8] uppercase mb-3">背景音乐 (BGM)</div>
                            <button
                                v-if="!formData.music_url"
                                @click="openChooseMusic"
                                class="w-full h-11 rounded-xl border-2 border-dashed border-[#E2E8F0] flex items-center justify-center gap-2 text-[#94A3B8] hover:text-primary hover:border-primary transition-all">
                                <Icon name="local-icon-upload3" :size="14" />
                                <span>添加音乐素材</span>
                            </button>
                            <div
                                v-else
                                class="flex items-center justify-between p-3 bg-white rounded-xl border border-primary/20">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <Icon name="local-icon-music" class="text-primary" />
                                    <span class="text-[13px] font-medium text-[#1E293B] truncate">{{
                                        formData.music_name
                                    }}</span>
                                </div>
                                <button
                                    @click="handleDeleteMusic"
                                    class="w-6 h-6 flex items-center justify-center rounded-full hover:bg-red-50 text-[#CBD5E1] hover:text-red-500 transition-all">
                                    <Icon name="el-icon-Close" :size="12" />
                                </button>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>

            <div class="mt-6 pt-6 border-t border-br">
                <CreatePanel :form-data="formData" @success="handleCreateSuccess" @error="handleCreateError" />
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
        v-if="showChooseTone"
        ref="chooseToneRef"
        :is_show_original="isShowOriginalTone"
        @close="showChooseTone = false"
        @confirm="getChooseTone"></choose-tone>
    <choose-music
        v-if="showChooseMusic"
        ref="chooseMusicRef"
        @close="showChooseMusic = false"
        @confirm="getChooseMusic"></choose-music>
    <generate-prompt
        v-if="showGeneratePrompt"
        ref="generatePromptRef"
        :prompt-type="CreateVideoTypeEnum.DIGITAL_HUMAN"
        :max-size="textLimit"
        @close="showGeneratePrompt = false"
        @use-content="getGenerateContent"></generate-prompt>
</template>
<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { dayjs, ElInput } from "element-plus";
import { getPublicAnchorList, getVoiceList as getVoiceListApi } from "@/api/digital_human";
import { addMaterialMusic, getMaterialMusicList } from "@/api/material";
import { getClipConfig } from "@/api/app";
import {
    ModeTypeEnum,
    CreateTypeEnum,
    DigitalHumanModelVersionEnum,
    SidebarTypeEnum,
} from "@/pages/app/digital_human/_enums";
import { ClipStyleMap, ClipStyleEnum } from "@/pages/app/_enums/indexEnum";
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";
import GeneratePrompt from "@/pages/app/digital_human/_components/generate-prompt.vue";
import Upload from "@/components/upload/index.vue";
import ChooseTone from "@/pages/app/_components/choose-tone.vue";
import ChooseMusic from "@/pages/app/_components/choose-audio.vue";
import UploadForm from "./_components/upload-form.vue";
import CreatePanel from "./_components/create-panel.vue";
import ChooseAnchor from "./_components/choose-anchor.vue";

const router = useRouter();

const appStore = useAppStore();
const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        const list = channel.filter((item: any) => {
            item.id = parseInt(item.id);
            return item.status == 1 && [DigitalHumanModelVersionEnum.CHANJING].includes(item.id);
        });

        return list;
    }
    return [];
});

const nameInputRef = shallowRef<InstanceType<typeof ElInput>>();

const formData = reactive<Record<string, any>>({
    url: "",
    name: dayjs().format("YYYYMMDDHHmmss").substring(2) + "数字人口播",
    anchor_name: "",
    anchor_id: "",
    pic: "",
    model_version: "",
    audio_type: CreateTypeEnum.TEXT,
    audio_src: "",
    voice_id: -1,
    voice_url: "",
    voice_name: "",
    msg: "",
    audio_duration: 0,
    audio_url: "",
    voice_type: 1,
    music_url: "",
    music_name: "",
    music_type: 1,
    clip_type: `${ClipStyleEnum.AI_RECOMMEND}`,
    automatic_clip: 0,
});
const voiceId = ref(-1);

const randomCopywriter = [
    `你是不是也有过这样的时刻？很想放弃，但又不甘心；很累很累，却还在硬撑。没人知道你经历了什么，但你知道，你不是为了谁在坚持，而是为了不辜负自己。别怕慢，只要不退，就已经很勇敢了。`,
    `总有人说你太敏感、太情绪化，可你只是太真诚了。你把别人放心上，却常常忽略了自己。没关系，慢慢来，允许自己不完美，也允许偶尔情绪失控。别总苛求自己坚强，温柔一点，你值得被好好对待。`,
    `夜深了，是不是又睡不着？回想白天的种种，总觉得哪里没做好。但你已经尽力了，真的。别再为过去懊悔，也别为未来焦虑，此刻的你，只需要好好休息。熬过去，天亮之后，一切都会好起来的。`,
];

const voiceList = ref<any[]>([]);
// 获取系统音色列表
const getSystemVoiceList = computed(() => {
    return (
        appStore.getDigitalHumanConfig?.voice
            .filter((item: any) => item.status == "1")
            .map((item) => ({ ...item, id: parseFloat(99 + item.code), builtin: 0 })) || []
    );
});

const handleModelChange = (value: number) => {
    formData.voice_id = -1;
    formData.voice_name = "";
    voiceId.value = -1;
    const {
        anchor_ids: { chanjing_anchor_id, shanjian_anchor_id, weiju_anchor_id },
    } = anchorPager.lists[currentAnchorIndex.value];
    const anchorIds = {
        [DigitalHumanModelVersionEnum.CHANJING]: chanjing_anchor_id,
        [DigitalHumanModelVersionEnum.SHANJIAN]: shanjian_anchor_id,
        [DigitalHumanModelVersionEnum.STANDARD]: weiju_anchor_id,
    };
    formData.anchor_id = anchorIds[value];
    getVoiceList();
};

const handleVoiceChange = (value: number) => {
    const currVoice = voiceList.value.find((item) => item.id == value) || {};
    formData.voice_type = currVoice.builtin === 0 ? 0 : 1;
    formData.voice_name = currVoice.name;
    if (currVoice.builtin === 0) {
        formData.voice_id = currVoice.id;
    } else {
        formData.voice_id = currVoice.voice_id;
    }
};

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

// 当前形象索引
const currentAnchorIndex = ref<number>(-1);
const anchorQueryParams = reactive({
    status: 1,
    filter: 2,
    page_no: 1,
    page_size: 20,
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

const loadMoreAnchor = async (e) => {
    if (e == "bottom") {
        if (!anchorPager.isLoad || anchorPager.loading) return;
        anchorQueryParams.page_no++;
        await getAnchorLists();
    }
};

const isPublicAnchor = computed(() => {
    if (anchorPager.lists.length === 0 || currentAnchorIndex.value === -1) return false;
    const { model_version } = anchorPager.lists[currentAnchorIndex.value];
    return model_version === 0;
});

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
const handleSelectAnchor = (index: number) => {
    // 更新当前选中的形象索引
    const {
        status,
        model_version,
        name,
        result_url,
        pic,
        width,
        height,
        source_type,
        anchor_ids: { chanjing_anchor_id, shanjian_anchor_id, weiju_anchor_id },
    } = anchorPager.lists[index];
    const anchorStatus = getAnchorStatus(status, source_type);
    if (anchorStatus != 1) {
        feedback.msgWarning("该形象正在克隆中，请稍后再试");
        return;
    }
    currentAnchorIndex.value = index;

    // 如果模型版本不同,重置相关数据
    if (formData.model_version != model_version) {
        formData.voice_id = -1;
        formData.voice_name = "";
        voiceId.value = -1;
    }
    if (model_version == 0) {
        formData.model_version = DigitalHumanModelVersionEnum.CHANJING;
    } else {
        formData.model_version = model_version;
    }
    formData.anchor_name = name;
    formData.url = result_url;
    formData.pic = pic;
    formData.width = width;
    formData.height = height;
    formData.anchor_id =
        formData.model_version == DigitalHumanModelVersionEnum.CHANJING
            ? chanjing_anchor_id
            : formData.model_version == DigitalHumanModelVersionEnum.SHANJIAN
            ? shanjian_anchor_id
            : weiju_anchor_id;
    // 重新请求音色
    getVoiceList();
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
};

/** 形象操作 End */

const handleRandomCopywriter = () => {
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

const getGenerateContent = (content: string) => {
    formData.msg = content.substring(0, textLimit.value);
};

/** 文案操作 End */

/** 音色操作 Start  */

const chooseToneRef = shallowRef<InstanceType<typeof ChooseTone>>();
const showChooseTone = ref<boolean>(false);

const isShowOriginalTone = computed(() => {
    return (
        formData.model_version == DigitalHumanModelVersionEnum.CHANJING ||
        formData.model_version == DigitalHumanModelVersionEnum.STANDARD
    );
});

const openChooseTone = async () => {
    if (!formData.model_version) {
        feedback.msgWarning("请先选择形象~");
        return;
    }
    showChooseTone.value = true;
    await nextTick();
    chooseToneRef.value?.open(formData.model_version);
    if (formData.voice_id) {
        chooseToneRef.value?.setChooseTone({
            type: formData.voice_type,
            voice_id: formData.voice_id,
        });
    }
};

const getChooseTone = (data: any) => {
    const { builtin, voice_id, name } = data;
    formData.voice_name = name;
    formData.voice_id = voice_id;
    formData.voice_type = builtin;
    voiceId.value = voice_id;
};

/** 音色操作 End  */

/** 剪辑操作 Start */

const chooseMusicRef = shallowRef<InstanceType<typeof ChooseMusic>>();
const showChooseMusic = ref<boolean>(false);

const openChooseMusic = async () => {
    showChooseMusic.value = true;
    await nextTick();
    chooseMusicRef.value?.open();
};

const getChooseMusic = (data: any) => {
    showChooseMusic.value = false;
    formData.music_url = data.url;
    formData.music_name = data.name;
};

const getUploadBgMusic = (result: any) => {
    const { uri, name } = result.data;
    formData.music_name = name;
    formData.music_url = uri;
    addMaterialMusic({
        url: uri,
        name,
        type: "0",
    });
};

const handleDeleteMusic = () => {
    formData.music_name = "";
    formData.music_url = "";
};

/** 剪辑操作 End */

const uploadFormRef = shallowRef<InstanceType<typeof UploadForm>>();
const showUpload = ref<boolean>(false);

// 文本限制
const textLimit = computed(() => {
    //@ts-ignore
    const limits: Record<DigitalHumanModelVersionEnum, number> = {
        [DigitalHumanModelVersionEnum.STANDARD]: 150,
        [DigitalHumanModelVersionEnum.SUPER]: 300,
        [DigitalHumanModelVersionEnum.ADVANCED]: 1000,
        [DigitalHumanModelVersionEnum.ELITE]: 1000,
        [DigitalHumanModelVersionEnum.CHANJING]: 4000,
    };
    return limits[formData.model_version] || 150;
});

const getPromptContent = (content: string) => {
    if (content.length > textLimit.value) {
        feedback.msgWarning(`内容过长，将截取前${textLimit.value}字符`);
        formData.msg = content.substring(0, textLimit.value);
    } else {
        formData.msg = content;
    }
};

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

const getVoiceList = async (query?: string) => {
    const { lists } = await getVoiceListApi({
        name: query,
        status: 1,
        builtin: 1,
        page_no: 1,
        page_size: 100,
        model_version: formData.model_version,
    });
    voiceList.value = [{ id: -1, name: "视频原音" }, ...getSystemVoiceList.value, ...lists];
};

const getClipConfigData = async () => {
    const { code } = await getClipConfig();
    clipConfig.is_open = code == 10000;
};

const toAnchorCreate = () => {
    navigateTo(`/app/digital_human?type=${SidebarTypeEnum.ANCHOR_CLONE}`);

    router.push({
        path: "/app/digital_human",
        query: {
            type: 7,
        },
    });
};

const loading = ref(true);
const init = async () => {
    try {
        await getAnchorLists();
        // 初始化形象列表
        if (anchorPager.lists.length > 0) {
            const index = anchorPager.lists.findIndex(
                (item: any) => getAnchorStatus(item.status, item.source_type) == 1
            );
            if (index != -1) {
                handleSelectAnchor(index);
            } else {
                handleSelectAnchor(0);
            }
        }
        await getClipConfigData();
    } finally {
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
        @apply absolute inset-0 bg-black/5 z-0;
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
