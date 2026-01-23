<template>
    <DefineTemplate v-slot="{ type }">
        <upload
            class="w-full"
            show-progress
            :type="type"
            :accept="
                type === 'video' ? montageUploadConfig.videoAccept.join(',') : montageUploadConfig.imageAccept.join(',')
            "
            :data="{ ffmpeg: type == 'image' ? 1 : 0 }"
            :show-file-list="false"
            :max-size="type === 'video' ? montageUploadConfig.videoSize : montageUploadConfig.imageSize"
            :min-duration="montageUploadConfig.videoDuration[0]"
            :max-duration="montageUploadConfig.videoDuration[1]"
            @success="handleUploadSuccess($event, type)">
            <div
                class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-[#0065fb]/5 cursor-pointer group transition-all"
                :class="type === 'video' ? 'hover:bg-blue-50' : 'hover:bg-orange-50'">
                <div
                    class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform"
                    :class="type === 'video' ? 'bg-blue-50  text-primary' : 'bg-orange-50 text-orange-500'">
                    <Icon :name="type === 'video' ? 'el-icon-VideoCamera' : 'el-icon-Picture'" :size="20" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-700"
                        >上传{{ type === "video" ? "视频" : "图片" }}素材</span
                    >
                    <span class="text-[10px] text-slate-400 font-bold uppercase"
                        >Dynamic {{ type === "video" ? "Video" : "Image" }}</span
                    >
                </div>
            </div>
        </upload>
    </DefineTemplate>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 flex flex-col gap-3 overflow-hidden">
            <div class="flex-[1.2] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                <header
                    class="px-8 py-5 border-b border-slate-50 flex justify-between items-center bg-[#f8fafc]/80 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_12px_rgba(0,101,251,0.4)]"></div>
                        <h3 class="text-[18px] font-bold text-slate-800 tracking-tight">形象素材选择</h3>
                        <div class="flex items-center bg-[#0065fb]/10 px-3 py-1 rounded-full">
                            <span class="text-primary text-[11px] font-bold uppercase tracking-wider"
                                >Selected: {{ formData.anchorLists.length }}</span
                            >
                        </div>
                    </div>
                </header>

                <div class="flex-1 min-h-0">
                    <ElScrollbar :distance="20" @end-reached="loadMoreAnchor">
                        <div class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                            <div
                                class="aspect-[3/4] rounded-[24px] border-2 border-dashed border-slate-200 bg-[#f8fafc]/50 hover:border-primary hover:bg-[#0065fb]/5 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 group"
                                @click="toCloneAnchor()">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-white shadow-light flex items-center justify-center group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
                                    <Icon name="el-icon-Plus" :size="24" />
                                </div>
                                <span class="text-[13px] font-black text-slate-500 group-hover:text-primary"
                                    >形象克隆</span
                                >
                            </div>

                            <div
                                v-for="(item, index) in anchorPager.lists"
                                :key="item.id"
                                @click="toggleSelectAnchor(item)"
                                :class="[
                                    'aspect-[3/4] rounded-[24px] relative group overflow-hidden transition-all duration-300 cursor-pointer border-2',
                                    isAnchorSelected(item.id) ? 'border-primary scale-[0.98]' : 'border-[transparent] ',
                                ]">
                                <ElImage :src="item.pic" fit="cover" lazy class="w-full h-full" />

                                <div
                                    v-if="isAnchorSelected(item.id)"
                                    class="absolute top-3 right-3 w-7 h-7 bg-primary rounded-full flex items-center justify-center border-2 border-white z-20 animate-in zoom-in duration-300">
                                    <Icon name="el-icon-Check" color="#fff" :size="16" />
                                </div>

                                <div
                                    class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                    <button
                                        @click.stop="handleVideoPlay(item.anchor_url)"
                                        class="w-11 h-11 rounded-full bg-[#ffffff]/20 backdrop-blur-md text-white hover:bg-white hover:text-primary transition-all flex items-center justify-center shadow-2xl">
                                        <Icon name="el-icon-VideoPlay" :size="22" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="anchorPager.isLoad" v-if="anchorPager.lists.length > 0"></load-text>
                    </ElScrollbar>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-[20px] border border-[#e2e8f0]/60 flex flex-col overflow-hidden">
                <div class="px-8 py-5 flex justify-between items-center bg-[#f8fafc]/80">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-orange-400 shadow-[0_0_12px_rgba(251,146,60,0.4)]"></div>
                        <h3 class="text-[16px] font-black text-slate-700">参考素材</h3>
                        <div
                            class="text-[11px] text-slate-400 font-bold bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">
                            总量限制：全部素材总时长不得超过{{ montageUploadConfig.materialTotalDuration }}分钟
                            (图片按{{ montageUploadConfig.imageDuration }}秒/张，视频按实际时长/个)
                        </div>
                    </div>
                    <ElPopover
                        trigger="click"
                        :width="240"
                        popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]"
                        v-if="formData.materialList.length > 0">
                        <template #reference>
                            <ElButton link type="primary" class="!font-bold !text-[13px]">+ 补充素材</ElButton>
                        </template>
                        <UseTemplate type="image" />
                        <UseTemplate type="video" />
                    </ElPopover>
                </div>

                <div class="flex-1 min-h-0">
                    <ElScrollbar v-if="formData.materialList.length > 0">
                        <div class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                            <div
                                v-for="(item, index) in formData.materialList"
                                :key="index"
                                class="aspect-square shrink-0 rounded-[24px] relative group overflow-hidden border border-slate-100 transition-transform hover:scale-105">
                                <img :src="item.pic" class="w-full h-full object-cover" />
                                <div
                                    class="absolute inset-0 bg-[#000000]/20 group-hover:bg-[#000000]/40 transition-colors"></div>
                                <button
                                    @click="handleDeleteMaterial(index)"
                                    class="absolute top-2 right-2 w-7 h-7 rounded-xl bg-[#ef4444]/90 backdrop-blur text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600">
                                    <Icon name="el-icon-Close" :size="12" />
                                </button>
                                <div
                                    class="absolute bottom-2 left-2 px-2 py-1 bg-[#ffffff]/20 backdrop-blur-md rounded-lg text-[9px] text-white font-black border border-[#ffffff]/20">
                                    {{ item.type === "image" ? "IMAGE" : "VIDEO" }}
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                    <div
                        class="flex flex-col justify-center items-center h-full py-12"
                        v-show="formData.materialList.length === 0">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-[#0065fb]/10 blur-[60px] rounded-full animate-pulse"></div>
                            <div
                                class="relative w-24 h-24 bg-slate-50 rounded-[32px] flex items-center justify-center border border-slate-100 shadow-sm">
                                <Icon name="el-icon-Files" color="var(--slate-300)" :size="40" />
                            </div>
                        </div>

                        <div class="text-[15px] font-[1000] text-slate-400 mb-8 tracking-wider uppercase">
                            当前素材库空空如也
                        </div>

                        <ElPopover
                            placement="top"
                            :width="240"
                            popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                            <template #reference>
                                <button class="add-material-btn group">
                                    <div
                                        class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-xl"></div>

                                    <div
                                        class="relative flex items-center gap-3 px-8 py-4 bg-primary rounded-[22px] shadow-lg shadow-[#0065fb]/30 group-hover:scale-105 group-hover:shadow-[#0065fb]/50 transition-all duration-300 active:scale-95">
                                        <div
                                            class="w-6 h-6 rounded-lg bg-[#ffffff]/20 flex items-center justify-center group-hover:rotate-90 transition-transform duration-500">
                                            <Icon name="el-icon-Plus" color="#ffffff" :size="18" />
                                        </div>
                                        <span class="text-white font-[1000] text-base tracking-wide mr-1"
                                            >添加参考素材</span
                                        >
                                        <Icon name="el-icon-ArrowDown" color="rgba(255,255,255,0.6)" :size="14" />
                                    </div>

                                    <div
                                        class="absolute -inset-1 border-2 border-[#0065fb]/30 rounded-[24px] animate-ping opacity-20 group-hover:hidden"></div>
                                </button>
                            </template>
                            <UseTemplate type="image" />
                            <UseTemplate type="video" />
                        </ElPopover>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-[380px] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br">
            <header class="mb-5">
                <h2 class="text-[24px] font-bold text-slate-800 tracking-tight">生成设置</h2>
                <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
            </header>

            <ElScrollbar class="flex-1 -mr-4 pr-4">
                <div class="flex flex-col gap-3">
                    <div class="px-5 py-2 rounded-2xl flex items-center gap-x-3 bg-slate-50 border border-br">
                        <div class="text-[13px] font-black text-[#64748B]">视频名称</div>
                        <div class="w-[1px] h-3 bg-[#E2E8F0]"></div>
                        <div class="flex-1">
                            <ElInput
                                v-model="formData.name"
                                class="custom-input"
                                placeholder="请输入名称"
                                maxlength="20"
                                :input-style="{
                                    textAlign: 'right',
                                    fontSize: '15px',
                                    fontWeight: '900',
                                    color: '#1E293B',
                                }"
                                clearable />
                        </div>
                    </div>

                    <section class="bg-slate-50 rounded-[20px] p-3 border border-br">
                        <div class="flex justify-between items-center mb-4 px-2">
                            <h4 class="text-[14px] font-bold text-slate-700">人设设定</h4>
                            <button
                                @click="showCharacter = true"
                                class="text-primary text-xs font-black hover:underline">
                                历史人设
                            </button>
                        </div>
                        <div class="space-y-4">
                            <ElInput
                                v-model="formData.person_name"
                                placeholder="人物名称 (如: 资深分析师)"
                                class="custom-input !h-11" />
                            <ElInput
                                v-model="formData.person_introduction"
                                type="textarea"
                                :rows="3"
                                placeholder="简述人物背景及..."
                                class="custom-textarea"
                                resize="none" />
                        </div>
                    </section>
                    <section class="bg-slate-50 rounded-[20px] p-3 border border-br">
                        <div class="flex items-center justify-between mb-5">
                            <label class="text-[14px] font-bold flex items-center gap-2">
                                <Icon name="el-icon-Microphone" /> 口播内容配置
                            </label>
                            <button
                                @click="openAiGenerateContent"
                                class="bg-primary text-white px-4 py-2 rounded-[14px] text-xs font-bold flex items-center gap-2 shadow-lg shadow-[#0065fb]/30 hover:scale-105 transition-transform">
                                <Icon name="el-icon-MagicStick" /> AI 智能生成
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-[11px] font-black text-slate-400 uppercase ml-2 mb-1 block"
                                    >口播标题</span
                                >
                                <ElInput
                                    v-model="formData.title"
                                    maxlength="50"
                                    placeholder="请输入口播标题..."
                                    class="custom-input !h-11" />
                            </div>
                            <div>
                                <span class="text-[11px] font-black text-slate-400 uppercase ml-2 mb-1 block"
                                    >口播内容</span
                                >
                                <ElInput
                                    v-model="formData.content"
                                    type="textarea"
                                    :rows="8"
                                    placeholder="输入口播内容..."
                                    class="custom-textarea"
                                    resize="none" />
                                <div class="flex justify-end mt-2">
                                    <span class="text-[11px] font-black text-slate-300"
                                        >{{ formData.content.length }}/{{ contentLimit }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div class="p-3 bg-white rounded-[20px] border border-br">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[14px] font-bold text-slate-700">背景音乐 (BGM)</span>
                            </div>
                            <div
                                class="h-[56px] rounded-2xl bg-[#f1f5f9]/50 border border-slate-100 flex items-center px-4 cursor-pointer hover:bg-white hover:border-primary transition-all group"
                                @click="openMusicDialog">
                                <span class="text-primary mr-3 group-hover:scale-110 transition-transform leading-[0]">
                                    <Icon name="el-icon-Headset" :size="20" />
                                </span>

                                <span class="text-[13px] font-bold text-slate-600 flex-1 truncate">{{
                                    formData.music[0]?.name || "点击选择配乐素材"
                                }}</span>
                                <Icon name="el-icon-ArrowRight" color="var(--slate-300)" />
                            </div>
                            <div class="mt-5 px-1">
                                <div
                                    class="flex justify-between text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">
                                    <span>音量</span>
                                    <span class="text-primary">{{ (formData.extra.volume * 100).toFixed(0) }}%</span>
                                </div>
                                <ElSlider
                                    v-model="formData.extra.volume"
                                    :min="0"
                                    :max="1"
                                    :step="0.01"
                                    :show-tooltip="false"
                                    class="premium-slider" />
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="text-[10px] font-black text-slate-400">素材视频原声</div>
                                <ElSwitch v-model="formData.extra.soundSwitch" size="small" />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between px-8 h-[80px] bg-slate-900 rounded-[20px] text-white shadow-xl">
                            <div class="flex flex-col">
                                <span class="text-[15px] font-black">生成数量</span>
                                <span class="text-[9px] text-[#ffffff]/30 uppercase font-bold tracking-widest"
                                    >Total Batch</span
                                >
                            </div>
                            <div class="flex items-center gap-6">
                                <button
                                    @click="handleMinusVideoCount('minus')"
                                    class="w-9 h-9 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10 transition-colors">
                                    <Icon name="el-icon-Minus" />
                                </button>
                                <span class="text-[22px] font-bold w-8 text-center tabular-nums">{{
                                    formData.video_count
                                }}</span>
                                <button
                                    @click="handleMinusVideoCount('add')"
                                    class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30">
                                    <Icon name="el-icon-Plus" />
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </ElScrollbar>

            <footer class="mt-6 pt-6 border-t border-br">
                <ElButton class="w-full !h-[50px]" type="primary" size="large">
                    <span class="font-bold text-lg">立即生成视频</span>
                </ElButton>
            </footer>
        </div>
    </div>
    <preview-video
        v-if="showVideoPreview"
        ref="videoPreviewPlayerRef"
        @close="showVideoPreview = false"></preview-video>
</template>
<script setup lang="ts">
import dayjs from "dayjs";
import { createReusableTemplate } from "@vueuse/core";
import { useUserStore } from "@/stores/user";
import { getShanjianAnchorList, createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { uploadImage } from "@/api/app";
import { MontageTypeEnum, SidebarTypeEnum } from "@/pages/app/digital_human/_enums";
import { montageUploadConfig } from "@/pages/app/digital_human/_config";
import ChooseAudio from "@/pages/app/_components/choose-audio.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// 表单数据
const formData = reactive<{
    anchorLists: any[];
    materialList: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    video_count: number;
    shanjian_type: MontageTypeEnum;
    music: any[];
    extra: { volume: number; soundSwitch: boolean };
    content: string;
    title: string;
}>({
    anchorLists: [],
    materialList: [],
    name: dayjs().format("YYYYMMDDHHmm") + "口播混剪",
    person_name: "",
    person_introduction: "",
    video_count: 1,
    shanjian_type: MontageTypeEnum.REAL_PERSON_AI,
    music: [],
    extra: { volume: 0.5, soundSwitch: false },
    content: "",
    title: "",
});

const anchorQueryParams = reactive({
    page_no: 1,
    page_size: 20,
    status: [0, 6],
});

const showVideoPreview = ref(false);
const videoPreviewPlayerRef = shallowRef();
const chooseAudioRef = ref<InstanceType<typeof ChooseAudio>>();
const contentLimit = 500;

// 状态控制
const showCharacter = ref(false);
const showMusicDialog = ref(false);

const { pager: anchorPager, getLists: getAnchorLists } = usePaging({
    fetchFun: getShanjianAnchorList,
    params: anchorQueryParams,
    isScroll: true,
});

const loadMoreAnchor = (e: string) => {
    if (e === "bottom" && (!anchorPager.isLoad || anchorPager.loading)) return;
    anchorQueryParams.page_no++;
    getAnchorLists();
};

const toCloneAnchor = () => {
    navigateTo(`/app/digital_human?type=${SidebarTypeEnum.ANCHOR_CLONE}`);
};

const toggleSelectAnchor = (item: any) => {
    const index = formData.anchorLists.findIndex((val) => val.id === item.id);
    if (index > -1) {
        formData.anchorLists.splice(index, 1);
    } else {
        formData.anchorLists.push(item); // 多选
    }
};

const isAnchorSelected = (id: number) => {
    return formData.anchorLists.some((item) => item.id === id);
};

// 视频播放
const handleVideoPlay = async (url: string) => {
    showVideoPreview.value = true;
    await nextTick();
    videoPreviewPlayerRef.value?.open();
    videoPreviewPlayerRef.value?.setUrl(url);
};

const handleUploadSuccess = async (res: any, type: "image" | "video") => {
    const {
        data: { uri },
    } = res;
    // 计算当前素材的总时长
    const calculateTotalDuration = () => {
        const videoDuration = formData.materialList.reduce(
            (acc, item) => (item.type === "video" ? acc + item.duration : acc),
            0
        );
        const imageDuration =
            formData.materialList.filter((item: any) => item.type === "image").length *
            montageUploadConfig.imageDuration;
        return videoDuration + imageDuration;
    };

    const totalDuration = calculateTotalDuration();

    try {
        const newMaterial = { type, url: uri, duration: 0, pic: uri };

        if (type === "video") {
            const { duration, file } = await getVideoFirstFrame(uri);
            const { uri: picUri } = await uploadImage({ file, ffmpeg: 1 });
            newMaterial.duration = duration;
            newMaterial.pic = picUri;
        } else {
            newMaterial.duration = montageUploadConfig.imageDuration;
        }
        // 检查总时长是否超过限制
        if (totalDuration + newMaterial.duration > montageUploadConfig.materialTotalDuration * 60) {
            feedback.msgError("素材总时长不能超过" + montageUploadConfig.materialTotalDuration + "分钟，将会被过滤");
            return false;
        }
        formData.materialList.push(newMaterial);
    } catch (error) {
        console.error(error);
        feedback.msgError(error);
    }
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
};

// 删除逻辑
const handleDeleteAnchor = (id: number) => {
    // formData.anchorLists = formData.anchorLists.filter((item) => item.id !== id);
    // handleDeleteMaterialFromHook(id)
};

// 数量控制
const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.video_count > 1) formData.video_count--;
    } else {
        if (formData.video_count < 99) formData.video_count++;
    }
};

