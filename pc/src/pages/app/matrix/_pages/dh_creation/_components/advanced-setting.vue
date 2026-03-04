<template>
    <ElDrawer v-model="show" body-class="!p-0" size="480px" :with-header="false" class="advanced-setting-drawer">
        <div class="h-full flex flex-col bg-white overflow-hidden">
            <div
                class="flex items-center justify-between h-[72px] px-6 border-b border-[#F1F5F9] bg-white flex-shrink-0">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Operation" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">高级设置</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Advanced Configurations
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8" @click="close">
                    <close-btn :icon-size="10" />
                </div>
            </div>

            <div class="grow overflow-y-auto px-6 py-6 custom-scrollbar">
                <div class="p-5 rounded-[24px] bg-slate-50 border border-[#F1F5F9]">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[14px] font-black text-[#1E293B]">AI 智能剪辑</span>
                            <span class="text-[11px] text-[#94A3B8] font-medium mt-0.5"
                                >自动为生成的视频匹配转场与特效</span
                            >
                        </div>
                        <ElSwitch
                            v-model="formData.automatic_clip"
                            active-color="#0065fb"
                            inactive-color="#E2E8F0"
                            :active-value="1"
                            :inactive-value="0" />
                    </div>

                    <Transition name="fade-slide" v-if="false">
                        <div v-if="formData.automatic_clip == 1" class="mt-6 pt-6 border-t border-br/50">
                            <div class="mb-6">
                                <label class="block text-xs font-black text-[#64748B] uppercase mb-3 ml-1"
                                    >剪辑风格选择</label
                                >
                                <ElSelect
                                    v-model="clipStyle"
                                    class="w-full custom-select-pill"
                                    multiple
                                    collapse-tags
                                    collapse-tags-tooltip
                                    :show-arrow="false"
                                    placeholder="请选择剪辑风格"
                                    @change="handleChangeClipStyle">
                                    <ElOption
                                        v-for="(label, key) in ClipStyleMap"
                                        :key="key"
                                        :label="label"
                                        :value="key">
                                    </ElOption>
                                </ElSelect>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-[#64748B] uppercase mb-3 ml-1"
                                    >背景音乐库</label
                                >

                                <div class="flex flex-col gap-2 mb-4" v-if="formData.music.length > 0">
                                    <div
                                        v-for="(item, index) in formData.music"
                                        :key="index"
                                        class="flex items-center justify-between p-3 bg-white border border-br rounded-xl hover: transition-all group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div
                                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#F1F5F9] text-[#64748B]">
                                                <Icon name="el-icon-Headset" :size="14"></Icon>
                                            </div>
                                            <span class="text-[13px] font-medium text-[#475569] truncate">{{
                                                item.name
                                            }}</span>
                                        </div>
                                        <div class="w-6 h-6" @click="handleDeleteMusic(index)">
                                            <close-btn :icon-size="10" />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <upload class="w-full" type="audio" @success="getUploadBgMusic">
                                        <div
                                            class="w-full flex items-center justify-center gap-2 h-11 rounded-xl bg-white border-2 border-dashed border-br text-[#64748B] hover:border-[#0065fb] hover:text-primary transition-all cursor-pointer">
                                            <Icon name="el-icon-Upload" :size="16"></Icon>
                                            <span class="text-[13px] font-medium">本地上传</span>
                                        </div>
                                    </upload>
                                    <div
                                        class="flex items-center justify-center gap-2 h-11 rounded-xl bg-[#0065fb]/10 text-primary border border-transparent hover:bg-[#0065fb]/10 transition-all cursor-pointer"
                                        @click="handleSelectAudio">
                                        <Icon name="el-icon-FolderOpened" :size="16"></Icon>
                                        <span class="text-[13px] font-medium">音乐库选择</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="p-6 border-t border-[#F1F5F9] bg-white flex-shrink-0">
                <ElButton
                    type="primary"
                    class="!w-full !h-[52px] !rounded-full !text-[15px] !font-black !shadow-xl !shadow-[#0065fb]/20 active:scale-95 transition-all"
                    @click="handleConfirm">
                    保存高级配置
                </ElButton>
            </div>
        </div>
    </ElDrawer>
    <audio-material
        v-if="showAudioMaterial"
        ref="audioMaterialRef"
        multiple
        @close="showAudioMaterial = false"
        @confirm="getChooseAudio" />
</template>

<script setup lang="ts">
import AudioMaterial from "@/pages/app/_components/choose-audio.vue";
import { ClipStyleMap } from "@/pages/app/_enums/indexEnum";

type Result = {
    music: Array<{ url: string; name: string }>;
    clip: Array<{ type: number | string }>;
    automatic_clip: number;
};

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success", result: Result): void;
}>();

const formData = reactive<Result>({
    automatic_clip: 0,
    music: [],
    clip: [],
});

const clipStyle = ref<string[]>([]);

const show = defineModel<boolean>("show");

const showAudioMaterial = ref(false);
const audioMaterialRef = shallowRef<InstanceType<typeof AudioMaterial>>();

const handleSelectAudio = async () => {
    showAudioMaterial.value = true;
    await nextTick();
    audioMaterialRef.value.open();
};

const getUploadBgMusic = (result: any) => {
    const { uri, name } = result.data;
    formData.music.push({
        url: uri,
        name,
    });
};

const handleDeleteMusic = (index: number) => {
    formData.music.splice(index, 1);
};

const getChooseAudio = (result: any[]) => {
    formData.music.push(...result.map((item: any) => ({ url: item.url, name: item.name })));
};

const handleChangeClipStyle = (value: string[]) => {
    formData.clip = value.map((item: string) => ({ type: item }));
};

const handleConfirm = () => {
    if (formData.automatic_clip == 1) {
        // if (formData.clip.length == 0) {
        //     feedback.msgWarning("请选择剪辑风格");
        //     return;
        // }
        // if (formData.music.length == 0) {
        //     feedback.msgWarning("请上传背景音乐");
        //     return;
        // }
    }
    close();
    emit("success", formData);
};

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({
    open,
    setFormData: (data) => {
        data.clip = isArray(data.clip) ? data.clip : JSON.parse(data.clip);
        data.music = isArray(data.music) ? data.music : JSON.parse(data.music);
        setFormData(data, formData);
        if (data.clip && data.clip.length > 0) {
            clipStyle.value = data.clip.map((item: any) => item.type);
        }
    },
});
</script>

<style scoped lang="scss">
/* 抽屉全局样式覆盖 */
:deep(.advanced-setting-drawer) {
    .el-drawer__body {
        background-color: #ffffff !important;
    }
}

/* Select 控件样式美化 */
:deep(.custom-select-pill) {
    .el-select__wrapper {
        border-radius: 12px !important;
        height: 48px !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 1px #e2e8f0 inset !important;
        &.is-focus {
            box-shadow: 0 0 0 1px #4f46e5 inset !important;
        }
    }
}

/* 动画效果 */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* 自定义滚动条 */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