// 历史人设选择
const handleSelectCharacter = (item: any) => {
    formData.person_name = item.name;
    formData.person_introduction = item.introduced;
    showCharacter.value = false;
};

// AI生成文案
const openAiGenerateContent = () => {
    console.log("AI生成文案");
};

// 音乐选择
const openMusicDialog = async () => {
    showMusicDialog.value = true;
    await nextTick();
    chooseAudioRef.value?.open();
};
const handleMusicConfirm = (data: any) => {
    formData.music = Array.isArray(data) ? data : [data];
    showMusicDialog.value = false;
};

// 创建任务
const handleCreateVideo = async () => {
    // if (formData.anchorLists.length === 0) return ElMessage.warning("请至少上传一个口播视频")
    // if (!formData.person_name || !formData.person_introduction) return ElMessage.warning("请完善身份人设信息")
    // if (formData.materialList.length < 3) return ElMessage.warning("请至少上传3个参考素材")
    // if (!formData.name) return ElMessage.warning("请输入视频名称")
    // // 检查素材时长
    // const totalDuration =
    //     formData.materialList.reduce((acc, item) => (item.type === "video" ? acc + item.duration : acc), 0) +
    //     formData.materialList.filter((item: any) => item.type === "image").length * montageConfig.imageDuration
    // if (totalDuration > montageConfig.materialTotalDuration * 60) {
    //     return ElMessage.warning(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟`)
    // }
    // creating.value = true
    // try {
    //     const res = await createShanjianTask({
    //         name: formData.name,
    //         anchor: formData.anchorLists.map((item: any) => ({
    //             pic: item.pic,
    //             anchor_url: item.url,
    //             name: item.name,
    //             duration: item.duration,
    //         })),
    //         character_design: [{
    //             name: formData.person_name,
    //             introduced: formData.person_introduction,
    //         }],
    //         copywriting: [], // 混剪模式可能不需要文案
    //         material: formData.materialList.map((item: any) => ({
    //             fileUrl: item.url,
    //             type: item.type,
    //             cover: item.pic,
    //         })),
    //         shanjian_type: formData.shanjian_type,
    //         video_count: formData.video_count,
    //         music: formData.music.map((item: any) => item.content || item.url),
    //         extra: {
    //             volume: formData.extra.volume,
    //         },
    //     })
    //     // 保存人设
    //     if (formData.person_name && formData.person_introduction) {
    //         addShanjianPerson({
    //             name: formData.person_name,
    //             introduced: formData.person_introduction,
    //         })
    //     }
    //     ElMessage.success("任务创建成功，请前往历史记录查看")
    //     // 跳转逻辑
    //     // router.push(...)
    // } catch (error: any) {
    //     console.error(error)
    // } finally {
    //     creating.value = false
    // }
};

const [DefineTemplate, UseTemplate] = createReusableTemplate<{
    type: "image" | "video";
}>();

getAnchorLists();
</script>

<style lang="scss">
.add-material-btn {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    outline: none;
}

/* 呼吸动画：吸引用户注意力 */
@keyframes ping {
    75%,
    100% {
        transform: scale(1.2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
